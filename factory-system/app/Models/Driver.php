<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    /** @var array<int, string> */
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'license_number',
        'license_expiry',
        'is_active',
        'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
            'is_active' => 'boolean',
        ];
    }

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
        return $this->license_expiry !== null && $this->license_expiry->isPast();
    }

    public function hasActiveShipment(): bool
    {
        return $this->shipments()
            ->whereDate('shipment_date', today())
            ->whereIn('status', ['planned', 'loading', 'dispatched'])
            ->exists();
    }
}
