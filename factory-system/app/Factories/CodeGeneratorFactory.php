<?php

namespace App\Factories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CodeGeneratorFactory
{
    public function generate(string $prefix, string $modelClass, string $column = 'code'): string
    {
        if (! is_a($modelClass, Model::class, true)) {
            throw new InvalidArgumentException('Code generation requires an Eloquent model class.');
        }

        return DB::transaction(function () use ($prefix, $modelClass, $column): string {
            $year = now()->year;
            $pattern = "{$prefix}-{$year}-%";
            $query = in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)
                ? $modelClass::withTrashed()
                : $modelClass::query();

            $last = $query->where($column, 'like', $pattern)
                ->lockForUpdate()
                ->orderByDesc($column)
                ->value($column);

            $sequence = is_string($last) ? ((int) substr($last, -5)) + 1 : 1;

            return sprintf('%s-%d-%05d', $prefix, $year, $sequence);
        });
    }
}
