<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Truck extends Model
{
    use HasFactory, SoftDeletes;

    /** @var array<int, string> */
    protected $fillable = [
        'plate_number',
        'model',
        'capacity_kg',
        'capacity_units',
        'status',
        'notes',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'capacity_kg' => 'decimal:2',
            'capacity_units' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available')->where('is_active', true);
    }

    public function getStatusLabelAttribute(): string
    {
        return config("factory.truck_statuses.{$this->status}", $this->status);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->is_active;
    }

    public function hasActiveShipment(): bool
    {
        return $this->shipments()
            ->whereDate('shipment_date', today())
            ->whereIn('status', ['planned', 'loading', 'dispatched'])
            ->exists();
    }
}
