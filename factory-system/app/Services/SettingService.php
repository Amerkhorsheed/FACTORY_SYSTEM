<?php

namespace App\Services;

use App\Models\SystemSetting;

/**
 * Simple settings reader backed by the system_settings table.
 */
class SettingService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = SystemSetting::where('key', $key)->first();

        return $setting?->typedValue() ?? $default;
    }
}
