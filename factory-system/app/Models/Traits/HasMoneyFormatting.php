<?php

namespace App\Models\Traits;

use App\ValueObjects\Money;

trait HasMoneyFormatting
{
    public function __get($key): mixed
    {
        if (str_starts_with((string) $key, 'formatted_')) {
            $column = substr((string) $key, 10);

            if ($this->isMoneyColumn($column)) {
                return $this->formatMoneyColumn($column);
            }
        }

        return parent::__get($key);
    }

    public function isMoneyColumn(string $column): bool
    {
        return in_array($column, $this->moneyColumns ?? [], true);
    }

    public function formatMoneyColumn(string $column): string
    {
        return Money::of((int) ($this->attributes[$column] ?? 0))->format();
    }
}
