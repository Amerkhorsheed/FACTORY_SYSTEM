<?php

namespace App\Services\Distribution;

use App\Contracts\Repositories\ShipmentRepositoryInterface;
use App\Contracts\Services\ShipmentServiceInterface;
use App\DTOs\Shipments\CreateShipmentDTO;
use App\Events\Orders\OrderShipped;
use App\Events\ShipmentDispatched;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Truck;
use App\Services\Orders\OrderStatusService;
use App\StateMachines\OrderStateMachine;
use App\StateMachines\ShipmentStateMachine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ShipmentService implements ShipmentServiceInterface
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $repository,
        private readonly ShipmentStateMachine $stateMachine,
        private readonly OrderStateMachine $orderStateMachine,
        private readonly OrderStatusService $orderStatus,
    ) {}

    /** @param array<string, mixed> $filters */
    public function list(array $filters, int $perPage = 0): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters, $perPage ?: 20);
    }

    public function create(CreateShipmentDTO $dto): Shipment
    {
        return DB::transaction(function () use ($dto) {
            $shipment = $this->repository->create([
                ...$dto->toArray(),
                'status' => 'planned',
                'created_by' => auth()->id(),
            ]);

            return $shipment->load(['truck', 'driver']);
        });
    }

    /** @param array<int, int> $orderIds */
    public function attachOrders(Shipment $shipment, array $orderIds): void
    {
        DB::transaction(function () use ($shipment, $orderIds) {
            $shipment = Shipment::query()
                ->whereKey($shipment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($shipment->status, ['planned', 'loading'], true)) {
                throw new InvalidStatusTransitionException('Orders can only be attached to planned or loading shipments.');
            }

            $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
            if ($orderIds === []) {
                throw new \InvalidArgumentException('At least one order must be selected.');
            }

            $orders = Order::query()
                ->whereIn('id', $orderIds)
                ->whereNull('shipment_id')
                ->where('status', 'ready')
                ->lockForUpdate()
                ->get();

            if ($orders->count() !== count($orderIds)) {
                throw new InvalidStatusTransitionException('Only ready, unassigned orders can be attached to a shipment.');
            }

            Order::whereKey($orders->modelKeys())->update(['shipment_id' => $shipment->id]);
        });
    }

    public function detachOrder(Shipment $shipment, Order $order): void
    {
        DB::transaction(function () use ($shipment, $order) {
            $shipment = Shipment::query()
                ->whereKey($shipment->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $order = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->shipment_id !== $shipment->id) {
                throw new \InvalidArgumentException('Order does not belong to this shipment.');
            }

            if (! in_array($shipment->status, ['planned', 'loading'], true) || $order->status !== 'ready') {
                throw new InvalidStatusTransitionException('Only ready orders on planned or loading shipments can be detached.');
            }

            $order->shipment_id = null;
            $order->save();
        });
    }

    public function dispatch(Shipment $shipment): Shipment
    {
        return DB::transaction(function () use ($shipment) {
            $shipment = Shipment::query()
                ->with('orders')
                ->whereKey($shipment->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $this->stateMachine->transition($shipment->status, 'dispatched');

            if (! $shipment->canBeDispatched()) {
                throw new InvalidStatusTransitionException('Shipment cannot be dispatched. No ready orders found.');
            }

            $truck = Truck::query()
                ->whereKey($shipment->truck_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $truck->isAvailable()) {
                throw new InvalidStatusTransitionException('Shipment truck is not available.');
            }

            $shipment->update([
                'status' => 'dispatched',
                'departure_time' => now(),
            ]);

            $truck->update(['status' => 'on_trip']);

            $orders = $shipment->orders()->where('status', 'ready')->lockForUpdate()->get();

            foreach ($orders as $order) {
                $this->orderStateMachine->transition($order->status, 'shipped');
                $order->update([
                    'status' => 'shipped',
                    'shipped_at' => now(),
                    'shipped_by' => auth()->id(),
                ]);
                event(new OrderShipped($order->fresh()));
            }

            event(new ShipmentDispatched($shipment));

            return $shipment->fresh();
        });
    }

    public function markOrderDelivered(Shipment $shipment, Order $order): void
    {
        DB::transaction(function () use ($shipment, $order) {
            $shipment = Shipment::query()
                ->whereKey($shipment->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $order = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->shipment_id !== $shipment->id) {
                throw new \InvalidArgumentException('Order does not belong to this shipment.');
            }

            if ($shipment->status !== 'dispatched') {
                throw new InvalidStatusTransitionException('Only dispatched shipments can mark orders as delivered.');
            }

            if ($order->status !== 'shipped') {
                throw new InvalidStatusTransitionException('Only shipped orders can be marked delivered from a shipment.');
            }

            $actor = auth()->user();
            if (! $actor) {
                throw new \RuntimeException('Authenticated user is required to confirm delivery.');
            }

            $this->orderStatus->confirmDelivery($order, $actor);

            $shipment->refresh();
            if ($shipment->allOrdersResolved()) {
                $this->complete($shipment);
            }
        });
    }

    public function complete(Shipment $shipment): Shipment
    {
        return DB::transaction(function () use ($shipment) {
            $shipment = Shipment::query()
                ->whereKey($shipment->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $this->stateMachine->transition($shipment->status, 'completed');

            $shipment->update([
                'status' => 'completed',
                'return_time' => now(),
            ]);

            Truck::whereKey($shipment->truck_id)->update(['status' => 'available']);

            return $shipment->fresh();
        });
    }

    public function cancel(Shipment $shipment, string $reason): Shipment
    {
        return DB::transaction(function () use ($shipment, $reason) {
            $shipment = Shipment::query()
                ->with('orders')
                ->whereKey($shipment->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $this->stateMachine->transition($shipment->status, 'cancelled');

            if ($shipment->orders()->whereIn('status', ['delivered', 'returned'])->exists()) {
                throw new InvalidStatusTransitionException('Shipment cannot be cancelled after an order is delivered or returned.');
            }

            $shipment->orders()
                ->where('status', 'shipped')
                ->lockForUpdate()
                ->update([
                    'status' => 'ready',
                    'shipment_id' => null,
                    'shipped_at' => null,
                    'shipped_by' => null,
                ]);

            $shipment->orders()
                ->where('status', 'ready')
                ->lockForUpdate()
                ->update(['shipment_id' => null]);

            $shipment->update([
                'status' => 'cancelled',
                'notes' => $shipment->notes
                    ? $shipment->notes."\n\nCancelled: {$reason}"
                    : "Cancelled: {$reason}",
            ]);

            Truck::whereKey($shipment->truck_id)->update(['status' => 'available']);

            return $shipment->fresh();
        });
    }

    public function update(Shipment $shipment, CreateShipmentDTO $dto): Shipment
    {
        return DB::transaction(function () use ($shipment, $dto) {
            $this->repository->update($shipment, $dto->toArray());

            return $shipment->fresh();
        });
    }

    public function delete(Shipment $shipment): void
    {
        $this->repository->delete($shipment);
    }

    public function availableTrucks(): Collection
    {
        return $this->repository->availableTrucks();
    }

    public function availableDrivers(): Collection
    {
        return $this->repository->availableDrivers();
    }

    public function readyOrders(): Collection
    {
        return $this->repository->readyOrders();
    }
}
