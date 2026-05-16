<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\SystemSetting;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    public function index(): View
    {
        $this->authorize('viewAny', SystemSetting::class);

        $settings = $this->settings->all();

        return view('admin.settings.index', compact('settings'));
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->authorize('update', SystemSetting::class);

        $data = Arr::except($request->validated(), ['factory_logo']);

        if ($request->hasFile('factory_logo')) {
            $data['factory_logo'] = $request->file('factory_logo')->store('factory', 'public');
        }

        $this->settings->setMany($data);

        return back()->with('success', __('admin.settings_saved'));
    }
}
