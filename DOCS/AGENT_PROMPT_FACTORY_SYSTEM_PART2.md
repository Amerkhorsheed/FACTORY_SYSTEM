# 🏭 MASTER AGENT PROMPT — PART 2
## Full Concrete Implementations, DTOs, Services, Views & Testing
### نظام إدارة معمل التوزيع والشحن — التنفيذ الكامل
---
> **PART 2 OF 2** | Read PART 1 first, then continue here.
> This file contains all concrete code implementations the agent must follow exactly.

---

## ╔══════════════════════════════════════════════════════════════╗
## ║           SECTION A — DATA TRANSFER OBJECTS (DTOs)           ║
## ╚══════════════════════════════════════════════════════════════╝

> DTOs enforce type safety at service boundaries. Services accept DTOs, not raw arrays.
> All DTO properties are `readonly` — they are immutable once constructed.

### `app/DTOs/Orders/CreateOrderDTO.php`
```php
<?php
namespace App\DTOs\Orders;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Data transfer object for order creation.
 * Constructed from validated FormRequest data.
 * Immutable — properties are readonly.
 *
 * @package App\DTOs\Orders
 */
final class CreateOrderDTO
{
    /**
     * @param  int              $customerId
     * @param  Carbon           $orderDate
     * @param  Collection<OrderItemDTO> $items
     * @param  Carbon|null      $requestedDeliveryDate
     * @param  string|null      $notes
     * @param  int              $createdBy  User ID
     */
    public function __construct(
        public readonly int        $customerId,
        public readonly Carbon     $orderDate,
        public readonly Collection $items,
        public readonly ?Carbon    $requestedDeliveryDate = null,
        public readonly ?string    $notes                 = null,
        public readonly int        $createdBy             = 0,
    ) {}

    /**
     * Build from validated request array.
     *
     * @param  array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            customerId:             (int) $data['customer_id'],
            orderDate:              Carbon::parse($data['order_date']),
            items:                  collect($data['items'])->map(
                fn(array $item) => OrderItemDTO::fromArray($item)
            ),
            requestedDeliveryDate:  isset($data['requested_delivery_date'])
                                    ? Carbon::parse($data['requested_delivery_date'])
                                    : null,
            notes:                  $data['notes'] ?? null,
            createdBy:              (int) ($data['created_by'] ?? auth()->id()),
        );
    }

    /** Total item count across all line items */
    public function totalQuantity(): int
    {
        return $this->items->sum(fn(OrderItemDTO $i) => $i->quantity);
    }
}
```

### `app/DTOs/Orders/OrderItemDTO.php`
```php
<?php
namespace App\DTOs\Orders;

/**
 * Represents a single line item in an order DTO.
 *
 * @package App\DTOs\Orders
 */
final class OrderItemDTO
{
    public function __construct(
        public readonly int    $productId,
        public readonly int    $quantity,
        public readonly int    $unitPrice,        // BIGINT — smallest currency unit
        public readonly float  $discountPercent = 0.0,
        public readonly ?string $notes          = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId:       (int)   $data['product_id'],
            quantity:        (int)   $data['quantity'],
            unitPrice:       (int)   $data['unit_price'],
            discountPercent: (float) ($data['discount_percent'] ?? 0.0),
            notes:           $data['notes'] ?? null,
        );
    }

    /** Calculate line total in smallest currency unit */
    public function lineTotal(): int
    {
        $gross    = $this->unitPrice * $this->quantity;
        $discount = (int) round($gross * ($this->discountPercent / 100));
        return $gross - $discount;
    }

    /** Calculate discount amount in smallest currency unit */
    public function discountAmount(): int
    {
        $gross = $this->unitPrice * $this->quantity;
        return (int) round($gross * ($this->discountPercent / 100));
    }
}
```

### `app/DTOs/Invoices/RecordPaymentDTO.php`
```php
<?php
namespace App\DTOs\Invoices;

use Carbon\Carbon;

/**
 * DTO for recording a payment against an invoice.
 *
 * @package App\DTOs\Invoices
 */
final class RecordPaymentDTO
{
    public function __construct(
        public readonly int     $invoiceId,
        public readonly int     $amount,          // BIGINT
        public readonly string  $paymentMethod,   // cash|credit|check|bank_transfer
        public readonly Carbon  $paymentDate,
        public readonly int     $receivedBy,      // User ID
        public readonly ?string $referenceNumber  = null,
        public readonly ?string $notes            = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            invoiceId:       (int)    $data['invoice_id'],
            amount:          (int)    $data['amount'],
            paymentMethod:   (string) $data['payment_method'],
            paymentDate:     Carbon::parse($data['payment_date']),
            receivedBy:      (int)    ($data['received_by'] ?? auth()->id()),
            referenceNumber: $data['reference_number'] ?? null,
            notes:           $data['notes'] ?? null,
        );
    }
}
```

### `app/DTOs/Customers/CreateCustomerDTO.php`
```php
<?php
namespace App\DTOs\Customers;

/**
 * DTO for creating a new customer.
 *
 * @package App\DTOs\Customers
 */
final class CreateCustomerDTO
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $phone,
        public readonly string  $address,
        public readonly string  $category,        // A|B|C
        public readonly int     $createdBy,
        public readonly ?string $businessName     = null,
        public readonly ?string $phoneAlt         = null,
        public readonly ?string $email            = null,
        public readonly ?string $city             = null,
        public readonly ?string $region           = null,
        public readonly int     $creditLimit       = 0,
        public readonly ?string $notes            = null,
        public readonly bool    $portalAccess      = false,
        public readonly ?string $portalPassword    = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:           $data['name'],
            phone:          $data['phone'],
            address:        $data['address'],
            category:       $data['category'] ?? 'B',
            createdBy:      (int) ($data['created_by'] ?? auth()->id()),
            businessName:   $data['business_name'] ?? null,
            phoneAlt:       $data['phone_alt'] ?? null,
            email:          $data['email'] ?? null,
            city:           $data['city'] ?? null,
            region:         $data['region'] ?? null,
            creditLimit:    (int) ($data['credit_limit'] ?? 0),
            notes:          $data['notes'] ?? null,
            portalAccess:   (bool) ($data['portal_access'] ?? false),
            portalPassword: $data['portal_password'] ?? null,
        );
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║            SECTION B — REPOSITORIES (CONCRETE)              ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Repositories/OrderRepository.php`
```php
<?php
namespace App\Repositories;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Concrete Order repository. All Eloquent queries for Order live here.
 * Services MUST NOT use Order model directly — use this repository.
 *
 * @package App\Repositories
 */
class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Order());
    }

    public function findById(int $id): ?Order
    {
        return Order::with(['customer','items.product','invoice','shipment'])->find($id);
    }

    public function findByIdOrFail(int $id): Order
    {
        return Order::with(['customer','items.product','invoice','shipment'])->findOrFail($id);
    }

    public function findByNumber(string $number): ?Order
    {
        return Order::where('order_number', $number)->first();
    }

    /**
     * Paginate orders with optional filters.
     *
     * @param  array{
     *   search?: string,
     *   status?: string|string[],
     *   customer_id?: int,
     *   date_from?: string,
     *   date_to?: string,
     *   region?: string,
     * } $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Order::with(['customer','shipment'])
            ->latest('order_date')
            ->latest('id');

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('order_number', 'like', "%{$filters['search']}%")
                  ->orWhereHas('customer', fn($c) =>
                      $c->where('name', 'like', "%{$filters['search']}%")
                  );
            });
        }

        if (! empty($filters['status'])) {
            is_array($filters['status'])
                ? $query->whereIn('status', $filters['status'])
                : $query->where('status', $filters['status']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('order_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('order_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['region'])) {
            $query->whereHas('customer', fn($c) => $c->where('region', $filters['region']));
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getForDate(Carbon $date): Collection
    {
        return Order::with(['customer','items','shipment'])
            ->whereDate('order_date', $date)
            ->orderBy('status')
            ->get();
    }

    public function getPendingForCustomer(int $customerId): Collection
    {
        return Order::where('customer_id', $customerId)
            ->whereNotIn('status', ['delivered','cancelled','returned'])
            ->get();
    }

    public function getReadyOrders(): Collection
    {
        return Order::with(['customer'])
            ->where('status', 'ready')
            ->whereNull('shipment_id')
            ->orderBy('order_date')
            ->get();
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function update(Order $order, array $data): Order
    {
        $order->update($data);
        return $order->fresh(['customer','items','invoice']);
    }

    public function delete(Order $order): void
    {
        $order->delete();
    }
}
```

### `app/Repositories/ProductRepository.php`
```php
<?php
namespace App\Repositories;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Concrete Product repository — all product data access.
 *
 * @package App\Repositories
 */
class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Product());
    }

    /**
     * Paginate products with search and filter support.
     *
     * @param  array{search?: string, category_id?: int, is_active?: bool, low_stock?: bool} $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::with('category')->orderBy('sort_order')->orderBy('name');

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('code', 'like', "%{$filters['search']}%")
                  ->orWhere('barcode', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (! empty($filters['low_stock'])) {
            $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /** Search products for order form autocomplete */
    public function searchForOrder(string $term, int $limit = 10): Collection
    {
        return Product::where('is_active', true)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id','name','code','unit','unit_price','stock_quantity']);
    }

    public function findByCode(string $code): ?Product
    {
        return Product::where('code', $code)->first();
    }

    /** Lock a product row for stock update (use inside transaction) */
    public function lockForUpdate(int $id): Product
    {
        return Product::where('id', $id)->lockForUpdate()->firstOrFail();
    }

    public function getLowStock(): Collection
    {
        return Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('is_active', true)
            ->orderBy('stock_quantity')
            ->get();
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║            SECTION C — SERVICES (FULL IMPLEMENTATIONS)      ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Services/Orders/OrderService.php`
```php
<?php
namespace App\Services\Orders;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\DTOs\Orders\CreateOrderDTO;
use App\DTOs\Orders\OrderItemDTO;
use App\Events\Orders\OrderCreated;
use App\Exceptions\CreditLimitExceededException;
use App\Exceptions\InsufficientStockException;
use App\Models\{Customer, Order, OrderItem, Product};
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pipeline\Pipeline;

/**
 * Core order management service.
 * Handles creation, editing, listing, and deletion of orders.
 * Status transitions are delegated to OrderStatusService.
 *
 * @package App\Services\Orders
 */
class OrderService extends BaseService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderFinancialsService   $financials,
        private readonly Pipeline                 $pipeline,
    ) {}

    /**
     * Paginate orders with optional filters.
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
            // Run through validation pipeline
            $dto = $this->pipeline
                ->send($dto)
                ->through([
                    \App\Pipelines\Order\ValidateCustomerCreditPipe::class,
                    \App\Pipelines\Order\ValidateStockAvailabilityPipe::class,
                    \App\Pipelines\Order\CalculateOrderTotalsPipe::class,
                ])
                ->thenReturn();

            $totals = $this->financials->calculateTotals($dto->items);

            $order = $this->orders->create([
                'customer_id'              => $dto->customerId,
                'order_date'               => $dto->orderDate,
                'requested_delivery_date'  => $dto->requestedDeliveryDate,
                'status'                   => 'pending',
                'subtotal'                 => $totals['subtotal'],
                'discount_amount'          => $totals['discount'],
                'tax_amount'               => $totals['tax'],
                'total_amount'             => $totals['total'],
                'notes'                    => $dto->notes,
                'created_by'               => $dto->createdBy,
            ]);

            $this->createOrderItems($order, $dto->items);

            event(new OrderCreated($order));

            return $order->load(['items.product', 'customer']);
        });
    }

    /**
     * Update an editable order's items and notes.
     * Only allowed when status is pending or accepted.
     *
     * @throws \DomainException when order is not editable
     * @throws \Throwable
     */
    public function update(Order $order, CreateOrderDTO $dto): Order
    {
        if (! $order->isEditable()) {
            throw new \DomainException(__('orders.cannot_edit_in_status', [
                'status' => __("factory.order_statuses.{$order->status}")
            ]));
        }

        return $this->transaction(function () use ($order, $dto) {
            $totals = $this->financials->calculateTotals($dto->items);

            $order->items()->delete();
            $this->createOrderItems($order, $dto->items);

            return $this->orders->update($order, [
                'requested_delivery_date' => $dto->requestedDeliveryDate,
                'subtotal'                => $totals['subtotal'],
                'discount_amount'         => $totals['discount'],
                'tax_amount'              => $totals['tax'],
                'total_amount'            => $totals['total'],
                'notes'                   => $dto->notes,
            ]);
        });
    }

    /**
     * Soft-delete an order. Only if it has no active financial records.
     *
     * @throws \DomainException
     */
    public function delete(Order $order): void
    {
        if (in_array($order->status, ['shipped','delivered'], true)) {
            throw new \DomainException(__('orders.cannot_delete_shipped'));
        }

        $this->orders->delete($order);
    }

    // ── Private helpers ──────────────────────────────────────────────────

    /**
     * Persist order items from DTO collection.
     *
     * @param  \Illuminate\Support\Collection<OrderItemDTO> $items
     */
    private function createOrderItems(Order $order, \Illuminate\Support\Collection $items): void
    {
        $order->items()->createMany(
            $items->map(fn(OrderItemDTO $item) => [
                'product_id'      => $item->productId,
                'quantity'        => $item->quantity,
                'unit_price'      => $item->unitPrice,
                'discount_percent'=> $item->discountPercent,
                'discount_amount' => $item->discountAmount(),
                'line_total'      => $item->lineTotal(),
                'notes'           => $item->notes,
            ])->toArray()
        );
    }
}
```

### `app/Services/Orders/OrderStatusService.php`
```php
<?php
namespace App\Services\Orders;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Events\Orders\{OrderAccepted, OrderCancelled, OrderDelivered, OrderShipped};
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\{Order, User};
use App\Services\BaseService;
use App\Services\Products\StockService;
use App\Services\Invoices\InvoiceService;
use App\StateMachines\OrderStateMachine;
use Carbon\Carbon;

/**
 * Manages all order status transitions.
 * Every transition is validated by OrderStateMachine before execution.
 * Stock and invoice operations triggered here on relevant transitions.
 *
 * @package App\Services\Orders
 */
class OrderStatusService extends BaseService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderStateMachine         $stateMachine,
        private readonly StockService              $stock,
        private readonly InvoiceService            $invoices,
    ) {}

    /**
     * Accept a pending order: deduct stock, create draft invoice.
     *
     * @throws InvalidStatusTransitionException
     * @throws \Throwable
     */
    public function accept(Order $order, User $actor): Order
    {
        $this->stateMachine->transition($order->status, 'accepted');

        return $this->transaction(function () use ($order, $actor) {
            // Deduct stock for each item
            foreach ($order->items as $item) {
                $this->stock->moveStock(
                    product:   $item->product,
                    type:      'out',
                    quantity:  $item->quantity,
                    meta: [
                        'reference_type' => 'order',
                        'reference_id'   => $order->id,
                        'unit_cost'      => $item->product->cost_price,
                    ]
                );
            }

            // Create draft invoice
            $this->invoices->createFromOrder($order);

            $updated = $this->orders->update($order, [
                'status'      => 'accepted',
                'accepted_by' => $actor->id,
                'accepted_at' => Carbon::now(),
            ]);

            event(new OrderAccepted($updated));
            return $updated;
        });
    }

    /**
     * Mark order as preparing (warehouse is packing).
     *
     * @throws InvalidStatusTransitionException
     */
    public function markPreparing(Order $order): Order
    {
        $this->stateMachine->transition($order->status, 'preparing');
        return $this->orders->update($order, ['status' => 'preparing']);
    }

    /**
     * Mark order as ready for shipment assignment.
     *
     * @throws InvalidStatusTransitionException
     */
    public function markReady(Order $order): Order
    {
        $this->stateMachine->transition($order->status, 'ready');
        return $this->orders->update($order, ['status' => 'ready']);
    }

    /**
     * Confirm delivery: issue invoice, update customer balance.
     *
     * @throws InvalidStatusTransitionException
     * @throws \Throwable
     */
    public function confirmDelivery(Order $order, User $actor): Order
    {
        $this->stateMachine->transition($order->status, 'delivered');

        return $this->transaction(function () use ($order, $actor) {
            // Issue the draft invoice
            if ($order->invoice && $order->invoice->status === 'draft') {
                $this->invoices->issue($order->invoice);
            }

            $updated = $this->orders->update($order, [
                'status'       => 'delivered',
                'delivered_at' => Carbon::now(),
                'shipped_by'   => $actor->id,
            ]);

            event(new OrderDelivered($updated));
            return $updated;
        });
    }

    /**
     * Cancel an order: return stock if already deducted, void invoice.
     *
     * @throws InvalidStatusTransitionException
     * @throws \Throwable
     */
    public function cancel(Order $order, string $reason, User $actor): Order
    {
        $this->stateMachine->transition($order->status, 'cancelled');

        return $this->transaction(function () use ($order, $reason, $actor) {
            // Return stock if order was accepted (stock was deducted)
            if (in_array($order->status, ['accepted','preparing','ready'], true)) {
                foreach ($order->items as $item) {
                    $this->stock->moveStock(
                        product:  $item->product,
                        type:     'return',
                        quantity: $item->quantity,
                        meta:     ['reference_type' => 'order', 'reference_id' => $order->id]
                    );
                }
            }

            // Void draft invoice if exists
            if ($order->invoice && $order->invoice->status === 'draft') {
                $this->invoices->void($order->invoice, __('invoices.voided_due_to_cancellation'));
            }

            $updated = $this->orders->update($order, [
                'status'        => 'cancelled',
                'cancel_reason' => $reason,
            ]);

            event(new OrderCancelled($updated));
            return $updated;
        });
    }

    /**
     * Record a full or partial order return.
     *
     * @throws InvalidStatusTransitionException
     * @throws \Throwable
     */
    public function recordReturn(Order $order, string $notes): Order
    {
        $this->stateMachine->transition($order->status, 'returned');

        return $this->transaction(function () use ($order, $notes) {
            // Return stock
            foreach ($order->items as $item) {
                $this->stock->moveStock(
                    product:  $item->product,
                    type:     'return',
                    quantity: $item->quantity - $item->returned_qty,
                    meta:     ['reference_type' => 'order', 'reference_id' => $order->id]
                );
            }

            return $this->orders->update($order, [
                'status'       => 'returned',
                'returned_at'  => Carbon::now(),
                'return_notes' => $notes,
            ]);
        });
    }
}
```

### `app/Services/Orders/OrderFinancialsService.php`
```php
<?php
namespace App\Services\Orders;

use App\DTOs\Orders\OrderItemDTO;
use App\Services\BaseService;
use App\Services\SettingService;
use Illuminate\Support\Collection;

/**
 * Calculates order financial totals.
 * Isolated to ensure 100% test coverage of financial logic.
 * All values are integers (smallest currency unit).
 *
 * @package App\Services\Orders
 */
class OrderFinancialsService extends BaseService
{
    public function __construct(
        private readonly SettingService $settings,
    ) {}

    /**
     * Calculate subtotal, discount, tax, and grand total for a set of items.
     *
     * @param  Collection<OrderItemDTO> $items
     * @return array{subtotal: int, discount: int, tax: int, total: int}
     */
    public function calculateTotals(Collection $items): array
    {
        $subtotal = $items->sum(fn(OrderItemDTO $i) => $i->unitPrice * $i->quantity);
        $discount = $items->sum(fn(OrderItemDTO $i) => $i->discountAmount());
        $taxRate  = (float) $this->settings->get('invoice_tax_rate', 0);
        $taxable  = $subtotal - $discount;
        $tax      = (int) round($taxable * ($taxRate / 100));
        $total    = $taxable + $tax;

        return compact('subtotal', 'discount', 'tax', 'total');
    }

    /**
     * Calculate how much credit this order would consume.
     * Used for pre-creation credit check.
     */
    public function calculateOrderValue(Collection $items): int
    {
        return $this->calculateTotals($items)['total'];
    }
}
```

### `app/Services/Invoices/InvoiceService.php`
```php
<?php
namespace App\Services\Invoices;

use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\DTOs\Invoices\RecordPaymentDTO;
use App\Events\Invoices\{InvoiceIssued, PaymentReceived};
use App\Exceptions\InvoiceCannotBeVoidedException;
use App\Models\{Invoice, Order, Payment};
use App\Services\BaseService;
use App\Services\SettingService;

/**
 * Invoice and payment management service.
 * CRITICAL: All payment operations wrapped in transactions.
 * Customer balance updated on every payment change.
 *
 * @package App\Services\Invoices
 */
class InvoiceService extends BaseService
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoices,
        private readonly SettingService             $settings,
    ) {}

    /**
     * Create a draft invoice from an accepted order.
     * Called automatically by OrderStatusService::accept().
     */
    public function createFromOrder(Order $order): Invoice
    {
        $taxRate = (float) $this->settings->get('invoice_tax_rate', 0);

        return $this->invoices->create([
            'order_id'        => $order->id,
            'customer_id'     => $order->customer_id,
            'type'            => 'sale',
            'status'          => 'draft',
            'issue_date'      => today(),
            'due_date'        => today()->addDays(
                (int) $this->settings->get('invoice_due_days', 30)
            ),
            'subtotal'        => $order->subtotal,
            'discount_amount' => $order->discount_amount,
            'tax_rate'        => $taxRate,
            'tax_amount'      => $order->tax_amount,
            'total_amount'    => $order->total_amount,
            'paid_amount'     => 0,
            'balance_due'     => $order->total_amount,
            'created_by'      => auth()->id(),
        ]);
    }

    /**
     * Transition invoice from draft to issued.
     */
    public function issue(Invoice $invoice): Invoice
    {
        $updated = $this->invoices->update($invoice, ['status' => 'issued']);
        event(new InvoiceIssued($updated));
        return $updated;
    }

    /**
     * Void an invoice. Not allowed if payments exist.
     *
     * @throws InvoiceCannotBeVoidedException
     * @throws \Throwable
     */
    public function void(Invoice $invoice, string $reason): Invoice
    {
        if ($invoice->paid_amount > 0) {
            throw new InvoiceCannotBeVoidedException(
                __('invoices.cannot_void_with_payments')
            );
        }

        return $this->transaction(function () use ($invoice, $reason) {
            return $this->invoices->update($invoice, [
                'status'      => 'void',
                'voided_at'   => now(),
                'void_reason' => $reason,
            ]);
        });
    }

    /**
     * Record a payment against an invoice.
     * Updates invoice status, customer balance, and fires event.
     *
     * @throws \DomainException when amount exceeds balance_due
     * @throws \Throwable
     */
    public function recordPayment(RecordPaymentDTO $dto): Payment
    {
        $invoice = $this->invoices->findByIdOrFail($dto->invoiceId);

        if ($dto->amount > $invoice->balance_due) {
            throw new \DomainException(__('invoices.payment_exceeds_balance', [
                'balance' => money_format($invoice->balance_due),
            ]));
        }

        return $this->transaction(function () use ($dto, $invoice) {
            $payment = Payment::create([
                'invoice_id'       => $invoice->id,
                'customer_id'      => $invoice->customer_id,
                'amount'           => $dto->amount,
                'payment_method'   => $dto->paymentMethod,
                'payment_date'     => $dto->paymentDate,
                'reference_number' => $dto->referenceNumber,
                'notes'            => $dto->notes,
                'received_by'      => $dto->receivedBy,
            ]);

            $this->recalculateInvoiceBalance($invoice);
            $this->updateCustomerBalance($invoice->customer_id);

            event(new PaymentReceived($payment->load('invoice.customer')));

            return $payment;
        });
    }

    /**
     * Delete a payment and recalculate balances.
     *
     * @throws \Throwable
     */
    public function deletePayment(Payment $payment): void
    {
        $invoiceId  = $payment->invoice_id;
        $customerId = $payment->customer_id;

        $this->transaction(function () use ($payment, $invoiceId, $customerId) {
            $payment->delete();
            $invoice = $this->invoices->findByIdOrFail($invoiceId);
            $this->recalculateInvoiceBalance($invoice);
            $this->updateCustomerBalance($customerId);
        });
    }

    // ── Private helpers ──────────────────────────────────────────────────

    private function recalculateInvoiceBalance(Invoice $invoice): void
    {
        $paid   = $invoice->payments()->sum('amount');
        $status = match(true) {
            $paid <= 0                            => $invoice->status === 'void' ? 'void' : 'issued',
            $paid >= $invoice->total_amount       => 'paid',
            default                               => 'partial',
        };

        $invoice->update([
            'paid_amount' => $paid,
            'balance_due' => max(0, $invoice->total_amount - $paid),
            'status'      => $status,
        ]);
    }

    private function updateCustomerBalance(int $customerId): void
    {
        $outstanding = Invoice::where('customer_id', $customerId)
            ->whereIn('status', ['issued','sent','partial'])
            ->sum('balance_due');

        \App\Models\Customer::where('id', $customerId)
            ->update(['outstanding_balance' => $outstanding]);
    }
}
```

### `app/Services/SettingService.php`
```php
<?php
namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * System settings service with Redis caching.
 * Use via Setting facade: Setting::get('key'), Setting::set('key', value).
 * Cache TTL: 60 minutes. Invalidated on every set() call.
 *
 * @package App\Services
 */
class SettingService
{
    private const CACHE_KEY = 'system:settings:all';
    private const CACHE_TTL  = 3600; // 60 minutes

    /**
     * Get a setting value by key. Returns default if not found.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();
        return $settings[$key] ?? $default;
    }

    /**
     * Set a setting value and invalidate cache.
     */
    public function set(string $key, mixed $value): void
    {
        SystemSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $this->serialize($value)]
        );

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Set multiple settings at once.
     *
     * @param array<string, mixed> $pairs
     */
    public function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $this->serialize($value)]
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get all settings as key→value map.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return SystemSetting::all()
                ->mapWithKeys(fn($s) => [$s->key => $this->deserialize($s->value, $s->type)])
                ->toArray();
        });
    }

    /** @param mixed $value */
    private function serialize(mixed $value): string
    {
        return is_array($value) || is_object($value)
            ? json_encode($value)
            : (string) $value;
    }

    private function deserialize(mixed $value, string $type): mixed
    {
        return match($type) {
            'integer' => (int)    $value,
            'boolean' => (bool)   $value,
            'json'    => json_decode($value, true),
            default   => $value,
        };
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║            SECTION D — PIPELINE PIPES                       ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Pipelines/Order/ValidateCustomerCreditPipe.php`
```php
<?php
namespace App\Pipelines\Order;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\DTOs\Orders\CreateOrderDTO;
use App\Exceptions\CreditLimitExceededException;
use App\Services\Orders\OrderFinancialsService;
use Closure;

/**
 * Pipeline pipe: validates the customer has sufficient credit for this order.
 * Checks: credit_limit - outstanding_balance >= order total
 *
 * @package App\Pipelines\Order
 */
class ValidateCustomerCreditPipe
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly OrderFinancialsService      $financials,
    ) {}

    /**
     * @throws CreditLimitExceededException
     */
    public function handle(CreateOrderDTO $dto, Closure $next): CreateOrderDTO
    {
        $customer   = $this->customers->findByIdOrFail($dto->customerId);
        $orderValue = $this->financials->calculateOrderValue($dto->items);

        $availableCredit = $customer->credit_limit - $customer->outstanding_balance;

        if ($customer->credit_limit > 0 && $orderValue > $availableCredit) {
            throw new CreditLimitExceededException(
                __('orders.credit_limit_exceeded', [
                    'available' => money_format($availableCredit),
                    'required'  => money_format($orderValue),
                ])
            );
        }

        return $next($dto);
    }
}
```

### `app/Pipelines/Order/ValidateStockAvailabilityPipe.php`
```php
<?php
namespace App\Pipelines\Order;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\DTOs\Orders\{CreateOrderDTO, OrderItemDTO};
use App\Exceptions\InsufficientStockException;
use Closure;

/**
 * Pipeline pipe: validates stock availability for all order items.
 * Collects ALL stock errors before throwing, so user sees everything at once.
 *
 * @package App\Pipelines\Order
 */
class ValidateStockAvailabilityPipe
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    /**
     * @throws InsufficientStockException
     */
    public function handle(CreateOrderDTO $dto, Closure $next): CreateOrderDTO
    {
        $errors = [];

        foreach ($dto->items as $item) {
            $product = $this->products->findByIdOrFail($item->productId);

            if ($product->stock_quantity < $item->quantity) {
                $errors[] = __('orders.insufficient_stock', [
                    'product'    => $product->name,
                    'available'  => $product->stock_quantity,
                    'requested'  => $item->quantity,
                ]);
            }
        }

        if (! empty($errors)) {
            throw new InsufficientStockException(implode("\n", $errors));
        }

        return $next($dto);
    }
}
```

### `app/Pipelines/Order/CalculateOrderTotalsPipe.php`
```php
<?php
namespace App\Pipelines\Order;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\DTOs\Orders\{CreateOrderDTO, OrderItemDTO};
use App\Services\Orders\OrderFinancialsService;
use Closure;

/**
 * Pipeline pipe: enriches DTO items with current product prices.
 * Snapshots the current unit_price into each OrderItemDTO.
 * This ensures price is locked at order creation time.
 *
 * @package App\Pipelines\Order
 */
class CalculateOrderTotalsPipe
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function handle(CreateOrderDTO $dto, Closure $next): CreateOrderDTO
    {
        // Snapshot current prices into item DTOs
        $enrichedItems = $dto->items->map(function (OrderItemDTO $item) {
            $product = $this->products->findByIdOrFail($item->productId);

            return new OrderItemDTO(
                productId:       $item->productId,
                quantity:        $item->quantity,
                unitPrice:       $product->unit_price, // Always use current product price
                discountPercent: $item->discountPercent,
                notes:           $item->notes,
            );
        });

        // Return a new DTO with enriched items (DTOs are immutable)
        return new CreateOrderDTO(
            customerId:            $dto->customerId,
            orderDate:             $dto->orderDate,
            items:                 $enrichedItems,
            requestedDeliveryDate: $dto->requestedDeliveryDate,
            notes:                 $dto->notes,
            createdBy:             $dto->createdBy,
        );
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║            SECTION E — CONTROLLERS                          ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Http/Controllers/Orders/OrderController.php`
```php
<?php
namespace App\Http\Controllers\Orders;

use App\DTOs\Orders\CreateOrderDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\{StoreOrderRequest, UpdateOrderRequest};
use App\Models\Order;
use App\Services\Orders\OrderService;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

/**
 * Handles Order CRUD operations.
 * Status transitions are handled by OrderStatusController.
 * All business logic delegated to OrderService.
 *
 * @package App\Http\Controllers\Orders
 */
class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
        $this->authorizeResource(Order::class, 'order');
    }

    public function index(Request $request): View
    {
        $orders = $this->orders->list($request->only([
            'search','status','customer_id','date_from','date_to','region',
        ]));

        return view('orders.index', compact('orders'));
    }

    public function create(): View
    {
        return view('orders.create');
    }

    /**
     * @throws \App\Exceptions\CreditLimitExceededException
     * @throws \App\Exceptions\InsufficientStockException
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $dto   = CreateOrderDTO::fromArray($request->validated());
        $order = $this->orders->create($dto);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', __('orders.created_successfully', [
                'number' => $order->order_number,
            ]));
    }

    public function show(Order $order): View
    {
        $order->load(['customer','items.product','invoice.payments','shipment.truck']);
        return view('orders.show', compact('order'));
    }

    public function edit(Order $order): View
    {
        if (! $order->isEditable()) {
            return redirect()->route('orders.show', $order)
                ->with('error', __('orders.not_editable'));
        }

        $order->load(['items.product','customer']);
        return view('orders.edit', compact('order'));
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $dto = CreateOrderDTO::fromArray($request->validated());
        $this->orders->update($order, $dto);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', __('orders.updated_successfully'));
    }

    public function destroy(Order $order): RedirectResponse
    {
        $this->orders->delete($order);

        return redirect()
            ->route('orders.index')
            ->with('success', __('orders.deleted_successfully'));
    }

    public function daily(Request $request): View
    {
        $date   = \Carbon\Carbon::parse($request->get('date', today()));
        $orders = \App\Models\Order::with(['customer','shipment'])
            ->whereDate('order_date', $date)
            ->orderBy('status')
            ->get()
            ->groupBy('status');

        return view('orders.daily', compact('orders', 'date'));
    }
}
```

### `app/Http/Controllers/Orders/OrderStatusController.php`
```php
<?php
namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\CancelOrderRequest;
use App\Models\Order;
use App\Services\Orders\OrderStatusService;
use Illuminate\Http\RedirectResponse;

/**
 * Handles all Order status transition endpoints.
 * Separated from OrderController to enforce Single Responsibility.
 * Each method represents exactly one transition.
 *
 * @package App\Http\Controllers\Orders
 */
class OrderStatusController extends Controller
{
    public function __construct(private readonly OrderStatusService $statusService) {}

    public function accept(Order $order): RedirectResponse
    {
        $this->authorize('update', $order);
        $this->statusService->accept($order, auth()->user());

        return back()->with('success', __('orders.accepted'));
    }

    public function cancel(CancelOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('cancel', $order);
        $this->statusService->cancel($order, $request->validated('reason'), auth()->user());

        return back()->with('success', __('orders.cancelled'));
    }

    public function markPreparing(Order $order): RedirectResponse
    {
        $this->authorize('update', $order);
        $this->statusService->markPreparing($order);

        return back()->with('success', __('orders.marked_preparing'));
    }

    public function markReady(Order $order): RedirectResponse
    {
        $this->authorize('update', $order);
        $this->statusService->markReady($order);

        return back()->with('success', __('orders.marked_ready'));
    }

    public function confirmDelivery(Order $order): RedirectResponse
    {
        $this->authorize('confirm_delivery', $order);
        $this->statusService->confirmDelivery($order, auth()->user());

        return back()->with('success', __('orders.delivery_confirmed'));
    }

    public function recordReturn(Order $order): RedirectResponse
    {
        $this->authorize('update', $order);
        $this->statusService->recordReturn($order, request()->get('notes', ''));

        return back()->with('success', __('orders.return_recorded'));
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║            SECTION F — FORM REQUESTS                        ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Http/Requests/Orders/StoreOrderRequest.php`
```php
<?php
namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates order creation payload.
 * Money values validated as integers (no floats accepted).
 *
 * @package App\Http\Requests\Orders
 */
class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('orders.create');
    }

    /**
     * @return array<string, string|array<string>>
     */
    public function rules(): array
    {
        return [
            'customer_id'               => ['required', 'integer', 'exists:customers,id'],
            'order_date'                => ['required', 'date', 'before_or_equal:today'],
            'requested_delivery_date'   => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes'                     => ['nullable', 'string', 'max:2000'],

            // Line items
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.product_id'        => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'          => ['required', 'integer', 'min:1', 'max:99999'],
            'items.*.unit_price'        => ['required', 'integer', 'min:0'],
            'items.*.discount_percent'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes'             => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required'         => __('validation.order_customer_required'),
            'items.required'               => __('validation.order_items_required'),
            'items.min'                    => __('validation.order_items_min'),
            'items.*.product_id.required'  => __('validation.order_item_product_required'),
            'items.*.quantity.min'         => __('validation.order_item_quantity_min'),
        ];
    }
}
```

### `app/Http/Requests/Products/StoreProductRequest.php`
```php
<?php
namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates product creation.
 *
 * @package App\Http\Requests\Products
 */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products.create');
    }

    public function rules(): array
    {
        return [
            'code'                => ['required', 'string', 'max:50', 'unique:products,code'],
            'name'                => ['required', 'string', 'max:200'],
            'category_id'         => ['nullable', 'integer', 'exists:product_categories,id'],
            'unit'                => ['required', 'string', 'max:30'],
            'description'         => ['nullable', 'string', 'max:2000'],
            'unit_price'          => ['required', 'integer', 'min:0'],
            'cost_price'          => ['required', 'integer', 'min:0'],
            'barcode'             => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'stock_quantity'      => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'is_active'           => ['boolean'],
            'sort_order'          => ['integer', 'min:0'],
            'image'               => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║            SECTION G — LIVEWIRE COMPONENTS                  ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Livewire/Orders/OrderItemsTable.php`
```php
<?php
namespace App\Livewire\Orders;

use App\Models\Product;
use Livewire\Component;
use Livewire\Attributes\{Computed, On};

/**
 * Livewire component: dynamic order items table.
 * Powers the order create/edit form with live totals and stock checks.
 * Emits 'order-total-updated' event when totals change.
 *
 * @package App\Livewire\Orders
 */
class OrderItemsTable extends Component
{
    /** @var array<int, array{product_id: int|null, quantity: int, unit_price: int, discount_percent: float}> */
    public array $items = [];

    public int $subtotal       = 0;
    public int $discountTotal  = 0;
    public int $grandTotal     = 0;

    public function mount(): void
    {
        $this->items = [
            $this->emptyRow(),
        ];
    }

    public function addRow(): void
    {
        $this->items[] = $this->emptyRow();
    }

    public function removeRow(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->recalculate();
    }

    /** Called when product_id changes on a row — auto-fill unit_price */
    public function productSelected(int $index, int $productId): void
    {
        $product = Product::find($productId);
        if ($product) {
            $this->items[$index]['unit_price']      = $product->unit_price;
            $this->items[$index]['product_name']    = $product->name;
            $this->items[$index]['stock_available'] = $product->stock_quantity;
            $this->items[$index]['unit']            = $product->unit;
        }
        $this->recalculate();
    }

    /** Recalculate totals on any item change */
    public function updatedItems(): void
    {
        $this->recalculate();
    }

    private function recalculate(): void
    {
        $this->subtotal      = 0;
        $this->discountTotal = 0;

        foreach ($this->items as $item) {
            if (empty($item['product_id']) || empty($item['quantity'])) {
                continue;
            }
            $gross           = (int) $item['unit_price'] * (int) $item['quantity'];
            $discount        = (int) round($gross * ((float) ($item['discount_percent'] ?? 0) / 100));
            $this->subtotal      += $gross;
            $this->discountTotal += $discount;
        }

        $this->grandTotal = $this->subtotal - $this->discountTotal;
        $this->dispatch('order-total-updated', total: $this->grandTotal);
    }

    private function emptyRow(): array
    {
        return [
            'product_id'       => null,
            'product_name'     => '',
            'quantity'         => 1,
            'unit_price'       => 0,
            'discount_percent' => 0,
            'stock_available'  => null,
            'unit'             => '',
            'notes'            => '',
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.orders.order-items-table');
    }
}
```

### `app/Livewire/Orders/CustomerBalanceChecker.php`
```php
<?php
namespace App\Livewire\Orders;

use App\Models\Customer;
use Livewire\Component;
use Livewire\Attributes\On;

/**
 * Livewire component: shows live credit availability while building an order.
 * Listens to 'order-total-updated' and 'customer-selected' events.
 *
 * @package App\Livewire\Orders
 */
class CustomerBalanceChecker extends Component
{
    public ?int    $customerId        = null;
    public int     $creditLimit       = 0;
    public int     $outstandingBalance= 0;
    public int     $orderTotal        = 0;
    public bool    $creditExceeded    = false;

    #[On('customer-selected')]
    public function loadCustomer(int $customerId): void
    {
        $this->customerId = $customerId;
        $customer = Customer::find($customerId);

        if ($customer) {
            $this->creditLimit        = $customer->credit_limit;
            $this->outstandingBalance = $customer->outstanding_balance;
        }

        $this->checkCredit();
    }

    #[On('order-total-updated')]
    public function updateTotal(int $total): void
    {
        $this->orderTotal = $total;
        $this->checkCredit();
    }

    public function availableCredit(): int
    {
        return max(0, $this->creditLimit - $this->outstandingBalance);
    }

    private function checkCredit(): void
    {
        $this->creditExceeded = $this->creditLimit > 0
            && $this->orderTotal > $this->availableCredit();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.orders.customer-balance-checker');
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║            SECTION H — BLADE VIEW PATTERNS                  ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/orders/show.blade.php`
```blade
{{-- Maximum 120 lines — complex sections extracted to partials --}}
@extends('layouts.app')

@section('title', __('orders.show_title', ['number' => $order->order_number]))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- Page header with actions --}}
    @include('orders.partials.order-header', ['order' => $order])

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main content area --}}
        <div class="lg:col-span-2 space-y-6">
            @include('orders.partials.order-items-table', ['order' => $order])
            @include('orders.partials.order-totals', ['order' => $order])
            @include('orders.partials.order-timeline', ['order' => $order])
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Customer card --}}
            <x-card :title="__('orders.customer_info')">
                <div class="space-y-2 text-sm">
                    <div class="font-semibold text-lg">{{ $order->customer->name }}</div>
                    <div class="text-gray-600">{{ $order->customer->phone }}</div>
                    <div class="text-gray-600">{{ $order->customer->address }}</div>
                    @can('customers.view_balance')
                        <div class="mt-3 pt-3 border-t">
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ __('customers.outstanding') }}</span>
                                <span class="font-medium text-red-600">
                                    {{ money_format($order->customer->outstanding_balance) }}
                                </span>
                            </div>
                        </div>
                    @endcan
                    <a href="{{ route('customers.show', $order->customer) }}"
                       class="text-brand-600 hover:underline text-sm mt-2 block">
                        {{ __('customers.view_profile') }} ←
                    </a>
                </div>
            </x-card>

            {{-- Invoice card --}}
            @if($order->invoice)
                @include('orders.partials.invoice-summary', ['invoice' => $order->invoice])
            @endif

            {{-- Action buttons --}}
            @include('orders.partials.order-actions', ['order' => $order])
        </div>
    </div>
</div>
@endsection
```

### `resources/views/orders/partials/order-actions.blade.php`
```blade
{{-- Order action buttons — rendered based on current status --}}
<x-card :title="__('orders.actions')">
    <div class="space-y-2">

        @if($order->status === 'pending')
            @can('orders.create')
                <form action="{{ route('orders.status.accept', $order) }}" method="POST">
                    @csrf
                    <x-btn type="submit" variant="primary" class="w-full">
                        ✓ {{ __('orders.accept') }}
                    </x-btn>
                </form>
            @endcan
        @endif

        @if(in_array($order->status, ['accepted','preparing']))
            <form action="{{ route('orders.status.mark-ready', $order) }}" method="POST">
                @csrf
                <x-btn type="submit" variant="secondary" class="w-full">
                    {{ __('orders.mark_ready') }}
                </x-btn>
            </form>
        @endif

        @if($order->status === 'shipped')
            @can('orders.confirm_delivery')
                <form action="{{ route('orders.status.deliver', $order) }}" method="POST">
                    @csrf
                    <x-btn type="submit" variant="primary" class="w-full">
                        ✓ {{ __('orders.confirm_delivery') }}
                    </x-btn>
                </form>
                <x-btn
                    variant="ghost"
                    x-on:click="$dispatch('open-return-modal')"
                    class="w-full">
                    ↩ {{ __('orders.record_return') }}
                </x-btn>
            @endcan
        @endif

        @if($order->isCancellable())
            @can('orders.cancel')
                <x-btn
                    variant="danger"
                    x-on:click="$dispatch('open-cancel-modal')"
                    class="w-full">
                    ✕ {{ __('orders.cancel') }}
                </x-btn>
            @endcan
        @endif

        <hr class="my-2">

        @if($order->invoice)
            <a href="{{ route('invoices.print', $order->invoice) }}" target="_blank">
                <x-btn variant="ghost" class="w-full">
                    🖨 {{ __('invoices.print') }}
                </x-btn>
            </a>
        @endif
    </div>
</x-card>

{{-- Cancel confirmation modal --}}
<x-confirm-modal
    event="open-cancel-modal"
    :title="__('orders.cancel_confirm_title')"
    :action="route('orders.status.cancel', $order)"
    method="POST"
    :confirm-label="__('orders.cancel')"
    variant="danger">
    <x-form-textarea
        name="reason"
        :label="__('orders.cancel_reason')"
        required />
</x-confirm-modal>
```

### `resources/views/components/kpi-card.blade.php`
```blade
{{--
    KPI Dashboard Card Component
    Props:
        $title  : string — Arabic label
        $value  : string — Formatted value
        $trend  : string|null — 'up'|'down'|null
        $change : string|null — Change text (e.g. "+12%")
        $icon   : string|null — Heroicon name
        $color  : string — 'blue'|'green'|'red'|'yellow' (default: 'blue')
--}}
@props([
    'title'  => '',
    'value'  => '0',
    'trend'  => null,
    'change' => null,
    'icon'   => null,
    'color'  => 'blue',
])

@php
$colorMap = [
    'blue'   => 'bg-blue-50 text-blue-700 border-blue-100',
    'green'  => 'bg-green-50 text-green-700 border-green-100',
    'red'    => 'bg-red-50 text-red-700 border-red-100',
    'yellow' => 'bg-yellow-50 text-yellow-700 border-yellow-100',
];
$colorClass = $colorMap[$color] ?? $colorMap['blue'];
@endphp

<div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-500">{{ $title }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 tabular-nums">{{ $value }}</p>
            @if($change)
                <div class="mt-1 flex items-center gap-1 text-xs">
                    @if($trend === 'up')
                        <span class="text-green-600">↑ {{ $change }}</span>
                    @elseif($trend === 'down')
                        <span class="text-red-600">↓ {{ $change }}</span>
                    @else
                        <span class="text-gray-500">{{ $change }}</span>
                    @endif
                </div>
            @endif
        </div>
        @if($icon)
            <div class="rounded-lg p-3 {{ $colorClass }}">
                <x-dynamic-component :component="'heroicon-o-'.$icon" class="w-6 h-6" />
            </div>
        @endif
    </div>
</div>
```

### `resources/views/components/badge.blade.php`
```blade
{{--
    Status Badge Component
    Maps status strings to color classes.
    Reads label from factory config or lang files.
    Props: $status (string), $size (sm|md)
--}}
@props(['status' => 'pending', 'size' => 'md'])

@php
$colorMap = [
    // Order statuses
    'pending'    => 'bg-yellow-100 text-yellow-800',
    'accepted'   => 'bg-blue-100 text-blue-800',
    'preparing'  => 'bg-indigo-100 text-indigo-800',
    'ready'      => 'bg-cyan-100 text-cyan-800',
    'shipped'    => 'bg-violet-100 text-violet-800',
    'delivered'  => 'bg-green-100 text-green-800',
    'cancelled'  => 'bg-red-100 text-red-800',
    'returned'   => 'bg-orange-100 text-orange-800',
    // Invoice statuses
    'draft'      => 'bg-gray-100 text-gray-700',
    'issued'     => 'bg-blue-100 text-blue-800',
    'paid'       => 'bg-green-100 text-green-800',
    'partial'    => 'bg-yellow-100 text-yellow-800',
    'void'       => 'bg-red-100 text-red-800',
    // Shipment statuses
    'planned'    => 'bg-gray-100 text-gray-700',
    'loading'    => 'bg-yellow-100 text-yellow-800',
    'dispatched' => 'bg-blue-100 text-blue-800',
    'completed'  => 'bg-green-100 text-green-800',
    // Truck statuses
    'available'  => 'bg-green-100 text-green-800',
    'on_trip'    => 'bg-blue-100 text-blue-800',
    'maintenance'=> 'bg-orange-100 text-orange-800',
    'inactive'   => 'bg-gray-100 text-gray-600',
];

$sizeClass = $size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-xs';
$color     = $colorMap[$status] ?? 'bg-gray-100 text-gray-600';

// Look up Arabic label
$allStatuses = array_merge(
    config('factory.order_statuses', []),
    config('factory.invoice_statuses', []),
    config('factory.shipment_statuses', []),
);
$label = $allStatuses[$status] ?? $status;
@endphp

<span {{ $attributes->merge([
    'class' => "inline-flex items-center rounded-full font-medium {$color} {$sizeClass}"
]) }}>
    {{ $label }}
</span>
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║            SECTION I — PDF TEMPLATES                        ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/pdf/invoice.blade.php`
```blade
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
/* ALL CSS INLINE — DomPDF does not support external stylesheets */
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'DejaVu Sans', 'Arial', sans-serif;
    direction: rtl; text-align: right;
    font-size: 11px; color: #1a1a1a;
    line-height: 1.5;
}
.page { padding: 20mm 15mm; }
/* Header */
.header { display: table; width: 100%; margin-bottom: 20px; }
.header-right { display: table-cell; width: 60%; vertical-align: top; }
.header-left  { display: table-cell; width: 40%; vertical-align: top; text-align: left; }
.factory-name { font-size: 20px; font-weight: bold; color: #1e3a8a; }
.invoice-title { font-size: 18px; font-weight: bold; text-align: center;
    border: 2px solid #1e3a8a; padding: 8px 20px; color: #1e3a8a;
    display: inline-block; margin: 10px auto; }
/* Info box */
.info-grid { display: table; width: 100%; border: 1px solid #ddd;
    border-radius: 6px; margin-bottom: 15px; }
.info-col { display: table-cell; width: 50%; padding: 10px 12px; }
.info-col:first-child { border-left: 1px solid #ddd; }
.info-label { font-size: 9px; color: #666; margin-bottom: 2px; }
.info-value { font-size: 11px; font-weight: 600; }
/* Items table */
table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
thead th {
    background: #1e3a8a; color: #fff; padding: 8px 6px;
    font-size: 10px; font-weight: bold; border: 1px solid #1e3a8a;
}
tbody td { padding: 7px 6px; border: 1px solid #e5e7eb; font-size: 10px; }
tbody tr:nth-child(even) td { background: #f8fafc; }
.money { font-weight: bold; text-align: left; direction: ltr; }
/* Totals */
.totals { float: left; width: 45%; }
.totals table { border: 1px solid #ddd; }
.totals td { padding: 6px 10px; font-size: 11px; }
.totals .total-row td { background: #1e3a8a; color: #fff; font-weight: bold; font-size: 13px; }
/* Amount in words */
.amount-words { clear: both; margin-top: 10px; padding: 8px 12px;
    background: #f0f4f8; border-radius: 4px; font-size: 10px; color: #374151; }
/* Footer */
.footer { margin-top: 20px; border-top: 1px solid #e5e7eb;
    padding-top: 10px; font-size: 9px; color: #6b7280; text-align: center; }
.signature-row { display: table; width: 100%; margin-top: 25px; }
.signature-box { display: table-cell; width: 33%; text-align: center;
    border-top: 1px solid #666; padding-top: 5px; font-size: 9px; }
</style>
</head>
<body>
<div class="page">

    {{-- ═══ HEADER ═══ --}}
    <div class="header">
        <div class="header-right">
            @if($logo = \App\Facades\Setting::get('factory_logo'))
                <img src="{{ storage_path('app/public/'.$logo) }}" height="60" alt="logo">
            @endif
            <div class="factory-name">{{ \App\Facades\Setting::get('factory_name') }}</div>
            <div style="font-size:9px; color:#666; margin-top:4px;">
                {{ \App\Facades\Setting::get('factory_address') }}<br>
                هاتف: {{ \App\Facades\Setting::get('factory_phone') }}
                &nbsp;|&nbsp;
                رقم ضريبي: {{ \App\Facades\Setting::get('factory_tax_number','—') }}
            </div>
        </div>
        <div class="header-left">
            <div class="invoice-title">فاتورة مبيعات</div>
        </div>
    </div>

    {{-- ═══ INVOICE INFO + CUSTOMER INFO ═══ --}}
    <div class="info-grid">
        <div class="info-col">
            <div><div class="info-label">رقم الفاتورة</div>
                <div class="info-value">{{ $invoice->invoice_number }}</div></div>
            <div style="margin-top:8px"><div class="info-label">تاريخ الإصدار</div>
                <div class="info-value">{{ $invoice->issue_date->format('Y/m/d') }}</div></div>
            <div style="margin-top:8px"><div class="info-label">تاريخ الاستحقاق</div>
                <div class="info-value">{{ $invoice->due_date?->format('Y/m/d') ?? '—' }}</div></div>
        </div>
        <div class="info-col">
            <div><div class="info-label">اسم العميل</div>
                <div class="info-value">{{ $invoice->customer->name }}</div></div>
            <div style="margin-top:8px"><div class="info-label">الهاتف</div>
                <div class="info-value">{{ $invoice->customer->phone }}</div></div>
            <div style="margin-top:8px"><div class="info-label">العنوان</div>
                <div class="info-value">{{ $invoice->customer->address }}</div></div>
        </div>
    </div>

    {{-- ═══ ITEMS TABLE ═══ --}}
    <table>
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th style="width:30%">اسم المنتج</th>
                <th style="width:10%">الكود</th>
                <th style="width:10%">الوحدة</th>
                <th style="width:10%">الكمية</th>
                <th style="width:12%">سعر الوحدة</th>
                <th style="width:8%">الخصم%</th>
                <th style="width:15%">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->order->items as $index => $item)
            <tr>
                <td style="text-align:center">{{ $index + 1 }}</td>
                <td>{{ $item->product->name }}</td>
                <td style="text-align:center">{{ $item->product->code }}</td>
                <td style="text-align:center">{{ $item->product->unit }}</td>
                <td style="text-align:center">{{ $item->quantity }}</td>
                <td class="money">{{ number_format($item->unit_price) }}</td>
                <td style="text-align:center">{{ $item->discount_percent }}%</td>
                <td class="money">{{ number_format($item->line_total) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ═══ TOTALS ═══ --}}
    <div class="totals">
        <table>
            <tr><td>المجموع الفرعي</td>
                <td class="money">{{ number_format($invoice->subtotal) }}</td></tr>
            @if($invoice->discount_amount > 0)
            <tr><td>الخصم</td>
                <td class="money" style="color:#dc2626">- {{ number_format($invoice->discount_amount) }}</td></tr>
            @endif
            @if($invoice->tax_amount > 0)
            <tr><td>الضريبة ({{ $invoice->tax_rate }}%)</td>
                <td class="money">{{ number_format($invoice->tax_amount) }}</td></tr>
            @endif
            <tr class="total-row">
                <td>الإجمالي الكلي</td>
                <td class="money">{{ number_format($invoice->total_amount) }}</td></tr>
            <tr><td>المدفوع</td>
                <td class="money" style="color:#16a34a">{{ number_format($invoice->paid_amount) }}</td></tr>
            <tr><td><strong>المتبقي</strong></td>
                <td class="money"><strong>{{ number_format($invoice->balance_due) }}</strong></td></tr>
        </table>
    </div>
    <div style="clear:both"></div>

    {{-- ═══ AMOUNT IN WORDS ═══ --}}
    <div class="amount-words">
        <strong>المبلغ بالكلمات:</strong>
        {{ \App\Helpers\AmountToWords::toArabic($invoice->total_amount) }}
        {{ config('factory.currency','ل.س') }} فقط لا غير
    </div>

    {{-- ═══ FOOTER ═══ --}}
    <div style="margin-top:25px">
        <div class="signature-row">
            <div class="signature-box">توقيع المستلم</div>
            <div class="signature-box"></div>
            <div class="signature-box">توقيع المحاسب</div>
        </div>
    </div>

    <div class="footer">
        <p>{{ \App\Facades\Setting::get('invoice_footer_text', 'شكراً لتعاملكم معنا') }}</p>
        <p style="margin-top:4px; color:#9ca3af">
            طبع بتاريخ: {{ now()->format('Y/m/d H:i') }}
        </p>
    </div>

</div>
</body>
</html>
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║            SECTION J — TEST IMPLEMENTATIONS                 ║
## ╚══════════════════════════════════════════════════════════════╝

### `tests/Unit/StockServiceTest.php`
```php
<?php
use App\Models\{Product, User};
use App\Services\Products\StockService;
use App\Exceptions\InsufficientStockException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->user    = User::factory()->create()->assignRole('super_admin');
    $this->service = app(StockService::class);
    $this->product = Product::factory()->create(['stock_quantity' => 50]);
    $this->actingAs($this->user);
});

it('moves stock in and records movement', function () {
    $movement = $this->service->moveStock($this->product, 'in', 20);

    expect($movement->quantity_before)->toBe(50)
        ->and($movement->quantity_after)->toBe(70)
        ->and($this->product->fresh()->stock_quantity)->toBe(70);
});

it('moves stock out and records movement', function () {
    $movement = $this->service->moveStock($this->product, 'out', 10);

    expect($movement->quantity_after)->toBe(40)
        ->and($this->product->fresh()->stock_quantity)->toBe(40);
});

it('fires LowStockDetected event when threshold is crossed', function () {
    \Illuminate\Support\Facades\Event::fake();

    $product = Product::factory()->create([
        'stock_quantity'      => 15,
        'low_stock_threshold' => 10,
    ]);

    $this->service->moveStock($product, 'out', 6); // drops to 9 — below threshold

    \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\Stock\LowStockDetected::class);
});

it('does NOT fire event when stock stays above threshold', function () {
    \Illuminate\Support\Facades\Event::fake();
    $this->service->moveStock($this->product, 'out', 5); // 50→45, threshold=10
    \Illuminate\Support\Facades\Event::assertNotDispatched(\App\Events\Stock\LowStockDetected::class);
});

it('adjusts stock to absolute quantity', function () {
    $this->service->adjustStock($this->product, 100, 'جرد يدوي');
    expect($this->product->fresh()->stock_quantity)->toBe(100);
});
```

### `tests/Unit/OrderStateMachineTest.php`
```php
<?php
use App\StateMachines\OrderStateMachine;
use App\Exceptions\InvalidStatusTransitionException;

beforeEach(fn() => $this->sm = new OrderStateMachine());

it('allows all valid forward transitions', function (string $from, string $to) {
    expect($this->sm->transition($from, $to))->toBe($to);
})->with([
    ['pending',   'accepted'],
    ['accepted',  'preparing'],
    ['preparing', 'ready'],
    ['ready',     'shipped'],
    ['shipped',   'delivered'],
    ['shipped',   'returned'],
]);

it('allows cancellation from all pre-shipped statuses', function (string $status) {
    expect($this->sm->canBeCancelled($status))->toBeTrue();
})->with(['pending','accepted','preparing','ready']);

it('rejects cancellation from shipped and beyond', function (string $status) {
    expect($this->sm->canBeCancelled($status))->toBeFalse();
})->with(['shipped','delivered','returned','cancelled']);

it('throws exception for illegal transitions', function () {
    $this->sm->transition('pending', 'delivered');
})->throws(InvalidStatusTransitionException::class);

it('treats delivered and cancelled as final states', function (string $status) {
    expect($this->sm->isFinal($status))->toBeTrue();
})->with(['delivered','cancelled','returned']);
```

### `tests/Feature/OrderLifecycleTest.php`
```php
<?php
use App\Models\{User, Customer, Product, Order};
use App\Services\Orders\OrderService;
use App\DTOs\Orders\{CreateOrderDTO, OrderItemDTO};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin    = User::factory()->create()->assignRole('super_admin');
    $this->customer = Customer::factory()->create(['credit_limit' => 10_000_000]);
    $this->product  = Product::factory()->create([
        'stock_quantity' => 100,
        'unit_price'     => 50_000,
        'cost_price'     => 30_000,
    ]);
    $this->actingAs($this->admin);
});

it('creates a pending order with correct totals', function () {
    $dto = CreateOrderDTO::fromArray([
        'customer_id' => $this->customer->id,
        'order_date'  => today()->toDateString(),
        'items'       => [
            ['product_id' => $this->product->id, 'quantity' => 2,
             'unit_price' => 50_000, 'discount_percent' => 0],
        ],
    ]);

    $order = app(OrderService::class)->create($dto);

    expect($order->status)->toBe('pending')
        ->and($order->total_amount)->toBe(100_000)
        ->and($order->order_number)->toMatch('/^ORD-\d{4}-\d{5}$/');
});

it('deducts stock on order acceptance', function () {
    $order = Order::factory()->pending()->for($this->customer)->create();
    $order->items()->create([
        'product_id' => $this->product->id, 'quantity' => 5,
        'unit_price' => 50_000, 'line_total' => 250_000,
    ]);

    $this->post(route('orders.status.accept', $order));

    expect($this->product->fresh()->stock_quantity)->toBe(95)
        ->and($order->fresh()->status)->toBe('accepted')
        ->and($order->fresh()->invoice)->not->toBeNull();
});

it('returns stock on cancellation after acceptance', function () {
    $order = Order::factory()->accepted()->for($this->customer)->create();
    $order->items()->create([
        'product_id' => $this->product->id, 'quantity' => 5,
        'unit_price' => 50_000, 'line_total' => 250_000,
    ]);
    // Simulate stock having been deducted
    $this->product->update(['stock_quantity' => 95]);

    $this->post(route('orders.status.cancel', $order), ['reason' => 'اختبار']);

    expect($this->product->fresh()->stock_quantity)->toBe(100)
        ->and($order->fresh()->status)->toBe('cancelled');
});

it('blocks order exceeding credit limit', function () {
    $customer = Customer::factory()->create([
        'credit_limit'        => 100_000,
        'outstanding_balance' => 80_000,
    ]);

    $dto = CreateOrderDTO::fromArray([
        'customer_id' => $customer->id,
        'order_date'  => today()->toDateString(),
        'items'       => [
            ['product_id' => $this->product->id, 'quantity' => 5,
             'unit_price' => 50_000, 'discount_percent' => 0],
        ],
    ]);

    expect(fn() => app(OrderService::class)->create($dto))
        ->toThrow(\App\Exceptions\CreditLimitExceededException::class);
});

it('completes full lifecycle pending to delivered', function () {
    // Create
    $dto = CreateOrderDTO::fromArray([
        'customer_id' => $this->customer->id,
        'order_date'  => today()->toDateString(),
        'items'       => [
            ['product_id' => $this->product->id, 'quantity' => 3,
             'unit_price' => 50_000, 'discount_percent' => 0],
        ],
    ]);
    $order = app(OrderService::class)->create($dto);
    expect($order->status)->toBe('pending');

    // Accept
    $this->post(route('orders.status.accept', $order));
    expect($order->fresh()->status)->toBe('accepted');

    // Mark preparing
    $this->post(route('orders.status.preparing', $order));
    expect($order->fresh()->status)->toBe('preparing');

    // Mark ready
    $this->post(route('orders.status.ready', $order));
    expect($order->fresh()->status)->toBe('ready');

    // Deliver
    $this->post(route('orders.status.deliver', $order));
    expect($order->fresh()->status)->toBe('delivered')
        ->and($order->fresh()->invoice->status)->toBe('issued');
});
```

### `tests/Feature/RoleAccessTest.php`
```php
<?php
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

it('blocks shipping_staff from ERP dashboard', function () {
    $user = User::factory()->create()->assignRole('shipping_staff');
    $this->actingAs($user)->get(route('erp.dashboard'))->assertForbidden();
});

it('blocks customer role from orders index', function () {
    $user = User::factory()->create()->assignRole('customer');
    $this->actingAs($user)->get(route('orders.index'))->assertRedirect();
});

it('blocks shipping_staff from admin users list', function () {
    $user = User::factory()->create()->assignRole('shipping_staff');
    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
});

it('allows accountant to access ERP dashboard', function () {
    $user = User::factory()->create()->assignRole('accountant');
    $this->actingAs($user)->get(route('erp.dashboard'))->assertOk();
});

it('allows super_admin full access', function () {
    $user = User::factory()->create()->assignRole('super_admin');
    $this->actingAs($user)
        ->get(route('erp.dashboard'))->assertOk();
    $this->actingAs($user)
        ->get(route('admin.users.index'))->assertOk();
    $this->actingAs($user)
        ->get(route('admin.settings.index'))->assertOk();
});

it('prevents customer from viewing other customers orders', function () {
    $customer1 = User::factory()->create()->assignRole('customer');
    $customer2 = User::factory()->create()->assignRole('customer');

    $order = \App\Models\Order::factory()
        ->for(\App\Models\Customer::factory()->create(['user_id' => $customer2->id]))
        ->create();

    $this->actingAs($customer1)
        ->get(route('portal.orders.show', $order))
        ->assertForbidden();
});
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║            SECTION K — ROUTES COMPLETE DEFINITION           ║
## ╚══════════════════════════════════════════════════════════════╝

### `routes/web.php` — Complete Route Map
```php
<?php
use Illuminate\Support\Facades\Route;

// ── Authentication ───────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',         [\App\Http\Controllers\Auth\LoginController::class, 'showForm'])->name('login');
    Route::post('/login',        [\App\Http\Controllers\Auth\LoginController::class, 'login']);
    Route::get('/password/reset',[\App\Http\Controllers\Auth\PasswordResetController::class, 'showRequestForm']);
    Route::post('/password/email',[\App\Http\Controllers\Auth\PasswordResetController::class, 'sendResetLink']);
    Route::get('/password/reset/{token}',[\App\Http\Controllers\Auth\PasswordResetController::class, 'showResetForm']);
    Route::post('/password/reset',[\App\Http\Controllers\Auth\PasswordResetController::class, 'reset']);
});
Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])
    ->middleware('auth')->name('logout');

// ── Authenticated Staff Routes ───────────────────────────────────────────────
Route::middleware(['auth', 'active', 'locale'])
    ->group(function () {

        Route::get('/', fn() => redirect()->route('erp.dashboard'))->name('home');

        // ── Products ──
        Route::resource('products', \App\Http\Controllers\Products\ProductController::class);
        Route::post('products/{product}/restore', [\App\Http\Controllers\Products\ProductController::class, 'restore'])->name('products.restore');
        Route::resource('products/categories', \App\Http\Controllers\Products\ProductCategoryController::class)->except(['show']);
        Route::get('stock/movements',    [\App\Http\Controllers\Products\StockController::class, 'index'])->name('stock.movements');
        Route::post('stock/adjustment',  [\App\Http\Controllers\Products\StockController::class, 'adjust'])->name('stock.adjust');
        Route::get('stock/low-alert',    [\App\Http\Controllers\Products\StockController::class, 'lowAlert'])->name('stock.low-alert');

        // ── Customers ──
        Route::resource('customers', \App\Http\Controllers\Customers\CustomerController::class);
        Route::post('customers/{customer}/activate',      [\App\Http\Controllers\Customers\CustomerController::class, 'activate'])->name('customers.activate');
        Route::post('customers/{customer}/portal-access', [\App\Http\Controllers\Customers\CustomerController::class, 'togglePortalAccess'])->name('customers.portal-access');
        Route::get('customers/{customer}/statement',      [\App\Http\Controllers\Customers\CustomerController::class, 'statement'])->name('customers.statement');

        // ── Orders ──
        Route::resource('orders', \App\Http\Controllers\Orders\OrderController::class);
        Route::get('orders/daily',  [\App\Http\Controllers\Orders\OrderController::class, 'daily'])->name('orders.daily');
        Route::prefix('orders/{order}/status')->name('orders.status.')->group(function () {
            Route::post('accept',    [\App\Http\Controllers\Orders\OrderStatusController::class, 'accept'])->name('accept');
            Route::post('cancel',    [\App\Http\Controllers\Orders\OrderStatusController::class, 'cancel'])->name('cancel');
            Route::post('preparing', [\App\Http\Controllers\Orders\OrderStatusController::class, 'markPreparing'])->name('preparing');
            Route::post('ready',     [\App\Http\Controllers\Orders\OrderStatusController::class, 'markReady'])->name('ready');
            Route::post('deliver',   [\App\Http\Controllers\Orders\OrderStatusController::class, 'confirmDelivery'])->name('deliver');
            Route::post('return',    [\App\Http\Controllers\Orders\OrderStatusController::class, 'recordReturn'])->name('return');
        });

        // ── Distribution ──
        Route::resource('trucks',  \App\Http\Controllers\Distribution\TruckController::class);
        Route::resource('drivers', \App\Http\Controllers\Distribution\DriverController::class)->except(['show','create','edit']);
        Route::resource('shipments', \App\Http\Controllers\Distribution\ShipmentController::class);
        Route::prefix('shipments/{shipment}')->name('shipments.')->group(function () {
            Route::post('dispatch',         [\App\Http\Controllers\Distribution\ShipmentController::class, 'dispatch'])->name('dispatch');
            Route::post('complete',         [\App\Http\Controllers\Distribution\ShipmentController::class, 'complete'])->name('complete');
            Route::post('cancel',           [\App\Http\Controllers\Distribution\ShipmentController::class, 'cancel'])->name('cancel');
            Route::get('manifest',          [\App\Http\Controllers\Distribution\ShipmentController::class, 'manifest'])->name('manifest');
            Route::post('orders/attach',    [\App\Http\Controllers\Distribution\ShipmentOrderController::class, 'attach'])->name('orders.attach');
            Route::delete('orders/{order}', [\App\Http\Controllers\Distribution\ShipmentOrderController::class, 'detach'])->name('orders.detach');
            Route::post('orders/{order}/deliver', [\App\Http\Controllers\Distribution\ShipmentOrderController::class, 'markDelivered'])->name('orders.deliver');
        });

        // ── Invoices & Payments ──
        Route::resource('invoices', \App\Http\Controllers\Invoices\InvoiceController::class)->except(['create','store','edit','update']);
        Route::get('invoices/{invoice}/print',    [\App\Http\Controllers\Invoices\InvoiceController::class, 'print'])->name('invoices.print');
        Route::get('invoices/{invoice}/download', [\App\Http\Controllers\Invoices\InvoiceController::class, 'download'])->name('invoices.download');
        Route::post('invoices/{invoice}/send',    [\App\Http\Controllers\Invoices\InvoiceController::class, 'send'])->name('invoices.send');
        Route::post('invoices/{invoice}/void',    [\App\Http\Controllers\Invoices\InvoiceController::class, 'void'])->name('invoices.void');
        Route::post('invoices/{invoice}/payments',[\App\Http\Controllers\Invoices\PaymentController::class, 'store'])->name('payments.store');
        Route::delete('payments/{payment}',       [\App\Http\Controllers\Invoices\PaymentController::class, 'destroy'])->name('payments.destroy');

        // ── ERP (accountant + admin only) ──
        Route::middleware('role:accountant|super_admin')->prefix('erp')->name('erp.')->group(function () {
            Route::get('dashboard', [\App\Http\Controllers\Erp\DashboardController::class, 'index'])->name('dashboard');
            Route::resource('expenses', \App\Http\Controllers\Erp\ExpenseController::class);
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('sales',              [\App\Http\Controllers\Erp\ReportController::class, 'sales'])->name('sales');
                Route::get('receivables',        [\App\Http\Controllers\Erp\ReportController::class, 'receivables'])->name('receivables');
                Route::get('stock-movements',    [\App\Http\Controllers\Erp\ReportController::class, 'stockMovements'])->name('stock-movements');
                Route::get('profit-loss',        [\App\Http\Controllers\Erp\ReportController::class, 'profitLoss'])->name('profit-loss');
                Route::get('customer-statement/{customer}', [\App\Http\Controllers\Erp\ReportController::class, 'customerStatement'])->name('customer-statement');
            });
        });

        // ── Admin (super_admin only) ──
        Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
            Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
            Route::post('users/{user}/reset-password', [\App\Http\Controllers\Admin\UserController::class, 'resetPassword'])->name('users.reset-password');
            Route::get('settings',  [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
            Route::post('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');
            Route::get('audit-log', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-log.index');
        });
    });

// ── API for Charts (JSON) ─────────────────────────────────────────────────
Route::middleware(['auth','active'])->prefix('api/charts')->name('api.charts.')->group(function () {
    Route::get('daily-sales',       [\App\Http\Controllers\Erp\ChartController::class, 'dailySales'])->name('daily-sales');
    Route::get('sales-by-category', [\App\Http\Controllers\Erp\ChartController::class, 'salesByCategory'])->name('sales-by-category');
    Route::get('invoice-status',    [\App\Http\Controllers\Erp\ChartController::class, 'invoiceStatus'])->name('invoice-status');
    Route::get('top-customers',     [\App\Http\Controllers\Erp\ChartController::class, 'topCustomers'])->name('top-customers');
});
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║            SECTION L — HELPERS & GLOBAL FUNCTIONS           ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Helpers/MoneyHelper.php`
```php
<?php
namespace App\Helpers;

/**
 * Money formatting helpers.
 * Loaded globally via composer.json autoload → files.
 *
 * @package App\Helpers
 */
class MoneyHelper
{
    /**
     * Format an integer amount (smallest unit) to human-readable string.
     * Example: 150000 → "150,000 ل.س"
     */
    public static function format(int $amount, ?string $currency = null): string
    {
        $currency = $currency ?? config('factory.currency', 'SYP');
        $symbol   = match($currency) {
            'SYP' => 'ل.س',
            'USD' => '$',
            'EUR' => '€',
            default => $currency,
        };
        return number_format($amount) . ' ' . $symbol;
    }

    /**
     * Parse a human-readable money string to integer.
     * Handles "1,500.00 ل.س" → 1500
     */
    public static function parse(string|float|int $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        $clean = preg_replace('/[^\d.]/', '', (string) $value);
        return (int) round((float) $clean);
    }
}
```

### Add to `composer.json` autoload:
```json
{
    "autoload": {
        "psr-4": { "App\\": "app/" },
        "files": [
            "app/Helpers/helpers.php"
        ]
    }
}
```

### `app/Helpers/helpers.php` — Global helper functions
```php
<?php
// Global helper functions — loaded via composer autoload files

if (! function_exists('money_format')) {
    /**
     * Format an integer amount to currency string.
     * @param int $amount  Amount in smallest currency unit
     */
    function money_format(int $amount, ?string $currency = null): string
    {
        return \App\Helpers\MoneyHelper::format($amount, $currency);
    }
}

if (! function_exists('parse_money')) {
    /**
     * Parse a money string/float to integer (smallest unit).
     */
    function parse_money(string|float|int $value): int
    {
        return \App\Helpers\MoneyHelper::parse($value);
    }
}

if (! function_exists('arabic_number')) {
    /**
     * Convert Western digits to Eastern Arabic numerals if setting is enabled.
     * 123 → ١٢٣
     */
    function arabic_number(int|float $number): string
    {
        $useArabic = app(\App\Services\SettingService::class)
            ->get('enable_arabic_numerals', false);

        if (! $useArabic) {
            return (string) $number;
        }

        $eastern = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        return str_replace(range(0, 9), $eastern, (string) $number);
    }
}

if (! function_exists('generate_code')) {
    /**
     * Generate next sequential code for a model.
     * Example: generate_code('ORD', Order::class) → 'ORD-2026-00042'
     */
    function generate_code(string $prefix, string $modelClass, string $column = 'code'): string
    {
        return app(\App\Factories\CodeGeneratorFactory::class)
            ->generate($prefix, $modelClass, $column);
    }
}
```

### `app/Factories/CodeGeneratorFactory.php`
```php
<?php
namespace App\Factories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Generates sequential, human-readable codes for models.
 * Format: {PREFIX}-{YEAR}-{NNNNN}  →  ORD-2026-00042
 * Uses DB-level locking to prevent duplicate codes under concurrency.
 *
 * @package App\Factories
 */
class CodeGeneratorFactory
{
    /**
     * Generate the next code for a given model and column.
     *
     * @param  string $prefix     e.g. 'ORD', 'INV', 'CUS'
     * @param  string $modelClass Fully-qualified model class
     * @param  string $column     Column that stores the code
     */
    public function generate(string $prefix, string $modelClass, string $column = 'code'): string
    {
        return DB::transaction(function () use ($prefix, $modelClass, $column) {
            $year    = now()->year;
            $pattern = "{$prefix}-{$year}-%";

            /** @var Model $modelClass */
            $last = $modelClass::withTrashed()
                ->where($column, 'like', $pattern)
                ->lockForUpdate()
                ->orderByDesc($column)
                ->value($column);

            $sequence = $last
                ? ((int) substr($last, -5)) + 1
                : 1;

            return sprintf('%s-%d-%05d', $prefix, $year, $sequence);
        });
    }

    /**
     * Generate a simple customer code without year.
     * Format: CUS-0042
     */
    public function generateCustomerCode(string $modelClass): string
    {
        $last = $modelClass::withTrashed()
            ->where('code', 'like', 'CUS-%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $sequence = $last ? ((int) substr($last, 4)) + 1 : 1;
        return sprintf('CUS-%04d', $sequence);
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║            SECTION M — ARABIC LANGUAGE FILES                ║
## ╚══════════════════════════════════════════════════════════════╝

### `lang/ar/orders.php`
```php
<?php
return [
    'created_successfully'   => 'تم إنشاء الطلبية :number بنجاح',
    'updated_successfully'   => 'تم تحديث الطلبية بنجاح',
    'deleted_successfully'   => 'تم حذف الطلبية بنجاح',
    'accepted'               => 'تم قبول الطلبية بنجاح',
    'cancelled'              => 'تم إلغاء الطلبية',
    'marked_preparing'       => 'تم تحديث الطلبية إلى قيد التجهيز',
    'marked_ready'           => 'الطلبية جاهزة للشحن',
    'delivery_confirmed'     => 'تم تأكيد استلام الطلبية',
    'return_recorded'        => 'تم تسجيل المرتجع',
    'not_found'              => 'الطلبية غير موجودة',
    'not_editable'           => 'لا يمكن تعديل الطلبية في حالتها الحالية',
    'cannot_edit_in_status'  => 'لا يمكن تعديل الطلبية بحالة (:status)',
    'cannot_delete_shipped'  => 'لا يمكن حذف طلبية مشحونة أو مسلّمة',
    'cancel_confirm_title'   => 'تأكيد إلغاء الطلبية',
    'cancel_reason'          => 'سبب الإلغاء',
    'credit_limit_exceeded'  => 'الحد الائتماني المتاح (:available) أقل من قيمة الطلبية (:required)',
    'insufficient_stock'     => 'الكمية المطلوبة (:requested) من (:product) تتجاوز المخزون المتاح (:available)',
    'show_title'             => 'الطلبية :number',
    'customer_info'          => 'معلومات العميل',
    'actions'                => 'الإجراءات',
    'accept'                 => 'قبول الطلبية',
    'cancel'                 => 'إلغاء',
    'mark_ready'             => 'تحديد كجاهزة للشحن',
    'confirm_delivery'       => 'تأكيد الاستلام',
    'record_return'          => 'تسجيل مرتجع',
];
```

### `lang/ar/invoices.php`
```php
<?php
return [
    'created_successfully'              => 'تم إنشاء الفاتورة بنجاح',
    'voided'                            => 'تم إلغاء الفاتورة',
    'payment_recorded'                  => 'تم تسجيل الدفعة بنجاح',
    'payment_deleted'                   => 'تم حذف الدفعة',
    'payment_exceeds_balance'           => 'مبلغ الدفعة يتجاوز الرصيد المستحق (:balance)',
    'cannot_void_with_payments'         => 'لا يمكن إلغاء فاتورة تحتوي على مدفوعات',
    'voided_due_to_cancellation'        => 'ملغاة تبعاً لإلغاء الطلبية',
    'print'                             => 'طباعة الفاتورة',
    'download'                          => 'تنزيل PDF',
];
```

### `lang/ar/validation.php` — Key custom messages
```php
<?php
return [
    // Laravel default messages translated to Arabic
    'required'    => 'حقل :attribute مطلوب.',
    'unique'      => 'قيمة :attribute مُستخدمة مسبقاً.',
    'exists'      => ':attribute غير موجود في النظام.',
    'integer'     => 'حقل :attribute يجب أن يكون رقماً صحيحاً.',
    'min'         => [
        'numeric' => 'حقل :attribute يجب ألا يقل عن :min.',
        'string'  => 'حقل :attribute يجب أن يحتوي على :min أحرف على الأقل.',
        'array'   => 'يجب أن يحتوي :attribute على :min عناصر على الأقل.',
    ],
    'max'         => [
        'numeric' => 'حقل :attribute يجب ألا يتجاوز :max.',
        'string'  => 'حقل :attribute يجب ألا يتجاوز :max حرفاً.',
        'file'    => 'حجم الملف في :attribute يجب ألا يتجاوز :max كيلوبايت.',
    ],
    'email'       => 'حقل :attribute يجب أن يكون بريداً إلكترونياً صحيحاً.',
    'date'        => 'حقل :attribute يجب أن يكون تاريخاً صحيحاً.',
    'image'       => 'حقل :attribute يجب أن يكون صورة.',
    'mimes'       => 'حقل :attribute يجب أن يكون من الأنواع: :values.',
    'numeric'     => 'حقل :attribute يجب أن يكون رقماً.',
    'boolean'     => 'حقل :attribute يجب أن يكون صحيح أو خطأ.',
    'string'      => 'حقل :attribute يجب أن يكون نصاً.',
    // Custom order messages
    'order_customer_required'       => 'يجب اختيار العميل.',
    'order_items_required'          => 'يجب إضافة منتج واحد على الأقل.',
    'order_items_min'               => 'يجب إضافة منتج واحد على الأقل.',
    'order_item_product_required'   => 'يجب اختيار منتج لكل سطر.',
    'order_item_quantity_min'       => 'الكمية يجب أن تكون على الأقل 1.',
    // Attribute names in Arabic
    'attributes' => [
        'name'             => 'الاسم',
        'email'            => 'البريد الإلكتروني',
        'password'         => 'كلمة المرور',
        'phone'            => 'رقم الهاتف',
        'address'          => 'العنوان',
        'customer_id'      => 'العميل',
        'order_date'       => 'تاريخ الطلبية',
        'unit_price'       => 'سعر الوحدة',
        'quantity'         => 'الكمية',
        'product_id'       => 'المنتج',
        'code'             => 'الكود',
        'credit_limit'     => 'الحد الائتماني',
        'amount'           => 'المبلغ',
        'payment_method'   => 'طريقة الدفع',
        'payment_date'     => 'تاريخ الدفع',
        'image'            => 'الصورة',
        'plate_number'     => 'رقم اللوحة',
        'shipment_date'    => 'تاريخ الشحنة',
        'truck_id'         => 'الشاحنة',
        'driver_id'        => 'السائق',
    ],
];
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION N — ERP DASHBOARD & CHART CONTROLLER        ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Http/Controllers/Erp/ChartController.php`
```php
<?php
namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\{Invoice, Product};
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Serves JSON data for Chart.js dashboard charts.
 * All responses cached in Redis (5-minute TTL).
 * Requires accountant or super_admin role.
 *
 * @package App\Http\Controllers\Erp
 */
class ChartController extends Controller
{
    private const CACHE_TTL = 300; // 5 minutes

    /** Last 30 days daily revenue */
    public function dailySales(): JsonResponse
    {
        $data = Cache::remember('chart:daily_sales', self::CACHE_TTL, function () {
            $rows = Invoice::query()
                ->selectRaw('DATE(issue_date) as date, SUM(total_amount) as total')
                ->whereIn('status', ['issued','sent','paid','partial'])
                ->where('issue_date', '>=', now()->subDays(29))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('total', 'date');

            // Fill in zero for days with no sales
            $dates  = collect(range(0, 29))->map(fn($d) => now()->subDays(29 - $d)->toDateString());
            return [
                'labels' => $dates->toArray(),
                'values' => $dates->map(fn($d) => (int) ($rows[$d] ?? 0))->toArray(),
            ];
        });

        return response()->json($data);
    }

    /** Invoice status breakdown (paid/partial/unpaid) */
    public function invoiceStatus(): JsonResponse
    {
        $data = Cache::remember('chart:invoice_status', self::CACHE_TTL, function () {
            return Invoice::query()
                ->selectRaw('status, COUNT(*) as count')
                ->whereIn('status', ['issued','paid','partial'])
                ->groupBy('status')
                ->pluck('count', 'status');
        });

        return response()->json([
            'labels' => [
                __('factory.invoice_statuses.paid'),
                __('factory.invoice_statuses.partial'),
                __('factory.invoice_statuses.issued'),
            ],
            'values' => [
                (int) ($data['paid']    ?? 0),
                (int) ($data['partial'] ?? 0),
                (int) ($data['issued']  ?? 0),
            ],
            'colors' => ['#16a34a','#ca8a04','#2563eb'],
        ]);
    }

    /** Top 10 customers by revenue this month */
    public function topCustomers(): JsonResponse
    {
        $data = Cache::remember('chart:top_customers', self::CACHE_TTL, function () {
            return Invoice::query()
                ->with('customer:id,name')
                ->selectRaw('customer_id, SUM(total_amount) as total')
                ->whereIn('status', ['issued','sent','paid','partial'])
                ->whereMonth('issue_date', now()->month)
                ->whereYear('issue_date', now()->year)
                ->groupBy('customer_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(fn($row) => [
                    'name'  => $row->customer->name ?? '—',
                    'total' => (int) $row->total,
                ]);
        });

        return response()->json([
            'labels' => $data->pluck('name')->toArray(),
            'values' => $data->pluck('total')->toArray(),
        ]);
    }

    /** Sales breakdown by product category */
    public function salesByCategory(): JsonResponse
    {
        $data = Cache::remember('chart:sales_category', self::CACHE_TTL, function () {
            return \DB::table('order_items')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->join('product_categories', 'product_categories.id', '=', 'products.category_id')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->selectRaw('product_categories.name as category, SUM(order_items.line_total) as total')
                ->whereIn('orders.status', ['delivered'])
                ->whereMonth('orders.order_date', now()->month)
                ->groupBy('product_categories.name')
                ->orderByDesc('total')
                ->limit(8)
                ->get();
        });

        return response()->json([
            'labels' => $data->pluck('category')->toArray(),
            'values' => $data->pluck('total')->map(fn($v) => (int) $v)->toArray(),
        ]);
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION O — EXCEPTION HANDLERS                      ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Exceptions/Handler.php` — Arabic-aware exception responses
```php
<?php
namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\{Request, JsonResponse, RedirectResponse};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Global exception handler — returns Arabic error messages.
 * All exceptions redirect with Arabic flash messages.
 *
 * @package App\Exceptions
 */
class Handler extends ExceptionHandler
{
    protected $dontReport = [
        InvalidStatusTransitionException::class,
        CreditLimitExceededException::class,
        InsufficientStockException::class,
        InvoiceCannotBeVoidedException::class,
    ];

    public function render($request, Throwable $e): mixed
    {
        // Domain exceptions → redirect back with Arabic error
        if ($e instanceof InvalidStatusTransitionException ||
            $e instanceof CreditLimitExceededException     ||
            $e instanceof InsufficientStockException       ||
            $e instanceof InvoiceCannotBeVoidedException) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        // Authorization → Arabic 403 page
        if ($e instanceof AuthorizationException) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('auth.unauthorized')], 403);
            }
            return response()->view('errors.403', [], 403);
        }

        // Model not found → Arabic 404
        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('errors.not_found')], 404);
            }
            return response()->view('errors.404', [], 404);
        }

        return parent::render($request, $e);
    }

    protected function unauthenticated($request, AuthenticationException $exception): mixed
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => __('auth.unauthenticated')], 401);
        }
        return redirect()->guest(route('login'));
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION P — FINAL VERIFICATION COMMANDS             ║
## ╚══════════════════════════════════════════════════════════════╝

### Run these commands in order to verify the complete build:

```bash
# ── 1. Reset and build from scratch ──────────────────────────────────────────
php artisan migrate:fresh --seed
php artisan key:generate
php artisan storage:link

# ── 2. Build assets ───────────────────────────────────────────────────────────
npm run build

# ── 3. Run test suite ─────────────────────────────────────────────────────────
php artisan test                              # All tests must pass
php artisan test --coverage --min=80          # Coverage must be ≥ 80%

# ── 4. Verify routes are clean ────────────────────────────────────────────────
php artisan route:list | grep -c 'GET'        # Count GET routes
php artisan route:cache                       # Must succeed with no errors

# ── 5. Verify caching ─────────────────────────────────────────────────────────
php artisan config:cache
php artisan view:cache
php artisan event:cache

# ── 6. Check for N+1 issues (dev) ─────────────────────────────────────────────
# Visit each major page with Debugbar open
# Max 10 queries per page, zero N+1 patterns

# ── 7. Security check ─────────────────────────────────────────────────────────
grep -r "float" app/Models/          # Must return 0 results for money columns
grep -r "DB::statement\|DB::unprepared" app/  # Must return 0 results
grep -r "->all()" app/Http/Controllers/ # Should return 0 — use ->validated()

# ── 8. File size audit ────────────────────────────────────────────────────────
find app/ -name "*.php" -exec wc -l {} \; | sort -rn | head -20
# All files must show < 400 lines

# ── 9. Arabic text test ───────────────────────────────────────────────────────
php artisan tinker --execute="echo money_format(150000);"
# Expected output: 150,000 ل.س

# ── 10. Final smoke test ──────────────────────────────────────────────────────
php artisan serve &
curl -I http://localhost:8000/login
# Expected: HTTP/1.1 200 OK
# Verify: dir="rtl" in HTML source
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         APPENDIX — SESSION TRACKING TEMPLATE                ║
## ╚══════════════════════════════════════════════════════════════╝

### How to track progress — copy this template into `PROGRESS.md` after each session:

```markdown
## Session 001 — Environment & Bootstrap
**Date:** YYYY-MM-DD
**Duration:** Xh
**Phase:** 00 — Bootstrap

### Completed:
- [x] composer install → no errors
- [x] npm install → no errors
- [x] .env configured
- [x] config/factory.php created
- [x] AGENT.md, PROGRESS.md, TODO.md, DECISIONS.md, SKILLS.md created

### Files Created (7):
- composer.json (updated)
- .env
- config/factory.php
- config/money.php
- AGENT.md
- PROGRESS.md
- TODO.md

### Tests:
- N/A (no tests yet)

### Blockers:
- None

### Next Session Plan:
- PHASE 01: Write all 17 migrations in correct order
- PHASE 02: Value objects and state machines

---

## Session 002 — Database Layer
**Date:** YYYY-MM-DD
**Phase:** 01-02

### Completed:
- [x] All 17 migrations written
- [x] php artisan migrate:fresh → zero errors
- [x] Money value object with tests
- [x] OrderStateMachine with tests
- [x] ShipmentStateMachine with tests

### Tests Passing: 12/12

### Files Created (21):
- database/migrations/001 through 017
- app/ValueObjects/Money.php
- app/StateMachines/OrderStateMachine.php
- app/StateMachines/ShipmentStateMachine.php
- tests/Unit/MoneyValueObjectTest.php
- tests/Unit/OrderStateMachineTest.php
```

---

*PART 2 OF 2 — MASTER AGENT PROMPT v1.0.0*
*Factory Distribution & Shipping Management System*
*نظام إدارة معمل التوزيع والشحن*
*May 2026 · Complete Implementation Guide*

**AGENT INSTRUCTION:**
Read PART 1 for phases and architecture.
Read PART 2 (this file) for concrete implementations.
Together they form the complete execution blueprint.
Start with Phase 00. Follow the protocols. Update PROGRESS.md after every task.
لا تبدأ الكود قبل قراءة كلا الجزأين كاملاً.
