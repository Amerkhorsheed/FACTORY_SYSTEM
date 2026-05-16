<?php

namespace App\Models;

use App\Models\Traits\GeneratesSequentialCode;
use App\Models\Traits\HasMoneyFormatting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use GeneratesSequentialCode, HasFactory, HasMoneyFormatting, SoftDeletes;

    protected string $codePrefix = 'INV';

    protected string $codeColumn = 'invoice_number';

    /** @var array<int, string> */
    protected array $moneyColumns = [
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'balance_due',
    ];

    /** @var array<int, string> */
    protected $fillable = [
        'invoice_number',
        'order_id',
        'customer_id',
        'type',
        'status',
        'issue_date',
        'due_date',
        'subtotal',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'balance_due',
        'notes',
        'pdf_path',
        'sent_at',
        'voided_at',
        'void_reason',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'integer',
            'total_amount' => 'integer',
            'paid_amount' => 'integer',
            'balance_due' => 'integer',
            'sent_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

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

    public function scopeByStatus(Builder $query, string|array $status): Builder
    {
        return is_array($status)
            ? $query->whereIn('status', $status)
            : $query->where('status', $status);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['paid', 'void'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', today());
    }

    public function scopeByCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    public function isFullyPaid(): bool
    {
        return $this->status === 'paid' || $this->balance_due <= 0;
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && ! in_array($this->status, ['paid', 'void'], true);
    }

    public function canBeVoided(): bool
    {
        return $this->paid_amount === 0 && in_array($this->status, ['draft', 'issued'], true);
    }

    public function getDaysOverdueAttribute(): int
    {
        return $this->isOverdue() ? (int) $this->due_date->diffInDays(today()) : 0;
    }
}
