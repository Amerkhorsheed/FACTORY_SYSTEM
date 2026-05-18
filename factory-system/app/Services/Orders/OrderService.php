<?php

namespace App\Services\Orders;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
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
use App\Services\Invoices\InvoiceService;
use App\Services\Products\StockService;
use App\ValueObjects\Money;
use Carbon\Carbon;
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
        private readonly CustomerRepositoryInterface $customers,
        private readonly ProductRepositoryInterface $products,
        private readonly OrderFinancialsService $financials,
        private readonly StockService $stock,
        private readonly InvoiceService $invoices,
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
                    CalculateOrderTotalsPipe::class,
                    ValidateCustomerCreditPipe::class,
                    ValidateStockAvailabilityPipe::class,
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
            $dto = $this->enrichItemsWithCurrentPrices($dto);
            $totals = $this->financials->calculateTotals($dto->items);

            $this->validateCreditForUpdate($order, $dto, $totals['total']);
            $this->validateStockForUpdate($order, $dto);

            if ($order->status === 'accepted') {
                $this->syncAcceptedStock($order, $dto);
            }

            $order->items()->delete();
            $this->createOrderItems($order, $dto->items);

            $updated = $this->orders->update($order, [
                'customer_id' => $dto->customerId,
                'order_date' => $dto->orderDate,
                'requested_delivery_date' => $dto->requestedDeliveryDate,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'],
                'notes' => $dto->notes,
            ]);

            if ($updated->status === 'accepted') {
                $this->invoices->syncFromOrder($updated);
            }

            return $updated;
        });
    }

    /**
     * Soft-delete an order.
     *
     * @throws \DomainException
     */
    public function delete(Order $order): void
    {
        if (! in_array($order->status, ['pending', 'cancelled'], true)) {
            throw new \DomainException(__('orders.cannot_delete_shipped'));
        }

        $this->orders->delete($order);
    }

    /** @return Collection<string, \Illuminate\Database\Eloquent\Collection<int, Order>> */
    public function daily(Carbon $date): Collection
    {
        return $this->orders->getForDate($date)->groupBy('status');
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
                'discount_percent' => $item->discountPercentForStorage(),
                'discount_amount' => $item->discountAmount(),
                'line_total' => $item->lineTotal(),
                'notes' => $item->notes,
            ])->toArray()
        );
    }

    private function enrichItemsWithCurrentPrices(CreateOrderDTO $dto): CreateOrderDTO
    {
        $items = $dto->items->map(function (OrderItemDTO $item) {
            $product = $this->products->findByIdOrFail($item->productId);

            return new OrderItemDTO(
                productId: $item->productId,
                quantity: $item->quantity,
                unitPrice: $product->unit_price,
                discountBasisPoints: $item->discountBasisPoints,
                notes: $item->notes,
            );
        });

        return new CreateOrderDTO(
            customerId: $dto->customerId,
            orderDate: $dto->orderDate,
            items: $items,
            requestedDeliveryDate: $dto->requestedDeliveryDate,
            notes: $dto->notes,
            createdBy: $dto->createdBy,
        );
    }

    private function validateCreditForUpdate(Order $order, CreateOrderDTO $dto, int $orderValue): void
    {
        $customer = $this->customers->findByIdOrFail($dto->customerId);
        $availableCredit = $customer->credit_limit - $customer->outstanding_balance;

        $order->loadMissing('invoice');
        if ($order->customer_id === $dto->customerId && $order->invoice && ! in_array($order->invoice->status, ['paid', 'void'], true)) {
            $availableCredit += $order->invoice->balance_due;
        }

        if ($customer->credit_limit > 0 && $orderValue > $availableCredit) {
            throw new CreditLimitExceededException(__('orders.credit_limit_exceeded', [
                'available' => Money::of($availableCredit)->format(),
                'required' => Money::of($orderValue)->format(),
            ]));
        }
    }

    private function validateStockForUpdate(Order $order, CreateOrderDTO $dto): void
    {
        $currentReserved = $order->status === 'accepted'
            ? $order->items()->selectRaw('product_id, SUM(quantity) as quantity')->groupBy('product_id')->pluck('quantity', 'product_id')
            : collect();

        $errors = [];

        $requested = $dto->items
            ->groupBy(fn (OrderItemDTO $item) => $item->productId)
            ->map(fn (Collection $items) => $items->sum(fn (OrderItemDTO $item) => $item->quantity));

        foreach ($requested as $productId => $quantity) {
            $product = $this->products->findByIdOrFail((int) $productId);
            $available = $product->stock_quantity + (int) ($currentReserved[$productId] ?? 0);

            if ($available < $quantity) {
                $errors[] = __('orders.insufficient_stock', [
                    'product' => $product->name,
                    'available' => $available,
                    'requested' => $quantity,
                ]);
            }
        }

        if ($errors !== []) {
            throw new InsufficientStockException(implode("\n", $errors));
        }
    }

    private function syncAcceptedStock(Order $order, CreateOrderDTO $dto): void
    {
        $current = $order->items()
            ->selectRaw('product_id, SUM(quantity) as quantity')
            ->groupBy('product_id')
            ->pluck('quantity', 'product_id');
        $requested = $dto->items
            ->groupBy(fn (OrderItemDTO $item) => $item->productId)
            ->map(fn (Collection $items) => $items->sum(fn (OrderItemDTO $item) => $item->quantity));

        $productIds = $current->keys()->merge($requested->keys())->unique();

        foreach ($productIds as $productId) {
            $oldQuantity = (int) ($current[$productId] ?? 0);
            $newQuantity = (int) ($requested[$productId] ?? 0);
            $delta = $newQuantity - $oldQuantity;

            if ($delta === 0) {
                continue;
            }

            $product = $this->products->findByIdOrFail((int) $productId);
            $this->stock->moveStock($product, $delta > 0 ? 'out' : 'return', abs($delta), [
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'unit_cost' => $product->cost_price,
            ]);
        }
    }
}
