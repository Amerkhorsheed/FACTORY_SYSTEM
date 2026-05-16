<?php

namespace App\Services;

use App\ValueObjects\Money;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Shared service infrastructure for application business services.
 */
abstract class BaseService
{
    /**
     * Execute a unit of business work atomically.
     *
     * @throws \Throwable
     */
    protected function transaction(Closure $callback, int $attempts = 1): mixed
    {
        return DB::transaction($callback, $attempts);
    }

    /**
     * Paginate using the application default when no explicit size is given.
     */
    protected function paginate(Builder $query, int $perPage = 0): LengthAwarePaginator
    {
        $resolvedPerPage = $perPage > 0
            ? $perPage
            : (int) config('factory.pagination.per_page', 20);

        return $query->paginate($resolvedPerPage)->withQueryString();
    }

    /**
     * Wrap a persisted integer amount in the configured currency.
     */
    protected function money(int $amount): Money
    {
        return Money::of($amount, (string) config('factory.currency', 'SYP'));
    }

    /**
     * Parse formatted integer money input without using floating point math.
     */
    protected function parseMoney(string|int $amount): int
    {
        if (is_int($amount)) {
            return $amount;
        }

        $normalized = trim($amount);

        if ($normalized === '') {
            throw new InvalidArgumentException('Money amount cannot be empty.');
        }

        $isNegative = str_starts_with($normalized, '-');
        $digits = preg_replace('/\D/', '', $normalized);

        if ($digits === null || $digits === '') {
            throw new InvalidArgumentException('Money amount must contain digits.');
        }

        $value = (int) $digits;

        return $isNegative ? -$value : $value;
    }
}
