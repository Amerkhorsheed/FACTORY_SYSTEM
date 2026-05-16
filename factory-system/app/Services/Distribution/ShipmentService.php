<?php

namespace App\Services\Distribution;

use App\Contracts\Repositories\ShipmentRepositoryInterface;
use App\Contracts\Services\ShipmentServiceInterface;
use App\DTOs\Shipments\CreateShipmentDTO;
use App\Events\ShipmentDispatched;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Truck;
use App\StateMachines\ShipmentStateMachine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ShipmentService implements ShipmentServiceInterface
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $repository,
        private readonly ShipmentStateMachine $stateMachine,
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
            Order::whereIn('id', $orderIds)
                ->whereNull('shipment_id')
                ->update(['shipment_id' => $shipment->id]);
        });
    }

    public function detachOrder(Shipment $shipment, Order $order): void
    {
        DB::transaction(function () use ($shipment, $order) {
            if ($order->shipment_id !== $shipment->id) {
                throw new \InvalidArgumentException('Order does not belong to this shipment.');
            }

            $order->shipment_id = null;
            $order->save();
        });
    }

    public function dispatch(Shipment $shipment): Shipment
    {
        return DB::transaction(function () use ($shipment) {
            $this->stateMachine->transition($shipment->status, 'dispatched');

            if (! $shipment->canBeDispatched()) {
                throw new InvalidStatusTransitionException('Shipment cannot be dispatched. No ready orders found.');
            }

            $shipment->update([
                'status' => 'dispatched',
                'departure_time' => now(),
            ]);

            Truck::whereKey($shipment->truck_id)->update(['status' => 'on_trip']);

            $shipment->orders()->where('status', 'ready')->update(['status' => 'shipped']);

            event(new ShipmentDispatched($shipment));

            return $shipment->fresh();
        });
    }

    public function markOrderDelivered(Shipment $shipment, Order $order): void
    {
        DB::transaction(function () use ($shipment, $order) {
            if ($order->shipment_id !== $shipment->id) {
                throw new \InvalidArgumentException('Order does not belong to this shipment.');
            }

            $order->status = 'delivered';
            $order->delivered_at = now();
            $order->save();

            $shipment->refresh();
            if ($shipment->allOrdersResolved()) {
                $this->complete($shipment);
            }
        });
    }

    public function complete(Shipment $shipment): Shipment
    {
        return DB::transaction(function () use ($shipment) {
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
            $this->stateMachine->transition($shipment->status, 'cancelled');

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
}
