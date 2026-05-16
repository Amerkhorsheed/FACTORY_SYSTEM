<?php

namespace App\Models;

use App\Models\Traits\HasMoneyFormatting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasFactory, HasMoneyFormatting, SoftDeletes;

    /** @var array<int, string> */
    protected array $moneyColumns = ['unit_price', 'discount_amount', 'line_total'];

    /** @var array<int, string> */
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'line_total',
        'returned_qty',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'integer',
            'line_total' => 'integer',
            'returned_qty' => 'integer',
        ];
    }

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
        return max(0, $this->quantity - $this->returned_qty);
    }
}
