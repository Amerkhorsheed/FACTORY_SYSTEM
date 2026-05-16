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

class Customer extends Model
{
    use GeneratesSequentialCode, HasFactory, HasMoneyFormatting, HasSoftDeleteGuard, SoftDeletes;

    protected string $codePrefix = 'CUS';

    /** @var array<int, string> */
    protected array $moneyColumns = ['credit_limit', 'outstanding_balance'];

    /** @var array<int, string> */
    protected $fillable = [
        'user_id',
        'code',
        'name',
        'business_name',
        'phone',
        'phone_alt',
        'email',
        'address',
        'city',
        'region',
        'category',
        'credit_limit',
        'outstanding_balance',
        'notes',
        'is_active',
        'portal_access',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'credit_limit' => 'integer',
            'outstanding_balance' => 'integer',
            'is_active' => 'boolean',
            'portal_access' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function activeOrders(): HasMany
    {
        return $this->orders()->whereNotIn('status', ['delivered', 'cancelled', 'returned']);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

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

    public function canAcceptOrder(int $orderAmount): bool
    {
        return $this->credit_limit === 0 || $orderAmount <= $this->available_credit;
    }

    public function getAvailableCreditAttribute(): int
    {
        return max(0, $this->credit_limit - $this->outstanding_balance);
    }

    public function getCreditUsagePercentAttribute(): float
    {
        return $this->credit_limit === 0
            ? 0.0
            : round(($this->outstanding_balance / $this->credit_limit) * 100, 1);
    }

    /** @return array<string, string> */
    protected function getActiveRelationChecks(): array
    {
        return ['activeOrders' => __('customers.has_active_orders')];
    }
}
