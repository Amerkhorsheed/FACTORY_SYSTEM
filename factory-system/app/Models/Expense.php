<?php

namespace App\Models;

use App\Models\Traits\HasMoneyFormatting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, HasMoneyFormatting, SoftDeletes;

    /** @var array<int, string> */
    protected array $moneyColumns = ['amount'];

    /** @var array<int, string> */
    protected $fillable = [
        'category',
        'amount',
        'expense_date',
        'description',
        'reference',
        'attachment',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'expense_date' => 'date',
        ];
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('expense_date', $year)->whereMonth('expense_date', $month);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment ? asset('storage/'.$this->attachment) : null;
    }
}
