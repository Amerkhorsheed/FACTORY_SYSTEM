<?php

namespace App\Services;

use App\Repositories\SystemSettingRepository;

/**
 * Settings reader/writer backed by the system_settings table.
 */
class SettingService extends BaseService
{
    public function __construct(private readonly SystemSettingRepository $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->settings->findByKey($key);

        return $setting?->typedValue() ?? $default;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->settings->allOrdered()
            ->mapWithKeys(fn ($setting) => [$setting->key => $setting->typedValue()])
            ->all();
    }

    /** @param array<string, mixed> $values */
    public function setMany(array $values): void
    {
        $this->transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                $this->settings->setValue($key, $value);
            }
        });
    }
}
