<?php

namespace App\Models;

use App\Models\Traits\GeneratesSequentialCode;
use App\Models\Traits\HasMoneyFormatting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use GeneratesSequentialCode, HasFactory, HasMoneyFormatting, SoftDeletes;

    protected string $codePrefix = 'PAY';

    protected string $codeColumn = 'payment_number';

    /** @var array<int, string> */
    protected array $moneyColumns = ['amount'];

    /** @var array<int, string> */
    protected $fillable = [
        'payment_number',
        'invoice_id',
        'customer_id',
        'amount',
        'payment_method',
        'payment_date',
        'reference_number',
        'notes',
        'received_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'payment_date' => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeForInvoice(Builder $query, int $invoiceId): Builder
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeBetweenDates(Builder $query, mixed $from, mixed $to): Builder
    {
        return $query->whereBetween('payment_date', [$from, $to]);
    }

    public function getMethodLabelAttribute(): string
    {
        return config("factory.payment_methods.{$this->payment_method}", $this->payment_method);
    }
}
