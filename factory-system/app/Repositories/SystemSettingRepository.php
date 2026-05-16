<?php

namespace App\Repositories;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Collection;

/**
 * Data access for application settings.
 */
class SystemSettingRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new SystemSetting);
    }

    /** @return Collection<int, SystemSetting> */
    public function allOrdered(): Collection
    {
        return SystemSetting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get();
    }

    public function findByKey(string $key): ?SystemSetting
    {
        return SystemSetting::query()->where('key', $key)->first();
    }

    public function setValue(string $key, mixed $value): SystemSetting
    {
        $setting = $this->findByKey($key) ?? new SystemSetting([
            'key' => $key,
            'type' => $this->inferType($value),
            'group' => 'general',
            'label' => $key,
        ]);

        $setting->value = $this->stringValue($value);
        $setting->save();

        return $setting->refresh();
    }

    private function stringValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return (string) $value;
    }

    private function inferType(mixed $value): string
    {
        return match (true) {
            is_int($value) => 'integer',
            is_bool($value) => 'boolean',
            is_array($value) => 'json',
            default => 'string',
        };
    }
}
