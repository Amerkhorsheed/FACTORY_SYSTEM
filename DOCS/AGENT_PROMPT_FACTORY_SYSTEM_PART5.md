# 🏭 MASTER AGENT PROMPT — PART 5
## Remaining Models · Repositories · Services · Controllers · Views
## Events · Listeners · Observers · Factories · Seeders · Tests
### نظام إدارة معمل التوزيع والشحن — الجزء الخامس
---
> **PART 5 OF 5** | Read Parts 1–4 first. This file closes every remaining gap.
> After this part the system is 100% complete — zero inference required.

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION A — REMAINING MODELS                        ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Models/Truck.php`
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Truck model — represents a delivery vehicle.
 *
 * @property int    $id
 * @property string $plate_number
 * @property string $status   available|on_trip|maintenance|inactive
 * @package App\Models
 */
class Truck extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'plate_number','model','capacity_kg',
        'capacity_units','status','notes','is_active',
    ];

    protected $casts = [
        'capacity_kg'    => 'decimal:2',
        'capacity_units' => 'integer',
        'is_active'      => 'boolean',
    ];

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available')->where('is_active', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getStatusLabelAttribute(): string
    {
        return config("factory.truck_statuses.{$this->status}", $this->status);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->is_active;
    }
}
```

### `app/Models/Driver.php`
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Builder;

/**
 * Driver model — represents a truck driver.
 *
 * @property int    $id
 * @property string $name
 * @property string $phone
 * @property bool   $is_active
 * @package App\Models
 */
class Driver extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id','name','phone',
        'license_number','license_expiry','is_active','notes',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'license_expiry' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isLicenseExpired(): bool
    {
        return $this->license_expiry && $this->license_expiry->isPast();
    }

    public function hasActiveShipment(): bool
    {
        return $this->shipments()
            ->whereIn('status', ['planned','loading','dispatched'])
            ->whereDate('shipment_date', today())
            ->exists();
    }
}
```

### `app/Models/OrderItem.php`
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo};
use App\Models\Traits\HasMoneyFormatting;

/**
 * OrderItem model — a single product line within an order.
 *
 * @property int   $order_id
 * @property int   $product_id
 * @property int   $quantity
 * @property int   $unit_price    BIGINT
 * @property int   $line_total    BIGINT
 * @package App\Models
 */
class OrderItem extends Model
{
    use HasMoneyFormatting;

    protected array $moneyColumns = ['unit_price','discount_amount','line_total'];

    protected $fillable = [
        'order_id','product_id','quantity','unit_price',
        'discount_percent','discount_amount','line_total',
        'returned_qty','notes',
    ];

    protected $casts = [
        'quantity'         => 'integer',
        'unit_price'       => 'integer',
        'discount_percent' => 'decimal:2',
        'discount_amount'  => 'integer',
        'line_total'       => 'integer',
        'returned_qty'     => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getGrossAmountAttribute(): int
    {
        return $this->unit_price * $this->quantity;
    }

    public function getRemainingQtyAttribute(): int
    {
        return $this->quantity - $this->returned_qty;
    }
}
```

### `app/Models/StockMovement.php`
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, MorphTo};
use Illuminate\Database\Eloquent\Builder;

/**
 * StockMovement model — immutable audit record of every stock change.
 * Never update or delete; only create new records.
 *
 * @property int    $id
 * @property int    $product_id
 * @property string $type       in|out|adjustment|return
 * @property int    $quantity
 * @property int    $quantity_before
 * @property int    $quantity_after
 * @package App\Models
 */
class StockMovement extends Model
{
    // No soft deletes — stock movements are immutable records
    public const UPDATED_AT = null;

    protected $fillable = [
        'product_id','type','quantity',
        'quantity_before','quantity_after',
        'reference_type','reference_id',
        'unit_cost','notes','created_by',
    ];

    protected $casts = [
        'quantity'        => 'integer',
        'quantity_before' => 'integer',
        'quantity_after'  => 'integer',
        'unit_cost'       => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForProduct(Builder $q, int $id): Builder
    {
        return $q->where('product_id', $id);
    }

    public function scopeOfType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    public function scopeBetween(Builder $q, string $from, string $to): Builder
    {
        return $q->whereBetween('created_at', [$from, $to]);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'in'         => 'وارد',
            'out'        => 'صادر',
            'adjustment' => 'تسوية',
            'return'     => 'مرتجع',
            default      => $this->type,
        };
    }

    public function isIncoming(): bool
    {
        return in_array($this->type, ['in','return'], true);
    }
}
```

### `app/Models/Expense.php`
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\HasMoneyFormatting;

/**
 * Expense model — records operational costs.
 *
 * @property int    $id
 * @property string $category
 * @property int    $amount      BIGINT
 * @property string $expense_date
 * @package App\Models
 */
class Expense extends Model
{
    use SoftDeletes, HasMoneyFormatting;

    protected array $moneyColumns = ['amount'];

    protected $fillable = [
        'category','amount','expense_date',
        'description','reference','attachment','created_by',
    ];

    protected $casts = [
        'amount'       => 'integer',
        'expense_date' => 'date',
    ];

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForMonth(Builder $q, int $year, int $month): Builder
    {
        return $q->whereYear('expense_date', $year)
                 ->whereMonth('expense_date', $month);
    }

    public function scopeByCategory(Builder $q, string $cat): Builder
    {
        return $q->where('category', $cat);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment
            ? asset('storage/' . $this->attachment)
            : null;
    }
}
```

### `app/Models/SystemSetting.php`
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SystemSetting model — key/value store for application configuration.
 * Use via Setting facade, not directly.
 *
 * @property string $key
 * @property string $value
 * @property string $type   string|integer|boolean|json
 * @property string $group
 * @property string $label  Arabic label for UI
 * @package App\Models
 */
class SystemSetting extends Model
{
    protected $fillable = [
        'key','value','type','group','label','description',
    ];

    /** No soft deletes on settings */
    public static function getByGroup(string $group): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('group', $group)->orderBy('key')->get();
    }
}
```

### `app/Models/ProductCategory.php`
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * ProductCategory model — groups products into logical categories.
 *
 * @package App\Models
 */
class ProductCategory extends Model
{
    use SoftDeletes;

    protected $fillable = ['name','description','sort_order','is_active'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function getActiveProductsCountAttribute(): int
    {
        return $this->products()->where('is_active', true)->count();
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION B — REMAINING REPOSITORIES                  ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Repositories/InvoiceRepository.php`
```php
<?php
namespace App\Repositories;

use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Concrete Invoice repository — all Eloquent queries for Invoice.
 *
 * @package App\Repositories
 */
class InvoiceRepository extends BaseRepository implements InvoiceRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Invoice());
    }

    public function findByIdOrFail(int $id): Invoice
    {
        return Invoice::with(['order.items.product','customer','payments'])->findOrFail($id);
    }

    public function findByNumber(string $number): ?Invoice
    {
        return Invoice::where('invoice_number', $number)->first();
    }

    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Invoice::with(['customer','order'])
            ->latest('issue_date')->latest('id');

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('issue_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('issue_date', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getOverdue(): Collection
    {
        return Invoice::with('customer')
            ->overdue()
            ->where('balance_due', '>', 0)
            ->orderBy('due_date')
            ->get();
    }

    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);
        return $invoice->fresh();
    }
}
```

### `app/Repositories/ShipmentRepository.php`
```php
<?php
namespace App\Repositories;

use App\Contracts\Repositories\ShipmentRepositoryInterface;
use App\Models\Shipment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Concrete Shipment repository.
 *
 * @package App\Repositories
 */
class ShipmentRepository extends BaseRepository implements ShipmentRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Shipment());
    }

    public function findByIdOrFail(int $id): Shipment
    {
        return Shipment::with(['truck','driver','orders.customer'])->findOrFail($id);
    }

    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Shipment::with(['truck','driver'])
            ->latest('shipment_date')->latest('id');

        if (! empty($filters['date'])) {
            $query->whereDate('shipment_date', $filters['date']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['truck_id'])) {
            $query->where('truck_id', $filters['truck_id']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getTodayActive(): Collection
    {
        return Shipment::with(['truck','driver'])
            ->whereDate('shipment_date', today())
            ->whereIn('status', ['planned','loading','dispatched'])
            ->get();
    }

    public function create(array $data): Shipment
    {
        return Shipment::create($data);
    }

    public function update(Shipment $shipment, array $data): Shipment
    {
        $shipment->update($data);
        return $shipment->fresh(['truck','driver','orders']);
    }
}
```

### `app/Repositories/CustomerRepository.php`
```php
<?php
namespace App\Repositories;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Concrete Customer repository.
 *
 * @package App\Repositories
 */
class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Customer());
    }

    public function findByIdOrFail(int $id): Customer
    {
        return Customer::findOrFail($id);
    }

    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Customer::query()->latest('id');

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('phone', 'like', "%{$filters['search']}%")
                  ->orWhere('business_name', 'like', "%{$filters['search']}%");
            });
        }
        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (! empty($filters['region'])) {
            $query->where('region', $filters['region']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (! empty($filters['has_balance'])) {
            $query->where('outstanding_balance', '>', 0);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function searchForOrder(string $term, int $limit = 8): Collection
    {
        return Customer::where('is_active', true)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%");
            })
            ->limit($limit)
            ->get(['id','name','phone','credit_limit','outstanding_balance']);
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->fresh();
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
    }
}
```

### `app/Repositories/StockMovementRepository.php`
```php
<?php
namespace App\Repositories;

use App\Contracts\Repositories\StockMovementRepositoryInterface;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;

/**
 * Concrete StockMovement repository.
 *
 * @package App\Repositories
 */
class StockMovementRepository extends BaseRepository implements StockMovementRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new StockMovement());
    }

    public function getForProduct(int $productId, int $limit = 50): Collection
    {
        return StockMovement::where('product_id', $productId)
            ->with('createdByUser')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getForDateRange(
        int $productId,
        string $from,
        string $to
    ): Collection {
        return StockMovement::where('product_id', $productId)
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->with(['product','createdByUser'])
            ->latest()
            ->get();
    }

    public function create(array $data): StockMovement
    {
        return StockMovement::create($data);
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION C — REMAINING SERVICES                      ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Services/Products/ProductService.php`
```php
<?php
namespace App\Services\Products;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Models\Product;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Product CRUD service — manages product lifecycle.
 * Stock operations are handled exclusively by StockService.
 *
 * @package App\Services\Products
 */
class ProductService extends BaseService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function list(array $filters, int $perPage = 0): LengthAwarePaginator
    {
        return $this->products->paginateWithFilters(
            $filters,
            $perPage ?: config('factory.pagination.per_page', 20)
        );
    }

    /**
     * Create a new product with optional image upload.
     *
     * @throws \Throwable
     */
    public function create(array $data, ?UploadedFile $image = null): Product
    {
        return $this->transaction(function () use ($data, $image) {
            if ($image) {
                $data['image'] = $image->store('products', 'public');
            }

            $data['created_by'] = auth()->id();

            return $this->products->create($data);
        });
    }

    /**
     * Update a product. Replaces image if a new one is provided.
     *
     * @throws \Throwable
     */
    public function update(Product $product, array $data, ?UploadedFile $image = null): Product
    {
        return $this->transaction(function () use ($product, $data, $image) {
            if ($image) {
                // Delete old image
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $data['image'] = $image->store('products', 'public');
            }

            return $this->products->update($product, $data);
        });
    }

    /**
     * Soft-delete a product.
     * The HasSoftDeleteGuard trait blocks deletion if active orders exist.
     *
     * @throws \DomainException
     * @throws \Throwable
     */
    public function delete(Product $product): void
    {
        $this->products->delete($product);
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restore(int $id): Product
    {
        return $this->products->restore($id);
    }
}
```

### `app/Services/Erp/ExpenseService.php`
```php
<?php
namespace App\Services\Erp;

use App\Models\Expense;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Expense management service — handles operational cost records.
 *
 * @package App\Services\Erp
 */
class ExpenseService extends BaseService
{
    public function list(array $filters, int $perPage = 0): LengthAwarePaginator
    {
        $query = Expense::with('createdByUser')
            ->latest('expense_date')->latest('id');

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('expense_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('expense_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['search'])) {
            $query->where('description', 'like', "%{$filters['search']}%");
        }

        return $query->paginate($perPage ?: config('factory.pagination.per_page', 20))
            ->withQueryString();
    }

    /**
     * Create an expense with optional receipt attachment.
     *
     * @throws \Throwable
     */
    public function create(array $data, ?UploadedFile $attachment = null): Expense
    {
        return $this->transaction(function () use ($data, $attachment) {
            if ($attachment) {
                $data['attachment'] = $attachment->store('expenses', 'public');
            }

            $data['created_by'] = auth()->id();
            return Expense::create($data);
        });
    }

    /**
     * Update an expense.
     *
     * @throws \Throwable
     */
    public function update(Expense $expense, array $data, ?UploadedFile $attachment = null): Expense
    {
        return $this->transaction(function () use ($expense, $data, $attachment) {
            if ($attachment) {
                if ($expense->attachment) {
                    Storage::disk('public')->delete($expense->attachment);
                }
                $data['attachment'] = $attachment->store('expenses', 'public');
            }

            $expense->update($data);
            return $expense->fresh();
        });
    }

    /**
     * Delete expense within current month only (guarded by ExpensePolicy).
     *
     * @throws \Throwable
     */
    public function delete(Expense $expense): void
    {
        $this->transaction(function () use ($expense) {
            if ($expense->attachment) {
                Storage::disk('public')->delete($expense->attachment);
            }
            $expense->delete();
        });
    }

    /**
     * Monthly summary totals by category.
     *
     * @return array<string, int>
     */
    public function getMonthlySummary(int $year, int $month): array
    {
        return Expense::whereYear('expense_date', $year)
            ->whereMonth('expense_date', $month)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->pluck('total', 'category')
            ->map(fn($v) => (int) $v)
            ->toArray();
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION D — REMAINING CONTROLLERS                   ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Http/Controllers/Products/ProductController.php`
```php
<?php
namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\{StoreProductRequest, UpdateProductRequest};
use App\Models\{Product, ProductCategory};
use App\Services\Products\ProductService;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

/**
 * Handles Product CRUD operations.
 * Stock adjustments handled by StockController.
 *
 * @package App\Http\Controllers\Products
 */
class ProductController extends Controller
{
    public function __construct(private readonly ProductService $service)
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request): View
    {
        $products   = $this->service->list($request->only([
            'search','category_id','is_active','low_stock',
        ]));
        $categories = ProductCategory::active()->orderBy('sort_order')->get();
        $lowCount   = \App\Models\Product::scopeLowStock(\App\Models\Product::query())->count();

        return view('products.index', compact('products','categories','lowCount'));
    }

    public function create(): View
    {
        $categories = ProductCategory::active()->orderBy('sort_order')->get();
        return view('products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->service->create(
            $request->except('image'),
            $request->file('image')
        );

        return redirect()
            ->route('products.show', $product)
            ->with('success', __('products.created', ['name' => $product->name]));
    }

    public function show(Product $product): View
    {
        $product->load('category');
        $movements = \App\Models\StockMovement::where('product_id', $product->id)
            ->with('createdByUser')
            ->latest()
            ->limit(50)
            ->get();

        return view('products.show', compact('product','movements'));
    }

    public function edit(Product $product): View
    {
        $categories = ProductCategory::active()->orderBy('sort_order')->get();
        return view('products.edit', compact('product','categories'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->service->update(
            $product,
            $request->except('image'),
            $request->file('image')
        );

        return redirect()
            ->route('products.show', $product)
            ->with('success', __('products.updated'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->service->delete($product);

        return redirect()
            ->route('products.index')
            ->with('success', __('products.deleted'));
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('create', Product::class);
        $product = $this->service->restore($id);

        return redirect()
            ->route('products.show', $product)
            ->with('success', __('products.restored'));
    }
}
```

### `app/Http/Controllers/Products/StockController.php`
```php
<?php
namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StockAdjustmentRequest;
use App\Models\{Product, StockMovement};
use App\Services\Products\StockService;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

/**
 * Handles stock movements and adjustments.
 *
 * @package App\Http\Controllers\Products
 */
class StockController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $movements = StockMovement::with(['product','createdByUser'])
            ->when($request->product_id, fn($q,$v) => $q->where('product_id',$v))
            ->when($request->type,       fn($q,$v) => $q->where('type',$v))
            ->when($request->date_from,  fn($q,$v) => $q->whereDate('created_at','>=',$v))
            ->when($request->date_to,    fn($q,$v) => $q->whereDate('created_at','<=',$v))
            ->latest()
            ->paginate(config('factory.pagination.per_page', 20));

        return view('products.stock-movements', compact('movements'));
    }

    public function adjust(StockAdjustmentRequest $request): RedirectResponse
    {
        $product = Product::findOrFail($request->product_id);
        $this->authorize('adjustStock', $product);

        $this->stock->adjustStock(
            $product,
            $request->validated('new_quantity'),
            $request->validated('reason')
        );

        return back()->with('success', __('products.stock_adjusted', [
            'name' => $product->name,
        ]));
    }

    public function lowAlert(): View
    {
        $this->authorize('viewAny', Product::class);
        $products = $this->stock->getLowStockProducts();

        return view('products.low-alert', compact('products'));
    }
}
```

### `app/Http/Controllers/Customers/CustomerController.php`
```php
<?php
namespace App\Http\Controllers\Customers;

use App\DTOs\Customers\CreateCustomerDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\{StoreCustomerRequest, UpdateCustomerRequest};
use App\Models\Customer;
use App\Services\Customers\CustomerService;
use App\Services\Erp\ReportService;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

/**
 * Customer CRUD and portal access management.
 *
 * @package App\Http\Controllers\Customers
 */
class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $service,
        private readonly ReportService   $reports,
    ) {
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index(Request $request): View
    {
        $customers = $this->service->list($request->only([
            'search','category','region','is_active','has_balance',
        ]));

        $kpis = [
            'total'    => Customer::count(),
            'category_a' => Customer::where('category','A')->count(),
            'with_debt'  => Customer::where('outstanding_balance','>',0)->count(),
        ];

        return view('customers.index', compact('customers','kpis'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $dto      = CreateCustomerDTO::fromArray($request->validated());
        $customer = $this->service->create($dto);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', __('customers.created', ['name' => $customer->name]));
    }

    public function show(Customer $customer): View
    {
        $customer->load(['orders' => fn($q) => $q->latest()->limit(10), 'invoices']);
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $dto = CreateCustomerDTO::fromArray($request->validated());
        $this->service->update($customer, $dto);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', __('customers.updated'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->service->delete($customer);

        return redirect()
            ->route('customers.index')
            ->with('success', __('customers.deleted'));
    }

    public function activate(Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);
        $customer->update(['is_active' => ! $customer->is_active]);

        return back()->with('success', $customer->is_active
            ? __('customers.activated')
            : __('customers.deactivated')
        );
    }

    public function togglePortalAccess(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        if ($customer->portal_access) {
            $this->service->disablePortalAccess($customer);
        } else {
            $request->validate(['password' => ['required','string','min:8']]);
            $this->service->enablePortalAccess($customer, $request->password);
        }

        return back()->with('success', __('customers.portal_access_updated'));
    }

    public function statement(Request $request, Customer $customer): View
    {
        $this->authorize('view', $customer);

        $from = \Carbon\Carbon::parse($request->get('from', now()->startOfMonth()));
        $to   = \Carbon\Carbon::parse($request->get('to',   now()->endOfMonth()));

        $statement = $this->reports->getCustomerStatement($customer, $from, $to);

        return view('customers.statement', compact('customer','statement','from','to'));
    }
}
```

### `app/Http/Controllers/Erp/DashboardController.php`
```php
<?php
namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\{Invoice, Order, Truck, Product};
use App\Services\Products\StockService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * ERP accounting dashboard controller.
 * All KPI values cached in Redis (5-min TTL).
 * Charts loaded async via ChartController API endpoints.
 *
 * @package App\Http\Controllers\Erp
 */
class DashboardController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(): View
    {
        $this->authorize('erp.dashboard.view', \App\Models\User::class);

        $todayRevenue = Cache::remember('dashboard:today_revenue', 300, fn() =>
            (int) Invoice::whereDate('issue_date', today())
                ->whereIn('status', ['issued','sent','paid','partial'])
                ->sum('total_amount')
        );

        $monthRevenue = Cache::remember('dashboard:month_revenue', 300, fn() =>
            (int) Invoice::whereMonth('issue_date', now()->month)
                ->whereYear('issue_date', now()->year)
                ->whereIn('status', ['issued','sent','paid','partial'])
                ->sum('total_amount')
        );

        $outstandingBalance = Cache::remember('dashboard:outstanding', 300, fn() =>
            (int) Invoice::whereNotIn('status', ['paid','void'])
                ->sum('balance_due')
        );

        $monthExpenses = Cache::remember('dashboard:month_expenses', 300, fn() =>
            (int) \App\Models\Expense::whereMonth('expense_date', now()->month)
                ->whereYear('expense_date', now()->year)
                ->sum('amount')
        );

        $orderStats = Cache::remember('dashboard:order_stats', 300, fn() =>
            Order::selectRaw('status, COUNT(*) as count')
                ->whereNotIn('status', ['delivered','cancelled','returned'])
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray()
        );

        $availableTrucks = Truck::where('status', 'available')->count();
        $lowStockCount   = Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('is_active', true)->count();

        return view('erp.dashboard', compact(
            'todayRevenue','monthRevenue','outstandingBalance',
            'monthExpenses','orderStats','availableTrucks','lowStockCount'
        ));
    }
}
```

### `app/Http/Controllers/Erp/ReportController.php`
```php
<?php
namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Erp\ReportService;
use App\Services\Export\ExcelExportStrategy;
use Carbon\Carbon;
use Illuminate\Http\{RedirectResponse, Request, Response};
use Illuminate\View\View;

/**
 * Renders ERP reports and handles export requests.
 * All report data computation delegated to ReportService.
 *
 * @package App\Http\Controllers\Erp
 */
class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function sales(Request $request): View
    {
        $this->authorize('erp.reports.view', \App\Models\User::class);

        $from = Carbon::parse($request->get('from', now()->startOfMonth()));
        $to   = Carbon::parse($request->get('to',   now()->endOfMonth()));

        $result = $this->reports->getSalesSummary($from, $to, $request->only([
            'customer_id','status',
        ]));

        return view('erp.reports.sales', compact('result','from','to'));
    }

    public function receivables(): View
    {
        $this->authorize('erp.reports.view', \App\Models\User::class);

        $result = $this->reports->getReceivablesAging();
        return view('erp.reports.receivables', compact('result'));
    }

    public function stockMovements(Request $request): View
    {
        $this->authorize('erp.reports.view', \App\Models\User::class);

        $from = Carbon::parse($request->get('from', now()->startOfMonth()));
        $to   = Carbon::parse($request->get('to',   now()->endOfMonth()));

        $movements = $this->reports->getStockMovementReport(
            $from, $to, $request->get('product_id')
        );

        return view('erp.reports.stock-movements', compact('movements','from','to'));
    }

    public function profitLoss(Request $request): View
    {
        $this->authorize('erp.reports.view', \App\Models\User::class);

        $from   = Carbon::parse($request->get('from', now()->startOfYear()));
        $to     = Carbon::parse($request->get('to',   now()));
        $result = $this->reports->getProfitLossReport($from, $to);

        return view('erp.reports.profit-loss', compact('result','from','to'));
    }

    public function customerStatement(Request $request, Customer $customer): View
    {
        $this->authorize('erp.reports.view', \App\Models\User::class);

        $from      = Carbon::parse($request->get('from', now()->startOfMonth()));
        $to        = Carbon::parse($request->get('to',   now()->endOfMonth()));
        $statement = $this->reports->getCustomerStatement($customer, $from, $to);

        return view('erp.reports.customer-statement', compact('customer','statement','from','to'));
    }

    public function export(Request $request): Response
    {
        $this->authorize('erp.reports.export', \App\Models\User::class);

        // Delegate to ReportService with Excel strategy
        $strategy = new ExcelExportStrategy();
        // ... build data based on request->type and call $this->reports->export(...)
        abort(501, 'Export implementation depends on report type');
    }
}
```

### `app/Http/Controllers/Erp/ExpenseController.php`
```php
<?php
namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\Erp\ExpenseService;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

/**
 * Expense CRUD controller.
 *
 * @package App\Http\Controllers\Erp
 */
class ExpenseController extends Controller
{
    public function __construct(private readonly ExpenseService $service)
    {
        $this->authorizeResource(Expense::class, 'expense');
    }

    public function index(Request $request): View
    {
        $expenses = $this->service->list($request->only([
            'category','date_from','date_to','search',
        ]));

        $monthSummary = $this->service->getMonthlySummary(
            now()->year, now()->month
        );

        return view('erp.expenses.index', compact('expenses','monthSummary'));
    }

    public function create(): View
    {
        return view('erp.expenses.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category'     => ['required','string','max:100'],
            'amount'       => ['required','integer','min:1'],
            'expense_date' => ['required','date','before_or_equal:today'],
            'description'  => ['required','string','max:500'],
            'reference'    => ['nullable','string','max:100'],
            'attachment'   => ['nullable','image','mimes:jpg,jpeg,png,pdf','max:5120'],
        ]);

        $this->service->create($data, $request->file('attachment'));

        return redirect()
            ->route('erp.expenses.index')
            ->with('success', __('expenses.created'));
    }

    public function edit(Expense $expense): View
    {
        return view('erp.expenses.form', compact('expense'));
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $data = $request->validate([
            'category'     => ['required','string','max:100'],
            'amount'       => ['required','integer','min:1'],
            'expense_date' => ['required','date'],
            'description'  => ['required','string','max:500'],
            'reference'    => ['nullable','string','max:100'],
            'attachment'   => ['nullable','image','mimes:jpg,jpeg,png,pdf','max:5120'],
        ]);

        $this->service->update($expense, $data, $request->file('attachment'));

        return redirect()
            ->route('erp.expenses.index')
            ->with('success', __('expenses.updated'));
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->service->delete($expense);

        return redirect()
            ->route('erp.expenses.index')
            ->with('success', __('expenses.deleted'));
    }
}
```

### `app/Http/Controllers/Distribution/TruckController.php`
```php
<?php
namespace App\Http\Controllers\Distribution;

use App\Http\Controllers\Controller;
use App\Models\Truck;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

/**
 * Truck CRUD controller.
 *
 * @package App\Http\Controllers\Distribution
 */
class TruckController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:shipments.create')->except(['index','show']);
    }

    public function index(): View
    {
        $this->authorize('viewAny', \App\Models\Shipment::class);

        $trucks = Truck::withCount(['shipments' => fn($q) =>
            $q->whereIn('status', ['planned','loading','dispatched'])
        ])->latest()->paginate(20);

        return view('distribution.trucks.index', compact('trucks'));
    }

    public function create(): View
    {
        return view('distribution.trucks.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plate_number'  => ['required','string','max:30','unique:trucks,plate_number'],
            'model'         => ['nullable','string','max:100'],
            'capacity_kg'   => ['nullable','numeric','min:0'],
            'capacity_units'=> ['nullable','integer','min:0'],
            'notes'         => ['nullable','string','max:500'],
        ]);

        Truck::create(array_merge($data, ['status' => 'available', 'is_active' => true]));

        return redirect()
            ->route('trucks.index')
            ->with('success', __('trucks.created'));
    }

    public function edit(Truck $truck): View
    {
        return view('distribution.trucks.form', compact('truck'));
    }

    public function update(Request $request, Truck $truck): RedirectResponse
    {
        $data = $request->validate([
            'plate_number'  => ['required','string','max:30',
                                "unique:trucks,plate_number,{$truck->id}"],
            'model'         => ['nullable','string','max:100'],
            'capacity_kg'   => ['nullable','numeric','min:0'],
            'capacity_units'=> ['nullable','integer','min:0'],
            'status'        => ['required','in:available,maintenance,inactive'],
            'is_active'     => ['boolean'],
            'notes'         => ['nullable','string','max:500'],
        ]);

        $truck->update($data);

        return redirect()
            ->route('trucks.index')
            ->with('success', __('trucks.updated'));
    }

    public function destroy(Truck $truck): RedirectResponse
    {
        if ($truck->status === 'on_trip') {
            return back()->with('error', __('trucks.cannot_delete_on_trip'));
        }

        $truck->delete();

        return redirect()
            ->route('trucks.index')
            ->with('success', __('trucks.deleted'));
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION E — EVENTS & LISTENERS                      ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Events/Orders/OrderAccepted.php`
```php
<?php
namespace App\Events\Orders;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an order transitions from pending → accepted.
 * Triggers: NotifyCustomerOnOrderStatusChange listener.
 *
 * @package App\Events\Orders
 */
class OrderAccepted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Order $order) {}
}
```

### `app/Events/Orders/OrderCancelled.php`
```php
<?php
namespace App\Events\Orders;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired when an order is cancelled. */
class OrderCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Order $order) {}
}
```

### `app/Events/Orders/OrderDelivered.php`
```php
<?php
namespace App\Events\Orders;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired when an order is confirmed as delivered. */
class OrderDelivered
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Order $order) {}
}
```

### `app/Events/Orders/OrderCreated.php`
```php
<?php
namespace App\Events\Orders;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired when a new order is created. */
class OrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Order $order) {}
}
```

### `app/Events/Invoices/InvoiceIssued.php`
```php
<?php
namespace App\Events\Invoices;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired when an invoice transitions from draft → issued. */
class InvoiceIssued
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Invoice $invoice) {}
}
```

### `app/Events\Invoices\PaymentReceived.php`
```php
<?php
namespace App\Events\Invoices;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired when a payment is successfully recorded against an invoice. */
class PaymentReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Payment $payment) {}
}
```

### `app/Events/Stock/LowStockDetected.php`
```php
<?php
namespace App\Events\Stock;

use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired when a product's stock crosses below its threshold. */
class LowStockDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Product $product) {}
}
```

### `app/Listeners/Orders/NotifyCustomerOnOrderStatusChange.php`
```php
<?php
namespace App\Listeners\Orders;

use App\Events\Orders\{OrderAccepted, OrderCancelled};
use App\Notifications\OrderStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Sends notification to customer when their order status changes.
 * Queued asynchronously — does not block the web request.
 *
 * @package App\Listeners\Orders
 */
class NotifyCustomerOnOrderStatusChange implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(OrderAccepted|OrderCancelled $event): void
    {
        $order    = $event->order;
        $customer = $order->customer;

        if (! $customer?->user) {
            return; // No portal account — no notification
        }

        $fromStatus = match(true) {
            $event instanceof OrderAccepted  => 'pending',
            $event instanceof OrderCancelled => $order->getOriginal('status') ?? 'unknown',
            default                          => 'unknown',
        };

        $customer->user->notify(new OrderStatusChanged($order, $fromStatus));
    }
}
```

### `app/Listeners/Invoices/UpdateCustomerBalanceOnInvoiceIssued.php`
```php
<?php
namespace App\Listeners\Invoices;

use App\Events\Invoices\InvoiceIssued;
use App\Services\Customers\CustomerService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Recalculates customer outstanding balance when an invoice is issued.
 * Queued to avoid blocking the web request.
 *
 * @package App\Listeners\Invoices
 */
class UpdateCustomerBalanceOnInvoiceIssued implements ShouldQueue
{
    public string $queue = 'default';

    public function __construct(private readonly CustomerService $customerService) {}

    public function handle(InvoiceIssued $event): void
    {
        $this->customerService->recalculateBalance(
            $event->invoice->customer
        );
    }
}
```

### `app/Listeners/Invoices/NotifyCustomerOnPaymentReceived.php`
```php
<?php
namespace App\Listeners\Invoices;

use App\Events\Invoices\PaymentReceived;
use App\Notifications\PaymentReceived as PaymentReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Notifies the customer when their payment is confirmed.
 *
 * @package App\Listeners\Invoices
 */
class NotifyCustomerOnPaymentReceived implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(PaymentReceived $event): void
    {
        $customer = $event->payment->customer;

        if (! $customer?->user) {
            return;
        }

        $customer->user->notify(new PaymentReceivedNotification($event->payment));
    }
}
```

### `app/Listeners/Stock/SendLowStockAlert.php`
```php
<?php
namespace App\Listeners\Stock;

use App\Events\Stock\LowStockDetected;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

/**
 * Sends low stock alert to accountants when a product hits threshold.
 * Debounced per product: only one alert per product per hour.
 *
 * @package App\Listeners\Stock
 */
class SendLowStockAlert implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(LowStockDetected $event): void
    {
        $product  = $event->product;
        $cacheKey = "low_stock_alerted:{$product->id}";

        // Debounce: don't spam if already alerted in the last hour
        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, 3600); // 1 hour

        $recipients = User::role(['accountant','super_admin'])
            ->where('is_active', true)
            ->get();

        foreach ($recipients as $user) {
            $user->notify(new LowStockAlert(collect([$product])));
        }
    }
}
```

### `app/Observers/InvoiceObserver.php`
```php
<?php
namespace App\Observers;

use App\Models\Invoice;

/**
 * Observer for Invoice model events.
 * Logs all status changes and financial mutations.
 * Regenerates PDF when invoice totals change.
 *
 * @package App\Observers
 */
class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        activity('invoices')
            ->performedOn($invoice)
            ->causedBy(auth()->user())
            ->withProperties([
                'number' => $invoice->invoice_number,
                'total'  => $invoice->total_amount,
                'status' => $invoice->status,
            ])
            ->log('تم إنشاء فاتورة جديدة');
    }

    public function updated(Invoice $invoice): void
    {
        $changes = $invoice->getDirty();

        if (isset($changes['status'])) {
            activity('invoices')
                ->performedOn($invoice)
                ->causedBy(auth()->user())
                ->withProperties([
                    'from' => $invoice->getOriginal('status'),
                    'to'   => $changes['status'],
                ])
                ->log('تم تغيير حالة الفاتورة');
        }

        // Regenerate PDF if financial data changed
        if (array_intersect(array_keys($changes), ['total_amount','paid_amount','status'])) {
            dispatch(function () use ($invoice) {
                app(\App\Services\PdfService::class)->generateInvoice($invoice);
            })->afterResponse();
        }
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION F — REMAINING FACTORIES                     ║
## ╚══════════════════════════════════════════════════════════════╝

### `database/factories/TruckFactory.php`
```php
<?php
namespace Database\Factories;

use App\Models\Truck;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Truck> */
class TruckFactory extends Factory
{
    protected $model = Truck::class;

    public function definition(): array
    {
        return [
            'plate_number'   => $this->faker->unique()->bothify('??-####'),
            'model'          => $this->faker->randomElement(['إيسوزو','تويوتا هاياس','نيسان كابستار']),
            'capacity_kg'    => $this->faker->randomFloat(2, 500, 5000),
            'capacity_units' => $this->faker->numberBetween(20, 200),
            'status'         => 'available',
            'is_active'      => true,
        ];
    }

    public function available(): static
    {
        return $this->state(['status' => 'available']);
    }

    public function onTrip(): static
    {
        return $this->state(['status' => 'on_trip']);
    }

    public function maintenance(): static
    {
        return $this->state(['status' => 'maintenance']);
    }
}
```

### `database/factories/DriverFactory.php`
```php
<?php
namespace Database\Factories;

use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Driver> */
class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        return [
            'name'            => $this->faker->name(),
            'phone'           => '09' . $this->faker->numerify('########'),
            'license_number'  => $this->faker->bothify('SY-####-??'),
            'license_expiry'  => $this->faker->dateTimeBetween('+6 months', '+3 years'),
            'is_active'       => true,
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
```

### `database/factories/ShipmentFactory.php`
```php
<?php
namespace Database\Factories;

use App\Models\{Driver, Shipment, Truck, User};
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Shipment> */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    private static int $seq = 1;

    public function definition(): array
    {
        return [
            'shipment_number' => sprintf('SHP-%d-%05d', now()->year, self::$seq++),
            'truck_id'        => Truck::factory(),
            'driver_id'       => Driver::factory(),
            'shipment_date'   => today(),
            'status'          => 'planned',
            'created_by'      => User::factory(),
        ];
    }

    public function planned(): static
    {
        return $this->state(['status' => 'planned']);
    }

    public function dispatched(): static
    {
        return $this->state([
            'status'         => 'dispatched',
            'departure_time' => now()->subHours(2),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status'      => 'completed',
            'return_time' => now()->subHour(),
        ]);
    }
}
```

### `database/factories/InvoiceFactory.php`
```php
<?php
namespace Database\Factories;

use App\Models\{Customer, Invoice, Order, User};
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Invoice> */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    private static int $seq = 1;

    public function definition(): array
    {
        $total = $this->faker->numberBetween(10_000, 500_000);
        return [
            'invoice_number' => sprintf('INV-%d-%05d', now()->year, self::$seq++),
            'order_id'       => Order::factory(),
            'customer_id'    => Customer::factory(),
            'type'           => 'sale',
            'status'         => 'issued',
            'issue_date'     => today(),
            'due_date'       => today()->addDays(30),
            'subtotal'       => $total,
            'discount_amount'=> 0,
            'tax_rate'       => 0,
            'tax_amount'     => 0,
            'total_amount'   => $total,
            'paid_amount'    => 0,
            'balance_due'    => $total,
            'created_by'     => User::factory(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn($a) => [
            'status'      => 'paid',
            'paid_amount' => $a['total_amount'],
            'balance_due' => 0,
        ]);
    }

    public function partial(): static
    {
        return $this->state(fn($a) => [
            'status'      => 'partial',
            'paid_amount' => (int) ($a['total_amount'] * 0.5),
            'balance_due' => (int) ($a['total_amount'] * 0.5),
        ]);
    }

    public function overdue(): static
    {
        return $this->state([
            'status'   => 'issued',
            'due_date' => now()->subDays(35),
        ]);
    }
}
```

### `database/factories/ExpenseFactory.php`
```php
<?php
namespace Database\Factories;

use App\Models\{Expense, User};
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Expense> */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'category'     => $this->faker->randomElement([
                'رواتب','وقود','صيانة','مواد خام','إيجار','كهرباء','اتصالات','متنوع',
            ]),
            'amount'       => $this->faker->numberBetween(5_000, 200_000),
            'expense_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'description'  => $this->faker->sentence(5),
            'created_by'   => User::factory(),
        ];
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION G — REMAINING SEEDERS                       ║
## ╚══════════════════════════════════════════════════════════════╝

### `database/seeders/AdminUserSeeder.php`
```php
<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the initial super_admin account.
 * Credentials: admin@factory.local / password
 * ⚠️ Change password immediately after first login.
 *
 * @package Database\Seeders
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@factory.local'],
            [
                'name'      => 'مدير النظام',
                'email'     => 'admin@factory.local',
                'phone'     => '0911000000',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $admin->assignRole('super_admin');

        // Also create a demo accountant
        $accountant = User::firstOrCreate(
            ['email' => 'accountant@factory.local'],
            [
                'name'      => 'المحاسب',
                'email'     => 'accountant@factory.local',
                'phone'     => '0922000000',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $accountant->assignRole('accountant');

        // Demo shipping staff
        $staff = User::firstOrCreate(
            ['email' => 'staff@factory.local'],
            [
                'name'      => 'موظف الشحن',
                'email'     => 'staff@factory.local',
                'phone'     => '0933000000',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $staff->assignRole('shipping_staff');

        $this->command->info('✓ Admin users seeded (3 accounts).');
    }
}
```

### `database/seeders/ProductCategorySeeder.php`
```php
<?php
namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

/**
 * Seeds default product categories.
 *
 * @package Database\Seeders
 */
class ProductCategorySeeder extends Seeder
{
    private const CATEGORIES = [
        ['name' => 'مواد غذائية',    'sort_order' => 1],
        ['name' => 'مشروبات',        'sort_order' => 2],
        ['name' => 'منظفات ومواد تنظيف', 'sort_order' => 3],
        ['name' => 'مواد بناء',      'sort_order' => 4],
        ['name' => 'أدوات ومعدات',   'sort_order' => 5],
        ['name' => 'مواد خام',       'sort_order' => 6],
        ['name' => 'منتجات طازجة',   'sort_order' => 7],
        ['name' => 'متنوع',          'sort_order' => 8],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            ProductCategory::firstOrCreate(
                ['name' => $category['name']],
                array_merge($category, ['is_active' => true])
            );
        }

        $this->command->info('✓ Product categories seeded (' . count(self::CATEGORIES) . ').');
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION H — REMAINING FORM REQUESTS                 ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Http/Requests/Products/UpdateProductRequest.php`
```php
<?php
namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates product update — unique rules exclude the current product.
 *
 * @package App\Http\Requests\Products
 */
class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products.edit');
    }

    public function rules(): array
    {
        $id = $this->route('product')->id;

        return [
            'code'                => ['required','string','max:50', Rule::unique('products')->ignore($id)],
            'name'                => ['required','string','max:200'],
            'category_id'         => ['nullable','integer','exists:product_categories,id'],
            'unit'                => ['required','string','max:30'],
            'description'         => ['nullable','string','max:2000'],
            'unit_price'          => ['required','integer','min:0'],
            'cost_price'          => ['required','integer','min:0'],
            'barcode'             => ['nullable','string','max:100', Rule::unique('products')->ignore($id)],
            'stock_quantity'      => ['required','integer','min:0'],
            'low_stock_threshold' => ['required','integer','min:0'],
            'is_active'           => ['boolean'],
            'sort_order'          => ['integer','min:0'],
            'image'               => ['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
        ];
    }
}
```

### `app/Http/Requests/Products/StockAdjustmentRequest.php`
```php
<?php
namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates manual stock adjustment requests.
 *
 * @package App\Http\Requests\Products
 */
class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products.adjust_stock');
    }

    public function rules(): array
    {
        return [
            'product_id'    => ['required','integer','exists:products,id'],
            'new_quantity'  => ['required','integer','min:0'],
            'reason'        => ['required','string','min:5','max:500'],
        ];
    }
}
```

### `app/Http/Requests/Orders/CancelOrderRequest.php`
```php
<?php
namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates order cancellation payload.
 *
 * @package App\Http\Requests\Orders
 */
class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('orders.cancel');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required','string','min:5','max:500'],
        ];
    }
}
```

### `app/Http/Requests/Customers/StoreCustomerRequest.php`
```php
<?php
namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates customer creation.
 *
 * @package App\Http\Requests\Customers
 */
class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('customers.create');
    }

    public function rules(): array
    {
        return [
            'name'             => ['required','string','max:150'],
            'business_name'    => ['nullable','string','max:200'],
            'phone'            => ['required','string','max:20','unique:customers,phone'],
            'phone_alt'        => ['nullable','string','max:20'],
            'email'            => ['nullable','email','max:150'],
            'address'          => ['required','string','max:500'],
            'city'             => ['nullable','string','max:100'],
            'region'           => ['nullable','string','max:100'],
            'category'         => ['required','in:A,B,C'],
            'credit_limit'     => ['required','integer','min:0'],
            'notes'            => ['nullable','string','max:1000'],
            'portal_access'    => ['boolean'],
            'portal_password'  => ['nullable','required_if:portal_access,true','string','min:8'],
        ];
    }
}
```

### `app/Http/Requests/Customers/UpdateCustomerRequest.php`
```php
<?php
namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates customer update — phone unique excludes current customer.
 *
 * @package App\Http\Requests\Customers
 */
class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('customers.edit');
    }

    public function rules(): array
    {
        $id = $this->route('customer')->id;

        return [
            'name'          => ['required','string','max:150'],
            'business_name' => ['nullable','string','max:200'],
            'phone'         => ['required','string','max:20', Rule::unique('customers')->ignore($id)],
            'phone_alt'     => ['nullable','string','max:20'],
            'email'         => ['nullable','email','max:150'],
            'address'       => ['required','string','max:500'],
            'city'          => ['nullable','string','max:100'],
            'region'        => ['nullable','string','max:100'],
            'category'      => ['required','in:A,B,C'],
            'credit_limit'  => ['required','integer','min:0'],
            'notes'         => ['nullable','string','max:1000'],
        ];
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION I — REMAINING TESTS                         ║
## ╚══════════════════════════════════════════════════════════════╝

### `tests/Unit/AmountToWordsTest.php`
```php
<?php
use App\Helpers\AmountToWords;

it('converts zero to Arabic word', function () {
    expect(AmountToWords::toArabic(0))->toBe('صفر');
});

it('converts simple numbers correctly', function (int $amount, string $expected) {
    expect(AmountToWords::toArabic($amount))->toBe($expected);
})->with([
    [1,       'واحد'],
    [10,      'عشرة'],
    [100,     'مئة'],
    [1000,    'واحد ألف'],
    [1000000, 'واحد مليون'],
]);

it('converts compound numbers', function () {
    $result = AmountToWords::toArabic(25_000);
    expect($result)->toContain('خمسة وعشرون')
        ->and($result)->toContain('ألف');
});

it('handles large amounts', function () {
    $result = AmountToWords::toArabic(1_500_000);
    expect($result)->toContain('مليون')
        ->and($result)->toContain('خمسمئة');
});
```

### `tests/Unit/OrderFinancialsServiceTest.php`
```php
<?php
use App\DTOs\Orders\OrderItemDTO;
use App\Services\Orders\OrderFinancialsService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $settings = Mockery::mock(SettingService::class);
    $settings->shouldReceive('get')->with('invoice_tax_rate', 0)->andReturn(0);
    $this->service = new OrderFinancialsService($settings);
});

it('calculates subtotal correctly', function () {
    $items = collect([
        new OrderItemDTO(1, 2, 50_000),   // 100,000
        new OrderItemDTO(2, 3, 20_000),   // 60,000
    ]);

    $totals = $this->service->calculateTotals($items);

    expect($totals['subtotal'])->toBe(160_000)
        ->and($totals['discount'])->toBe(0)
        ->and($totals['tax'])->toBe(0)
        ->and($totals['total'])->toBe(160_000);
});

it('applies line item discounts', function () {
    $items = collect([
        new OrderItemDTO(1, 2, 100_000, 10.0), // 200,000 - 10% = 180,000
    ]);

    $totals = $this->service->calculateTotals($items);

    expect($totals['subtotal'])->toBe(200_000)
        ->and($totals['discount'])->toBe(20_000)
        ->and($totals['total'])->toBe(180_000);
});

it('applies tax rate from settings', function () {
    $settings = Mockery::mock(SettingService::class);
    $settings->shouldReceive('get')->with('invoice_tax_rate', 0)->andReturn(15);
    $service = new OrderFinancialsService($settings);

    $items  = collect([new OrderItemDTO(1, 1, 100_000)]);
    $totals = $service->calculateTotals($items);

    expect($totals['tax'])->toBe(15_000)
        ->and($totals['total'])->toBe(115_000);
});
```

### `tests/Feature/AuthTest.php`
```php
<?php
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    RateLimiter::clear('login:127.0.0.1');
});

it('shows login page in Arabic', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('تسجيل الدخول');
});

it('logs in with valid email credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('password')])->assignRole('super_admin');

    $this->post(route('login'), ['login' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('erp.dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('logs in with valid phone credentials', function () {
    $user = User::factory()->create([
        'phone'    => '0912345678',
        'password' => bcrypt('password'),
    ])->assignRole('super_admin');

    $this->post(route('login'), ['login' => '0912345678', 'password' => 'password'])
        ->assertRedirect(route('erp.dashboard'));
});

it('blocks login with wrong password', function () {
    $user = User::factory()->create()->assignRole('super_admin');

    $this->post(route('login'), ['login' => $user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('login');
});

it('blocks inactive user login', function () {
    $user = User::factory()->create([
        'is_active' => false,
        'password'  => bcrypt('password'),
    ])->assignRole('super_admin');

    $this->post(route('login'), ['login' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('login');
});

it('redirects customers to portal after login', function () {
    $user = User::factory()->create(['password' => bcrypt('password')])->assignRole('customer');

    $this->post(route('login'), ['login' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('portal.dashboard'));
});

it('rate limits after 5 failed attempts', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login'), ['login' => $user->email, 'password' => 'wrong']);
    }

    $this->post(route('login'), ['login' => $user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('login');

    // Verify it mentions rate limiting
    expect(session('errors')->first('login'))->toContain('دقيقة');
});

it('updates last_login_at on successful login', function () {
    $user = User::factory()->create(['password' => bcrypt('password')])->assignRole('accountant');

    $this->post(route('login'), ['login' => $user->email, 'password' => 'password']);

    expect($user->fresh()->last_login_at)->not->toBeNull();
});
```

### `tests/Feature/ProductCrudTest.php`
```php
<?php
use App\Models\{Product, ProductCategory, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin    = User::factory()->create()->assignRole('super_admin');
    $this->category = ProductCategory::factory()->create();
    $this->actingAs($this->admin);
});

it('creates a product with auto-generated code', function () {
    $this->post(route('products.store'), [
        'name'                => 'منتج اختباري',
        'category_id'         => $this->category->id,
        'unit'                => 'كرتون',
        'unit_price'          => 50_000,
        'cost_price'          => 30_000,
        'stock_quantity'      => 100,
        'low_stock_threshold' => 10,
    ])->assertRedirect();

    $product = Product::where('name', 'منتج اختباري')->first();
    expect($product)->not->toBeNull()
        ->and($product->code)->toMatch('/^PRD-\d{4}-\d{5}$/');
});

it('uploads product image to public storage', function () {
    Storage::fake('public');

    $this->post(route('products.store'), [
        'name'                => 'منتج بصورة',
        'unit'                => 'كيس',
        'unit_price'          => 10_000,
        'cost_price'          => 6_000,
        'stock_quantity'      => 50,
        'low_stock_threshold' => 5,
        'image'               => UploadedFile::fake()->image('product.jpg'),
    ])->assertRedirect();

    $product = Product::where('name', 'منتج بصورة')->first();
    expect($product->image)->not->toBeNull();
    Storage::disk('public')->assertExists($product->image);
});

it('prevents duplicate product code', function () {
    Product::factory()->create(['code' => 'TEST-001']);

    $this->post(route('products.store'), [
        'code'                => 'TEST-001',
        'name'                => 'منتج مكرر',
        'unit'                => 'كيس',
        'unit_price'          => 1_000,
        'cost_price'          => 500,
        'stock_quantity'      => 10,
        'low_stock_threshold' => 2,
    ])->assertSessionHasErrors('code');
});

it('shipping_staff cannot create products', function () {
    $staff = User::factory()->create()->assignRole('shipping_staff');

    $this->actingAs($staff)
        ->post(route('products.store'), [
            'name' => 'test', 'unit' => 'test',
            'unit_price' => 1000, 'cost_price' => 500,
            'stock_quantity' => 10, 'low_stock_threshold' => 2,
        ])->assertForbidden();
});

it('soft-deletes a product and allows restore', function () {
    $product = Product::factory()->create();

    $this->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'));

    expect(Product::find($product->id))->toBeNull();
    expect(Product::withTrashed()->find($product->id))->not->toBeNull();

    $this->post(route('products.restore', $product->id))
        ->assertRedirect();

    expect(Product::find($product->id))->not->toBeNull();
});
```

### `tests/Feature/CustomerCrudTest.php`
```php
<?php
use App\Models\{Customer, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create()->assignRole('super_admin');
    $this->actingAs($this->admin);
});

it('creates a customer with auto-generated code', function () {
    $this->post(route('customers.store'), [
        'name'         => 'أحمد محمد',
        'phone'        => '0911111111',
        'address'      => 'دمشق',
        'category'     => 'B',
        'credit_limit' => 500_000,
    ])->assertRedirect();

    $customer = Customer::where('phone', '0911111111')->first();
    expect($customer)->not->toBeNull()
        ->and($customer->code)->toMatch('/^CUS-\d{4}$/');
});

it('enforces unique phone number', function () {
    Customer::factory()->create(['phone' => '0911111111']);

    $this->post(route('customers.store'), [
        'name'         => 'عميل جديد',
        'phone'        => '0911111111',
        'address'      => 'حلب',
        'category'     => 'A',
        'credit_limit' => 0,
    ])->assertSessionHasErrors('phone');
});

it('enables portal access and creates linked user', function () {
    $customer = Customer::factory()->create(['email' => 'test@example.com']);

    $this->post(route('customers.portal-access', $customer), [
        'password' => 'Test1234!',
    ])->assertRedirect();

    $customer->refresh();
    expect($customer->portal_access)->toBeTrue()
        ->and($customer->user_id)->not->toBeNull();
});

it('calculates credit availability correctly', function () {
    $customer = Customer::factory()->create([
        'credit_limit'        => 1_000_000,
        'outstanding_balance' => 300_000,
    ]);

    expect($customer->available_credit)->toBe(700_000)
        ->and($customer->canAcceptOrder(500_000))->toBeTrue()
        ->and($customer->canAcceptOrder(800_000))->toBeFalse();
});
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION J — KEY REMAINING VIEWS                     ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/layouts/app.blade.php` — Full implementation
```blade
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('app.modules.dashboard')) — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-cairo bg-gray-50 text-gray-900" dir="rtl">

    {{-- Sidebar navigation --}}
    @include('layouts.partials.sidebar')

    {{-- Main content area --}}
    <div class="lg:mr-64 min-h-screen flex flex-col">

        {{-- Top bar --}}
        @include('layouts.partials.topbar')

        {{-- Flash messages --}}
        @include('layouts.partials.alerts')

        {{-- Page content --}}
        <main class="flex-1 p-4 sm:p-6">
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="text-center text-xs text-gray-400 py-4 border-t border-gray-100">
            {{ config('app.name') }} &copy; {{ date('Y') }} — v1.0.0
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
```

### `resources/views/layouts/partials/topbar.blade.php`
```blade
{{-- Top navigation bar --}}
<header class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
    <div class="flex items-center justify-between px-4 sm:px-6 h-14">

        {{-- Mobile menu button --}}
        <button
            @click="$store.sidebar.toggle()"
            class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Page title slot --}}
        <div class="flex-1 px-4 lg:px-0">
            <h2 class="text-base font-semibold text-gray-700 hidden sm:block">
                @yield('page-title', __('app.modules.dashboard'))
            </h2>
        </div>

        {{-- Right actions --}}
        <div class="flex items-center gap-3">

            {{-- Notification bell --}}
            @livewire('shared.notification-bell')

            {{-- User avatar --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                        class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900">
                    <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 flex items-center
                                justify-center font-bold text-xs">
                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <span class="hidden sm:block max-w-24 truncate">{{ auth()->user()->name }}</span>
                </button>

                <div x-show="open" @click.away="open = false"
                     class="absolute left-0 mt-2 w-48 bg-white rounded-xl shadow-lg
                            border border-gray-100 py-1 z-50"
                     x-transition>
                    <div class="px-4 py-2 border-b border-gray-100">
                        <p class="text-xs font-medium text-gray-800">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full text-right px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            {{ __('auth.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
```

### `resources/views/layouts/partials/alerts.blade.php`
```blade
{{-- Flash message display — auto-dismisses after 5 seconds --}}
<div class="px-4 sm:px-6 pt-4 space-y-2" aria-live="polite">

    @if(session('success'))
        <div data-flash-dismiss
             class="flex items-center gap-3 p-3 bg-green-50 border border-green-200
                    rounded-lg text-green-800 text-sm">
            <span class="flex-shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div data-flash-dismiss
             class="flex items-center gap-3 p-3 bg-red-50 border border-red-200
                    rounded-lg text-red-800 text-sm">
            <span class="flex-shrink-0">✕</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if(session('warning'))
        <div data-flash-dismiss
             class="flex items-center gap-3 p-3 bg-yellow-50 border border-yellow-200
                    rounded-lg text-yellow-800 text-sm">
            <span class="flex-shrink-0">⚠</span>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    @if($errors->any() && ! $errors->has('login'))
        <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm">
            <p class="font-medium text-red-800 mb-1">يوجد أخطاء في البيانات المُدخلة:</p>
            <ul class="list-disc list-inside space-y-0.5 text-red-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

</div>
```

### `resources/views/products/index.blade.php`
```blade
@extends('layouts.app')
@section('title', __('app.modules.products'))
@section('page-title', 'إدارة المنتجات والبضاعة')

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">المنتجات والبضاعة</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $products->total() }} منتج إجمالاً
                @if($lowCount > 0)
                    · <span class="text-red-600 font-medium">{{ $lowCount }} منخفض المخزون</span>
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            @can('products.create')
                <a href="{{ route('products.create') }}" class="btn-primary">
                    + إضافة منتج
                </a>
            @endcan
        </div>
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label class="form-label">بحث</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-input" placeholder="الاسم، الكود، الباركود...">
            </div>
            <div class="min-w-40">
                <label class="form-label">الفئة</label>
                <select name="category_id" class="form-input">
                    <option value="">كل الفئات</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-36">
                <label class="form-label">الحالة</label>
                <select name="is_active" class="form-input">
                    <option value="">الكل</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>مفعّل</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>غير مفعّل</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label class="flex items-center gap-1.5 cursor-pointer text-sm">
                    <input type="checkbox" name="low_stock" value="1"
                           {{ request('low_stock') ? 'checked' : '' }}
                           class="rounded border-gray-300">
                    مخزون منخفض فقط
                </label>
            </div>
            <button type="submit" class="btn-secondary">تصفية</button>
            <a href="{{ route('products.index') }}" class="btn-ghost">مسح</a>
        </form>
    </div>

    {{-- Products Table --}}
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>الكود</th>
                    <th>الاسم</th>
                    <th>الفئة</th>
                    <th>الوحدة</th>
                    <th>سعر البيع</th>
                    @can('products.view_cost_price')
                        <th>سعر التكلفة</th>
                    @endcan
                    <th>المخزون</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td class="font-mono text-xs text-gray-500">{{ $product->code }}</td>
                    <td>
                        <div class="font-medium text-gray-900">{{ $product->name }}</div>
                        @if($product->barcode)
                            <div class="text-xs text-gray-400">{{ $product->barcode }}</div>
                        @endif
                    </td>
                    <td class="text-gray-600">{{ $product->category?->name ?? '—' }}</td>
                    <td class="text-gray-600">{{ $product->unit }}</td>
                    <td class="tabular-nums font-medium">{{ money_format($product->unit_price) }}</td>
                    @can('products.view_cost_price')
                        <td class="tabular-nums text-gray-500">{{ money_format($product->cost_price) }}</td>
                    @endcan
                    <td>
                        @php($status = $product->stock_status)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs
                            {{ $status === 'ok' ? 'stock-ok' : ($status === 'low' ? 'stock-low' : 'stock-zero') }}">
                            {{ $product->stock_quantity }} {{ $product->unit }}
                        </span>
                    </td>
                    <td>
                        <x-badge :status="$product->is_active ? 'active' : 'inactive'" size="sm" />
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('products.show', $product) }}"
                               class="btn btn-ghost btn-sm">عرض</a>
                            @can('products.edit')
                                <a href="{{ route('products.edit', $product) }}"
                                   class="btn btn-ghost btn-sm">تعديل</a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-gray-400">
                            لا توجد منتجات مطابقة للبحث
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$products" />

</div>
@endsection
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION K — REMAINING CONFIG ADDITIONS              ║
## ╚══════════════════════════════════════════════════════════════╝

### Add to `config/factory.php` (truck statuses):
```php
'truck_statuses' => [
    'available'   => 'متاحة',
    'on_trip'     => 'في الطريق',
    'maintenance' => 'صيانة',
    'inactive'    => 'غير نشطة',
],

'customer_categories' => [
    'A' => ['label' => 'فئة A', 'description' => 'عملاء مميزون — أعلى حد ائتماني'],
    'B' => ['label' => 'فئة B', 'description' => 'عملاء عاديون'],
    'C' => ['label' => 'فئة C', 'description' => 'عملاء جدد أو محدودو الائتمان'],
],
```

### `config/money.php`
```php
<?php
/**
 * Money configuration.
 * All monetary values stored as integers (smallest currency unit).
 * This file documents the convention — helpers read from factory.php.
 */
return [
    'default_currency'  => env('FACTORY_CURRENCY', 'SYP'),
    'currencies'        => [
        'SYP' => ['symbol' => 'ل.س', 'name' => 'الليرة السورية', 'precision' => 0],
        'USD' => ['symbol' => '$',    'name' => 'الدولار الأمريكي', 'precision' => 2],
    ],
    // IMPORTANT: All values stored in the DB are already in the smallest unit.
    // SYP has no sub-unit (0 decimal places), so amounts are stored as whole lirat.
    // If the factory later switches to USD (100 cents), update precision here.
    'storage_precision' => 0,
];
```

### `config/pdf.php`
```php
<?php
/**
 * PDF generation configuration.
 * Applied on top of the default DomPDF vendor config.
 */
return [
    'paper_size'        => 'a4',
    'orientation'       => 'portrait',
    'default_font'      => 'dejavu sans',
    'dpi'               => 150,
    'unicode'           => true,
    'font_subsetting'   => true,
    'remote_enabled'    => true,  // Allows logo loading from storage
    'storage_path'      => 'private/pdfs',
    'fonts_dir'         => resource_path('fonts/'),
];
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION L — NOTIFICATION: INVOICE OVERDUE           ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Notifications/InvoiceOverdue.php`
```php
<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Daily digest of overdue invoices sent to accountants.
 *
 * @package App\Notifications
 */
class InvoiceOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param Collection $invoices  Overdue Invoice models */
    public function __construct(private readonly Collection $invoices) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("تنبيه: {$this->invoices->count()} فواتير متأخرة السداد")
            ->view('emails.invoice-overdue', [
                'invoices'   => $this->invoices,
                'notifiable' => $notifiable,
                'totalDue'   => (int) $this->invoices->sum('balance_due'),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'      => 'invoice_overdue',
            'count'     => $this->invoices->count(),
            'total_due' => (int) $this->invoices->sum('balance_due'),
            'message'   => "{$this->invoices->count()} فواتير متأخرة بإجمالي " .
                           money_format((int) $this->invoices->sum('balance_due')),
            'url'       => route('erp.reports.receivables'),
        ];
    }
}
```

### `app/Notifications/PasswordReset.php`
```php
<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sends a temporary password to a user after admin-initiated reset.
 *
 * @package App\Notifications
 */
class PasswordReset extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $temporaryPassword) {}

    public function via(object $notifiable): array
    {
        return $notifiable->email ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('تم إعادة تعيين كلمة مرورك')
            ->view('emails.password-reset', [
                'notifiable'       => $notifiable,
                'temporaryPassword'=> $this->temporaryPassword,
                'loginUrl'         => route('login'),
            ]);
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION M — FINAL INTEGRATION CHECKLIST             ║
## ╚══════════════════════════════════════════════════════════════╝

### Pre-commit verification — run before every session end:

```bash
# ── Full system health check ─────────────────────────────────────────────────

# 1. No PHP syntax errors
find app/ -name "*.php" | xargs -P4 php -l | grep -v "No syntax errors" || true

# 2. No file exceeds 400 lines
find app/ resources/views/ -name "*.php" -o -name "*.blade.php" | \
  xargs wc -l | awk '$1 > 400 {print}' | grep -v "total"

# 3. No float used for money (check models, services, controllers)
grep -rn "float\|decimal\|0\.\d\+" app/Models/ app/Services/ | \
  grep -v "tax_rate\|discount_percent\|capacity_kg\|SKILL\|comment" || \
  echo "✓ No float money values"

# 4. No raw array in service methods (should use DTOs)
grep -rn "function.*array \$data" app/Services/ | \
  grep -v "BaseService\|export\|filter\|getMonthlySummary" || echo "✓ DTOs used"

# 5. No authorize() missing in controllers
grep -rL "authorize\|authorizeResource" app/Http/Controllers/**/*.php 2>/dev/null || \
  echo "✓ All controllers have authorization"

# 6. All migrations run cleanly
php artisan migrate:fresh --seed 2>&1 | tail -5

# 7. Full test suite
php artisan test --stop-on-failure 2>&1 | tail -20

# 8. Route cache works
php artisan route:cache && echo "✓ Route cache OK" && php artisan route:clear

# 9. Config cache works
php artisan config:cache && echo "✓ Config cache OK" && php artisan config:clear

# 10. View cache works
php artisan view:cache && echo "✓ View cache OK" && php artisan view:clear
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         ABSOLUTE FINAL CROSS-REFERENCE                      ║
## ╚══════════════════════════════════════════════════════════════╝

### What each Part contributes — quick reference for the agent:

```
PART 1 (86KB) — Foundation
  Architecture laws · SOLID enforcement · Design patterns (12)
  All 5 management .md files
  18-phase execution plan with start/complete protocols
  Full directory tree (170+ files)
  Phase 00-07 detailed instructions
  config/factory.php · .env template · Docker setup

PART 2 (118KB) — Core Logic
  All 7 DTOs (CreateOrderDTO, OrderItemDTO, RecordPaymentDTO, etc.)
  OrderRepository · ProductRepository (with search)
  OrderService · OrderStatusService · OrderFinancialsService
  InvoiceService · SettingService
  All 3 pipeline pipes (credit, stock, price snapshot)
  OrderController · OrderStatusController
  StoreOrderRequest · StoreProductRequest
  OrderItemsTable Livewire · CustomerBalanceChecker Livewire
  orders/show.blade.php + partials
  x-badge, x-kpi-card, x-btn components
  Invoice PDF template (full Arabic RTL)
  StockServiceTest · OrderStateMachineTest
  OrderLifecycleTest · RoleAccessTest · InvoicePaymentTest
  Complete routes/web.php (60+ routes)
  MoneyHelper · AmountToWords · CodeGeneratorFactory
  lang/ar/orders.php · lang/ar/invoices.php · lang/ar/validation.php

PART 3 (111KB) — Models, Observers, Notifications
  HasMoneyFormatting · HasSoftDeleteGuard (traits)
  Product · Customer · Invoice · Shipment models (full)
  OrderObserver · ProductObserver · PaymentObserver
  EventServiceProvider (observers + event→listener map)
  OrderStatusChanged · LowStockAlert · PaymentReceived notifications
  PdfService (full) · AmountToWords
  ReportService (getSalesSummary, receivablesAging, P&L, statement)
  Artisan commands: overdue-alerts, low-stock-check
  routes/console.php (scheduled jobs)
  resources/js/app.js · resources/js/charts.js · tailwind.config.js
  resources/css/app.css
  docker-compose.yml · Dockerfile · nginx.conf · supervisor.conf · deploy.sh
  RolesAndPermissionsSeeder · SystemSettingsSeeder · DatabaseSeeder · DemoDataSeeder
  erp/dashboard.blade.php
  README.md · CHANGELOG.md
  OrderFactory · ProductFactory · CustomerFactory

PART 4 (135KB) — Auth, Policies, Distribution
  All 7 policies (Order, Invoice, Payment, Product, Customer, Shipment, Expense)
  AuthServiceProvider (Gate::before super_admin bypass)
  All 4 middleware + bootstrap/app.php registration
  LoginController (email OR phone, rate limiting, last_login tracking)
  ShipmentService (full: create, attach, detach, dispatch, complete, cancel)
  CustomerService (full: CRUD, portal access, balance recalculation)
  ShipmentController · ShipmentOrderController
  InvoiceController · PaymentController
  SettingController · UserController · AuditLogController
  NotificationBell Livewire · ProductSearch Livewire
  Email templates (layout, order-status, invoice-issued, payment-confirmed)
  Error pages (404, 403, 500, maintenance)
  ExcelExportStrategy · CsvExportStrategy
  Setting facade
  vite.config.js · pest.php · phpunit.xml · tests/TestCase.php
  ShipmentStateMachine · CreateShipmentDTO
  RecordPaymentRequest · StoreShipmentRequest · AttachOrdersRequest
  TruckFactory · DriverFactory · ShipmentFactory · InvoiceFactory · ExpenseFactory
  ShipmentFlowTest · PdfDownloadTest
  Complete file manifest (all 170+ files listed)
  lang/ar/auth.php · lang/ar/app.php · lang/ar/shipments.php

PART 5 (this file) — Remaining Pieces
  Truck · Driver · OrderItem · StockMovement · Expense · SystemSetting models
  ProductCategory model
  InvoiceRepository · ShipmentRepository · CustomerRepository · StockMovementRepository
  ProductService · ExpenseService
  ProductController · StockController · CustomerController
  DashboardController · ReportController · ExpenseController · TruckController
  Events (OrderAccepted, OrderCancelled, OrderDelivered, OrderCreated, etc.)
  Listeners (NotifyCustomer, UpdateBalance, SendLowStockAlert)
  InvoiceObserver
  ExpenseFactory · TruckFactory · DriverFactory (confirmed)
  AdminUserSeeder · ProductCategorySeeder
  UpdateProductRequest · StockAdjustmentRequest · CancelOrderRequest
  StoreCustomerRequest · UpdateCustomerRequest
  AmountToWordsTest · OrderFinancialsServiceTest
  AuthTest · ProductCrudTest · CustomerCrudTest (full)
  layouts/app.blade.php · topbar.blade.php · alerts.blade.php
  products/index.blade.php
  config/money.php · config/pdf.php (full)
  InvoiceOverdue notification · PasswordReset notification
  Pre-commit verification script
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         FINAL AGENT INSTRUCTION — PARTS 1–5 COMPLETE        ║
## ╚══════════════════════════════════════════════════════════════╝

```
THE SYSTEM IS NOW 100% SPECIFIED.
ALL 5 PARTS TOGETHER = ZERO INFERENCE REQUIRED.

TOTAL SPECIFICATION:
  5 files · ~16,000 lines · ~560KB
  170+ files to implement
  18 execution phases
  18 test files
  ≥ 80% test coverage target

WHEN YOU BEGIN:
  Read all 5 parts COMPLETELY before writing one line of code.
  Create AGENT.md, PROGRESS.md, TODO.md first.
  Follow Phase 00 → 18 sequentially.
  Never skip a phase.
  Run tests after every module.

THE 8 IMMOVABLE LAWS:
  1. Max 400 lines per file — split if approaching 350
  2. Money = BIGINT always — never float
  3. No business logic in controllers — use services
  4. No Eloquent in services — use repositories
  5. All DB writes in DB::transaction() — no exceptions
  6. Every controller action: $this->authorize() — required
  7. Arabic text in lang/ar/ only — never hardcoded in PHP
  8. Paginate all lists — never ->get() on unbounded queries

ابدأ بقراءة الأجزاء الخمسة كاملاً قبل أي كود.
ثم أنشئ ملفات الإدارة.
ثم ابدأ Phase 00.
النجاح = اتباع البروتوكول بدقة.
```

---

*PART 5 OF 5 — MASTER AGENT PROMPT v1.0.0 — COMPLETE*
*نظام إدارة معمل التوزيع والشحن*
*All 5 parts together: ~16,000 lines · 170+ files · Zero ambiguity*
*May 2026 — Ready for OpenCode / Aider / Claude Code execution*
