<?php

namespace App\Models;

use App\Models\Traits\GeneratesSequentialCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use GeneratesSequentialCode, HasFactory, SoftDeletes;

    protected string $codePrefix = 'SHP';

    protected string $codeColumn = 'shipment_number';

    /** @var array<int, string> */
    protected $fillable = [
        'shipment_number',
        'truck_id',
        'driver_id',
        'shipment_date',
        'status',
        'departure_time',
        'return_time',
        'manifest_path',
        'notes',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'shipment_date' => 'date',
            'departure_time' => 'datetime',
            'return_time' => 'datetime',
        ];
    }

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

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['planned', 'loading', 'dispatched']);
    }

    public function scopeForDate(Builder $query, mixed $date): Builder
    {
        return $query->whereDate('shipment_date', $date);
    }

    public function scopeByStatus(Builder $query, string|array $status): Builder
    {
        return is_array($status)
            ? $query->whereIn('status', $status)
            : $query->where('status', $status);
    }

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

        return $total === 0 ? 0.0 : round(($this->delivered_count / $total) * 100, 1);
    }

    public function allOrdersResolved(): bool
    {
        return ! $this->orders()
            ->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
            ->exists();
    }

    public function canBeDispatched(): bool
    {
        return $this->status === 'planned' && $this->orders()->where('status', 'ready')->exists();
    }
}
