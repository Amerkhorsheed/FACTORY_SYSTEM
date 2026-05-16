<?php

namespace App\Models;

use App\Models\Traits\GeneratesSequentialCode;
use App\Models\Traits\HasMoneyFormatting;
use App\Models\Traits\HasSoftDeleteGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use GeneratesSequentialCode, HasFactory, HasMoneyFormatting, HasSoftDeleteGuard, SoftDeletes;

    protected string $codePrefix = 'PRD';

    protected string $codeColumn = 'code';

    /** @var array<int, string> */
    protected array $moneyColumns = ['unit_price', 'cost_price'];

    /** @var array<int, string> */
    protected $fillable = [
        'category_id',
        'code',
        'name',
        'description',
        'unit',
        'unit_price',
        'cost_price',
        'barcode',
        'image',
        'stock_quantity',
        'low_stock_threshold',
        'is_active',
        'sort_order',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'cost_price' => 'integer',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function activeOrderItems(): HasMany
    {
        return $this->orderItems()->whereHas(
            'order',
            fn (Builder $query): Builder => $query->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
        );
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

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
        return match (true) {
            $this->is_out_of_stock => 'out_of_stock',
            $this->is_low_stock => 'low',
            default => 'ok',
        };
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    /** @return array<string, string> */
    protected function getActiveRelationChecks(): array
    {
        return ['activeOrderItems' => __('products.has_active_orders')];
    }
}
