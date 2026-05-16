<?php

namespace App\Models\Traits;

use App\Factories\CodeGeneratorFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait GeneratesSequentialCode
{
    public static function bootGeneratesSequentialCode(): void
    {
        static::creating(function (Model $model): void {
            $column = $model->getCodeColumn();

            if ($model->getAttribute($column) !== null && $model->getAttribute($column) !== '') {
                return;
            }

            $model->setAttribute(
                $column,
                app(CodeGeneratorFactory::class)->generate(
                    $model->getCodePrefix(),
                    $model::class,
                    $column
                )
            );
        });
    }

    public function getCodeColumn(): string
    {
        return property_exists($this, 'codeColumn') ? $this->codeColumn : 'code';
    }

    public function getCodePrefix(): string
    {
        if (! property_exists($this, 'codePrefix') || $this->codePrefix === '') {
            throw new LogicException('Models using GeneratesSequentialCode must define $codePrefix.');
        }

        return $this->codePrefix;
    }
}
