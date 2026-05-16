<?php

namespace App\Models;

use App\Models\Traits\HasMoneyFormatting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockMovement extends Model
{
    use HasFactory, HasMoneyFormatting, SoftDeletes;

    /** @var array<int, string> */
    protected array $moneyColumns = ['unit_cost'];

    /** @var array<int, string> */
    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_type',
        'reference_id',
        'unit_cost',
        'notes',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'unit_cost' => 'integer',
        ];
    }

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

    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public function getTypeLabelAttribute(): string
    {
        return __('stock_movements.types.'.$this->type);
    }

    public function isIncoming(): bool
    {
        return in_array($this->type, ['in', 'return'], true);
    }
}
