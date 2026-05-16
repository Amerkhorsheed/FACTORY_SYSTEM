<?php

namespace App\Models\Traits;

use DomainException;
use Illuminate\Database\Eloquent\Model;

trait HasSoftDeleteGuard
{
    public static function bootHasSoftDeleteGuard(): void
    {
        static::deleting(function (Model $model): void {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                return;
            }

            foreach ($model->getActiveRelationChecks() as $relation => $message) {
                if (! method_exists($model, $relation)) {
                    continue;
                }

                if ($model->{$relation}()->exists()) {
                    throw new DomainException($message);
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function getActiveRelationChecks(): array
    {
        return [];
    }
}
