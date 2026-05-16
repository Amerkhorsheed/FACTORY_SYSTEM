<?php

namespace App\Services\Orders;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Services\OrderServiceInterface;
use App\DTOs\Orders\CreateOrderDTO;
use App\DTOs\Orders\OrderItemDTO;
use App\Events\Orders\OrderCreated;
use App\Exceptions\CreditLimitExceededException;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Pipelines\Order\CalculateOrderTotalsPipe;
use App\Pipelines\Order\ValidateCustomerCreditPipe;
use App\Pipelines\Order\ValidateStockAvailabilityPipe;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;

/**
 * Core order management service.
 */
class OrderService extends BaseService implements OrderServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderFinancialsService $financials,
        private readonly Pipeline $pipeline,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $perPage = 0): LengthAwarePaginator
    {
        return $this->orders->paginateWithFilters(
            $filters,
            $perPage ?: config('factory.pagination.per_page', 20)
        );
    }

    /**
     * Create a new order through the validation pipeline.
     *
     * @throws CreditLimitExceededException
     * @throws InsufficientStockException
     * @throws \Throwable
     */
    public function create(CreateOrderDTO $dto): Order
    {
        return $this->transaction(function () use ($dto) {
            $dto = $this->pipeline
                ->send($dto)
                ->through([
                    ValidateCustomerCreditPipe::class,
                    ValidateStockAvailabilityPipe::class,
                    CalculateOrderTotalsPipe::class,
                ])
                ->thenReturn();

            $totals = $this->financials->calculateTotals($dto->items);

            $order = $this->orders->create([
                'customer_id' => $dto->customerId,
                'order_date' => $dto->orderDate,
                'requested_delivery_date' => $dto->requestedDeliveryDate,
                'status' => 'pending',
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'],
                'notes' => $dto->notes,
                'created_by' => $dto->createdBy,
            ]);

            $this->createOrderItems($order, $dto->items);

            event(new OrderCreated($order));

            return $order->load(['items.product', 'customer']);
        });
    }

    /**
     * Update an editable order.
     *
     * @throws \DomainException when order is not editable
     * @throws \Throwable
     */
    public function update(Order $order, CreateOrderDTO $dto): Order
    {
        if (! $order->isEditable()) {
            throw new \DomainException(__('orders.cannot_edit_in_status', [
                'status' => $order->status,
            ]));
        }

        return $this->transaction(function () use ($order, $dto) {
            $totals = $this->financials->calculateTotals($dto->items);

            $order->items()->delete();
            $this->createOrderItems($order, $dto->items);

            return $this->orders->update($order, [
                'requested_delivery_date' => $dto->requestedDeliveryDate,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'],
                'notes' => $dto->notes,
            ]);
        });
    }

    /**
     * Soft-delete an order.
     *
     * @throws \DomainException
     */
    public function delete(Order $order): void
    {
        if (in_array($order->status, ['shipped', 'delivered'], true)) {
            throw new \DomainException(__('orders.cannot_delete_shipped'));
        }

        $this->orders->delete($order);
    }

    /**
     * @param  Collection<int, OrderItemDTO>  $items
     */
    private function createOrderItems(Order $order, Collection $items): void
    {
        $order->items()->createMany(
            $items->map(fn (OrderItemDTO $item) => [
                'product_id' => $item->productId,
                'quantity' => $item->quantity,
                'unit_price' => $item->unitPrice,
                'discount_percent' => $item->discountPercent,
                'discount_amount' => $item->discountAmount(),
                'line_total' => $item->lineTotal(),
                'notes' => $item->notes,
            ])->toArray()
        );
    }
}
