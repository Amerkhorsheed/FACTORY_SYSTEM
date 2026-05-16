# 🏭 MASTER AGENT PROMPT — PART 6
## Remaining Blade Views · Livewire Templates · Missing Controllers
## User Model · UserFactory · DriverController · PasswordReset · Final Tests
### نظام إدارة معمل التوزيع والشحن — الجزء السادس والأخير
---
> **PART 6 OF 6** | Read Parts 1–5 first. This is the final part.
> Covers: all remaining Blade views (orders/create, shipments/show, customers/index,
> admin pages, report views), all Livewire view templates, missing controllers,
> User model, UserFactory, and the remaining test files.

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION A — USER MODEL & FACTORY                    ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Models/User.php` — Complete Implementation
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * User model — represents any authenticated user in the system.
 * Customers get a linked portal user; staff use this directly.
 * Uses Spatie HasRoles for RBAC.
 *
 * @property int    $id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property bool   $is_active
 * @property \Carbon\Carbon|null $last_login_at
 * @package App\Models
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'password',
        'avatar', 'is_active',
        'last_login_at', 'last_login_ip',
        'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active'         => 'boolean',
        'last_login_at'     => 'datetime',
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    // ── Computed Attributes ───────────────────────────────────────────────

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        // Generate initials-based avatar URL
        $initials = mb_substr($this->name, 0, 1);
        return "https://ui-avatars.com/api/?name={$initials}&background=2563eb&color=fff&size=64";
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->getRoleNames()->first() ?? '—';
    }

    // ── Business Methods ──────────────────────────────────────────────────

    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAccountant(): bool
    {
        return $this->hasRole('accountant');
    }

    public function isShippingStaff(): bool
    {
        return $this->hasRole('shipping_staff');
    }
}
```

### `database/factories/UserFactory.php`
```php
<?php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'              => $this->faker->name(),
            'email'             => $this->faker->unique()->safeEmail(),
            'phone'             => '09' . $this->faker->numerify('########'),
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'remember_token'    => Str::random(10),
            'is_active'         => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /** Create a user pre-assigned to super_admin role */
    public function admin(): static
    {
        return $this->afterCreating(fn(User $user) => $user->assignRole('super_admin'));
    }

    /** Create a user pre-assigned to accountant role */
    public function accountant(): static
    {
        return $this->afterCreating(fn(User $user) => $user->assignRole('accountant'));
    }

    /** Create a user pre-assigned to customer role */
    public function customerRole(): static
    {
        return $this->afterCreating(fn(User $user) => $user->assignRole('customer'));
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION B — MISSING CONTROLLERS                     ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Http/Controllers/Auth/PasswordResetController.php`
```php
<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{Hash, Password};
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Handles password reset via email token.
 * Arabic UI throughout.
 *
 * @package App\Http\Controllers\Auth
 */
class PasswordResetController extends Controller
{
    public function showRequestForm(): View
    {
        return view('auth.password-reset');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => __('validation.required', ['attribute' => __('validation.attributes.email')]),
            'email.email'    => __('validation.email',    ['attribute' => __('validation.attributes.email')]),
        ]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __('passwords.sent'))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.password-new', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (object $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __('passwords.reset'))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
```

### `app/Http/Controllers/Distribution/DriverController.php`
```php
<?php
namespace App\Http\Controllers\Distribution;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

/**
 * Driver management — list, create, update, soft-delete.
 *
 * @package App\Http\Controllers\Distribution
 */
class DriverController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:shipments.create')->except('index');
    }

    public function index(): View
    {
        $this->authorize('viewAny', \App\Models\Shipment::class);

        $drivers = Driver::withCount(['shipments' => fn($q) =>
            $q->whereIn('status', ['planned','loading','dispatched'])
        ])->latest()->paginate(20);

        return view('distribution.drivers.index', compact('drivers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:100'],
            'phone'          => ['required', 'string', 'max:20', 'unique:drivers,phone'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'license_expiry' => ['nullable', 'date', 'after:today'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        Driver::create(array_merge($data, ['is_active' => true]));

        return redirect()
            ->route('drivers.index')
            ->with('success', __('drivers.created'));
    }

    public function update(Request $request, Driver $driver): RedirectResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:100'],
            'phone'          => ['required', 'string', 'max:20',
                                "unique:drivers,phone,{$driver->id}"],
            'license_number' => ['nullable', 'string', 'max:50'],
            'license_expiry' => ['nullable', 'date'],
            'is_active'      => ['boolean'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $driver->update($data);

        return redirect()
            ->route('drivers.index')
            ->with('success', __('drivers.updated'));
    }

    public function destroy(Driver $driver): RedirectResponse
    {
        if ($driver->hasActiveShipment()) {
            return back()->with('error', __('drivers.has_active_shipment'));
        }

        $driver->delete();

        return redirect()
            ->route('drivers.index')
            ->with('success', __('drivers.deleted'));
    }
}
```

### `app/Http/Requests/Orders/UpdateOrderRequest.php`
```php
<?php
namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates order update payload.
 * Only notes, delivery date, and items can be updated.
 * Items are re-validated the same as on create.
 *
 * @package App\Http\Requests\Orders
 */
class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('orders.edit');
    }

    public function rules(): array
    {
        return [
            'requested_delivery_date'   => ['nullable', 'date', 'after_or_equal:today'],
            'notes'                     => ['nullable', 'string', 'max:2000'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.product_id'        => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'          => ['required', 'integer', 'min:1'],
            'items.*.unit_price'        => ['required', 'integer', 'min:0'],
            'items.*.discount_percent'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes'             => ['nullable', 'string', 'max:500'],
        ];
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION C — LIVEWIRE VIEW TEMPLATES                 ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/livewire/orders/order-items-table.blade.php`
```blade
{{--
    Livewire: OrderItemsTable
    Dynamic line items for the order create/edit form.
    Communicates with parent via Alpine events.
--}}
<div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:35%">المنتج</th>
                    <th style="width:12%">الكمية</th>
                    <th style="width:15%">سعر الوحدة</th>
                    <th style="width:10%">خصم%</th>
                    <th style="width:15%">الإجمالي</th>
                    <th style="width:5%"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $index => $item)
                <tr wire:key="item-{{ $index }}">
                    {{-- Product search --}}
                    <td>
                        <input
                            type="hidden"
                            name="items[{{ $index }}][product_id]"
                            value="{{ $item['product_id'] }}">
                        <input
                            type="text"
                            class="form-input text-sm"
                            placeholder="ابحث عن منتج..."
                            value="{{ $item['product_name'] }}"
                            wire:model.live.debounce.400ms="items.{{ $index }}.search"
                            @focus="$wire.set('items.{{ $index }}.showSearch', true)"
                            autocomplete="off">

                        @if(! empty($item['stock_available']))
                            <p class="text-xs mt-0.5 {{ $item['stock_available'] <= 10 ? 'text-red-500' : 'text-gray-400' }}">
                                متاح: {{ $item['stock_available'] }} {{ $item['unit'] }}
                            </p>
                        @endif
                    </td>

                    {{-- Quantity --}}
                    <td>
                        <input
                            type="number"
                            name="items[{{ $index }}][quantity]"
                            min="1"
                            class="form-input text-sm text-center"
                            wire:model.live="items.{{ $index }}.quantity"
                            value="{{ $item['quantity'] }}">
                    </td>

                    {{-- Unit price --}}
                    <td>
                        <input
                            type="number"
                            name="items[{{ $index }}][unit_price]"
                            min="0"
                            class="form-input text-sm tabular-nums"
                            wire:model.live="items.{{ $index }}.unit_price"
                            value="{{ $item['unit_price'] }}">
                    </td>

                    {{-- Discount percent --}}
                    <td>
                        <input
                            type="number"
                            name="items[{{ $index }}][discount_percent]"
                            min="0" max="100" step="0.5"
                            class="form-input text-sm text-center"
                            wire:model.live="items.{{ $index }}.discount_percent"
                            value="{{ $item['discount_percent'] }}">
                    </td>

                    {{-- Line total (read-only) --}}
                    <td class="tabular-nums font-medium text-gray-800">
                        @php
                            $gross = ($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1);
                            $disc  = round($gross * (($item['discount_percent'] ?? 0) / 100));
                            $lineTotal = $gross - $disc;
                        @endphp
                        {{ number_format($lineTotal) }}
                    </td>

                    {{-- Remove row --}}
                    <td>
                        @if(count($items) > 1)
                            <button
                                type="button"
                                wire:click="removeRow({{ $index }})"
                                class="text-red-400 hover:text-red-600 p-1">
                                ✕
                            </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Add row button --}}
    <div class="mt-3 flex items-center justify-between">
        <button
            type="button"
            wire:click="addRow"
            class="btn btn-ghost btn-sm">
            + إضافة صنف
        </button>

        {{-- Running totals --}}
        <div class="text-sm space-y-1 text-left">
            <div class="flex justify-between gap-8 text-gray-500">
                <span>المجموع الفرعي:</span>
                <span class="tabular-nums">{{ number_format($subtotal) }}</span>
            </div>
            @if($discountTotal > 0)
            <div class="flex justify-between gap-8 text-green-600">
                <span>إجمالي الخصم:</span>
                <span class="tabular-nums">- {{ number_format($discountTotal) }}</span>
            </div>
            @endif
            <div class="flex justify-between gap-8 font-bold text-gray-900 border-t pt-1">
                <span>الإجمالي:</span>
                <span class="tabular-nums">{{ number_format($grandTotal) }}</span>
            </div>
        </div>
    </div>
</div>
```

### `resources/views/livewire/orders/customer-balance-checker.blade.php`
```blade
{{--
    Livewire: CustomerBalanceChecker
    Shows available credit in real-time as order total changes.
--}}
@if($customerId)
<div class="p-3 rounded-lg text-sm {{ $creditExceeded ? 'bg-red-50 border border-red-200' : 'bg-gray-50 border border-gray-200' }}">
    <div class="space-y-1.5">
        <div class="flex justify-between">
            <span class="text-gray-500">الحد الائتماني:</span>
            <span class="font-medium tabular-nums">{{ money_format($creditLimit) }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500">المستخدم حالياً:</span>
            <span class="font-medium tabular-nums text-orange-600">
                {{ money_format($outstandingBalance) }}
            </span>
        </div>
        <div class="flex justify-between border-t pt-1.5">
            <span class="font-medium {{ $creditExceeded ? 'text-red-700' : 'text-green-700' }}">
                الرصيد المتاح:
            </span>
            <span class="font-bold tabular-nums {{ $creditExceeded ? 'text-red-700' : 'text-green-700' }}">
                {{ money_format($this->availableCredit()) }}
            </span>
        </div>
        @if($orderTotal > 0)
        <div class="flex justify-between text-xs">
            <span class="text-gray-500">قيمة الطلبية:</span>
            <span class="tabular-nums font-medium">{{ money_format($orderTotal) }}</span>
        </div>
        @endif
    </div>

    @if($creditExceeded)
    <div class="mt-2 flex items-center gap-1.5 text-red-700 font-medium text-xs">
        <span>⚠</span>
        <span>قيمة الطلبية تتجاوز الرصيد المتاح</span>
    </div>
    @elseif($creditLimit === 0)
    <p class="mt-1.5 text-xs text-blue-600">ائتمان غير محدود</p>
    @endif
</div>
@endif
```

### `resources/views/livewire/shared/notification-bell.blade.php`
```blade
{{--
    Livewire: NotificationBell
    Topbar notification icon with dropdown.
--}}
<div class="relative" x-data>
    {{-- Bell button --}}
    <button
        wire:click="toggle"
        class="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if($unreadCount > 0)
            <span class="absolute top-1 left-1 flex h-4 w-4 items-center justify-center
                         rounded-full bg-red-500 text-white text-xs font-bold">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown --}}
    @if($open)
    <div class="absolute left-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-100
                z-50 overflow-hidden"
         wire:click.outside="$set('open', false)">

        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <span class="font-semibold text-gray-800 text-sm">الإشعارات</span>
            @if($unreadCount > 0)
                <button wire:click="markAllRead"
                        class="text-xs text-brand-600 hover:underline">
                    تعليم الكل كمقروء
                </button>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
            @forelse($notifications as $notif)
            <a href="{{ $notif['url'] }}"
               wire:click="markRead('{{ $notif['id'] }}')"
               class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50
                      transition-colors {{ $notif['read'] ? 'opacity-60' : '' }}">
                <div class="mt-0.5 flex-shrink-0">
                    @if(! $notif['read'])
                        <div class="w-2 h-2 rounded-full bg-brand-500 mt-1"></div>
                    @else
                        <div class="w-2 h-2 rounded-full bg-gray-200 mt-1"></div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-800 leading-snug">{{ $notif['message'] }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $notif['time'] }}</p>
                </div>
            </a>
            @empty
            <div class="px-4 py-8 text-center text-gray-400 text-sm">
                لا توجد إشعارات
            </div>
            @endforelse
        </div>

        <div class="px-4 py-2 border-t border-gray-100 text-center">
            <a href="#" class="text-xs text-brand-600 hover:underline">
                عرض جميع الإشعارات
            </a>
        </div>
    </div>
    @endif
</div>
```

### `resources/views/livewire/products/product-search.blade.php`
```blade
{{--
    Livewire: ProductSearch
    Autocomplete product search for order form rows.
--}}
<div class="relative">
    <input
        type="text"
        class="form-input text-sm"
        placeholder="ابحث بالاسم أو الكود..."
        wire:model.live.debounce.350ms="search"
        @focus="$wire.set('showDropdown', true)"
        autocomplete="off">

    @if($showDropdown && count($results) > 0)
    <div class="absolute z-50 right-0 left-0 top-full mt-1 bg-white rounded-lg
                border border-gray-200 shadow-lg max-h-60 overflow-y-auto">
        @foreach($results as $product)
        <button
            type="button"
            wire:click="select({{ $product['id'] }})"
            class="w-full text-right flex items-center justify-between
                   px-3 py-2.5 hover:bg-brand-50 transition-colors border-b
                   border-gray-50 last:border-0">
            <div>
                <div class="font-medium text-gray-800 text-sm">{{ $product['name'] }}</div>
                <div class="text-xs text-gray-400">{{ $product['code'] }} · {{ $product['unit'] }}</div>
            </div>
            <div class="text-left text-xs">
                <div class="font-medium text-gray-700">{{ number_format($product['unit_price']) }}</div>
                <div class="{{ $product['is_low'] ? 'text-red-500' : 'text-green-600' }}">
                    {{ $product['stock'] }} متاح
                </div>
            </div>
        </button>
        @endforeach
    </div>
    @endif

    @if($showDropdown && strlen($search) >= 2 && count($results) === 0)
    <div class="absolute z-50 right-0 left-0 top-full mt-1 bg-white rounded-lg
                border border-gray-200 shadow-lg p-3 text-center text-sm text-gray-400">
        لا توجد منتجات مطابقة
    </div>
    @endif
</div>
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION D — BLADE COMPONENT VIEW FILES              ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/components/table.blade.php`
```blade
{{-- Generic table wrapper with RTL support --}}
@props(['headers' => [], 'empty' => 'لا توجد بيانات'])
<div class="table-wrapper">
    <table class="table">
        @if(count($headers) > 0)
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
```

### `resources/views/components/card.blade.php`
```blade
{{-- Content card with optional title --}}
@props(['title' => null, 'footer' => null])
<div {{ $attributes->merge(['class' => 'card']) }}>
    @if($title)
        <div class="card-header">{{ $title }}</div>
    @endif
    <div class="card-body">
        {{ $slot }}
    </div>
    @if($footer)
        <div class="px-6 py-3 border-t border-gray-100 text-sm text-gray-500">
            {{ $footer }}
        </div>
    @endif
</div>
```

### `resources/views/components/btn.blade.php`
```blade
{{-- Polymorphic button — renders as <a> or <button> --}}
@props([
    'variant' => 'primary',
    'size'    => 'md',
    'type'    => 'button',
    'href'    => null,
])

@php
$base   = 'btn';
$vars   = "btn-{$variant}";
$sizes  = ['sm' => 'btn-sm', 'md' => '', 'lg' => 'btn-lg'];
$sizeClass = $sizes[$size] ?? '';
$classes = trim("{$base} {$vars} {$sizeClass}");
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
```

### `resources/views/components/form-input.blade.php`
```blade
{{-- Form input with Arabic label, error state, and helper text --}}
@props([
    'label'    => '',
    'name'     => '',
    'type'     => 'text',
    'required' => false,
    'helper'   => null,
])
<div>
    @if($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if($required)
                <span class="text-red-500 mr-0.5">*</span>
            @endif
        </label>
    @endif
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        {{ $attributes->merge(['class' => 'form-input ' . ($errors->has($name) ? 'border-red-400' : '')]) }}>
    @if($helper)
        <p class="form-helper">{{ $helper }}</p>
    @endif
    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
```

### `resources/views/components/form-select.blade.php`
```blade
{{-- Select dropdown with RTL arrow --}}
@props(['label' => '', 'name' => '', 'required' => false])
<div>
    @if($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if($required) <span class="text-red-500 mr-0.5">*</span> @endif
        </label>
    @endif
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        {{ $attributes->merge(['class' => 'form-input ' . ($errors->has($name) ? 'border-red-400' : '')]) }}>
        {{ $slot }}
    </select>
    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
```

### `resources/views/components/form-textarea.blade.php`
```blade
@props(['label' => '', 'name' => '', 'rows' => 3, 'required' => false])
<div>
    @if($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if($required) <span class="text-red-500 mr-0.5">*</span> @endif
        </label>
    @endif
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        {{ $attributes->merge(['class' => 'form-input resize-none ' . ($errors->has($name) ? 'border-red-400' : '')]) }}>{{ $slot }}</textarea>
    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
```

### `resources/views/components/modal.blade.php`
```blade
{{-- Alpine.js modal wrapper --}}
@props(['event' => 'open-modal', 'title' => ''])
<div
    x-data="{ open: false }"
    @{{ $event }}.window="open = true">

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="open = false">

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="bg-white rounded-xl shadow-xl w-full max-w-lg">

            @if($title)
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">{{ $title }}</h3>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            @endif

            <div class="p-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
```

### `resources/views/components/confirm-modal.blade.php`
```blade
{{-- Arabic confirmation modal --}}
@props([
    'event'         => 'open-confirm-modal',
    'title'         => 'هل أنت متأكد؟',
    'action'        => '#',
    'method'        => 'POST',
    'confirmLabel'  => 'تأكيد',
    'cancelLabel'   => 'إلغاء',
    'variant'       => 'danger',
])
<div
    x-data="{ open: false }"
    @{{ $event }}.window="open = true">

    <div
        x-show="open"
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="open = false">

        <div x-show="open" x-transition class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-2">{{ $title }}</h3>

            @if($slot->isNotEmpty())
                <div class="my-4">{{ $slot }}</div>
            @else
                <p class="text-gray-500 mb-4">{{ __('app.labels.confirm_delete') }}</p>
            @endif

            <div class="flex items-center gap-3 justify-end mt-6">
                <button
                    type="button"
                    @click="open = false"
                    class="btn btn-secondary">
                    {{ $cancelLabel }}
                </button>
                <form method="POST" action="{{ $action }}">
                    @csrf
                    @if($method !== 'POST') @method($method) @endif
                    <button
                        type="submit"
                        class="btn {{ $variant === 'danger' ? 'btn-danger' : 'btn-primary' }}">
                        {{ $confirmLabel }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
```

### `resources/views/components/pagination.blade.php`
```blade
{{-- Arabic RTL pagination --}}
@props(['paginator'])
@if($paginator->hasPages())
<div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 text-sm">
    <div class="text-gray-500">
        عرض {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
        من {{ $paginator->total() }} نتيجة
    </div>
    <div class="flex items-center gap-1">
        {{-- Previous --}}
        @if($paginator->onFirstPage())
            <span class="btn btn-ghost btn-sm opacity-40 cursor-not-allowed">السابق</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-ghost btn-sm">السابق</a>
        @endif

        {{-- Page numbers --}}
        @foreach($paginator->getUrlRange(max(1,$paginator->currentPage()-2), min($paginator->lastPage(),$paginator->currentPage()+2)) as $page => $url)
            @if($page == $paginator->currentPage())
                <span class="btn btn-primary btn-sm">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="btn btn-ghost btn-sm">{{ $page }}</a>
            @endif
        @endforeach

        {{-- Next --}}
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-ghost btn-sm">التالي</a>
        @else
            <span class="btn btn-ghost btn-sm opacity-40 cursor-not-allowed">التالي</span>
        @endif
    </div>
</div>
@endif
```

### `resources/views/components/empty-state.blade.php`
```blade
{{-- Empty state illustration with CTA --}}
@props(['message' => 'لا توجد بيانات', 'cta' => null, 'ctaUrl' => null])
<div class="flex flex-col items-center justify-center py-16 text-center">
    <div class="text-5xl mb-4 opacity-30">📋</div>
    <h3 class="text-base font-medium text-gray-500">{{ $message }}</h3>
    @if($cta && $ctaUrl)
        <a href="{{ $ctaUrl }}" class="btn btn-primary mt-4">{{ $cta }}</a>
    @endif
    {{ $slot }}
</div>
```

### `resources/views/components/status-timeline.blade.php`
```blade
{{-- Order status progress steps (RTL) --}}
@props(['currentStatus' => 'pending'])

@php
$steps = [
    ['key' => 'pending',   'label' => 'معلقة'],
    ['key' => 'accepted',  'label' => 'مقبولة'],
    ['key' => 'preparing', 'label' => 'قيد التجهيز'],
    ['key' => 'ready',     'label' => 'جاهزة'],
    ['key' => 'shipped',   'label' => 'مشحونة'],
    ['key' => 'delivered', 'label' => 'مسلّمة'],
];
$statusOrder = array_column($steps, 'key');
$currentIdx  = array_search($currentStatus, $statusOrder) ?? -1;
@endphp

<div class="flex items-center gap-0 overflow-x-auto pb-2">
    @foreach($steps as $idx => $step)
        @php
            $done    = $idx < $currentIdx;
            $current = $idx === $currentIdx;
        @endphp
        <div class="flex items-center {{ $idx < count($steps)-1 ? 'flex-1' : '' }}">
            <div class="flex flex-col items-center">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                    {{ $done    ? 'bg-green-500 text-white' : '' }}
                    {{ $current ? 'bg-brand-600 text-white ring-4 ring-brand-100' : '' }}
                    {{ !$done && !$current ? 'bg-gray-200 text-gray-400' : '' }}">
                    @if($done) ✓ @else {{ $idx + 1 }} @endif
                </div>
                <span class="text-xs mt-1 whitespace-nowrap
                    {{ $current ? 'text-brand-700 font-semibold' : 'text-gray-400' }}">
                    {{ $step['label'] }}
                </span>
            </div>
            @if($idx < count($steps) - 1)
                <div class="flex-1 h-0.5 mb-4 mx-1
                    {{ $done ? 'bg-green-400' : 'bg-gray-200' }}"></div>
            @endif
        </div>
    @endforeach
</div>
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION E — ORDERS CREATE VIEW (CORE FORM)          ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/orders/create.blade.php`
```blade
@extends('layouts.app')
@section('title', 'إضافة طلبية جديدة')
@section('page-title', 'إضافة طلبية جديدة')

@section('content')
<form method="POST" action="{{ route('orders.store') }}" id="order-form">
@csrf

<div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Left/Main column — form fields --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Customer selection --}}
        <x-card :title="__('orders.customer_info')">
            <div class="space-y-4">

                <x-form-select
                    name="customer_id"
                    :label="__('validation.attributes.customer_id')"
                    required
                    data-tom-select
                    x-on:change="$dispatch('customer-selected', {customerId: $event.target.value})">
                    <option value="">— اختر العميل —</option>
                    @foreach(\App\Models\Customer::where('is_active',true)->orderBy('name')->get() as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }} ({{ $customer->phone }})
                        </option>
                    @endforeach
                </x-form-select>

                {{-- Live credit checker --}}
                @livewire('orders.customer-balance-checker')
            </div>
        </x-card>

        {{-- Order details --}}
        <x-card title="تفاصيل الطلبية">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-input
                    name="order_date"
                    :label="__('validation.attributes.order_date')"
                    type="date"
                    value="{{ old('order_date', today()->toDateString()) }}"
                    data-datepicker
                    required />
                <x-form-input
                    name="requested_delivery_date"
                    label="تاريخ التسليم المطلوب"
                    type="date"
                    value="{{ old('requested_delivery_date') }}"
                    data-datepicker />
            </div>
        </x-card>

        {{-- Order items --}}
        <x-card title="الأصناف المطلوبة">
            @livewire('orders.order-items-table')
        </x-card>

        {{-- Notes --}}
        <x-card title="ملاحظات">
            <x-form-textarea
                name="notes"
                label="ملاحظات إضافية"
                :rows="3">{{ old('notes') }}</x-form-textarea>
        </x-card>

    </div>

    {{-- Right column — summary & submit --}}
    <div class="space-y-5">
        <div class="card p-5 lg:sticky lg:top-20 space-y-4">
            <h3 class="font-semibold text-gray-800">ملخص الطلبية</h3>

            <div id="order-summary" class="space-y-2 text-sm">
                <div class="flex justify-between text-gray-500">
                    <span>عدد الأصناف:</span>
                    <span id="summary-items">0</span>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>المجموع الفرعي:</span>
                    <span id="summary-subtotal">0 ل.س</span>
                </div>
                <div class="flex justify-between font-bold text-gray-800 border-t pt-2">
                    <span>الإجمالي:</span>
                    <span id="summary-total" class="text-lg">0 ل.س</span>
                </div>
            </div>

            <div class="space-y-2 pt-2">
                <button type="submit" class="btn-primary w-full py-3">
                    ✓ إضافة الطلبية
                </button>
                <a href="{{ route('orders.index') }}" class="btn-secondary w-full text-center block py-2.5">
                    إلغاء
                </a>
            </div>
        </div>
    </div>
</div>

</form>
@endsection
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION F — REMAINING KEY VIEWS                     ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/customers/index.blade.php`
```blade
@extends('layouts.app')
@section('title', 'العملاء')
@section('page-title', 'إدارة العملاء')

@section('content')
<div class="space-y-5">

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        <x-kpi-card title="إجمالي العملاء"     :value="$kpis['total']"      color="blue"  icon="users" />
        <x-kpi-card title="عملاء فئة A"        :value="$kpis['category_a']" color="green" icon="star" />
        <x-kpi-card title="عملاء بديون مستحقة" :value="$kpis['with_debt']"  color="red"   icon="exclamation-circle" />
    </div>

    {{-- Filter bar --}}
    <div class="card p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label class="form-label">بحث</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-input" placeholder="الاسم، الهاتف...">
            </div>
            <div>
                <label class="form-label">الفئة</label>
                <select name="category" class="form-input">
                    <option value="">الكل</option>
                    @foreach(['A','B','C'] as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                            فئة {{ $cat }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">المنطقة</label>
                <input type="text" name="region" value="{{ request('region') }}"
                       class="form-input" placeholder="المنطقة">
            </div>
            <div class="flex items-center gap-1.5">
                <input type="checkbox" id="has_balance" name="has_balance" value="1"
                       {{ request('has_balance') ? 'checked' : '' }}
                       class="rounded border-gray-300">
                <label for="has_balance" class="text-sm cursor-pointer">بديون فقط</label>
            </div>
            <button type="submit" class="btn-secondary">تصفية</button>
            <a href="{{ route('customers.index') }}" class="btn-ghost">مسح</a>
        </form>
    </div>

    {{-- Header + Add button --}}
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">{{ $customers->total() }} عميل</p>
        @can('customers.create')
            <a href="{{ route('customers.create') }}" class="btn-primary">+ إضافة عميل</a>
        @endcan
    </div>

    {{-- Table --}}
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>الكود</th>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>المنطقة</th>
                    <th>الفئة</th>
                    @can('customers.view_balance')
                        <th>الرصيد المستحق</th>
                        <th>الحد الائتماني</th>
                    @endcan
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                <tr>
                    <td class="font-mono text-xs text-gray-400">{{ $customer->code }}</td>
                    <td>
                        <a href="{{ route('customers.show', $customer) }}"
                           class="font-medium text-brand-600 hover:underline">
                            {{ $customer->name }}
                        </a>
                        @if($customer->business_name)
                            <div class="text-xs text-gray-400">{{ $customer->business_name }}</div>
                        @endif
                    </td>
                    <td class="tabular-nums text-gray-600">{{ $customer->phone }}</td>
                    <td class="text-gray-600">{{ $customer->region ?? '—' }}</td>
                    <td>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $customer->category === 'A' ? 'bg-green-100 text-green-800' : ($customer->category === 'B' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600') }}">
                            فئة {{ $customer->category }}
                        </span>
                    </td>
                    @can('customers.view_balance')
                        <td class="tabular-nums {{ $customer->outstanding_balance > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                            {{ $customer->outstanding_balance > 0 ? money_format($customer->outstanding_balance) : '—' }}
                        </td>
                        <td class="tabular-nums text-gray-500">
                            {{ $customer->credit_limit > 0 ? money_format($customer->credit_limit) : 'غير محدود' }}
                        </td>
                    @endcan
                    <td><x-badge :status="$customer->is_active ? 'active' : 'inactive'" size="sm" /></td>
                    <td>
                        <div class="flex gap-1">
                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-ghost btn-sm">عرض</a>
                            @can('customers.edit')
                                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-ghost btn-sm">تعديل</a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <x-empty-state
                                message="لا يوجد عملاء"
                                cta="إضافة عميل جديد"
                                :cta-url="route('customers.create')" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-pagination :paginator="$customers" />
</div>
@endsection
```

### `resources/views/orders/daily.blade.php`
```blade
@extends('layouts.app')
@section('title', 'طلبيات اليوم')
@section('page-title', 'طلبيات اليوم')

@section('content')
<div class="space-y-5">

    {{-- Date navigation --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('orders.daily', ['date' => $date->copy()->subDay()->toDateString()]) }}"
           class="btn btn-ghost btn-sm">← اليوم السابق</a>
        <h2 class="text-lg font-bold text-gray-800">
            {{ $date->isoFormat('dddd D MMMM YYYY') }}
        </h2>
        @if(! $date->isToday())
            <a href="{{ route('orders.daily', ['date' => $date->copy()->addDay()->toDateString()]) }}"
               class="btn btn-ghost btn-sm">اليوم التالي →</a>
        @endif
        @if(! $date->isToday())
            <a href="{{ route('orders.daily') }}" class="btn btn-secondary btn-sm">اليوم</a>
        @endif
    </div>

    {{-- Kanban columns --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        @php
        $columns = [
            'pending'   => ['label' => 'معلقة',         'color' => 'yellow'],
            'accepted'  => ['label' => 'مقبولة',         'color' => 'blue'],
            'preparing' => ['label' => 'قيد التجهيز',    'color' => 'indigo'],
            'ready'     => ['label' => 'جاهزة للشحن',    'color' => 'cyan'],
            'shipped'   => ['label' => 'مشحونة',         'color' => 'violet'],
            'delivered' => ['label' => 'مسلّمة',          'color' => 'green'],
            'cancelled' => ['label' => 'ملغاة',           'color' => 'red'],
        ];
        @endphp

        @foreach($columns as $status => $col)
            @php($statusOrders = $orders[$status] ?? collect())
        <div class="bg-gray-50 rounded-xl p-3 border border-gray-200">
            {{-- Column header --}}
            <div class="flex items-center justify-between mb-3">
                <span class="font-semibold text-sm text-gray-700">{{ $col['label'] }}</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full
                    bg-{{ $col['color'] }}-100 text-{{ $col['color'] }}-700">
                    {{ $statusOrders->count() }}
                </span>
            </div>

            {{-- Order cards --}}
            <div class="space-y-2 min-h-16">
                @forelse($statusOrders as $order)
                <a href="{{ route('orders.show', $order) }}"
                   class="block bg-white rounded-lg p-3 shadow-sm border border-gray-100
                          hover:border-brand-200 hover:shadow-md transition-all">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-mono text-gray-400">{{ $order->order_number }}</p>
                            <p class="text-sm font-medium text-gray-800 mt-0.5">
                                {{ $order->customer->name }}
                            </p>
                        </div>
                        <span class="tabular-nums text-xs font-medium text-gray-600 text-left">
                            {{ money_format($order->total_amount) }}
                        </span>
                    </div>
                    @if($order->customer->region)
                        <p class="text-xs text-gray-400 mt-1">{{ $order->customer->region }}</p>
                    @endif
                </a>
                @empty
                <p class="text-xs text-gray-300 text-center py-4">—</p>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>

</div>
@endsection
```

### `resources/views/distribution/shipments/show.blade.php`
```blade
@extends('layouts.app')
@section('title', 'الشحنة ' . $shipment->shipment_number)

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    {{-- Shipment Header --}}
    <div class="card p-6">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-xl font-bold text-gray-900">{{ $shipment->shipment_number }}</h1>
                    <x-badge :status="$shipment->status" />
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mt-4">
                    <div>
                        <p class="text-gray-400 text-xs">التاريخ</p>
                        <p class="font-medium">{{ $shipment->shipment_date->format('Y/m/d') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">الشاحنة</p>
                        <p class="font-medium">{{ $shipment->truck->plate_number }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">السائق</p>
                        <p class="font-medium">{{ $shipment->driver->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">الطلبيات</p>
                        <p class="font-medium">{{ $shipment->orders->count() }}</p>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2 flex-wrap">
                @can('shipments.view_manifest')
                    <a href="{{ route('shipments.manifest', $shipment) }}"
                       target="_blank" class="btn btn-secondary btn-sm">
                        🖨 قائمة التوزيع
                    </a>
                @endcan

                @if($shipment->status === 'planned')
                    @can('shipments.dispatch')
                        @if($shipment->canBeDispatched())
                            <form method="POST" action="{{ route('shipments.dispatch', $shipment) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    🚛 إرسال الشحنة
                                </button>
                            </form>
                        @endif
                    @endcan
                @endif

                @if($shipment->status === 'dispatched')
                    @can('shipments.update_status')
                        @if($shipment->allOrdersResolved())
                            <form method="POST" action="{{ route('shipments.complete', $shipment) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    ✓ إنهاء الرحلة
                                </button>
                            </form>
                        @endif
                    @endcan
                @endif
            </div>
        </div>

        {{-- Progress bar for dispatched shipments --}}
        @if($shipment->status === 'dispatched')
        <div class="mt-5 pt-5 border-t border-gray-100">
            <div class="flex items-center justify-between text-sm mb-2">
                <span class="text-gray-500">تقدم التسليم</span>
                <span class="font-medium">
                    {{ $shipment->delivered_count }} / {{ $shipment->total_orders_count }} طلبية
                    ({{ $shipment->delivery_progress }}%)
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-green-500 h-2 rounded-full transition-all"
                     style="width: {{ $shipment->delivery_progress }}%"></div>
            </div>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Order Assignment (planned status) --}}
        @if($shipment->status === 'planned' && $readyOrders->count() > 0)
        <x-card title="إضافة طلبيات للشحنة">
            <form method="POST" action="{{ route('shipments.orders.attach', $shipment) }}">
                @csrf
                <div class="space-y-2 max-h-64 overflow-y-auto mb-3">
                    @foreach($readyOrders as $order)
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="order_ids[]" value="{{ $order->id }}"
                               class="rounded border-gray-300">
                        <div class="flex-1">
                            <p class="text-sm font-medium">{{ $order->customer->name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $order->order_number }} ·
                                {{ $order->customer->region ?? '—' }} ·
                                {{ money_format($order->total_amount) }}
                            </p>
                        </div>
                    </label>
                    @endforeach
                </div>
                <button type="submit" class="btn btn-secondary w-full">
                    إضافة المحدد للشحنة
                </button>
            </form>
        </x-card>
        @endif

        {{-- Current shipment orders --}}
        <div class="{{ $shipment->status === 'planned' ? '' : 'lg:col-span-2' }}">
            <x-card title="طلبيات الشحنة">
                @forelse($shipment->orders as $order)
                <div class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
                    <div>
                        <p class="font-medium text-sm text-gray-800">{{ $order->customer->name }}</p>
                        <p class="text-xs text-gray-400">
                            {{ $order->order_number }} · {{ $order->customer->address }}
                        </p>
                        <p class="text-xs text-gray-400">{{ $order->customer->phone }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="tabular-nums text-sm font-medium">
                            {{ money_format($order->total_amount) }}
                        </span>
                        <x-badge :status="$order->status" size="sm" />

                        {{-- Delivery action --}}
                        @if($shipment->status === 'dispatched' && $order->status === 'shipped')
                            @can('shipments.update_status')
                                <form method="POST"
                                      action="{{ route('shipments.orders.deliver', [$shipment, $order]) }}">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-primary btn-sm">
                                        ✓ تسليم
                                    </button>
                                </form>
                            @endcan
                        @endif

                        {{-- Remove from shipment (planned only) --}}
                        @if($shipment->status === 'planned')
                            @can('shipments.edit')
                                <form method="POST"
                                      action="{{ route('shipments.orders.detach', [$shipment, $order]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm text-red-400">
                                        ✕
                                    </button>
                                </form>
                            @endcan
                        @endif
                    </div>
                </div>
                @empty
                    <x-empty-state message="لم تتم إضافة أي طلبيات بعد" />
                @endforelse
            </x-card>
        </div>
    </div>
</div>
@endsection
```

### `resources/views/invoices/show.blade.php`
```blade
@extends('layouts.app')
@section('title', 'الفاتورة ' . $invoice->invoice_number)

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="card p-6">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h1 class="text-xl font-bold text-gray-900">{{ $invoice->invoice_number }}</h1>
                    <x-badge :status="$invoice->status" />
                    @if($invoice->isOverdue())
                        <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-medium">
                            متأخرة {{ $invoice->days_overdue }} يوم
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-500">
                    صدرت: {{ $invoice->issue_date->format('Y/m/d') }}
                    @if($invoice->due_date)
                        · الاستحقاق: {{ $invoice->due_date->format('Y/m/d') }}
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @can('view', $invoice)
                    <a href="{{ route('invoices.print', $invoice) }}"
                       target="_blank" class="btn btn-secondary btn-sm">🖨 طباعة</a>
                    <a href="{{ route('invoices.download', $invoice) }}"
                       class="btn btn-ghost btn-sm">⬇ تنزيل PDF</a>
                @endcan
                @can('send', $invoice)
                    @if($invoice->customer->email)
                        <form method="POST" action="{{ route('invoices.send', $invoice) }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm">📧 إرسال</button>
                        </form>
                    @endif
                @endcan
                @can('void', $invoice)
                    @if($invoice->canBeVoided())
                        <x-confirm-modal
                            event="void-invoice"
                            title="إلغاء الفاتورة"
                            :action="route('invoices.void', $invoice)"
                            method="POST"
                            confirm-label="إلغاء الفاتورة"
                            variant="danger">
                            <x-form-input name="reason" label="سبب الإلغاء" required />
                        </x-confirm-modal>
                        <button x-on:click="$dispatch('void-invoice')"
                                class="btn btn-danger btn-sm">إلغاء الفاتورة</button>
                    @endif
                @endcan
            </div>
        </div>

        {{-- Financial summary --}}
        <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-400">الإجمالي الكلي</p>
                <p class="text-lg font-bold tabular-nums">{{ money_format($invoice->total_amount) }}</p>
            </div>
            <div class="p-3 bg-green-50 rounded-lg">
                <p class="text-xs text-gray-400">المدفوع</p>
                <p class="text-lg font-bold text-green-600 tabular-nums">{{ money_format($invoice->paid_amount) }}</p>
            </div>
            <div class="p-3 {{ $invoice->balance_due > 0 ? 'bg-red-50' : 'bg-gray-50' }} rounded-lg">
                <p class="text-xs text-gray-400">المتبقي</p>
                <p class="text-lg font-bold {{ $invoice->balance_due > 0 ? 'text-red-600' : 'text-gray-400' }} tabular-nums">
                    {{ money_format($invoice->balance_due) }}
                </p>
            </div>
            <div class="p-3 bg-blue-50 rounded-lg">
                <p class="text-xs text-gray-400">نسبة السداد</p>
                @php($pct = $invoice->total_amount > 0 ? round(($invoice->paid_amount / $invoice->total_amount) * 100) : 0)
                <p class="text-lg font-bold text-blue-600">{{ $pct }}%</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Order items --}}
        <div class="lg:col-span-2 space-y-5">
            <x-card title="الأصناف">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المنتج</th>
                            <th>الكمية</th>
                            <th>سعر الوحدة</th>
                            <th>الخصم</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->order->items as $i => $item)
                        <tr>
                            <td class="text-gray-400">{{ $i + 1 }}</td>
                            <td>
                                <p class="font-medium">{{ $item->product->name }}</p>
                                <p class="text-xs text-gray-400">{{ $item->product->code }} · {{ $item->product->unit }}</p>
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="tabular-nums">{{ money_format($item->unit_price) }}</td>
                            <td class="text-center text-gray-500">{{ $item->discount_percent > 0 ? $item->discount_percent . '%' : '—' }}</td>
                            <td class="tabular-nums font-medium">{{ money_format($item->line_total) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Totals summary --}}
                <div class="mt-4 flex justify-end">
                    <table class="text-sm">
                        <tr>
                            <td class="text-gray-500 pl-8 py-1">المجموع الفرعي</td>
                            <td class="tabular-nums font-medium">{{ money_format($invoice->subtotal) }}</td>
                        </tr>
                        @if($invoice->discount_amount > 0)
                        <tr>
                            <td class="text-green-600 pl-8 py-1">الخصم</td>
                            <td class="tabular-nums text-green-600">- {{ money_format($invoice->discount_amount) }}</td>
                        </tr>
                        @endif
                        @if($invoice->tax_amount > 0)
                        <tr>
                            <td class="text-gray-500 pl-8 py-1">ضريبة ({{ $invoice->tax_rate }}%)</td>
                            <td class="tabular-nums">{{ money_format($invoice->tax_amount) }}</td>
                        </tr>
                        @endif
                        <tr class="border-t">
                            <td class="font-bold text-gray-800 pl-8 py-1">الإجمالي</td>
                            <td class="tabular-nums font-bold">{{ money_format($invoice->total_amount) }}</td>
                        </tr>
                    </table>
                </div>
            </x-card>

            {{-- Payment history --}}
            <x-card title="سجل المدفوعات">
                @forelse($invoice->payments as $payment)
                <div class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
                    <div>
                        <p class="text-sm font-medium">{{ money_format($payment->amount) }}</p>
                        <p class="text-xs text-gray-400">
                            {{ $payment->payment_date->format('Y/m/d') }} ·
                            {{ config("factory.payment_methods.{$payment->payment_method}") }}
                            @if($payment->reference_number) · {{ $payment->reference_number }} @endif
                        </p>
                    </div>
                    @can('delete', $payment)
                        <form method="POST" action="{{ route('payments.destroy', $payment) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-400 hover:text-red-600 text-xs">حذف</button>
                        </form>
                    @endcan
                </div>
                @empty
                    <p class="text-gray-400 text-sm text-center py-4">لا توجد مدفوعات بعد</p>
                @endforelse
            </x-card>
        </div>

        {{-- Sidebar: customer info + add payment --}}
        <div class="space-y-5">
            {{-- Customer --}}
            <x-card title="العميل">
                <p class="font-semibold">{{ $invoice->customer->name }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ $invoice->customer->phone }}</p>
                <p class="text-sm text-gray-500">{{ $invoice->customer->address }}</p>
            </x-card>

            {{-- Add payment --}}
            @if($invoice->balance_due > 0 && ! in_array($invoice->status, ['void','paid']))
                @can('payments.create')
                <x-card title="تسجيل دفعة">
                    <form method="POST" action="{{ route('payments.store', $invoice) }}" class="space-y-3">
                        @csrf
                        <x-form-input name="amount" label="المبلغ" type="number"
                                      :helper="'الرصيد المتبقي: ' . money_format($invoice->balance_due)"
                                      :value="old('amount', $invoice->balance_due)" required />
                        <x-form-select name="payment_method" label="طريقة الدفع" required>
                            @foreach(config('factory.payment_methods') as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </x-form-select>
                        <x-form-input name="payment_date" label="التاريخ" type="date"
                                      :value="today()->toDateString()" data-datepicker required />
                        <x-form-input name="reference_number" label="رقم المرجع" />
                        <button type="submit" class="btn btn-primary w-full">تسجيل الدفعة</button>
                    </form>
                </x-card>
                @endcan
            @endif
        </div>
    </div>
</div>
@endsection
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION G — ADMIN VIEWS                             ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/admin/users/index.blade.php`
```blade
@extends('layouts.app')
@section('title', 'إدارة المستخدمين')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">المستخدمون</h1>
        @can('system.users.create')
            <a href="{{ route('admin.users.create') }}" class="btn-primary">+ مستخدم جديد</a>
        @endcan
    </div>

    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>البريد الإلكتروني</th>
                    <th>الهاتف</th>
                    <th>الدور</th>
                    <th>آخر دخول</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td class="font-medium">{{ $user->name }}</td>
                    <td class="text-gray-500 text-sm" dir="ltr">{{ $user->email }}</td>
                    <td class="tabular-nums text-gray-500">{{ $user->phone ?? '—' }}</td>
                    <td>
                        @foreach($user->roles as $role)
                            <span class="inline-block px-2 py-0.5 bg-brand-100 text-brand-700
                                         rounded text-xs font-medium">
                                {{ $role->name }}
                            </span>
                        @endforeach
                    </td>
                    <td class="text-sm text-gray-400">
                        {{ $user->last_login_at?->diffForHumans() ?? 'لم يسجل بعد' }}
                    </td>
                    <td>
                        <x-badge :status="$user->is_active ? 'active' : 'inactive'" size="sm" />
                    </td>
                    <td>
                        <div class="flex gap-1">
                            @can('system.users.edit')
                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="btn btn-ghost btn-sm">تعديل</a>
                                <form method="POST"
                                      action="{{ route('admin.users.reset-password', $user) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm">
                                        إعادة كلمة المرور
                                    </button>
                                </form>
                            @endcan
                            @can('system.users.delete')
                                @if($user->id !== auth()->id())
                                    <form method="POST"
                                          action="{{ route('admin.users.destroy', $user) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-ghost btn-sm text-red-400"
                                                onclick="return confirm('{{ __('app.labels.confirm_delete') }}')">
                                            حذف
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state message="لا يوجد مستخدمون" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-pagination :paginator="$users" />
</div>
@endsection
```

### `resources/views/admin/settings/index.blade.php`
```blade
@extends('layouts.app')
@section('title', 'إعدادات النظام')

@section('content')
<div class="max-w-4xl mx-auto" x-data="{ tab: 'factory' }">
    <h1 class="text-xl font-bold text-gray-900 mb-6">إعدادات النظام</h1>

    {{-- Tabs --}}
    <div class="flex gap-1 bg-gray-100 p-1 rounded-lg mb-6 overflow-x-auto">
        @foreach([
            ['key'=>'factory',   'label'=>'معلومات المعمل'],
            ['key'=>'invoices',  'label'=>'إعدادات الفواتير'],
            ['key'=>'stock',     'label'=>'المخزون'],
            ['key'=>'customers', 'label'=>'العملاء'],
        ] as $t)
        <button @click="tab = '{{ $t['key'] }}'"
                :class="tab === '{{ $t['key'] }}' ? 'bg-white shadow text-brand-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="flex-1 px-4 py-2 rounded-md text-sm transition-all whitespace-nowrap">
            {{ $t['label'] }}
        </button>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf

        {{-- Factory info --}}
        <div x-show="tab === 'factory'" class="card p-6 space-y-4">
            <x-form-input name="factory_name"     label="اسم المعمل"
                          :value="$settings['factory_name'] ?? ''" />
            <x-form-input name="factory_address"  label="العنوان"
                          :value="$settings['factory_address'] ?? ''" />
            <x-form-input name="factory_phone"    label="الهاتف"
                          :value="$settings['factory_phone'] ?? ''" />
            <x-form-input name="factory_tax_number" label="الرقم الضريبي"
                          :value="$settings['factory_tax_number'] ?? ''" />
            <div>
                <label class="form-label">شعار المعمل</label>
                @if(!empty($settings['factory_logo']))
                    <img src="{{ asset('storage/'.$settings['factory_logo']) }}"
                         class="h-16 mb-2 object-contain border rounded" alt="Logo">
                @endif
                <input type="file" name="factory_logo" accept="image/*"
                       class="form-input">
            </div>
            <div>
                <label class="form-label">نص تذييل الفاتورة</label>
                <textarea name="invoice_footer_text" rows="2" class="form-input">{{ $settings['invoice_footer_text'] ?? 'شكراً لتعاملكم معنا' }}</textarea>
            </div>
        </div>

        {{-- Invoice settings --}}
        <div x-show="tab === 'invoices'" class="card p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <x-form-input name="invoice_due_days" label="أيام الاستحقاق" type="number"
                              :value="$settings['invoice_due_days'] ?? 30" />
                <x-form-input name="invoice_tax_rate" label="نسبة الضريبة (%)" type="number"
                              :value="$settings['invoice_tax_rate'] ?? 0" />
            </div>
            <div>
                <label class="form-label">بيانات البنك</label>
                <textarea name="invoice_bank_details" rows="3" class="form-input">{{ $settings['invoice_bank_details'] ?? '' }}</textarea>
            </div>
            <div>
                <label class="form-label">الشروط والأحكام</label>
                <textarea name="invoice_terms" rows="3" class="form-input">{{ $settings['invoice_terms'] ?? '' }}</textarea>
            </div>
        </div>

        {{-- Stock settings --}}
        <div x-show="tab === 'stock'" class="card p-6 space-y-4">
            <x-form-input name="default_low_threshold" label="حد المخزون المنخفض الافتراضي"
                          type="number" :value="$settings['default_low_threshold'] ?? 10" />
            <div class="flex items-center gap-2">
                <input type="hidden" name="enable_stock_warnings" value="0">
                <input type="checkbox" id="stock_warn" name="enable_stock_warnings" value="1"
                       {{ ($settings['enable_stock_warnings'] ?? false) ? 'checked' : '' }}>
                <label for="stock_warn" class="text-sm">تفعيل تحذيرات المخزون</label>
            </div>
        </div>

        {{-- Customer settings --}}
        <div x-show="tab === 'customers'" class="card p-6 space-y-4">
            <x-form-input name="default_credit_limit" label="الحد الائتماني الافتراضي (0 = غير محدود)"
                          type="number" :value="$settings['default_credit_limit'] ?? 0" />
            <x-form-select name="default_category" label="الفئة الافتراضية">
                @foreach(['A','B','C'] as $cat)
                    <option value="{{ $cat }}" {{ ($settings['default_category'] ?? 'B') === $cat ? 'selected' : '' }}>
                        فئة {{ $cat }}
                    </option>
                @endforeach
            </x-form-select>
        </div>

        <div class="mt-6">
            <button type="submit" class="btn-primary">حفظ الإعدادات</button>
        </div>
    </form>
</div>
@endsection
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION H — AUTH VIEWS                              ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/layouts/auth.blade.php`
```blade
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'تسجيل الدخول') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-cairo bg-gray-50" dir="rtl">
    @yield('content')
</body>
</html>
```

### `resources/views/layouts/print.blade.php`
```blade
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'طباعة')</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; direction: rtl; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    @yield('content')
    <script>window.onload = () => window.print();</script>
</body>
</html>
```

### `resources/views/auth/password-reset.blade.php`
```blade
@extends('layouts.auth')
@section('title', 'استعادة كلمة المرور')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-brand-900 to-brand-700 p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-white">استعادة كلمة المرور</h1>
            <p class="text-brand-200 text-sm mt-1">سنرسل لك رابط إعادة التعيين</p>
        </div>

        <div class="card p-8">
            @if(session('status'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf
                <x-form-input
                    name="email"
                    type="email"
                    label="البريد الإلكتروني"
                    :value="old('email')"
                    required />

                <button type="submit" class="btn-primary w-full py-3">
                    إرسال رابط الاستعادة
                </button>

                <div class="text-center">
                    <a href="{{ route('login') }}" class="text-sm text-brand-600 hover:underline">
                        ← العودة لتسجيل الدخول
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION I — PDF MANIFEST TEMPLATE                   ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/pdf/shipment-manifest.blade.php`
```blade
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    direction: rtl; text-align: right;
    font-size: 11px; color: #1a1a1a;
}
.page { padding: 15mm 12mm; }
.header { margin-bottom: 15px; }
.factory-name { font-size: 18px; font-weight: bold; color: #1e3a8a; }
.doc-title {
    text-align: center; font-size: 16px; font-weight: bold;
    border: 2px solid #1e3a8a; padding: 6px 20px;
    color: #1e3a8a; margin: 8px auto; display: inline-block;
}
.info-grid { display: table; width: 100%; margin-bottom: 12px;
    border: 1px solid #ddd; border-radius: 4px; }
.info-cell { display: table-cell; padding: 8px 12px; }
.info-label { font-size: 9px; color: #666; }
.info-value { font-size: 11px; font-weight: bold; }
table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
thead th {
    background: #1e3a8a; color: #fff; padding: 7px 6px;
    font-size: 10px; border: 1px solid #1e3a8a;
}
tbody td { padding: 7px 6px; border: 1px solid #e5e7eb; }
tbody tr:nth-child(even) td { background: #f8fafc; }
.sig-row { display: table; width: 100%; margin-top: 30px; }
.sig-box { display: table-cell; width: 33%; text-align: center;
    border-top: 1px solid #666; padding-top: 6px; font-size: 10px; }
.footer { margin-top: 15px; font-size: 9px; color: #6b7280;
    text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
</style>
</head>
<body>
<div class="page">
    {{-- Header --}}
    <div class="header" style="display:table; width:100%;">
        <div style="display:table-cell; vertical-align:top;">
            <div class="factory-name">{{ \App\Facades\Setting::get('factory_name') }}</div>
            <div style="font-size:9px; color:#666; margin-top:3px;">
                {{ \App\Facades\Setting::get('factory_address') }} ·
                {{ \App\Facades\Setting::get('factory_phone') }}
            </div>
        </div>
        <div style="display:table-cell; text-align:left; vertical-align:middle;">
            <div class="doc-title">قائمة التوزيع</div>
        </div>
    </div>

    {{-- Shipment info --}}
    <div class="info-grid">
        <div class="info-cell">
            <div class="info-label">رقم الشحنة</div>
            <div class="info-value">{{ $shipment->shipment_number }}</div>
        </div>
        <div class="info-cell">
            <div class="info-label">التاريخ</div>
            <div class="info-value">{{ $shipment->shipment_date->format('Y/m/d') }}</div>
        </div>
        <div class="info-cell">
            <div class="info-label">رقم السيارة</div>
            <div class="info-value">{{ $shipment->truck->plate_number }}</div>
        </div>
        <div class="info-cell">
            <div class="info-label">السائق</div>
            <div class="info-value">{{ $shipment->driver->name }}</div>
        </div>
        <div class="info-cell">
            <div class="info-label">هاتف السائق</div>
            <div class="info-value">{{ $shipment->driver->phone }}</div>
        </div>
    </div>

    {{-- Orders table --}}
    <table>
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th style="width:22%">اسم العميل</th>
                <th style="width:25%">العنوان</th>
                <th style="width:13%">الهاتف</th>
                <th style="width:10%">الطلبية</th>
                <th style="width:13%">المبلغ الإجمالي</th>
                <th style="width:12%">التوقيع</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shipment->orders as $idx => $order)
            <tr>
                <td style="text-align:center">{{ $idx + 1 }}</td>
                <td>{{ $order->customer->name }}</td>
                <td style="font-size:9px">{{ $order->customer->address }}</td>
                <td>{{ $order->customer->phone }}</td>
                <td style="text-align:center">{{ $order->order_number }}</td>
                <td style="font-weight:bold; direction:ltr; text-align:left;">
                    {{ number_format($order->total_amount) }}
                </td>
                <td></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div style="text-align:left; font-size:11px; margin-bottom:20px;">
        <strong>الإجمالي الكلي: {{ number_format($shipment->orders->sum('total_amount')) }} ل.س</strong>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        عدد الطلبيات: {{ $shipment->orders->count() }}
    </div>

    {{-- Signatures --}}
    <div class="sig-row">
        <div class="sig-box">توقيع السائق</div>
        <div class="sig-box"></div>
        <div class="sig-box">توقيع المشرف</div>
    </div>

    <div class="footer">
        <p>طبع بتاريخ: {{ now()->format('Y/m/d H:i') }} · صفحة 1</p>
    </div>
</div>
</body>
</html>
```

### `resources/views/pdf/customer-statement.blade.php`
```blade
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: 'DejaVu Sans', Arial, sans-serif;
       direction:rtl; text-align:right; font-size:11px; color:#1a1a1a; }
.page { padding:15mm 12mm; }
.factory-name { font-size:18px; font-weight:bold; color:#1e3a8a; }
.doc-title { font-size:15px; font-weight:bold; margin:10px 0;
    text-align:center; border-bottom:2px solid #1e3a8a; padding-bottom:8px; }
table { width:100%; border-collapse:collapse; margin-bottom:15px; }
thead th { background:#1e3a8a; color:#fff; padding:7px 6px;
    font-size:10px; border:1px solid #1e3a8a; }
tbody td { padding:6px; border:1px solid #e5e7eb; font-size:10px; }
.debit  { color:#dc2626; }
.credit { color:#16a34a; }
.balance-row td { font-weight:bold; background:#f0f4f8; }
</style>
</head>
<body>
<div class="page">
    {{-- Header --}}
    <div class="factory-name">{{ \App\Facades\Setting::get('factory_name') }}</div>
    <div style="font-size:9px; color:#666;">{{ \App\Facades\Setting::get('factory_address') }}</div>

    <div class="doc-title">كشف حساب عميل</div>

    {{-- Customer + period --}}
    <table style="margin-bottom:12px;">
        <tr>
            <td style="padding:6px; background:#f8fafc; width:50%;">
                <strong>العميل:</strong> {{ $customer->name }}
                ({{ $customer->code }})
            </td>
            <td style="padding:6px; background:#f8fafc;">
                <strong>الفترة:</strong>
                {{ $from->format('Y/m/d') }} — {{ $to->format('Y/m/d') }}
            </td>
        </tr>
    </table>

    {{-- Transactions --}}
    <table>
        <thead>
            <tr>
                <th style="width:12%">التاريخ</th>
                <th style="width:20%">المرجع</th>
                <th style="width:20%">البيان</th>
                <th style="width:16%">مدين (فاتورة)</th>
                <th style="width:16%">دائن (دفعة)</th>
                <th style="width:16%">الرصيد</th>
            </tr>
        </thead>
        <tbody>
            {{-- Opening balance --}}
            <tr class="balance-row">
                <td></td>
                <td colspan="2">رصيد افتتاحي</td>
                <td></td>
                <td></td>
                <td style="direction:ltr; text-align:left;">
                    {{ number_format($statement['opening_balance']) }}
                </td>
            </tr>
            {{-- Transactions --}}
            @foreach($statement['transactions'] as $tx)
            <tr>
                <td>{{ $tx['date']->format('Y/m/d') }}</td>
                <td style="font-size:9px;">{{ $tx['ref'] }}</td>
                <td>{{ $tx['type'] === 'invoice' ? 'فاتورة مبيعات' : 'دفعة مستلمة' }}</td>
                <td class="debit" style="direction:ltr; text-align:left;">
                    {{ $tx['debit'] > 0 ? number_format($tx['debit']) : '' }}
                </td>
                <td class="credit" style="direction:ltr; text-align:left;">
                    {{ $tx['credit'] > 0 ? number_format($tx['credit']) : '' }}
                </td>
                <td style="direction:ltr; text-align:left; font-weight:bold;">
                    {{ number_format($tx['balance']) }}
                </td>
            </tr>
            @endforeach
            {{-- Closing balance --}}
            <tr class="balance-row">
                <td></td>
                <td colspan="2"><strong>الرصيد الختامي</strong></td>
                <td></td>
                <td></td>
                <td style="direction:ltr; text-align:left; font-size:13px;">
                    {{ number_format($statement['closing_balance']) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top:25px; font-size:9px; color:#6b7280; text-align:center;">
        طبع بتاريخ: {{ now()->format('Y/m/d H:i') }}
    </div>
</div>
</body>
</html>
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION J — REMAINING UNIT TESTS                    ║
## ╚══════════════════════════════════════════════════════════════╝

### `tests/Feature/CustomerPortalTest.php`
```php
<?php
use App\Models\{Customer, Invoice, Order, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    // Create a customer with portal access
    $this->customerUser = User::factory()->create(['password' => bcrypt('password')])->assignRole('customer');
    $this->customer     = Customer::factory()->create([
        'user_id'        => $this->customerUser->id,
        'portal_access'  => true,
        'is_active'      => true,
    ]);

    // Create another customer
    $this->otherCustomer = Customer::factory()->create();
});

it('customer can view their own orders', function () {
    $order = Order::factory()->create(['customer_id' => $this->customer->id]);

    $this->actingAs($this->customerUser)
        ->get(route('portal.orders.show', $order))
        ->assertOk();
});

it('customer cannot view other customers orders', function () {
    $otherOrder = Order::factory()->create(['customer_id' => $this->otherCustomer->id]);

    $this->actingAs($this->customerUser)
        ->get(route('portal.orders.show', $otherOrder))
        ->assertForbidden();
});

it('customer cannot access admin routes', function () {
    $this->actingAs($this->customerUser)
        ->get(route('products.index'))
        ->assertRedirect(route('portal.dashboard'));
});

it('customer can view their own invoices', function () {
    $invoice = Invoice::factory()->create([
        'customer_id' => $this->customer->id,
        'status'      => 'issued',
    ]);

    $this->actingAs($this->customerUser)
        ->get(route('portal.invoices.show', $invoice))
        ->assertOk();
});

it('inactive customer cannot login', function () {
    $this->customerUser->update(['is_active' => false]);

    $this->post(route('login'), [
        'login'    => $this->customerUser->email,
        'password' => 'password',
    ])->assertSessionHasErrors('login');
});
```

### `tests/Feature/OrderCancellationTest.php`
```php
<?php
use App\Models\{Customer, Order, OrderItem, Product, User};
use App\Services\Orders\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin    = User::factory()->create()->assignRole('super_admin');
    $this->customer = Customer::factory()->create();
    $this->product  = Product::factory()->create(['stock_quantity' => 100]);
    $this->actingAs($this->admin);
});

it('cancels a pending order without stock change', function () {
    $order = Order::factory()->pending()->for($this->customer)->create();

    app(OrderStatusService::class)->cancel($order, 'اختبار الإلغاء', $this->admin);

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($this->product->fresh()->stock_quantity)->toBe(100); // unchanged
});

it('cancels an accepted order and returns stock', function () {
    $order = Order::factory()->accepted()->for($this->customer)->create();
    $order->items()->create([
        'product_id' => $this->product->id,
        'quantity'   => 10,
        'unit_price' => 5_000,
        'line_total' => 50_000,
    ]);
    // Simulate stock deduction on accept
    $this->product->update(['stock_quantity' => 90]);

    app(OrderStatusService::class)->cancel($order, 'إلغاء مع إرجاع مخزون', $this->admin);

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($this->product->fresh()->stock_quantity)->toBe(100); // returned
});

it('cannot cancel a delivered order', function () {
    $order = Order::factory()->delivered()->for($this->customer)->create();

    expect(fn() =>
        app(OrderStatusService::class)->cancel($order, 'محاولة إلغاء', $this->admin)
    )->toThrow(\App\Exceptions\InvalidStatusTransitionException::class);
});

it('voids the invoice when order is cancelled', function () {
    $order = Order::factory()->accepted()->for($this->customer)->create();
    $invoice = \App\Models\Invoice::factory()->create([
        'order_id'    => $order->id,
        'customer_id' => $this->customer->id,
        'status'      => 'draft',
    ]);

    app(OrderStatusService::class)->cancel($order, 'إلغاء الطلبية', $this->admin);

    expect($invoice->fresh()->status)->toBe('void');
});
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION K — `lang/ar/notifications.php`             ║
## ╚══════════════════════════════════════════════════════════════╝

### `lang/ar/notifications.php`
```php
<?php
return [
    'order_status_changed'   => 'تم تغيير حالة الطلبية :number إلى: :status',
    'invoice_issued'         => 'تم إصدار فاتورة جديدة :number بمبلغ :amount',
    'payment_received'       => 'تم استلام دفعة بمبلغ :amount على الفاتورة :invoice',
    'low_stock_alert'        => ':count منتجات وصلت لمستوى المخزون المنخفض',
    'invoice_overdue'        => ':count فاتورة متأخرة السداد بإجمالي :amount',
];
```

### `lang/ar/products.php`
```php
<?php
return [
    'created'             => 'تم إضافة المنتج :name بنجاح',
    'updated'             => 'تم تحديث المنتج بنجاح',
    'deleted'             => 'تم حذف المنتج',
    'restored'            => 'تم استعادة المنتج بنجاح',
    'stock_adjusted'      => 'تم تعديل مخزون المنتج :name بنجاح',
    'has_active_orders'   => 'لا يمكن حذف المنتج — يوجد طلبيات نشطة مرتبطة به',
];
```

### `lang/ar/customers.php`
```php
<?php
return [
    'created'                  => 'تم إضافة العميل :name بنجاح',
    'updated'                  => 'تم تحديث بيانات العميل بنجاح',
    'deleted'                  => 'تم حذف العميل',
    'activated'                => 'تم تفعيل حساب العميل',
    'deactivated'              => 'تم تعطيل حساب العميل',
    'portal_access_updated'    => 'تم تحديث صلاحيات البوابة',
    'portal_requires_email'    => 'يجب إدخال البريد الإلكتروني لتفعيل البوابة',
    'has_active_orders'        => 'لا يمكن حذف العميل — يوجد طلبيات نشطة مرتبطة به',
    'outstanding'              => 'الرصيد المستحق',
    'view_profile'             => 'عرض ملف العميل',
];
```

### `lang/ar/expenses.php`
```php
<?php
return [
    'created' => 'تم تسجيل المصروف بنجاح',
    'updated' => 'تم تحديث المصروف بنجاح',
    'deleted' => 'تم حذف المصروف',
];
```

### `lang/ar/drivers.php`
```php
<?php
return [
    'created'            => 'تم إضافة السائق بنجاح',
    'updated'            => 'تم تحديث بيانات السائق بنجاح',
    'deleted'            => 'تم حذف السائق',
    'has_active_shipment'=> 'السائق لديه شحنة نشطة اليوم',
];
```

### `lang/ar/trucks.php`
```php
<?php
return [
    'created'            => 'تم إضافة الشاحنة بنجاح',
    'updated'            => 'تم تحديث بيانات الشاحنة بنجاح',
    'deleted'            => 'تم حذف الشاحنة',
    'cannot_delete_on_trip' => 'لا يمكن حذف شاحنة في الطريق حالياً',
];
```

### `lang/ar/admin.php`
```php
<?php
return [
    'settings_saved'         => 'تم حفظ الإعدادات بنجاح',
    'user_created'           => 'تم إضافة المستخدم :name بنجاح',
    'user_updated'           => 'تم تحديث بيانات المستخدم بنجاح',
    'user_deleted'           => 'تم حذف المستخدم',
    'cannot_delete_self'     => 'لا يمكنك حذف حسابك الخاص',
    'password_reset_sent'    => 'تم إرسال كلمة المرور المؤقتة إلى بريد المستخدم',
];
```

### `lang/ar/erp.php`
```php
<?php
return [
    'dashboard'        => 'لوحة التحكم المالية',
    'today_revenue'    => 'مبيعات اليوم',
    'month_revenue'    => 'مبيعات الشهر',
    'outstanding_balance' => 'الديون المستحقة',
    'month_expenses'   => 'مصروفات الشهر',
];
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION L — CUSTOMER PORTAL ROUTES                  ║
## ╚══════════════════════════════════════════════════════════════╝

### `routes/portal.php` — Customer portal routes
```php
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Customers\CustomerPortalController;

/**
 * Customer portal routes.
 * Accessible to users with 'customer' role only.
 * Protected by: auth, active, portal middleware.
 */
Route::middleware(['auth','active','portal'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function () {

        Route::get('/', [CustomerPortalController::class, 'dashboard'])->name('dashboard');

        // Orders
        Route::get('orders',      [CustomerPortalController::class, 'orders'])->name('orders.index');
        Route::get('orders/{order}', [CustomerPortalController::class, 'showOrder'])->name('orders.show');
        Route::get('orders/create', [CustomerPortalController::class, 'createOrder'])->name('orders.create');
        Route::post('orders',     [CustomerPortalController::class, 'storeOrder'])->name('orders.store');

        // Invoices
        Route::get('invoices',         [CustomerPortalController::class, 'invoices'])->name('invoices.index');
        Route::get('invoices/{invoice}',[CustomerPortalController::class, 'showInvoice'])->name('invoices.show');

        // Profile
        Route::get('profile',   [CustomerPortalController::class, 'profile'])->name('profile');
        Route::put('profile',   [CustomerPortalController::class, 'updateProfile'])->name('profile.update');
    });
```

### `app/Http/Controllers/Customers/CustomerPortalController.php`
```php
<?php
namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\{Invoice, Order};
use Illuminate\Http\{Request, View};

/**
 * Customer-facing portal — shows customer's own data only.
 * All queries filter by auth()->user()->customer->id.
 *
 * @package App\Http\Controllers\Customers
 */
class CustomerPortalController extends Controller
{
    private function customer(): \App\Models\Customer
    {
        return auth()->user()->customer;
    }

    public function dashboard(): View
    {
        $customer = $this->customer();

        return view('portal.dashboard', [
            'customer'     => $customer,
            'recentOrders' => $customer->orders()->latest()->limit(5)->get(),
            'unpaidInvoices' => $customer->invoices()
                ->whereNotIn('status', ['paid','void'])
                ->latest()->get(),
        ]);
    }

    public function orders(): View
    {
        $orders = $this->customer()
            ->orders()
            ->with('invoice')
            ->latest('order_date')
            ->paginate(15);

        return view('portal.orders.index', compact('orders'));
    }

    public function showOrder(Order $order): View
    {
        $this->authorize('view', $order);
        $order->load(['items.product','invoice']);
        return view('portal.orders.show', compact('order'));
    }

    public function invoices(): View
    {
        $invoices = $this->customer()
            ->invoices()
            ->latest('issue_date')
            ->paginate(15);

        return view('portal.invoices.index', compact('invoices'));
    }

    public function showInvoice(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);
        $invoice->load(['order.items.product','payments']);
        return view('portal.invoices.show', compact('invoice'));
    }

    public function profile(): View
    {
        return view('portal.profile', ['customer' => $this->customer()]);
    }

    public function updateProfile(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'phone'     => ['required','string','max:20'],
            'phone_alt' => ['nullable','string','max:20'],
            'address'   => ['required','string','max:500'],
        ]);

        $this->customer()->update($data);

        return back()->with('success', 'تم تحديث بياناتك بنجاح');
    }

    public function createOrder(): View
    {
        return view('portal.orders.create', ['customer' => $this->customer()]);
    }

    public function storeOrder(Request $request): \Illuminate\Http\RedirectResponse
    {
        // Delegates to OrderService after setting customer_id to auth customer
        $request->merge(['customer_id' => $this->customer()->id]);

        return app(\App\Http\Controllers\Orders\OrderController::class)
            ->store($request);
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION M — COMPLETE EXCEPTIONS                     ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Exceptions/InvalidStatusTransitionException.php`
```php
<?php
namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an illegal status transition is attempted.
 * Caught by Handler and returned as 422 or redirect-back.
 */
class InvalidStatusTransitionException extends RuntimeException {}
```

### `app/Exceptions/InsufficientStockException.php`
```php
<?php
namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when order items request more quantity than available stock.
 */
class InsufficientStockException extends RuntimeException {}
```

### `app/Exceptions/CreditLimitExceededException.php`
```php
<?php
namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an order would exceed the customer's credit limit.
 */
class CreditLimitExceededException extends RuntimeException {}
```

### `app/Exceptions/InvoiceCannotBeVoidedException.php`
```php
<?php
namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when attempting to void an invoice that has payments.
 */
class InvoiceCannotBeVoidedException extends RuntimeException {}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         FINAL SECTION — COMPLETE SYSTEM SUMMARY             ║
## ╚══════════════════════════════════════════════════════════════╝

### What the 6-part prompt covers — complete inventory:

```
ARCHITECTURE (Part 1)
  ✅ 12 design patterns with enforcement rules
  ✅ SOLID principles with code examples
  ✅ 400-line rule with split strategies
  ✅ 18-phase execution plan
  ✅ Session start/task/module/end protocols
  ✅ Anti-pattern list

DATA LAYER (Parts 2, 3, 5)
  ✅ 17 database migrations with correct FK/index setup
  ✅ 14 Eloquent models with full relationships and scopes
  ✅ 4 model traits (GeneratesSequentialCode, HasMoneyFormatting,
     HasSoftDeleteGuard, HasStatusTransitions)
  ✅ 7 repositories with interface contracts
  ✅ 6 seeders (roles, admin, settings, categories, demo, master)

BUSINESS LOGIC (Parts 2, 3, 4, 5)
  ✅ 12 services across all modules
  ✅ 4 observers with Spatie activity logging
  ✅ 7 domain events + 5 queued listeners
  ✅ 3 pipeline pipes (order validation)
  ✅ 2 state machines (Order, Shipment)
  ✅ Money value object (immutable, arithmetic)
  ✅ CodeGeneratorFactory (DB-locked sequential codes)
  ✅ SettingService with Redis caching

HTTP LAYER (Parts 2, 4, 5, 6)
  ✅ 22 controllers (thin, authorize-first)
  ✅ 7 policies with Gate::before super_admin bypass
  ✅ 4 middleware (SetLocale, CheckActive, Portal, LastActivity)
  ✅ 11 form requests with Arabic validation messages
  ✅ Complete routes/web.php (60+ named routes)
  ✅ routes/portal.php (customer portal)
  ✅ routes/console.php (3 scheduled jobs)
  ✅ API routes for Chart.js data

FRONTEND (Parts 3, 4, 5, 6)
  ✅ app.js (Alpine.js + Flatpickr Arabic + Tom Select)
  ✅ charts.js (Chart.js RTL, 4 chart types)
  ✅ app.css (full Tailwind RTL + component classes)
  ✅ tailwind.config.js (Cairo font, brand colors, RTL plugin)
  ✅ vite.config.js (chunk splitting)
  ✅ 8 Livewire components (PHP + Blade templates)
  ✅ 15 Blade components (btn, card, badge, modal, etc.)
  ✅ 15+ page views (index, create, show, edit)
  ✅ Sidebar with permissions-gated nav items

PDF & DOCUMENTS (Parts 2, 3, 6)
  ✅ PdfService (generate, stream, download)
  ✅ Invoice PDF (full Arabic A4, amount-in-words)
  ✅ Shipment manifest PDF
  ✅ Customer statement PDF

NOTIFICATIONS (Parts 3, 5, 6)
  ✅ 6 notification classes (queued, database + email)
  ✅ 4 Arabic email templates
  ✅ NotificationBell Livewire (polling, mark-read)
  ✅ 2 Artisan commands (overdue-alerts, low-stock-check)

SECURITY (Parts 1, 4, 5)
  ✅ RBAC: 4 roles, 28+ permissions
  ✅ Rate limiting: 5 attempts/15 min
  ✅ Policy authorization on every controller action
  ✅ Customer isolation (cannot see other customers' data)
  ✅ Financial data hidden from shipping_staff

TESTING (Parts 2, 3, 4, 5, 6)
  ✅ 18 test files (Unit + Feature)
  ✅ Full order lifecycle test (pending → delivered)
  ✅ Cancellation with stock return
  ✅ Payment recording with balance recalculation
  ✅ Shipment flow (create → dispatch → deliver → complete)
  ✅ Role access control
  ✅ PDF download tests
  ✅ Auth tests (login, rate limit, inactive user)
  ✅ Product and customer CRUD
  ✅ CustomerPortal isolation
  ✅ State machine transitions
  ✅ Money value object arithmetic
  ✅ Amount-to-words Arabic conversion

DEPLOYMENT (Part 3)
  ✅ docker-compose.yml (5 services)
  ✅ Dockerfile (PHP 8.3-FPM Alpine)
  ✅ Nginx config (SSL, gzip, caching)
  ✅ Supervisor config (2 workers + horizon + scheduler)
  ✅ deploy.sh (10-step script with maintenance mode)
```

---

```
 ██████╗  ██████╗ ███╗   ██╗███████╗
 ██╔══██╗██╔═══██╗████╗  ██║██╔════╝
 ██║  ██║██║   ██║██╔██╗ ██║█████╗
 ██║  ██║██║   ██║██║╚██╗██║██╔══╝
 ██████╔╝╚██████╔╝██║ ╚████║███████╗
 ╚═════╝  ╚═════╝ ╚═╝  ╚═══╝╚══════╝

MASTER AGENT PROMPT — ALL 6 PARTS — COMPLETE
نظام إدارة معمل التوزيع والشحن
May 2026 — Total: ~20,000 lines · 670KB · Zero ambiguity

READ ORDER: Part 1 → 2 → 3 → 4 → 5 → 6
THEN: Create management files
THEN: Execute Phase 00 → 18

GO BUILD IT. ابدأ الآن.
```

---

*PART 6 OF 6 — MASTER AGENT PROMPT v1.0.0 — FINAL*
