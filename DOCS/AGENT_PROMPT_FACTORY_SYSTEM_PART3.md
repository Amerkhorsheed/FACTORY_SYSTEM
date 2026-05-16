# 🏭 MASTER AGENT PROMPT — PART 3
## Models · Observers · Notifications · PDF · Reports · Frontend · Deployment
### نظام إدارة معمل التوزيع والشحن — الجزء الثالث والأخير
---
> **PART 3 OF 3** | Read PART 1 and PART 2 first.
> This file covers: full model implementations, all observers, notification classes,
> PDF service, reports engine, frontend JS/CSS setup, deployment configs,
> seeders, and the complete `CHANGELOG.md` / `README.md`.

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION A — MODEL TRAITS (FULL IMPLEMENTATIONS)     ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Models/Traits/HasMoneyFormatting.php`
```php
<?php
namespace App\Models\Traits;

/**
 * Provides formatted money accessors for models with monetary columns.
 * Reads the model's $moneyColumns array to generate accessors dynamically.
 * All source values must be integers (smallest currency unit).
 *
 * Usage in model:
 *   use HasMoneyFormatting;
 *   protected array $moneyColumns = ['unit_price','cost_price','total_amount'];
 *
 * @package App\Models\Traits
 */
trait HasMoneyFormatting
{
    /**
     * Dynamically call formatted_* accessors for money columns.
     * Example: $product->formatted_unit_price → "50,000 ل.س"
     */
    public function __get(mixed $key): mixed
    {
        if (str_starts_with($key, 'formatted_')) {
            $column = substr($key, 10); // strip "formatted_"
            $cols   = $this->moneyColumns ?? [];

            if (in_array($column, $cols, true)) {
                return money_format((int) ($this->attributes[$column] ?? 0));
            }
        }

        return parent::__get($key);
    }

    /** Check if a given attribute is a money column */
    public function isMoneyColumn(string $column): bool
    {
        return in_array($column, $this->moneyColumns ?? [], true);
    }
}
```

### `app/Models/Traits/HasSoftDeleteGuard.php`
```php
<?php
namespace App\Models\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Prevents soft-delete when the model has active dependent records.
 * Models using this trait must implement getActiveRelationChecks().
 *
 * Usage:
 *   protected function getActiveRelationChecks(): array
 *   {
 *       return [
 *           'orders' => __('products.has_active_orders'),
 *       ];
 *   }
 *
 * @package App\Models\Traits
 */
trait HasSoftDeleteGuard
{
    /**
     * Boot the trait — intercept deletes and validate.
     *
     * @throws \DomainException when active relations exist
     */
    public static function bootHasSoftDeleteGuard(): void
    {
        static::deleting(function (self $model): void {
            foreach ($model->getActiveRelationChecks() as $relation => $message) {
                if ($model->$relation()->exists()) {
                    throw new \DomainException($message);
                }
            }
        });
    }

    /**
     * Return relation name → error message pairs to check before delete.
     * Override in the model.
     *
     * @return array<string, string>
     */
    protected function getActiveRelationChecks(): array
    {
        return [];
    }
}
```

### `app/Models/Product.php` — Full Implementation
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\{GeneratesSequentialCode, HasMoneyFormatting, HasSoftDeleteGuard};

/**
 * Product model — represents an inventory item.
 *
 * @property int    $id
 * @property string $code
 * @property string $name
 * @property int    $unit_price   Stored as integer (smallest currency unit)
 * @property int    $cost_price   Stored as integer (smallest currency unit)
 * @property int    $stock_quantity
 * @property int    $low_stock_threshold
 * @property bool   $is_active
 * @package App\Models
 */
class Product extends Model
{
    use SoftDeletes, GeneratesSequentialCode, HasMoneyFormatting, HasSoftDeleteGuard;

    protected string $codePrefix = 'PRD';
    protected string $codeColumn = 'code';

    protected array $moneyColumns = ['unit_price', 'cost_price'];

    protected $fillable = [
        'category_id','code','name','description','unit',
        'unit_price','cost_price','barcode','image',
        'stock_quantity','low_stock_threshold',
        'is_active','sort_order','created_by',
    ];

    protected $casts = [
        'unit_price'          => 'integer',
        'cost_price'          => 'integer',
        'stock_quantity'      => 'integer',
        'low_stock_threshold' => 'integer',
        'is_active'           => 'boolean',
        'sort_order'          => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    public function scopeInCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    // ── Computed Attributes ───────────────────────────────────────────────

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->stock_quantity <= 0;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock_quantity <= 0)        return 'out_of_stock';
        if ($this->stock_quantity <= $this->low_stock_threshold) return 'low';
        return 'ok';
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image
            ? asset('storage/' . $this->image)
            : null;
    }

    // ── Soft Delete Guard ─────────────────────────────────────────────────

    protected function getActiveRelationChecks(): array
    {
        return [
            'activeOrderItems' => __('products.has_active_orders'),
        ];
    }

    public function activeOrderItems(): HasMany
    {
        return $this->orderItems()
            ->whereHas('order', fn($q) =>
                $q->whereNotIn('status', ['delivered','cancelled','returned'])
            );
    }
}
```

### `app/Models/Customer.php` — Full Implementation
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\{HasMoneyFormatting, HasSoftDeleteGuard};

/**
 * Customer model — represents a business customer account.
 *
 * @property int    $id
 * @property string $code             Auto-generated CUS-0001
 * @property string $name
 * @property string $category         A|B|C
 * @property int    $credit_limit     BIGINT
 * @property int    $outstanding_balance BIGINT (cached)
 * @package App\Models
 */
class Customer extends Model
{
    use SoftDeletes, HasMoneyFormatting, HasSoftDeleteGuard;

    protected array $moneyColumns = ['credit_limit', 'outstanding_balance'];

    protected $fillable = [
        'user_id','code','name','business_name',
        'phone','phone_alt','email','address','city','region',
        'category','credit_limit','outstanding_balance',
        'notes','is_active','portal_access','created_by',
    ];

    protected $casts = [
        'credit_limit'        => 'integer',
        'outstanding_balance' => 'integer',
        'is_active'           => 'boolean',
        'portal_access'       => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeByRegion(Builder $query, string $region): Builder
    {
        return $query->where('region', $region);
    }

    public function scopeWithOverdueBalance(Builder $query): Builder
    {
        return $query->where('outstanding_balance', '>', 0);
    }

    // ── Business Methods ──────────────────────────────────────────────────

    /**
     * Check if customer can accept an additional order of given value.
     * Returns true if credit_limit is 0 (unlimited) OR if available credit >= amount.
     */
    public function canAcceptOrder(int $orderAmount): bool
    {
        if ($this->credit_limit === 0) {
            return true; // Unlimited credit
        }

        $available = $this->credit_limit - $this->outstanding_balance;
        return $orderAmount <= $available;
    }

    public function getAvailableCreditAttribute(): int
    {
        return max(0, $this->credit_limit - $this->outstanding_balance);
    }

    public function getCreditUsagePercentAttribute(): float
    {
        if ($this->credit_limit === 0) return 0;
        return round(($this->outstanding_balance / $this->credit_limit) * 100, 1);
    }

    // ── Soft Delete Guard ─────────────────────────────────────────────────

    protected function getActiveRelationChecks(): array
    {
        return [
            'activeOrders' => __('customers.has_active_orders'),
        ];
    }

    public function activeOrders(): HasMany
    {
        return $this->orders()
            ->whereNotIn('status', ['delivered','cancelled','returned']);
    }
}
```

### `app/Models/Invoice.php` — Full Implementation
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\{HasMoneyFormatting, GeneratesSequentialCode};

/**
 * Invoice model — financial document linked to an order.
 *
 * @property int    $id
 * @property string $invoice_number   INV-2026-00001
 * @property string $status           draft|issued|sent|paid|partial|void
 * @property int    $total_amount     BIGINT
 * @property int    $balance_due      BIGINT
 * @package App\Models
 */
class Invoice extends Model
{
    use SoftDeletes, HasMoneyFormatting, GeneratesSequentialCode;

    protected string $codePrefix = 'INV';
    protected string $codeColumn = 'invoice_number';

    protected array $moneyColumns = [
        'subtotal','discount_amount','tax_amount',
        'total_amount','paid_amount','balance_due',
    ];

    protected $fillable = [
        'invoice_number','order_id','customer_id','type','status',
        'issue_date','due_date',
        'subtotal','discount_amount','tax_rate','tax_amount',
        'total_amount','paid_amount','balance_due',
        'notes','pdf_path','sent_at','voided_at','void_reason','created_by',
    ];

    protected $casts = [
        'subtotal'        => 'integer',
        'discount_amount' => 'integer',
        'tax_amount'      => 'integer',
        'total_amount'    => 'integer',
        'paid_amount'     => 'integer',
        'balance_due'     => 'integer',
        'tax_rate'        => 'decimal:2',
        'issue_date'      => 'date',
        'due_date'        => 'date',
        'sent_at'         => 'datetime',
        'voided_at'       => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeByStatus(Builder $query, string|array $status): Builder
    {
        return is_array($status)
            ? $query->whereIn('status', $status)
            : $query->where('status', $status);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereNotIn('status', ['paid','void'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', today());
    }

    public function scopeByCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    // ── Business Methods ──────────────────────────────────────────────────

    public function isFullyPaid(): bool
    {
        return $this->status === 'paid' || $this->balance_due <= 0;
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && ! in_array($this->status, ['paid','void'], true);
    }

    public function canBeVoided(): bool
    {
        return $this->paid_amount === 0
            && in_array($this->status, ['draft','issued'], true);
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->isOverdue()) return 0;
        return (int) $this->due_date->diffInDays(today());
    }
}
```

### `app/Models/Shipment.php` — Full Implementation
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\GeneratesSequentialCode;

/**
 * Shipment model — represents a daily truck delivery run.
 *
 * @property int    $id
 * @property string $shipment_number   SHP-2026-00001
 * @property string $status            planned|loading|dispatched|completed|cancelled
 * @package App\Models
 */
class Shipment extends Model
{
    use SoftDeletes, GeneratesSequentialCode;

    protected string $codePrefix = 'SHP';
    protected string $codeColumn = 'shipment_number';

    protected $fillable = [
        'shipment_number','truck_id','driver_id',
        'shipment_date','status',
        'departure_time','return_time','notes','created_by',
    ];

    protected $casts = [
        'shipment_date'  => 'date',
        'departure_time' => 'datetime',
        'return_time'    => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['planned','loading','dispatched']);
    }

    public function scopeForDate(Builder $query, \Carbon\Carbon $date): Builder
    {
        return $query->whereDate('shipment_date', $date);
    }

    // ── Computed ──────────────────────────────────────────────────────────

    public function getTotalOrdersCountAttribute(): int
    {
        return $this->orders()->count();
    }

    public function getDeliveredCountAttribute(): int
    {
        return $this->orders()->where('status', 'delivered')->count();
    }

    public function getPendingDeliveryCountAttribute(): int
    {
        return $this->orders()->where('status', 'shipped')->count();
    }

    public function getDeliveryProgressAttribute(): float
    {
        $total = $this->total_orders_count;
        if ($total === 0) return 0;
        return round(($this->delivered_count / $total) * 100, 1);
    }

    public function allOrdersResolved(): bool
    {
        return ! $this->orders()
            ->whereNotIn('status', ['delivered','cancelled','returned'])
            ->exists();
    }

    public function canBeDispatched(): bool
    {
        return $this->status === 'planned' && $this->orders()->where('status','ready')->exists();
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION B — OBSERVERS (FULL IMPLEMENTATIONS)        ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Observers/OrderObserver.php`
```php
<?php
namespace App\Observers;

use App\Models\Order;
use Spatie\Activitylog\Facades\Activity;

/**
 * Observer for Order model events.
 * Logs all status changes and mutations via Spatie ActivityLog.
 * Registered in EventServiceProvider.
 *
 * @package App\Observers
 */
class OrderObserver
{
    /**
     * Log order creation.
     */
    public function created(Order $order): void
    {
        activity('orders')
            ->performedOn($order)
            ->causedBy(auth()->user())
            ->withProperties([
                'order_number' => $order->order_number,
                'customer'     => $order->customer->name ?? '—',
                'total'        => $order->total_amount,
            ])
            ->log('تم إنشاء الطلبية');
    }

    /**
     * Log order updates — track status changes specifically.
     */
    public function updated(Order $order): void
    {
        $changes = $order->getDirty();

        // Special handling for status changes
        if (isset($changes['status'])) {
            activity('orders')
                ->performedOn($order)
                ->causedBy(auth()->user())
                ->withProperties([
                    'from'   => $order->getOriginal('status'),
                    'to'     => $changes['status'],
                    'number' => $order->order_number,
                ])
                ->log('تم تغيير حالة الطلبية');
        }

        // Log any other significant field changes
        $tracked = ['total_amount','discount_amount','shipment_id'];
        $significant = array_intersect_key($changes, array_flip($tracked));

        if (! empty($significant)) {
            activity('orders')
                ->performedOn($order)
                ->causedBy(auth()->user())
                ->withProperties($significant)
                ->log('تم تحديث الطلبية');
        }
    }

    public function deleted(Order $order): void
    {
        activity('orders')
            ->performedOn($order)
            ->causedBy(auth()->user())
            ->log('تم حذف الطلبية: ' . $order->order_number);
    }
}
```

### `app/Observers/ProductObserver.php`
```php
<?php
namespace App\Observers;

use App\Models\Product;

/**
 * Observer for Product model — logs all changes including price updates.
 * Price changes are critical audit events.
 *
 * @package App\Observers
 */
class ProductObserver
{
    public function created(Product $product): void
    {
        activity('products')
            ->performedOn($product)
            ->causedBy(auth()->user())
            ->withProperties(['code' => $product->code, 'name' => $product->name])
            ->log('تم إضافة منتج جديد');
    }

    public function updated(Product $product): void
    {
        $changes = $product->getDirty();

        // Critical: price changes must always be logged
        if (isset($changes['unit_price']) || isset($changes['cost_price'])) {
            activity('products')
                ->performedOn($product)
                ->causedBy(auth()->user())
                ->withProperties([
                    'unit_price_before' => $product->getOriginal('unit_price'),
                    'unit_price_after'  => $product->unit_price,
                    'cost_price_before' => $product->getOriginal('cost_price'),
                    'cost_price_after'  => $product->cost_price,
                ])
                ->log('تم تغيير سعر المنتج');
        }

        // Stock changes
        if (isset($changes['stock_quantity'])) {
            activity('products')
                ->performedOn($product)
                ->causedBy(auth()->user())
                ->withProperties([
                    'before' => $product->getOriginal('stock_quantity'),
                    'after'  => $product->stock_quantity,
                ])
                ->log('تم تغيير كمية المخزون');
        }
    }

    public function deleted(Product $product): void
    {
        activity('products')
            ->performedOn($product)
            ->causedBy(auth()->user())
            ->log('تم حذف المنتج: ' . $product->code . ' - ' . $product->name);
    }
}
```

### `app/Observers/PaymentObserver.php`
```php
<?php
namespace App\Observers;

use App\Models\Payment;

/**
 * Observer for Payment model — every payment is a critical audit event.
 *
 * @package App\Observers
 */
class PaymentObserver
{
    public function created(Payment $payment): void
    {
        activity('payments')
            ->performedOn($payment)
            ->causedBy(auth()->user())
            ->withProperties([
                'amount'          => $payment->amount,
                'method'          => $payment->payment_method,
                'invoice_number'  => $payment->invoice->invoice_number ?? '—',
                'customer'        => $payment->customer->name ?? '—',
            ])
            ->log('تم تسجيل دفعة جديدة');
    }

    public function deleted(Payment $payment): void
    {
        activity('payments')
            ->performedOn($payment)
            ->causedBy(auth()->user())
            ->withProperties([
                'amount'  => $payment->amount,
                'invoice' => $payment->invoice->invoice_number ?? '—',
            ])
            ->log('تم حذف دفعة');
    }
}
```

### `app/Providers/EventServiceProvider.php`
```php
<?php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Models\{Order, Product, Invoice, Payment};
use App\Observers\{OrderObserver, ProductObserver, InvoiceObserver, PaymentObserver};

/**
 * Event service provider — registers all observers and event→listener mappings.
 *
 * @package App\Providers
 */
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Order domain events
        \App\Events\Orders\OrderCreated::class   => [],
        \App\Events\Orders\OrderAccepted::class  => [
            \App\Listeners\Orders\NotifyCustomerOnOrderStatusChange::class,
        ],
        \App\Events\Orders\OrderCancelled::class => [
            \App\Listeners\Orders\NotifyCustomerOnOrderStatusChange::class,
        ],
        \App\Events\Orders\OrderDelivered::class => [],

        // Invoice domain events
        \App\Events\Invoices\InvoiceIssued::class   => [
            \App\Listeners\Invoices\UpdateCustomerBalanceOnInvoiceIssued::class,
        ],
        \App\Events\Invoices\PaymentReceived::class => [
            \App\Listeners\Invoices\NotifyCustomerOnPaymentReceived::class,
        ],

        // Stock domain events
        \App\Events\Stock\LowStockDetected::class => [
            \App\Listeners\Stock\SendLowStockAlert::class,
        ],
    ];

    public function boot(): void
    {
        Order::observe(OrderObserver::class);
        Product::observe(ProductObserver::class);
        Invoice::observe(InvoiceObserver::class);
        Payment::observe(PaymentObserver::class);
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION C — NOTIFICATIONS (FULL IMPLEMENTATIONS)    ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Notifications/OrderStatusChanged.php`
```php
<?php
namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies customer and staff when an order status changes.
 * Sent via: database (always) + mail (for customer-visible transitions).
 * Queued asynchronously — never blocks the web request.
 *
 * @package App\Notifications
 */
class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /** Status transitions that send email to customer */
    private const CUSTOMER_EMAIL_STATUSES = [
        'accepted', 'shipped', 'delivered', 'cancelled',
    ];

    public function __construct(
        private readonly Order  $order,
        private readonly string $fromStatus,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (in_array($this->order->status, self::CUSTOMER_EMAIL_STATUSES, true)
            && $notifiable->email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = config("factory.order_statuses.{$this->order->status}", $this->order->status);

        return (new MailMessage())
            ->subject("طلبيتك رقم {$this->order->order_number} — {$statusLabel}")
            ->view('emails.order-status', [
                'order'       => $this->order,
                'statusLabel' => $statusLabel,
                'notifiable'  => $notifiable,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $statusLabel = config("factory.order_statuses.{$this->order->status}", $this->order->status);

        return [
            'type'          => 'order_status_changed',
            'order_id'      => $this->order->id,
            'order_number'  => $this->order->order_number,
            'from_status'   => $this->fromStatus,
            'to_status'     => $this->order->status,
            'status_label'  => $statusLabel,
            'message'       => "الطلبية {$this->order->order_number} أصبحت: {$statusLabel}",
            'url'           => route('orders.show', $this->order),
        ];
    }
}
```

### `app/Notifications/LowStockAlert.php`
```php
<?php
namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Alerts accountant/admin when product stock drops below threshold.
 * Batches multiple low-stock products into a single notification digest.
 * Sent via database + mail.
 *
 * @package App\Notifications
 */
class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param Collection<Product> $products  Products below threshold
     */
    public function __construct(
        private readonly Collection $products,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("تنبيه: {$this->products->count()} منتجات بمخزون منخفض")
            ->view('emails.low-stock', [
                'products'   => $this->products,
                'notifiable' => $notifiable,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'low_stock_alert',
            'count'   => $this->products->count(),
            'message' => "{$this->products->count()} منتجات وصلت لمستوى المخزون المنخفض",
            'url'     => route('stock.low-alert'),
            'items'   => $this->products->map(fn($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'stock' => $p->stock_quantity,
            ])->toArray(),
        ];
    }
}
```

### `app/Notifications/PaymentReceived.php`
```php
<?php
namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirms payment receipt to customer via database + mail.
 *
 * @package App\Notifications
 */
class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Payment $payment) {}

    public function via(object $notifiable): array
    {
        return $notifiable->email ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("تم استلام دفعتك — فاتورة {$this->payment->invoice->invoice_number}")
            ->view('emails.payment-confirmed', [
                'payment'    => $this->payment,
                'notifiable' => $notifiable,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'           => 'payment_received',
            'payment_id'     => $this->payment->id,
            'amount'         => $this->payment->amount,
            'invoice_number' => $this->payment->invoice->invoice_number,
            'balance_due'    => $this->payment->invoice->balance_due,
            'message'        => 'تم استلام دفعة بمبلغ ' . money_format($this->payment->amount),
            'url'            => route('invoices.show', $this->payment->invoice_id),
        ];
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION D — PDF SERVICE (FULL IMPLEMENTATION)       ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Services/PdfService.php`
```php
<?php
namespace App\Services;

use App\Models\{Customer, Invoice, Shipment};
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * PDF generation service using DomPDF.
 * All PDFs stored in storage/app/private/pdfs/{type}/.
 * Arabic RTL rendering requires DejaVu font + unicode mode.
 *
 * @package App\Services
 */
class PdfService
{
    private const STORAGE_ROOT = 'private/pdfs';

    /**
     * Generate invoice PDF and store it. Return storage path.
     *
     * @throws \Exception on generation failure
     */
    public function generateInvoice(Invoice $invoice): string
    {
        $invoice->load(['order.items.product', 'customer', 'payments']);

        $pdf  = $this->build('pdf.invoice', compact('invoice'));
        $path = $this->storagePath('invoices', "INV-{$invoice->id}.pdf");

        Storage::put($path, $pdf->output());

        // Update invoice record with PDF path
        $invoice->update(['pdf_path' => $path]);

        return $path;
    }

    /**
     * Generate shipment manifest PDF.
     */
    public function generateManifest(Shipment $shipment): string
    {
        $shipment->load(['truck', 'driver', 'orders.customer', 'orders.items.product']);

        $pdf  = $this->build('pdf.shipment-manifest', compact('shipment'));
        $path = $this->storagePath('manifests', "SHP-{$shipment->id}.pdf");

        Storage::put($path, $pdf->output());
        return $path;
    }

    /**
     * Generate customer account statement PDF.
     */
    public function generateStatement(Customer $customer, Carbon $from, Carbon $to): string
    {
        $invoices = $customer->invoices()
            ->with(['payments'])
            ->whereBetween('issue_date', [$from, $to])
            ->orderBy('issue_date')
            ->get();

        $pdf  = $this->build('pdf.customer-statement', compact('customer','invoices','from','to'));
        $path = $this->storagePath('statements', "STMT-{$customer->id}-{$from->format('Ymd')}.pdf");

        Storage::put($path, $pdf->output());
        return $path;
    }

    /**
     * Stream a PDF view directly to the browser (for print dialog).
     */
    public function stream(string $view, array $data, string $filename = 'document.pdf'): Response
    {
        return $this->build($view, $data)->stream($filename);
    }

    /**
     * Force-download a stored PDF file.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function download(string $storagePath, string $filename): Response
    {
        $content = Storage::get($storagePath);

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Build a DomPDF instance from a Blade view.
     */
    private function build(string $view, array $data): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'dejavu sans')
            ->setOption('isRemoteEnabled', true)
            ->setOption('isUnicode', true)
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('dpi', 150);
    }

    /**
     * Build the storage path ensuring the directory exists.
     */
    private function storagePath(string $type, string $filename): string
    {
        $dir = self::STORAGE_ROOT . '/' . $type;
        Storage::makeDirectory($dir);
        return $dir . '/' . $filename;
    }
}
```

### `app/Helpers/AmountToWords.php`
```php
<?php
namespace App\Helpers;

/**
 * Converts integer amounts to Arabic words.
 * Used in invoice PDF footer.
 * Example: 25000 → "خمسة وعشرون ألفاً"
 *
 * @package App\Helpers
 */
class AmountToWords
{
    private static array $ones = [
        '', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة',
        'ستة', 'سبعة', 'ثمانية', 'تسعة', 'عشرة',
        'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر',
        'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر',
    ];

    private static array $tens = [
        '', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون',
        'ستون', 'سبعون', 'ثمانون', 'تسعون',
    ];

    private static array $hundreds = [
        '', 'مئة', 'مئتان', 'ثلاثمئة', 'أربعمئة', 'خمسمئة',
        'ستمئة', 'سبعمئة', 'ثمانمئة', 'تسعمئة',
    ];

    /**
     * Convert an integer to Arabic words.
     * Handles up to 999,999,999.
     */
    public static function toArabic(int $amount): string
    {
        if ($amount === 0) return 'صفر';

        $parts = [];

        if ($millions = (int) ($amount / 1_000_000)) {
            $parts[] = self::below1000($millions) . ' مليون';
            $amount %= 1_000_000;
        }

        if ($thousands = (int) ($amount / 1_000)) {
            $parts[] = self::below1000($thousands) . ($thousands === 1 ? ' ألف' : ' ألفاً');
            $amount  %= 1_000;
        }

        if ($amount > 0) {
            $parts[] = self::below1000($amount);
        }

        return implode(' و', $parts);
    }

    private static function below1000(int $n): string
    {
        $result = '';

        if ($h = (int) ($n / 100)) {
            $result .= self::$hundreds[$h];
            $n %= 100;
        }

        if ($n >= 20) {
            $tens    = (int) ($n / 10);
            $one     = $n % 10;
            $result .= ($result ? ' و' : '') . self::$tens[$tens];
            if ($one) {
                $result .= ' و' . self::$ones[$one];
            }
        } elseif ($n > 0) {
            $result .= ($result ? ' و' : '') . self::$ones[$n];
        }

        return $result;
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION E — REPORTS SERVICE (FULL IMPLEMENTATION)   ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Services/Erp/ReportService.php`
```php
<?php
namespace App\Services\Erp;

use App\Contracts\Export\ExportStrategyInterface;
use App\Models\{Customer, Invoice, Order, OrderItem, Payment, Expense};
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Generates all ERP report data.
 * All queries optimized for large datasets.
 * Export delegated to strategy pattern.
 *
 * @package App\Services\Erp
 */
class ReportService extends BaseService
{
    /**
     * Sales summary report.
     *
     * @return array{
     *   rows: Collection,
     *   totals: array{revenue: int, collected: int, outstanding: int}
     * }
     */
    public function getSalesSummary(Carbon $from, Carbon $to, array $filters = []): array
    {
        $query = Invoice::with(['customer', 'order'])
            ->whereBetween('issue_date', [$from, $to])
            ->whereIn('status', ['issued','sent','paid','partial'])
            ->orderBy('issue_date');

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $rows = $query->get();

        return [
            'rows'   => $rows,
            'totals' => [
                'revenue'     => (int) $rows->sum('total_amount'),
                'collected'   => (int) $rows->sum('paid_amount'),
                'outstanding' => (int) $rows->sum('balance_due'),
            ],
        ];
    }

    /**
     * Accounts receivable aging report.
     * Buckets: 0-30 days, 31-60, 61-90, 90+ days overdue.
     *
     * @return array{
     *   rows: array,
     *   totals: array{bucket_30: int, bucket_60: int, bucket_90: int, bucket_over90: int, total: int}
     * }
     */
    public function getReceivablesAging(): array
    {
        $invoices = Invoice::with('customer')
            ->whereNotIn('status', ['paid','void'])
            ->where('balance_due', '>', 0)
            ->get();

        $rows   = [];
        $totals = ['bucket_30' => 0, 'bucket_60' => 0, 'bucket_90' => 0, 'bucket_over90' => 0, 'total' => 0];

        foreach ($invoices as $inv) {
            $daysOverdue = $inv->due_date ? (int) today()->diffInDays($inv->due_date, false) * -1 : 0;
            $daysOverdue = max(0, $daysOverdue);
            $bucket      = $this->agingBucket($daysOverdue);

            $rows[]          = compact('inv', 'daysOverdue', 'bucket');
            $totals[$bucket] += $inv->balance_due;
            $totals['total'] += $inv->balance_due;
        }

        return compact('rows', 'totals');
    }

    /**
     * Profit and loss report grouped by month.
     *
     * @return array{
     *   months: array,
     *   totals: array{revenue: int, cogs: int, gross_profit: int, expenses: int, net_profit: int}
     * }
     */
    public function getProfitLossReport(Carbon $from, Carbon $to): array
    {
        $months = [];
        $cursor = $from->copy()->startOfMonth();
        $totals = ['revenue' => 0, 'cogs' => 0, 'gross_profit' => 0, 'expenses' => 0, 'net_profit' => 0];

        while ($cursor->lte($to)) {
            $monthStart = $cursor->copy();
            $monthEnd   = $cursor->copy()->endOfMonth();

            $revenue = (int) Invoice::whereIn('status', ['issued','paid','partial'])
                ->whereBetween('issue_date', [$monthStart, $monthEnd])
                ->sum('total_amount');

            $cogs = (int) DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->where('orders.status', 'delivered')
                ->whereBetween('orders.delivered_at', [$monthStart, $monthEnd])
                ->selectRaw('SUM(order_items.quantity * products.cost_price)')
                ->value(0);

            $expenses = (int) Expense::whereBetween('expense_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $grossProfit = $revenue - $cogs;
            $netProfit   = $grossProfit - $expenses;

            $months[] = compact('monthStart','revenue','cogs','grossProfit','expenses','netProfit');

            $totals['revenue']      += $revenue;
            $totals['cogs']         += $cogs;
            $totals['gross_profit'] += $grossProfit;
            $totals['expenses']     += $expenses;
            $totals['net_profit']   += $netProfit;

            $cursor->addMonth();
        }

        return compact('months', 'totals');
    }

    /**
     * Customer account statement with running balance.
     *
     * @return array{
     *   customer: Customer,
     *   opening_balance: int,
     *   transactions: array,
     *   closing_balance: int
     * }
     */
    public function getCustomerStatement(Customer $customer, Carbon $from, Carbon $to): array
    {
        // Opening balance = all unpaid amounts before $from
        $openingBalance = (int) Invoice::where('customer_id', $customer->id)
            ->whereNotIn('status', ['paid','void'])
            ->where('issue_date', '<', $from)
            ->sum('balance_due');

        $transactions = [];
        $runningBalance = $openingBalance;

        // Invoices in range
        $invoices = Invoice::where('customer_id', $customer->id)
            ->whereBetween('issue_date', [$from, $to])
            ->orderBy('issue_date')->get();

        // Payments in range
        $payments = Payment::where('customer_id', $customer->id)
            ->whereBetween('payment_date', [$from, $to])
            ->orderBy('payment_date')->get();

        // Merge and sort chronologically
        $merged = $invoices->map(fn($inv) => [
            'date'    => $inv->issue_date,
            'type'    => 'invoice',
            'ref'     => $inv->invoice_number,
            'debit'   => $inv->total_amount,
            'credit'  => 0,
            'model'   => $inv,
        ])->concat($payments->map(fn($pay) => [
            'date'    => $pay->payment_date,
            'type'    => 'payment',
            'ref'     => $pay->payment_number ?? '—',
            'debit'   => 0,
            'credit'  => $pay->amount,
            'model'   => $pay,
        ]))->sortBy('date');

        foreach ($merged as $row) {
            $runningBalance += $row['debit'] - $row['credit'];
            $transactions[] = array_merge($row, ['balance' => $runningBalance]);
        }

        return [
            'customer'        => $customer,
            'opening_balance' => $openingBalance,
            'transactions'    => $transactions,
            'closing_balance' => $runningBalance,
        ];
    }

    /**
     * Export report data using a strategy.
     *
     * @param  array<int, array<string,mixed>> $data
     */
    public function export(array $data, array $headers, ExportStrategyInterface $strategy): string
    {
        return $strategy->export($data, $headers);
    }

    private function agingBucket(int $daysOverdue): string
    {
        return match(true) {
            $daysOverdue <= 30 => 'bucket_30',
            $daysOverdue <= 60 => 'bucket_60',
            $daysOverdue <= 90 => 'bucket_90',
            default            => 'bucket_over90',
        };
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION F — CONSOLE COMMANDS                        ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Console/Commands/SendOverdueInvoiceAlerts.php`
```php
<?php
namespace App\Console\Commands;

use App\Models\{Invoice, User};
use App\Notifications\InvoiceOverdue;
use Illuminate\Console\Command;

/**
 * Daily command: sends overdue invoice digest to accountant users.
 * Scheduled at 09:00 daily via routes/console.php.
 *
 * @package App\Console\Commands
 */
class SendOverdueInvoiceAlerts extends Command
{
    protected $signature   = 'factory:overdue-alerts';
    protected $description = 'Send overdue invoice alerts to accountants';

    public function handle(): int
    {
        $overdueInvoices = Invoice::with('customer')
            ->overdue()
            ->where('balance_due', '>', 0)
            ->get();

        if ($overdueInvoices->isEmpty()) {
            $this->info('No overdue invoices found.');
            return self::SUCCESS;
        }

        $accountants = User::role(['accountant','super_admin'])
            ->where('is_active', true)
            ->get();

        foreach ($accountants as $user) {
            $user->notify(new InvoiceOverdue($overdueInvoices));
        }

        $this->info("Notified {$accountants->count()} users about {$overdueInvoices->count()} overdue invoices.");
        return self::SUCCESS;
    }
}
```

### `app/Console/Commands/CheckLowStockLevels.php`
```php
<?php
namespace App\Console\Commands;

use App\Models\{Product, User};
use App\Notifications\LowStockAlert;
use App\Services\Products\StockService;
use Illuminate\Console\Command;

/**
 * Daily command: sends consolidated low-stock digest.
 * Avoids spamming — sends ONE digest per day, not one per product.
 *
 * @package App\Console\Commands
 */
class CheckLowStockLevels extends Command
{
    protected $signature   = 'factory:low-stock-check';
    protected $description = 'Check for low stock products and send daily digest';

    public function __construct(private readonly StockService $stock)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $lowStock = $this->stock->getLowStockProducts();

        if ($lowStock->isEmpty()) {
            $this->info('All products are above threshold.');
            return self::SUCCESS;
        }

        $recipients = User::role(['accountant','super_admin'])
            ->where('is_active', true)
            ->get();

        foreach ($recipients as $user) {
            $user->notify(new LowStockAlert($lowStock));
        }

        $this->info("Sent low-stock alert for {$lowStock->count()} products to {$recipients->count()} users.");
        return self::SUCCESS;
    }
}
```

### `routes/console.php` — Scheduled jobs
```php
<?php
use Illuminate\Support\Facades\Schedule;

// Daily overdue invoice alerts at 9 AM
Schedule::command('factory:overdue-alerts')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground();

// Daily low stock check at 8 AM
Schedule::command('factory:low-stock-check')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground();

// Daily backup at 2 AM
Schedule::command('backup:run')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Scheduled backup failed');
    });
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION G — FRONTEND SETUP (JS + CSS)               ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/js/app.js`
```javascript
/**
 * Factory System — Main JavaScript Entry Point
 * Stack: Alpine.js 3 + Flatpickr (Arabic locale) + Tom Select
 * All UI interaction handled here. Chart.js loaded separately via charts.js.
 */
import Alpine from 'alpinejs'
import { Arabic as FlatpickrArabic } from 'flatpickr/dist/l10n/ar.js'
import flatpickr from 'flatpickr'
import TomSelect from 'tom-select'

// ── Alpine Global Setup ─────────────────────────────────────────────────────

window.Alpine = Alpine

/**
 * Global notification store — updated by Livewire NotificationBell component
 */
Alpine.store('notifications', {
    count: 0,
    items: [],
    setCount(n) { this.count = n },
    markRead(id) {
        this.items = this.items.filter(i => i.id !== id)
        this.count = Math.max(0, this.count - 1)
    },
})

/**
 * Global sidebar store — controls mobile drawer state
 */
Alpine.store('sidebar', {
    open: false,
    toggle() { this.open = ! this.open },
    close() { this.open = false },
})

/**
 * Money formatting utility — mirrors PHP money_format()
 * @param {number} amount  Integer amount in smallest currency unit
 * @param {string} currency  Currency code (default: SYP)
 */
window.formatMoney = function(amount, currency = 'SYP') {
    const symbols = { SYP: 'ل.س', USD: '$', EUR: '€' }
    const symbol  = symbols[currency] || currency
    return new Intl.NumberFormat('ar-SA').format(amount) + ' ' + symbol
}

// ── Flatpickr Arabic Date Picker ────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-datepicker]').forEach(el => {
        flatpickr(el, {
            locale:     FlatpickrArabic,
            dateFormat: 'Y-m-d',
            allowInput: true,
        })
    })

    document.querySelectorAll('[data-datepicker-range]').forEach(el => {
        flatpickr(el, {
            locale:   FlatpickrArabic,
            mode:     'range',
            dateFormat: 'Y-m-d',
        })
    })
})

// ── Tom Select Searchable Dropdowns ─────────────────────────────────────────

window.initTomSelect = function(selector, options = {}) {
    const defaults = {
        plugins:     ['remove_button'],
        create:      false,
        maxItems:    1,
        placeholder: '— اختر —',
        searchField: ['text'],
        ...options,
    }
    document.querySelectorAll(selector).forEach(el => {
        if (! el._tomSelect) new TomSelect(el, defaults)
    })
}

document.addEventListener('DOMContentLoaded', () => {
    window.initTomSelect('[data-tom-select]')
})

// ── Flash Message Auto-dismiss ───────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-flash-dismiss]').forEach(el => {
        setTimeout(() => el.remove(), 5000)
    })
})

Alpine.start()
```

### `resources/js/charts.js`
```javascript
/**
 * Factory System — Dashboard Charts
 * Uses Chart.js with Arabic RTL configuration.
 * All data fetched from /api/charts/* endpoints.
 */
import Chart from 'chart.js/auto'

// ── Global Defaults ─────────────────────────────────────────────────────────

Chart.defaults.font.family   = 'Cairo, sans-serif'
Chart.defaults.color         = '#374151'
Chart.defaults.plugins.legend.position = 'bottom'

const BRAND_COLORS = [
    '#2563eb','#16a34a','#ca8a04','#dc2626',
    '#7c3aed','#0891b2','#ea580c','#be185d',
]

// ── Chart Factories ──────────────────────────────────────────────────────────

/**
 * Initialize daily sales line chart.
 * @param {string} canvasId  Canvas element ID
 */
async function initDailySalesChart(canvasId) {
    const ctx  = document.getElementById(canvasId)
    if (! ctx) return

    const res  = await fetch('/api/charts/daily-sales')
    const data = await res.json()

    new Chart(ctx, {
        type: 'line',
        data: {
            labels:   data.labels,
            datasets: [{
                label:           'المبيعات اليومية',
                data:            data.values,
                borderColor:     '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.1)',
                fill:            true,
                tension:         0.4,
                pointRadius:     4,
            }],
        },
        options: {
            responsive:          true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    ticks: {
                        callback: v => window.formatMoney(v),
                    },
                },
                x: {
                    ticks: { maxRotation: 45 },
                },
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: ctx => window.formatMoney(ctx.parsed.y),
                    },
                },
            },
        },
    })
}

/**
 * Initialize invoice status donut chart.
 */
async function initInvoiceStatusChart(canvasId) {
    const ctx  = document.getElementById(canvasId)
    if (! ctx) return

    const res  = await fetch('/api/charts/invoice-status')
    const data = await res.json()

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels:   data.labels,
            datasets: [{
                data:            data.values,
                backgroundColor: data.colors,
                borderWidth:     2,
                borderColor:     '#fff',
            }],
        },
        options: {
            responsive:  true,
            cutout:      '65%',
            plugins: {
                legend: { position: 'bottom' },
            },
        },
    })
}

/**
 * Initialize top customers bar chart.
 */
async function initTopCustomersChart(canvasId) {
    const ctx  = document.getElementById(canvasId)
    if (! ctx) return

    const res  = await fetch('/api/charts/top-customers')
    const data = await res.json()

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels:   data.labels,
            datasets: [{
                label:           'إجمالي المبيعات',
                data:            data.values,
                backgroundColor: BRAND_COLORS,
                borderRadius:    4,
            }],
        },
        options: {
            responsive:          true,
            maintainAspectRatio: false,
            indexAxis:           'y',   // horizontal bar — better for long names
            scales: {
                x: {
                    ticks: {
                        callback: v => window.formatMoney(v),
                    },
                },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => window.formatMoney(ctx.parsed.x),
                    },
                },
            },
        },
    })
}

// ── Auto-initialize on DOMContentLoaded ─────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    initDailySalesChart('chart-daily-sales')
    initInvoiceStatusChart('chart-invoice-status')
    initTopCustomersChart('chart-top-customers')
})
```

### `tailwind.config.js`
```javascript
/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Livewire/**/*.php',
        './app/View/Components/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                cairo:  ['Cairo', 'sans-serif'],
                arabic: ['Noto Naskh Arabic', 'serif'],
            },
            colors: {
                brand: {
                    50:  '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                },
            },
            spacing: {
                sidebar: '16rem', // 256px — sidebar width
            },
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
        require('tailwindcss-rtl'),
    ],
}
```

### `resources/css/app.css`
```css
/* Factory System — Global Styles */
@import '@fontsource/cairo/400.css';
@import '@fontsource/cairo/600.css';
@import '@fontsource/cairo/700.css';
@import 'flatpickr/dist/flatpickr.min.css';
@import 'tom-select/dist/css/tom-select.default.css';

@tailwind base;
@tailwind components;
@tailwind utilities;

/* ── Base RTL Overrides ────────────────────────────────────────────────────── */
@layer base {
    * { direction: rtl; }
    html { font-family: Cairo, sans-serif; }
    body { @apply bg-gray-50 text-gray-900 antialiased; }
    input, select, textarea { text-align: right; }
}

/* ── Custom Component Classes ──────────────────────────────────────────────── */
@layer components {

    /* Buttons */
    .btn { @apply inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg font-medium text-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed; }
    .btn-primary   { @apply btn bg-brand-600 text-white hover:bg-brand-700 focus:ring-brand-500; }
    .btn-secondary { @apply btn bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-brand-500; }
    .btn-danger    { @apply btn bg-red-600 text-white hover:bg-red-700 focus:ring-red-500; }
    .btn-ghost     { @apply btn text-gray-600 hover:bg-gray-100 focus:ring-gray-300; }
    .btn-sm        { @apply px-3 py-1.5 text-xs; }
    .btn-lg        { @apply px-6 py-3 text-base; }

    /* Cards */
    .card { @apply bg-white rounded-xl border border-gray-200 shadow-sm; }
    .card-header { @apply px-6 py-4 border-b border-gray-100 font-semibold text-gray-800; }
    .card-body { @apply px-6 py-5; }

    /* Form elements */
    .form-label { @apply block text-sm font-medium text-gray-700 mb-1; }
    .form-input { @apply block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm; }
    .form-error { @apply mt-1 text-xs text-red-600; }
    .form-helper { @apply mt-1 text-xs text-gray-500; }

    /* Tables */
    .table-wrapper { @apply overflow-x-auto rounded-xl border border-gray-200 shadow-sm; }
    .table { @apply min-w-full divide-y divide-gray-200; }
    .table thead { @apply bg-gray-50; }
    .table th { @apply px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider; }
    .table td { @apply px-4 py-3 text-sm text-gray-800; }
    .table tbody tr { @apply hover:bg-gray-50 transition-colors; }
    .table tbody tr:not(:last-child) { @apply border-b border-gray-100; }

    /* Sidebar navigation */
    .nav-item { @apply flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-brand-50 hover:text-brand-700 transition-colors text-sm font-medium; }
    .nav-item.active { @apply bg-brand-50 text-brand-700 font-semibold; }
    .nav-group-label { @apply px-3 py-1.5 text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2; }

    /* Status indicators */
    .stock-ok   { @apply text-green-700 bg-green-50; }
    .stock-low  { @apply text-yellow-700 bg-yellow-50; }
    .stock-zero { @apply text-red-700 bg-red-50; }
}

/* ── Print Styles ──────────────────────────────────────────────────────────── */
@media print {
    .no-print { display: none !important; }
    body { background: white; }
    .card { box-shadow: none; border: none; }
}

/* ── Flatpickr RTL Fix ─────────────────────────────────────────────────────── */
.flatpickr-calendar { direction: rtl; font-family: Cairo, sans-serif; }

/* ── Tom Select RTL Fix ────────────────────────────────────────────────────── */
.ts-control { text-align: right; direction: rtl; }
.ts-dropdown { text-align: right; direction: rtl; }
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION H — DEPLOYMENT FILES                        ║
## ╚══════════════════════════════════════════════════════════════╝

### `docker-compose.yml`
```yaml
name: factory-system

services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: factory-app
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - .:/var/www/html
      - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
    environment:
      - APP_ENV=local
    networks:
      - factory-net
    depends_on:
      - mysql
      - redis

  nginx:
    image: nginx:1.25-alpine
    container_name: factory-nginx
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - .:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    networks:
      - factory-net
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    container_name: factory-mysql
    restart: unless-stopped
    ports:
      - "3306:3306"
    environment:
      MYSQL_DATABASE:      factory_db
      MYSQL_USER:          factory_user
      MYSQL_PASSWORD:      secret
      MYSQL_ROOT_PASSWORD: root_secret
    volumes:
      - mysql-data:/var/lib/mysql
      - ./docker/mysql/my.cnf:/etc/mysql/conf.d/my.cnf
    command: --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci
    networks:
      - factory-net

  redis:
    image: redis:7-alpine
    container_name: factory-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    volumes:
      - redis-data:/data
    networks:
      - factory-net

  mailpit:
    image: axllent/mailpit:latest
    container_name: factory-mail
    restart: unless-stopped
    ports:
      - "8025:8025"   # Web UI
      - "1025:1025"   # SMTP
    networks:
      - factory-net

volumes:
  mysql-data:
  redis-data:

networks:
  factory-net:
    driver: bridge
```

### `docker/php/Dockerfile`
```dockerfile
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git curl libpng-dev libjpeg-turbo-dev \
    libzip-dev zip unzip icu-dev \
    freetype-dev libwebp-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo_mysql \
        gd \
        zip \
        intl \
        bcmath \
        opcache \
        pcntl

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
```

### `docker/nginx/default.conf`
```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php index.html;

    charset utf-8;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/javascript application/json;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass   app:9000;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include        fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf)$ {
        expires    1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### `supervisor/factory.conf`
```ini
[program:factory-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/factory-system/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --queue=default,notifications,exports
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/factory-system/storage/logs/worker.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=5

[program:factory-horizon]
command=php /var/www/factory-system/artisan horizon
process_name=%(program_name)s
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/factory-system/storage/logs/horizon.log
stdout_logfile_maxbytes=50MB

[program:factory-scheduler]
command=php /var/www/factory-system/artisan schedule:work
process_name=%(program_name)s
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/factory-system/storage/logs/scheduler.log
```

### `deploy.sh`
```bash
#!/usr/bin/env bash
# deploy.sh — Production deployment script for Factory System
# Usage: ./deploy.sh [branch=main]
# Run as: sudo -u www-data ./deploy.sh

set -euo pipefail

BRANCH="${1:-main}"
APP_DIR="/var/www/factory-system"
TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")

echo "════════════════════════════════════════"
echo " Factory System — Deployment"
echo " Branch: ${BRANCH}"
echo " Time:   ${TIMESTAMP}"
echo "════════════════════════════════════════"

cd "$APP_DIR"

# ── 1. Enable maintenance mode ──────────────────────────────────────────────
echo "→ [1/10] Enabling maintenance mode..."
php artisan down --retry=60 --render="errors.maintenance"

# ── 2. Pull latest code ──────────────────────────────────────────────────────
echo "→ [2/10] Pulling latest code from ${BRANCH}..."
git pull origin "$BRANCH"

# ── 3. Install PHP dependencies ──────────────────────────────────────────────
echo "→ [3/10] Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# ── 4. Install and build frontend assets ─────────────────────────────────────
echo "→ [4/10] Building frontend assets..."
npm ci --prefer-offline
npm run build

# ── 5. Clear all caches ──────────────────────────────────────────────────────
echo "→ [5/10] Clearing application caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# ── 6. Run migrations ────────────────────────────────────────────────────────
echo "→ [6/10] Running database migrations..."
php artisan migrate --force --no-interaction

# ── 7. Cache configuration for production ────────────────────────────────────
echo "→ [7/10] Caching configuration for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# ── 8. Restart queue workers ─────────────────────────────────────────────────
echo "→ [8/10] Restarting queue workers..."
php artisan horizon:terminate || true
php artisan queue:restart
supervisorctl restart factory-horizon factory-worker

# ── 9. Set file permissions ───────────────────────────────────────────────────
echo "→ [9/10] Setting file permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ── 10. Disable maintenance mode ─────────────────────────────────────────────
echo "→ [10/10] Disabling maintenance mode..."
php artisan up

echo ""
echo "════════════════════════════════════════"
echo " ✓ Deployment complete!"
echo " Time: $(date +"%Y-%m-%d %H:%M:%S")"
echo "════════════════════════════════════════"
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION I — DATABASE SEEDERS                        ║
## ╚══════════════════════════════════════════════════════════════╝

### `database/seeders/RolesAndPermissionsSeeder.php`
```php
<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Permission, Role};

/**
 * Creates all roles and permissions.
 * MUST run before AdminUserSeeder.
 * Run order: [1] in DatabaseSeeder.
 *
 * @package Database\Seeders
 */
class RolesAndPermissionsSeeder extends Seeder
{
    private const PERMISSIONS = [
        // Products
        'products.view','products.create','products.edit','products.delete',
        'products.adjust_stock','products.view_cost_price',
        // Customers
        'customers.view','customers.create','customers.edit','customers.delete',
        'customers.manage_credit','customers.view_balance',
        // Orders
        'orders.view','orders.create','orders.edit','orders.cancel',
        'orders.view_all','orders.assign_shipment','orders.confirm_delivery',
        // Shipments
        'shipments.view','shipments.create','shipments.edit','shipments.dispatch',
        'shipments.update_status','shipments.view_manifest',
        // Invoices
        'invoices.view','invoices.create','invoices.void','invoices.send',
        'invoices.view_all',
        // Payments
        'payments.view','payments.create','payments.delete',
        // ERP
        'erp.expenses.view','erp.expenses.create','erp.expenses.edit',
        'erp.reports.view','erp.reports.export','erp.dashboard.view',
        // System
        'system.users.view','system.users.create','system.users.edit','system.users.delete',
        'system.settings.view','system.settings.edit',
        'system.audit_log.view','system.roles.manage',
    ];

    private const ROLE_PERMISSIONS = [
        'accountant' => [
            'products.*','customers.*','orders.*','shipments.*',
            'invoices.*','payments.*','erp.*',
            'system.users.view','system.settings.view',
        ],
        'shipping_staff' => [
            'orders.view','orders.confirm_delivery',
            'shipments.view','shipments.update_status','shipments.view_manifest',
            'invoices.view',
            'products.view',
        ],
        'customer' => [
            'orders.create','orders.view',
            'invoices.view',
        ],
    ];

    public function run(): void
    {
        // Reset cached roles/permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create super_admin — gets everything dynamically (no explicit assignment needed)
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        // Create and assign other roles
        foreach (self::ROLE_PERMISSIONS as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($this->expandPermissions($perms));
        }

        $this->command->info('✓ Roles and permissions seeded.');
    }

    /**
     * Expand wildcard permissions like 'products.*' to all matching permissions.
     *
     * @param  array<string> $patterns
     * @return array<string>
     */
    private function expandPermissions(array $patterns): array
    {
        $resolved = [];

        foreach ($patterns as $pattern) {
            if (str_ends_with($pattern, '.*')) {
                $prefix = str_replace('.*', '.', $pattern);
                $resolved = array_merge(
                    $resolved,
                    array_filter(self::PERMISSIONS, fn($p) => str_starts_with($p, $prefix))
                );
            } else {
                $resolved[] = $pattern;
            }
        }

        return array_unique($resolved);
    }
}
```

### `database/seeders/SystemSettingsSeeder.php`
```php
<?php
namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds default system settings.
 * Safe to run multiple times (uses updateOrCreate).
 *
 * @package Database\Seeders
 */
class SystemSettingsSeeder extends Seeder
{
    private const SETTINGS = [
        // Factory info
        ['key' => 'factory_name',           'value' => 'المعمل النموذجي',       'type' => 'string',  'group' => 'factory',   'label' => 'اسم المعمل'],
        ['key' => 'factory_address',        'value' => 'دمشق، سوريا',           'type' => 'string',  'group' => 'factory',   'label' => 'عنوان المعمل'],
        ['key' => 'factory_phone',          'value' => '011-000-0000',           'type' => 'string',  'group' => 'factory',   'label' => 'هاتف المعمل'],
        ['key' => 'factory_tax_number',     'value' => '',                       'type' => 'string',  'group' => 'factory',   'label' => 'الرقم الضريبي'],
        ['key' => 'factory_logo',           'value' => null,                     'type' => 'string',  'group' => 'factory',   'label' => 'شعار المعمل'],
        // Invoice settings
        ['key' => 'invoice_prefix',         'value' => 'INV',                   'type' => 'string',  'group' => 'invoices',  'label' => 'بادئة رقم الفاتورة'],
        ['key' => 'invoice_due_days',       'value' => '30',                    'type' => 'integer', 'group' => 'invoices',  'label' => 'أيام الاستحقاق'],
        ['key' => 'invoice_tax_rate',       'value' => '0',                     'type' => 'integer', 'group' => 'invoices',  'label' => 'نسبة الضريبة (%)'],
        ['key' => 'invoice_footer_text',    'value' => 'شكراً لتعاملكم معنا',  'type' => 'string',  'group' => 'invoices',  'label' => 'نص تذييل الفاتورة'],
        ['key' => 'invoice_bank_details',   'value' => '',                       'type' => 'string',  'group' => 'invoices',  'label' => 'بيانات البنك'],
        ['key' => 'invoice_terms',          'value' => '',                       'type' => 'string',  'group' => 'invoices',  'label' => 'الشروط والأحكام'],
        // Stock settings
        ['key' => 'default_low_threshold',  'value' => '10',                    'type' => 'integer', 'group' => 'stock',     'label' => 'حد المخزون المنخفض الافتراضي'],
        ['key' => 'enable_stock_warnings',  'value' => '1',                     'type' => 'boolean', 'group' => 'stock',     'label' => 'تفعيل تحذيرات المخزون'],
        // Customer settings
        ['key' => 'default_credit_limit',   'value' => '0',                     'type' => 'integer', 'group' => 'customers', 'label' => 'الحد الائتماني الافتراضي'],
        ['key' => 'default_category',       'value' => 'B',                     'type' => 'string',  'group' => 'customers', 'label' => 'الفئة الافتراضية للعميل'],
        // UI settings
        ['key' => 'enable_arabic_numerals', 'value' => '0',                     'type' => 'boolean', 'group' => 'ui',        'label' => 'استخدام الأرقام العربية'],
    ];

    public function run(): void
    {
        foreach (self::SETTINGS as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('✓ System settings seeded (' . count(self::SETTINGS) . ' settings).');
    }
}
```

### `database/seeders/DatabaseSeeder.php`
```php
<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Master database seeder.
 * Order matters — DO NOT reorder.
 *
 * @package Database\Seeders
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,  // [1] Must be first
            AdminUserSeeder::class,             // [2] Needs roles
            SystemSettingsSeeder::class,        // [3] Independent
            ProductCategorySeeder::class,       // [4] Independent
        ]);

        // Development only — never in production
        if (app()->isLocal()) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
```

### `database/seeders/DemoDataSeeder.php`
```php
<?php
namespace Database\Seeders;

use App\Models\{Customer, Driver, Order, OrderItem, Product, Truck};
use Illuminate\Database\Seeder;

/**
 * Demo data seeder for development environment.
 * Creates 50 customers, 100 products, 200 orders.
 * NEVER run in production — guarded by isLocal() check in DatabaseSeeder.
 *
 * @package Database\Seeders
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding demo data...');

        // Trucks and drivers
        $trucks  = Truck::factory(5)->available()->create();
        $drivers = Driver::factory(5)->active()->create();

        // Customers (mix of categories)
        $customers = Customer::factory(20)->categoryA()->create()
            ->merge(Customer::factory(20)->categoryB()->create())
            ->merge(Customer::factory(10)->categoryC()->create());

        // Products across categories
        $products = Product::factory(50)->active()->create()
            ->merge(Product::factory(30)->lowStock()->create())
            ->merge(Product::factory(20)->outOfStock()->create());

        // Orders
        $orders = Order::factory(50)
            ->delivered()
            ->recycle($customers)
            ->create()
            ->each(function (Order $order) use ($products) {
                $items = $products->random(rand(1, 5));
                foreach ($items as $product) {
                    OrderItem::factory()->create([
                        'order_id'   => $order->id,
                        'product_id' => $product->id,
                        'unit_price' => $product->unit_price,
                    ]);
                }
            });

        $this->command->info('✓ Demo data seeded:');
        $this->command->info("  - {$trucks->count()} trucks, {$drivers->count()} drivers");
        $this->command->info("  - {$customers->count()} customers");
        $this->command->info("  - {$products->count()} products");
        $this->command->info("  - {$orders->count()} orders");
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION J — ERP DASHBOARD VIEW                      ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/erp/dashboard.blade.php`
```blade
@extends('layouts.app')
@section('title', __('erp.dashboard'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    {{-- ═══ PAGE HEADER ═══ --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">لوحة التحكم المالية</h1>
            <p class="text-sm text-gray-500 mt-1">{{ now()->format('l، j F Y') }}</p>
        </div>
        <div class="text-sm text-gray-400">آخر تحديث: {{ now()->format('H:i') }}</div>
    </div>

    {{-- ═══ KPI CARDS ═══ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-kpi-card
            :title="__('erp.today_revenue')"
            :value="money_format($todayRevenue)"
            icon="banknotes"
            color="blue" />
        <x-kpi-card
            :title="__('erp.month_revenue')"
            :value="money_format($monthRevenue)"
            icon="chart-bar"
            color="green" />
        <x-kpi-card
            :title="__('erp.outstanding_balance')"
            :value="money_format($outstandingBalance)"
            icon="exclamation-circle"
            color="red" />
        <x-kpi-card
            :title="__('erp.month_expenses')"
            :value="money_format($monthExpenses)"
            icon="credit-card"
            color="yellow" />
    </div>

    {{-- ═══ OPERATIONAL KPIs ═══ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach([
            ['label' => 'طلبيات معلقة',   'count' => $orderStats['pending'],   'color' => 'yellow'],
            ['label' => 'طلبيات مقبولة',   'count' => $orderStats['accepted'],  'color' => 'blue'],
            ['label' => 'جاهزة للشحن',     'count' => $orderStats['ready'],     'color' => 'cyan'],
            ['label' => 'شاحنات متاحة',    'count' => $availableTrucks,         'color' => 'green'],
        ] as $stat)
        <div class="card p-4 text-center">
            <div class="text-3xl font-bold text-gray-800">{{ $stat['count'] }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ $stat['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- ═══ CHARTS ═══ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 card p-6">
            <h3 class="font-semibold text-gray-800 mb-4">المبيعات اليومية — آخر 30 يوم</h3>
            <div style="height: 280px;">
                <canvas id="chart-daily-sales"></canvas>
            </div>
        </div>
        <div class="card p-6">
            <h3 class="font-semibold text-gray-800 mb-4">حالة الفواتير</h3>
            <div style="height: 280px; display:flex; align-items:center; justify-content:center;">
                <canvas id="chart-invoice-status"></canvas>
            </div>
        </div>
    </div>

    <div class="card p-6">
        <h3 class="font-semibold text-gray-800 mb-4">أعلى 10 عملاء مبيعاً — هذا الشهر</h3>
        <div style="height: 320px;">
            <canvas id="chart-top-customers"></canvas>
        </div>
    </div>

    {{-- ═══ LOW STOCK ALERT ═══ --}}
    @if($lowStockCount > 0)
    <div class="card border-yellow-200 bg-yellow-50 p-4">
        <div class="flex items-center gap-3">
            <span class="text-2xl">⚠️</span>
            <div>
                <div class="font-semibold text-yellow-800">
                    {{ $lowStockCount }} منتجات وصلت لمستوى المخزون المنخفض
                </div>
                <a href="{{ route('stock.low-alert') }}"
                   class="text-sm text-yellow-700 underline mt-1 inline-block">
                    عرض التفاصيل ←
                </a>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
    @vite('resources/js/charts.js')
@endpush
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION K — README & CHANGELOG                      ║
## ╚══════════════════════════════════════════════════════════════╝

### `README.md`
```markdown
# نظام إدارة معمل التوزيع والشحن
## Factory Distribution & Shipping Management System

---

### متطلبات النظام | System Requirements
- PHP 8.3+
- MySQL 8.0+
- Redis 7.x
- Node.js 20 LTS
- Composer 2.x

---

### التثبيت السريع | Quick Setup

```bash
# 1. Clone the repository
git clone https://github.com/your-org/factory-system.git
cd factory-system

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Configure environment
cp .env.example .env
php artisan key:generate

# Edit .env with your database credentials:
# DB_DATABASE=factory_db
# DB_USERNAME=your_user
# DB_PASSWORD=your_password

# 5. Run migrations and seeders
php artisan migrate --seed

# 6. Create storage symlink
php artisan storage:link

# 7. Build frontend assets
npm run dev        # Development (with hot-reload)
npm run build      # Production

# 8. Start the server
php artisan serve

# 9. Start queue worker (separate terminal)
php artisan queue:work
```

### Using Docker
```bash
docker compose up -d
docker compose exec app php artisan migrate --seed
```

---

### Default Credentials | بيانات الدخول الافتراضية
```
URL:      http://localhost:8000/login
Email:    admin@factory.local
Password: password
```
⚠️ Change the password immediately after first login.

---

### User Roles | أدوار المستخدمين
| Role          | Arabic       | Access                          |
|---------------|--------------|----------------------------------|
| super_admin   | مدير النظام  | Full unrestricted access        |
| accountant    | محاسب        | ERP + All modules               |
| shipping_staff| موظف الشحن   | Orders + Shipments (limited)    |
| customer      | عميل         | Own orders/invoices via portal  |

---

### Running Tests
```bash
php artisan test                    # All tests
php artisan test --coverage         # With coverage
php artisan test tests/Feature/     # Feature tests only
php artisan test tests/Unit/        # Unit tests only
```

---

### Architecture Overview
```
app/
├── Contracts/      → All interfaces (Repository + Service)
├── DTOs/           → Immutable data transfer objects
├── Events/         → Domain events (OrderAccepted, etc.)
├── Listeners/      → Event handlers
├── Observers/      → Model event hooks (logging, balance)
├── Pipelines/      → Order validation pipeline stages
├── Repositories/   → All Eloquent queries live here
├── Services/       → All business logic lives here
├── StateMachines/  → Order & Shipment status transitions
└── ValueObjects/   → Money (immutable, arithmetic)
```

### Key Rules
1. **Money is always BIGINT** — never float
2. **Transactions everywhere** — every DB write uses DB::transaction()
3. **400-line limit** — every file, no exceptions
4. **Controllers are thin** — delegate to Services
5. **Arabic strings** — always in lang/ar/ files, never hardcoded

---

### Changelog | سجل التغييرات
See [CHANGELOG.md](./CHANGELOG.md)
```

### `CHANGELOG.md`
```markdown
# Changelog

All notable changes to Factory Distribution System will be documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)

## [Unreleased]

## [1.0.0] — 2026-05-XX

### Added
- Complete inventory management with real-time stock tracking
- Customer management with credit limit enforcement
- Full order lifecycle: pending → accepted → preparing → ready → shipped → delivered
- Truck and driver management
- Shipment planning with order assignment and delivery tracking
- Arabic PDF invoice generation (A4, RTL, DomPDF)
- Shipment manifest PDF
- ERP accounting dashboard with Chart.js analytics
- Accounts receivable aging report
- Profit & loss report (monthly breakdown)
- Customer account statements
- Excel/CSV report exports (Laravel Excel)
- Role-based access control (Spatie Permission)
- Full Arabic RTL UI (Cairo font, Tailwind RTL plugin)
- In-app notifications with email support
- Daily scheduled: overdue invoice alerts, low-stock digests
- Automated daily backups (Spatie Backup)
- Docker Compose development environment
- Complete test suite (Pest PHP, ≥80% coverage)

### Architecture
- SOLID principles enforced throughout
- Service Layer + Repository Pattern
- Observer Pattern for model events and audit logging
- State Machine for order/shipment transitions
- Pipeline Pattern for order validation (credit + stock + pricing)
- Strategy Pattern for report exports
- DTO Pattern for type-safe service boundaries
- Value Object for money arithmetic
- Factory Pattern for sequential code generation

### Security
- Session-based auth with Redis driver
- RBAC with 28+ fine-grained permissions
- Rate limiting: 5 login attempts / 15 minutes
- Soft deletes on all core tables — no hard deletes
- Full audit trail via Spatie Activity Log
- CSRF protection on all state-changing routes
- File uploads restricted to images, max 5MB, stored outside webroot
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION L — MODEL FACTORIES (FULL)                  ║
## ╚══════════════════════════════════════════════════════════════╝

### `database/factories/OrderFactory.php`
```php
<?php
namespace Database\Factories;

use App\Models\{Customer, Order, User};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'customer_id'   => Customer::factory(),
            'status'        => 'pending',
            'order_date'    => $this->faker->dateTimeBetween('-30 days', 'now'),
            'subtotal'      => $amount = $this->faker->numberBetween(10_000, 500_000),
            'discount_amount' => 0,
            'tax_amount'    => 0,
            'total_amount'  => $amount,
            'paid_amount'   => 0,
            'created_by'    => User::factory(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function accepted(): static
    {
        return $this->state([
            'status'      => 'accepted',
            'accepted_by' => User::factory(),
            'accepted_at' => now(),
        ]);
    }

    public function ready(): static
    {
        return $this->state(['status' => 'ready']);
    }

    public function delivered(): static
    {
        return $this->state([
            'status'       => 'delivered',
            'delivered_at' => now(),
            'paid_amount'  => fn(array $attrs) => $attrs['total_amount'],
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status'        => 'cancelled',
            'cancel_reason' => 'إلغاء للاختبار',
        ]);
    }
}
```

### `database/factories/ProductFactory.php`
```php
<?php
namespace Database\Factories;

use App\Models\{Product, ProductCategory, User};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    private static int $sequence = 1;

    public function definition(): array
    {
        $price = $this->faker->numberBetween(1_000, 100_000);
        return [
            'category_id'         => ProductCategory::factory(),
            'code'                => sprintf('PRD-%04d', self::$sequence++),
            'name'                => $this->faker->words(3, true),
            'unit'                => $this->faker->randomElement(['كرتون','كيس','لتر','كيلو','قطعة']),
            'unit_price'          => $price,
            'cost_price'          => (int) ($price * 0.65),
            'stock_quantity'      => $this->faker->numberBetween(20, 200),
            'low_stock_threshold' => 10,
            'is_active'           => true,
            'sort_order'          => 0,
            'created_by'          => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true, 'stock_quantity' => 50]);
    }

    public function lowStock(): static
    {
        return $this->state([
            'stock_quantity'      => $this->faker->numberBetween(1, 9),
            'low_stock_threshold' => 10,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock_quantity' => 0]);
    }
}
```

### `database/factories/CustomerFactory.php`
```php
<?php
namespace Database\Factories;

use App\Models\{Customer, User};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;
    private static int $seq = 1;

    public function definition(): array
    {
        return [
            'code'                => sprintf('CUS-%04d', self::$seq++),
            'name'                => $this->faker->name(),
            'phone'               => '09' . $this->faker->numerify('########'),
            'address'             => $this->faker->address(),
            'city'                => $this->faker->randomElement(['دمشق','حلب','حمص','اللاذقية','طرطوس']),
            'region'              => $this->faker->randomElement(['الشمال','الجنوب','الشرق','الغرب','الوسط']),
            'category'            => 'B',
            'credit_limit'        => $this->faker->randomElement([0, 500_000, 1_000_000, 2_000_000]),
            'outstanding_balance' => 0,
            'is_active'           => true,
            'portal_access'       => false,
            'created_by'          => User::factory(),
        ];
    }

    public function categoryA(): static
    {
        return $this->state(['category' => 'A', 'credit_limit' => 2_000_000]);
    }

    public function categoryB(): static
    {
        return $this->state(['category' => 'B', 'credit_limit' => 1_000_000]);
    }

    public function categoryC(): static
    {
        return $this->state(['category' => 'C', 'credit_limit' => 500_000]);
    }

    public function withPortalAccess(): static
    {
        return $this->state([
            'portal_access' => true,
            'user_id'       => User::factory(),
        ]);
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION M — ADDITIONAL TESTS                        ║
## ╚══════════════════════════════════════════════════════════════╝

### `tests/Unit/MoneyValueObjectTest.php`
```php
<?php
use App\ValueObjects\Money;

it('creates a money object with correct amount and currency', function () {
    $m = Money::of(50_000, 'SYP');
    expect($m->amount())->toBe(50_000)
        ->and($m->currency())->toBe('SYP');
});

it('adds two money objects of the same currency', function () {
    $a = Money::of(30_000);
    $b = Money::of(20_000);
    expect($a->add($b)->amount())->toBe(50_000);
});

it('subtracts two money objects', function () {
    $a = Money::of(50_000);
    $b = Money::of(15_000);
    expect($a->subtract($b)->amount())->toBe(35_000);
});

it('multiplies by a factor', function () {
    $m = Money::of(10_000);
    expect($m->multiply(3)->amount())->toBe(30_000);
    expect($m->multiply(0.15)->amount())->toBe(1_500); // 15% of 10,000
});

it('throws exception when adding different currencies', function () {
    $syp = Money::of(100, 'SYP');
    $usd = Money::of(100, 'USD');
    expect(fn() => $syp->add($usd))->toThrow(\InvalidArgumentException::class);
});

it('correctly identifies zero amounts', function () {
    expect(Money::of(0)->isZero())->toBeTrue();
    expect(Money::of(1)->isZero())->toBeFalse();
});

it('formats money with Arabic currency symbol', function () {
    $m = Money::of(150_000, 'SYP');
    expect($m->format())->toContain('150,000')
        ->and($m->format())->toContain('ل.س');
});

it('money is immutable — operations return new instances', function () {
    $original = Money::of(10_000);
    $new      = $original->add(Money::of(5_000));
    expect($original->amount())->toBe(10_000) // original unchanged
        ->and($new->amount())->toBe(15_000);
});
```

### `tests/Unit/MoneyHelperTest.php`
```php
<?php
use App\Helpers\MoneyHelper;

it('formats integer amounts with SYP symbol', function () {
    expect(MoneyHelper::format(150_000))->toBe('150,000 ل.س');
});

it('formats zero', function () {
    expect(MoneyHelper::format(0))->toBe('0 ل.س');
});

it('formats large amounts with commas', function () {
    expect(MoneyHelper::format(1_500_000))->toBe('1,500,000 ل.س');
});

it('parses comma-separated string to integer', function () {
    expect(MoneyHelper::parse('150,000'))->toBe(150_000);
    expect(MoneyHelper::parse('1,500,000 ل.س'))->toBe(1_500_000);
});

it('parses float to integer', function () {
    expect(MoneyHelper::parse(150000.0))->toBe(150_000);
});

it('passes integer through unchanged', function () {
    expect(MoneyHelper::parse(75_000))->toBe(75_000);
});

it('global helper money_format() works', function () {
    expect(money_format(50_000))->toContain('50,000');
});
```

### `tests/Feature/InvoicePaymentTest.php`
```php
<?php
use App\Models\{Customer, Invoice, Order, Payment, User};
use App\Services\Invoices\InvoiceService;
use App\DTOs\Invoices\RecordPaymentDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin    = User::factory()->create()->assignRole('super_admin');
    $this->customer = Customer::factory()->create();
    $this->invoice  = Invoice::factory()->create([
        'customer_id'  => $this->customer->id,
        'status'       => 'issued',
        'total_amount' => 100_000,
        'paid_amount'  => 0,
        'balance_due'  => 100_000,
    ]);
    $this->actingAs($this->admin);
});

it('records a partial payment correctly', function () {
    $dto = RecordPaymentDTO::fromArray([
        'invoice_id'     => $this->invoice->id,
        'amount'         => 60_000,
        'payment_method' => 'cash',
        'payment_date'   => today()->toDateString(),
        'received_by'    => $this->admin->id,
    ]);

    app(InvoiceService::class)->recordPayment($dto);

    $this->invoice->refresh();
    expect($this->invoice->paid_amount)->toBe(60_000)
        ->and($this->invoice->balance_due)->toBe(40_000)
        ->and($this->invoice->status)->toBe('partial');
});

it('marks invoice as paid when full amount received', function () {
    $dto = RecordPaymentDTO::fromArray([
        'invoice_id'     => $this->invoice->id,
        'amount'         => 100_000,
        'payment_method' => 'bank_transfer',
        'payment_date'   => today()->toDateString(),
        'received_by'    => $this->admin->id,
    ]);

    app(InvoiceService::class)->recordPayment($dto);

    expect($this->invoice->fresh()->status)->toBe('paid')
        ->and($this->invoice->fresh()->balance_due)->toBe(0);
});

it('blocks payment exceeding balance due', function () {
    $dto = RecordPaymentDTO::fromArray([
        'invoice_id'     => $this->invoice->id,
        'amount'         => 200_000, // exceeds 100,000 balance
        'payment_method' => 'cash',
        'payment_date'   => today()->toDateString(),
        'received_by'    => $this->admin->id,
    ]);

    expect(fn() => app(InvoiceService::class)->recordPayment($dto))
        ->toThrow(\DomainException::class);
});

it('cannot void invoice with existing payments', function () {
    Payment::factory()->create([
        'invoice_id' => $this->invoice->id,
        'customer_id'=> $this->customer->id,
        'amount'     => 50_000,
    ]);
    $this->invoice->update(['paid_amount' => 50_000]);

    expect(fn() => app(InvoiceService::class)->void($this->invoice, 'test'))
        ->toThrow(\App\Exceptions\InvoiceCannotBeVoidedException::class);
});

it('updates customer outstanding balance after payment', function () {
    $this->customer->update(['outstanding_balance' => 100_000]);

    $dto = RecordPaymentDTO::fromArray([
        'invoice_id'     => $this->invoice->id,
        'amount'         => 100_000,
        'payment_method' => 'cash',
        'payment_date'   => today()->toDateString(),
        'received_by'    => $this->admin->id,
    ]);

    app(InvoiceService::class)->recordPayment($dto);

    expect($this->customer->fresh()->outstanding_balance)->toBe(0);
});
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION N — GLOBAL TODO.md TEMPLATE                 ║
## ╚══════════════════════════════════════════════════════════════╝

### `TODO.md` — Starting Template for the Agent
```markdown
# TODO.md — Factory System Sprint Board
*Updated by agent after every task*

---

## 🔴 PHASE 00 — ENVIRONMENT BOOTSTRAP
- [ ] Create AGENT.md
- [ ] Create PROGRESS.md
- [ ] Create DECISIONS.md
- [ ] Create SKILLS.md
- [ ] composer create-project laravel/laravel
- [ ] Install all composer packages
- [ ] Install all npm packages
- [ ] Configure .env
- [ ] Create config/factory.php
- [ ] Create config/money.php
- [ ] Create config/pdf.php
- [ ] php artisan key:generate
- [ ] php artisan storage:link

## 🔴 PHASE 01 — DATABASE MIGRATIONS
- [ ] Migration 001: users
- [ ] Migration 002: product_categories
- [ ] Migration 003: products
- [ ] Migration 004: customers
- [ ] Migration 005: trucks
- [ ] Migration 006: drivers
- [ ] Migration 007: shipments
- [ ] Migration 008: orders
- [ ] Migration 009: order_items
- [ ] Migration 010: stock_movements
- [ ] Migration 011: invoices
- [ ] Migration 012: payments
- [ ] Migration 013: expenses
- [ ] Migration 014: system_settings
- [ ] Migration 015: permission_tables (Spatie)
- [ ] Migration 016: activity_log (Spatie)
- [ ] Migration 017: notifications
- [ ] php artisan migrate:fresh → zero errors

## 🟡 PHASE 02 — VALUE OBJECTS & STATE MACHINES
- [ ] app/ValueObjects/Money.php
- [ ] app/StateMachines/OrderStateMachine.php
- [ ] app/StateMachines/ShipmentStateMachine.php
- [ ] tests/Unit/MoneyValueObjectTest.php
- [ ] tests/Unit/OrderStateMachineTest.php

## 🟡 PHASE 03 — BASE CLASSES & CONTRACTS
- [ ] app/Services/BaseService.php
- [ ] app/Repositories/BaseRepository.php
- [ ] All Repository interfaces (6 files)
- [ ] app/Providers/AppServiceProvider.php (DI bindings)
- [ ] app/Providers/EventServiceProvider.php

## 🟡 PHASE 04 — MODELS & TRAITS
- [ ] Trait: GeneratesSequentialCode
- [ ] Trait: HasMoneyFormatting
- [ ] Trait: HasSoftDeleteGuard
- [ ] Trait: HasStatusTransitions
- [ ] Model: User (update)
- [ ] Model: Customer
- [ ] Model: Product
- [ ] Model: ProductCategory
- [ ] Model: StockMovement
- [ ] Model: Order
- [ ] Model: OrderItem
- [ ] Model: Truck
- [ ] Model: Driver
- [ ] Model: Shipment
- [ ] Model: Invoice
- [ ] Model: Payment
- [ ] Model: Expense
- [ ] Model: SystemSetting
- [ ] Observer: OrderObserver
- [ ] Observer: ProductObserver
- [ ] Observer: InvoiceObserver
- [ ] Observer: PaymentObserver

## 🟡 PHASE 05 — SEEDERS
- [ ] RolesAndPermissionsSeeder
- [ ] AdminUserSeeder
- [ ] SystemSettingsSeeder
- [ ] ProductCategorySeeder
- [ ] DemoDataSeeder (dev only)
- [ ] DatabaseSeeder (master)
- [ ] php artisan migrate:fresh --seed → zero errors

## 🟢 PHASE 06 — AUTH & MIDDLEWARE
## 🟢 PHASE 07 — MODULE 01: INVENTORY
## 🟢 PHASE 08 — MODULE 02: CUSTOMERS
## 🟢 PHASE 09 — MODULE 03: ORDERS (CORE)
## 🟢 PHASE 10 — MODULE 04: DISTRIBUTION
## 🟢 PHASE 11 — MODULE 05: INVOICING
## 🟢 PHASE 12 — PDF GENERATION
## 🟢 PHASE 13 — ERP & REPORTS
## 🟢 PHASE 14 — FRONTEND ARCHITECTURE
## 🟢 PHASE 15 — NOTIFICATIONS
## 🟢 PHASE 16 — SECURITY HARDENING
## 🟢 PHASE 17 — FULL TEST SUITE
## 🟢 PHASE 18 — DEPLOYMENT

---

## ✅ DONE
*(agent moves completed items here)*
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║              FINAL AGENT INSTRUCTION                        ║
## ╚══════════════════════════════════════════════════════════════╝

```
YOU ARE NOW FULLY EQUIPPED.

READING ORDER:
  [1] AGENT_PROMPT_FACTORY_SYSTEM.md      → Architecture, Phases, Rules
  [2] AGENT_PROMPT_FACTORY_SYSTEM_PART2.md → DTOs, Repos, Services, Controllers, Views, Routes
  [3] AGENT_PROMPT_FACTORY_SYSTEM_PART3.md → Models, Observers, Notifications, PDF, Reports, Frontend, Deploy

EXECUTION ORDER:
  Phase 00 → 01 → 02 → 03 → 04 → 05 → 06 → 07 → 08 → 09
          → 10 → 11 → 12 → 13 → 14 → 15 → 16 → 17 → 18

BEFORE EVERY TASK:
  → Announce: "▶ STARTING: [Task ID] — [Description]"

AFTER EVERY TASK:
  → Announce: "✅ COMPLETED: [Task ID] | Tests: X passing"
  → Update PROGRESS.md and TODO.md

AFTER EVERY MODULE (complete group of tasks):
  → Run: php artisan test --filter=[ModuleName]
  → All tests must be GREEN before proceeding

IMMOVABLE LAWS:
  ✗ No file > 400 lines
  ✗ No money as float
  ✗ No business logic in controllers
  ✗ No Eloquent in services (use repositories)
  ✗ No DB write outside transaction()
  ✗ No controller action without $this->authorize()
  ✗ No Arabic string hardcoded in PHP
  ✗ No ->get() on unbounded collections

قبل أن تكتب أي سطر كود، اقرأ الملفات الثلاثة كاملاً.
BEGIN WHEN READY.
```

---

*PART 3 OF 3 — MASTER AGENT PROMPT v1.0.0*
*Factory Distribution & Shipping Management System*
*نظام إدارة معمل التوزيع والشحن*
*May 2026 · Complete Execution Blueprint*
*Total: 3 files · ~8,500 lines · 300KB of engineering specification*
