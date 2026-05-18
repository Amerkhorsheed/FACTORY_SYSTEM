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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use GeneratesSequentialCode, HasFactory, HasMoneyFormatting, HasSoftDeleteGuard, SoftDeletes;

    protected string $codePrefix = 'ORD';

    protected string $codeColumn = 'order_number';

    /** @var array<int, string> */
    protected array $moneyColumns = ['subtotal', 'discount_amount', 'tax_amount', 'total_amount', 'paid_amount'];

    /** @var array<int, string> */
    protected $fillable = [
        'order_number',
        'customer_id',
        'shipment_id',
        'status',
        'order_date',
        'requested_delivery_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'notes',
        'cancel_reason',
        'returned_at',
        'return_notes',
        'accepted_by',
        'accepted_at',
        'shipped_by',
        'shipped_at',
        'delivered_at',
        'delivered_by',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'requested_delivery_date' => 'date',
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'tax_amount' => 'integer',
            'total_amount' => 'integer',
            'paid_amount' => 'integer',
            'returned_at' => 'datetime',
            'accepted_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function acceptedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function shippedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    public function deliveredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeByStatus(Builder $query, string|array $status): Builder
    {
        return is_array($status)
            ? $query->whereIn('status', $status)
            : $query->where('status', $status);
    }

    public function scopeForToday(Builder $query): Builder
    {
        return $query->whereDate('order_date', today());
    }

    public function scopeForDate(Builder $query, mixed $date): Builder
    {
        return $query->whereDate('order_date', $date);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function getPaymentStatusAttribute(): string
    {
        return match (true) {
            $this->paid_amount >= $this->total_amount => 'paid',
            $this->paid_amount > 0 => 'partial',
            default => 'unpaid',
        };
    }

    public function getBalanceDueAttribute(): int
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['pending', 'accepted'], true);
    }

    public function isCancellable(): bool
    {
        return ! in_array($this->status, ['shipped', 'delivered', 'cancelled', 'returned'], true);
    }
}
