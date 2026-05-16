# 🏭 MASTER AGENT PROMPT — PART 4
## Policies · Middleware · Auth · Shipment/Customer Services · Remaining Controllers
## Livewire Components · Email Views · Error Pages · Export Strategies · Config Files
### نظام إدارة معمل التوزيع والشحن — الجزء الرابع والأخير
---
> **PART 4 OF 4** | Read Parts 1–3 first.
> This file completes every remaining implementation piece.
> After this file the system is 100% specified — nothing left to infer.

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION A — POLICIES (ALL 7 IMPLEMENTATIONS)        ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Policies/OrderPolicy.php`
```php
<?php
namespace App\Policies;

use App\Models\{Order, User};
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization policy for Order model.
 * All methods return bool — true = allowed, false = forbidden.
 * Customer role can only see their OWN orders.
 *
 * @package App\Policies
 */
class OrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        if (! $user->hasPermissionTo('orders.view')) {
            return false;
        }

        // Customers can only see their own orders
        if ($user->hasRole('customer')) {
            return $user->customer?->id === $order->customer_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('orders.create');
    }

    public function update(User $user, Order $order): bool
    {
        if (! $user->hasPermissionTo('orders.edit')) {
            return false;
        }

        // Cannot edit shipped, delivered, cancelled, returned orders
        return $order->isEditable();
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('orders.cancel')
            && $order->isCancellable();
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('orders.delete')
            && in_array($order->status, ['pending', 'cancelled'], true);
    }

    public function confirmDelivery(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('orders.confirm_delivery')
            && $order->status === 'shipped';
    }

    public function assignShipment(User $user, Order $order): bool
    {
        return $user->hasPermissionTo('orders.assign_shipment')
            && $order->status === 'ready';
    }
}
```

### `app/Policies/InvoicePolicy.php`
```php
<?php
namespace App\Policies;

use App\Models\{Invoice, User};
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization policy for Invoice model.
 * Customers can view their own invoices only.
 *
 * @package App\Policies
 */
class InvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if (! $user->hasPermissionTo('invoices.view')) {
            return false;
        }

        if ($user->hasRole('customer')) {
            return $user->customer?->id === $invoice->customer_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('invoices.create');
    }

    public function void(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.void')
            && $invoice->canBeVoided();
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.send')
            && in_array($invoice->status, ['issued', 'partial'], true);
    }

    public function print(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice);
    }
}
```

### `app/Policies/PaymentPolicy.php`
```php
<?php
namespace App\Policies;

use App\Models\{Payment, User};
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization policy for Payment model.
 * Only accountant and super_admin can create/delete payments.
 *
 * @package App\Policies
 */
class PaymentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('payments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('payments.create');
    }

    public function delete(User $user, Payment $payment): bool
    {
        // Only same-day payments can be deleted (safety guard)
        return $user->hasPermissionTo('payments.delete')
            && $payment->created_at->isToday();
    }
}
```

### `app/Policies/ProductPolicy.php`
```php
<?php
namespace App\Policies;

use App\Models\{Product, User};
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization policy for Product model.
 *
 * @package App\Policies
 */
class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('products.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('products.edit');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('products.delete');
    }

    public function adjustStock(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('products.adjust_stock');
    }

    public function viewCostPrice(User $user): bool
    {
        return $user->hasPermissionTo('products.view_cost_price');
    }
}
```

### `app/Policies/CustomerPolicy.php`
```php
<?php
namespace App\Policies;

use App\Models\{Customer, User};
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization policy for Customer model.
 *
 * @package App\Policies
 */
class CustomerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('customers.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('customers.edit');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('customers.delete');
    }

    public function manageCredit(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('customers.manage_credit');
    }

    public function viewBalance(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('customers.view_balance');
    }
}
```

### `app/Policies/ShipmentPolicy.php`
```php
<?php
namespace App\Policies;

use App\Models\{Shipment, User};
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization policy for Shipment model.
 *
 * @package App\Policies
 */
class ShipmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('shipments.view');
    }

    public function view(User $user, Shipment $shipment): bool
    {
        return $user->hasPermissionTo('shipments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('shipments.create');
    }

    public function update(User $user, Shipment $shipment): bool
    {
        return $user->hasPermissionTo('shipments.edit')
            && in_array($shipment->status, ['planned', 'loading'], true);
    }

    public function dispatch(User $user, Shipment $shipment): bool
    {
        return $user->hasPermissionTo('shipments.dispatch')
            && $shipment->status === 'planned'
            && $shipment->orders()->where('status', 'ready')->exists();
    }

    public function updateStatus(User $user, Shipment $shipment): bool
    {
        return $user->hasPermissionTo('shipments.update_status');
    }

    public function viewManifest(User $user, Shipment $shipment): bool
    {
        return $user->hasPermissionTo('shipments.view_manifest');
    }
}
```

### `app/Policies/ExpensePolicy.php`
```php
<?php
namespace App\Policies;

use App\Models\{Expense, User};
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization policy for Expense model.
 * Only accountant and super_admin can manage expenses.
 *
 * @package App\Policies
 */
class ExpensePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('erp.expenses.view');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->hasPermissionTo('erp.expenses.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('erp.expenses.create');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->hasPermissionTo('erp.expenses.edit')
            && $expense->expense_date->isSameMonth(now());
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->hasRole('super_admin')
            && $expense->expense_date->isSameMonth(now());
    }
}
```

### `app/Providers/AuthServiceProvider.php`
```php
<?php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\{Order, Invoice, Payment, Product, Customer, Shipment, Expense};
use App\Policies\{OrderPolicy, InvoicePolicy, PaymentPolicy, ProductPolicy,
                  CustomerPolicy, ShipmentPolicy, ExpensePolicy};

/**
 * Registers all model → policy mappings.
 *
 * @package App\Providers
 */
class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Order::class    => OrderPolicy::class,
        Invoice::class  => InvoicePolicy::class,
        Payment::class  => PaymentPolicy::class,
        Product::class  => ProductPolicy::class,
        Customer::class => CustomerPolicy::class,
        Shipment::class => ShipmentPolicy::class,
        Expense::class  => ExpensePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // super_admin bypasses all policy checks
        \Illuminate\Support\Facades\Gate::before(function ($user) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
        });
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION B — MIDDLEWARE (ALL 4 IMPLEMENTATIONS)      ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Http/Middleware/SetLocale.php`
```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces Arabic locale for all requests.
 * Must be registered globally in bootstrap/app.php.
 *
 * @package App\Http\Middleware
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('ar');
        return $next($request);
    }
}
```

### `app/Http/Middleware/CheckUserIsActive.php`
```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks deactivated users from accessing any route.
 * Logs them out and shows Arabic error message.
 *
 * @package App\Http\Middleware
 */
class CheckUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => __('auth.account_deactivated')]);
        }

        return $next($request);
    }
}
```

### `app/Http/Middleware/CustomerPortalMiddleware.php`
```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts customer-role users to the /portal route prefix only.
 * Any attempt to access admin/staff routes → redirect to portal home.
 *
 * @package App\Http\Middleware
 */
class CustomerPortalMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->hasRole('customer')) {
            // Allow portal routes
            if (! $request->routeIs('portal.*', 'logout')) {
                return redirect()->route('portal.dashboard')
                    ->with('warning', __('auth.portal_only'));
            }
        }

        return $next($request);
    }
}
```

### `app/Http/Middleware/LastActivityMiddleware.php`
```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Updates user's last_seen_at timestamp every 5 minutes.
 * Uses Redis cache to avoid a DB write on every request.
 *
 * @package App\Http\Middleware
 */
class LastActivityMiddleware
{
    private const UPDATE_INTERVAL = 300; // 5 minutes in seconds

    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $cacheKey = 'user:last_active:' . auth()->id();

            if (! Cache::has($cacheKey)) {
                auth()->user()->update(['last_login_at' => now()]);
                Cache::put($cacheKey, true, self::UPDATE_INTERVAL);
            }
        }

        return $next($request);
    }
}
```

### `bootstrap/app.php` — Middleware registration
```php
<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\{Exceptions, Middleware};

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:     __DIR__ . '/../routes/web.php',
        api:     __DIR__ . '/../routes/api.php',
        console: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware (runs on every request)
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\LastActivityMiddleware::class,
        ]);

        // Named aliases
        $middleware->alias([
            'active'  => \App\Http\Middleware\CheckUserIsActive::class,
            'portal'  => \App\Http\Middleware\CustomerPortalMiddleware::class,
            'locale'  => \App\Http\Middleware\SetLocale::class,
            'role'    => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION C — AUTH CONTROLLER & SERVICE               ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Http/Controllers/Auth/LoginController.php`
```php
<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{Auth, RateLimiter};
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Handles login via email OR phone number.
 * Rate-limited to 5 attempts per 15 minutes per IP.
 * Tracks last_login_at and last_login_ip on success.
 *
 * @package App\Http\Controllers\Auth
 */
class LoginController extends Controller
{
    private const MAX_ATTEMPTS   = 5;
    private const DECAY_SECONDS  = 900; // 15 minutes

    public function showForm(): View
    {
        return view('auth.login');
    }

    /**
     * @throws ValidationException
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required'    => __('auth.login_required'),
            'password.required' => __('auth.password_required'),
        ]);

        $this->checkRateLimit($request);

        // Determine if login field is email or phone
        $field      = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $credentials = [
            $field     => $request->login,
            'password' => $request->password,
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request), self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        // Check active status
        if (! Auth::user()->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'login' => __('auth.account_deactivated'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        // Update login metadata
        Auth::user()->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Redirect customers to portal
        if (Auth::user()->hasRole('customer')) {
            return redirect()->intended(route('portal.dashboard'));
        }

        return redirect()->intended(route('erp.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * @throws ValidationException
     */
    private function checkRateLimit(Request $request): void
    {
        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'login' => __('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }
    }

    private function throttleKey(Request $request): string
    {
        return 'login:' . $request->ip();
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION D — SHIPMENT SERVICE (FULL)                 ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Services/Distribution/ShipmentService.php`
```php
<?php
namespace App\Services\Distribution;

use App\Contracts\Repositories\ShipmentRepositoryInterface;
use App\DTOs\Shipments\CreateShipmentDTO;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\{Order, Shipment, Truck, Driver};
use App\Services\BaseService;
use App\Services\Orders\OrderStatusService;
use App\Services\PdfService;
use App\StateMachines\ShipmentStateMachine;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Manages shipment lifecycle: planning → loading → dispatched → completed.
 * Coordinates with Truck status, Driver availability, and Order transitions.
 *
 * @package App\Services\Distribution
 */
class ShipmentService extends BaseService
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $shipments,
        private readonly ShipmentStateMachine         $stateMachine,
        private readonly OrderStatusService           $orderStatus,
        private readonly PdfService                   $pdf,
    ) {}

    public function list(array $filters, int $perPage = 0): LengthAwarePaginator
    {
        return $this->shipments->paginateWithFilters(
            $filters,
            $perPage ?: config('factory.pagination.per_page', 20)
        );
    }

    /**
     * Create a new planned shipment.
     * Validates truck availability and driver availability.
     *
     * @throws \DomainException when truck or driver is unavailable
     * @throws \Throwable
     */
    public function create(CreateShipmentDTO $dto): Shipment
    {
        return $this->transaction(function () use ($dto) {
            $this->assertTruckAvailable($dto->truckId);
            $this->assertDriverAvailable($dto->driverId, $dto->shipmentDate);

            return $this->shipments->create([
                'truck_id'      => $dto->truckId,
                'driver_id'     => $dto->driverId,
                'shipment_date' => $dto->shipmentDate,
                'status'        => 'planned',
                'notes'         => $dto->notes,
                'created_by'    => auth()->id(),
            ]);
        });
    }

    /**
     * Attach ready orders to a planned shipment.
     * Only orders with status 'ready' and no shipment_id can be attached.
     *
     * @param  int[] $orderIds
     * @throws \DomainException
     * @throws \Throwable
     */
    public function attachOrders(Shipment $shipment, array $orderIds): void
    {
        $this->assertShipmentEditable($shipment);

        $this->transaction(function () use ($shipment, $orderIds) {
            foreach ($orderIds as $orderId) {
                $order = Order::where('id', $orderId)
                    ->where('status', 'ready')
                    ->whereNull('shipment_id')
                    ->firstOrFail();

                $order->update([
                    'shipment_id' => $shipment->id,
                ]);
            }
        });
    }

    /**
     * Remove an order from a shipment (before dispatch only).
     *
     * @throws \DomainException
     * @throws \Throwable
     */
    public function detachOrder(Shipment $shipment, Order $order): void
    {
        $this->assertShipmentEditable($shipment);

        $this->transaction(function () use ($order) {
            $order->update(['shipment_id' => null]);
        });
    }

    /**
     * Dispatch the shipment: set departure time, update truck status, transition orders.
     *
     * @throws InvalidStatusTransitionException
     * @throws \Throwable
     */
    public function dispatch(Shipment $shipment): Shipment
    {
        $this->stateMachine->transition($shipment->status, 'dispatched');

        return $this->transaction(function () use ($shipment) {
            // Transition all ready orders to shipped
            $shipment->orders()->where('status', 'ready')
                ->each(fn(Order $order) =>
                    $order->update([
                        'status'     => 'shipped',
                        'shipped_at' => now(),
                        'shipped_by' => auth()->id(),
                    ])
                );

            // Update truck status
            $shipment->truck()->update(['status' => 'on_trip']);

            $updated = $this->shipments->update($shipment, [
                'status'         => 'dispatched',
                'departure_time' => now(),
            ]);

            // Generate and cache manifest PDF (dispatched to queue)
            dispatch(function () use ($updated) {
                $this->pdf->generateManifest($updated);
            })->afterResponse();

            return $updated;
        });
    }

    /**
     * Mark a specific order as delivered from shipment view.
     *
     * @throws \Throwable
     */
    public function markOrderDelivered(Shipment $shipment, Order $order): void
    {
        if ($order->shipment_id !== $shipment->id) {
            throw new \DomainException(__('shipments.order_not_in_shipment'));
        }

        $this->orderStatus->confirmDelivery($order, auth()->user());
    }

    /**
     * Complete the shipment: requires all orders resolved.
     * Frees the truck back to available.
     *
     * @throws \DomainException when orders are still pending
     * @throws \Throwable
     */
    public function complete(Shipment $shipment): Shipment
    {
        $this->stateMachine->transition($shipment->status, 'completed');

        if (! $shipment->allOrdersResolved()) {
            throw new \DomainException(__('shipments.cannot_complete_with_pending_orders'));
        }

        return $this->transaction(function () use ($shipment) {
            // Free the truck
            $shipment->truck()->update(['status' => 'available']);

            return $this->shipments->update($shipment, [
                'status'      => 'completed',
                'return_time' => now(),
            ]);
        });
    }

    /**
     * Cancel a planned shipment (before dispatch only).
     *
     * @throws InvalidStatusTransitionException
     * @throws \Throwable
     */
    public function cancel(Shipment $shipment, string $reason): Shipment
    {
        $this->stateMachine->transition($shipment->status, 'cancelled');

        return $this->transaction(function () use ($shipment, $reason) {
            // Detach all orders back to ready
            $shipment->orders()->update(['shipment_id' => null]);

            return $this->shipments->update($shipment, [
                'status' => 'cancelled',
                'notes'  => $reason,
            ]);
        });
    }

    // ── Private guards ────────────────────────────────────────────────────

    private function assertTruckAvailable(int $truckId): void
    {
        $truck = Truck::findOrFail($truckId);
        if ($truck->status !== 'available') {
            throw new \DomainException(__('shipments.truck_not_available', [
                'status' => __("factory.truck_statuses.{$truck->status}"),
            ]));
        }
    }

    private function assertDriverAvailable(int $driverId, \Carbon\Carbon $date): void
    {
        $busy = Shipment::where('driver_id', $driverId)
            ->whereDate('shipment_date', $date)
            ->whereIn('status', ['planned','loading','dispatched'])
            ->exists();

        if ($busy) {
            throw new \DomainException(__('shipments.driver_already_assigned'));
        }
    }

    private function assertShipmentEditable(Shipment $shipment): void
    {
        if (! in_array($shipment->status, ['planned','loading'], true)) {
            throw new \DomainException(__('shipments.cannot_modify_dispatched'));
        }
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION E — CUSTOMER SERVICE (FULL)                 ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Services/Customers/CustomerService.php`
```php
<?php
namespace App\Services\Customers;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\DTOs\Customers\CreateCustomerDTO;
use App\Models\{Customer, User};
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Customer management service.
 * Handles CRUD, portal access, and account statement generation.
 *
 * @package App\Services\Customers
 */
class CustomerService extends BaseService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
    ) {}

    public function list(array $filters, int $perPage = 0): LengthAwarePaginator
    {
        return $this->customers->paginateWithFilters(
            $filters,
            $perPage ?: config('factory.pagination.per_page', 20)
        );
    }

    /**
     * Create a new customer. Optionally creates portal user account.
     *
     * @throws \Throwable
     */
    public function create(CreateCustomerDTO $dto): Customer
    {
        return $this->transaction(function () use ($dto) {
            $customer = $this->customers->create([
                'name'          => $dto->name,
                'business_name' => $dto->businessName,
                'phone'         => $dto->phone,
                'phone_alt'     => $dto->phoneAlt,
                'email'         => $dto->email,
                'address'       => $dto->address,
                'city'          => $dto->city,
                'region'        => $dto->region,
                'category'      => $dto->category,
                'credit_limit'  => $dto->creditLimit,
                'notes'         => $dto->notes,
                'is_active'     => true,
                'portal_access' => $dto->portalAccess,
                'created_by'    => $dto->createdBy,
            ]);

            if ($dto->portalAccess && $dto->portalPassword && $dto->email) {
                $this->createPortalUser($customer, $dto->portalPassword);
            }

            return $customer;
        });
    }

    /**
     * Update customer details.
     *
     * @throws \Throwable
     */
    public function update(Customer $customer, CreateCustomerDTO $dto): Customer
    {
        return $this->transaction(function () use ($customer, $dto) {
            return $this->customers->update($customer, [
                'name'          => $dto->name,
                'business_name' => $dto->businessName,
                'phone'         => $dto->phone,
                'phone_alt'     => $dto->phoneAlt,
                'email'         => $dto->email,
                'address'       => $dto->address,
                'city'          => $dto->city,
                'region'        => $dto->region,
                'category'      => $dto->category,
                'credit_limit'  => $dto->creditLimit,
                'notes'         => $dto->notes,
            ]);
        });
    }

    /**
     * Soft-delete a customer — guarded by HasSoftDeleteGuard on the model.
     *
     * @throws \DomainException if customer has active orders
     * @throws \Throwable
     */
    public function delete(Customer $customer): void
    {
        $this->customers->delete($customer);
    }

    /**
     * Enable portal access for a customer: create linked User with 'customer' role.
     *
     * @throws \DomainException if no email is set
     * @throws \Throwable
     */
    public function enablePortalAccess(Customer $customer, string $password): User
    {
        if (! $customer->email) {
            throw new \DomainException(__('customers.portal_requires_email'));
        }

        return $this->transaction(function () use ($customer, $password) {
            $user = $this->createPortalUser($customer, $password);
            $this->customers->update($customer, ['portal_access' => true]);
            return $user;
        });
    }

    /**
     * Disable portal access: deactivate the linked user account.
     *
     * @throws \Throwable
     */
    public function disablePortalAccess(Customer $customer): void
    {
        $this->transaction(function () use ($customer) {
            if ($customer->user) {
                $customer->user->update(['is_active' => false]);
            }
            $this->customers->update($customer, ['portal_access' => false]);
        });
    }

    /**
     * Recalculate outstanding balance from live invoice data.
     * Call this after any payment or invoice change.
     */
    public function recalculateBalance(Customer $customer): void
    {
        $outstanding = \App\Models\Invoice::where('customer_id', $customer->id)
            ->whereNotIn('status', ['paid', 'void'])
            ->sum('balance_due');

        $this->customers->update($customer, ['outstanding_balance' => (int) $outstanding]);
    }

    /**
     * Get customer's order history.
     */
    public function getOrderHistory(Customer $customer): \Illuminate\Database\Eloquent\Collection
    {
        return $customer->orders()
            ->with(['items.product', 'invoice'])
            ->latest('order_date')
            ->limit(100)
            ->get();
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function createPortalUser(Customer $customer, string $password): User
    {
        $user = User::create([
            'name'     => $customer->name,
            'email'    => $customer->email,
            'phone'    => $customer->phone,
            'password' => Hash::make($password),
            'is_active'=> true,
        ]);

        $user->assignRole('customer');

        $this->customers->update($customer, ['user_id' => $user->id]);

        return $user;
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION F — REMAINING CONTROLLERS                   ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Http/Controllers/Distribution/ShipmentController.php`
```php
<?php
namespace App\Http\Controllers\Distribution;

use App\DTOs\Shipments\CreateShipmentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shipments\StoreShipmentRequest;
use App\Models\{Driver, Shipment, Truck};
use App\Services\Distribution\ShipmentService;
use App\Services\PdfService;
use Illuminate\Http\{RedirectResponse, Request, Response};
use Illuminate\View\View;

/**
 * Manages Shipment CRUD and lifecycle transitions.
 * Order attachment handled by ShipmentOrderController.
 *
 * @package App\Http\Controllers\Distribution
 */
class ShipmentController extends Controller
{
    public function __construct(
        private readonly ShipmentService $service,
        private readonly PdfService      $pdf,
    ) {
        $this->authorizeResource(Shipment::class, 'shipment');
    }

    public function index(Request $request): View
    {
        $shipments = $this->service->list($request->only(['date','status','truck_id']));
        $todayStats = [
            'total'      => Shipment::whereDate('shipment_date', today())->count(),
            'dispatched' => Shipment::whereDate('shipment_date', today())
                ->where('status','dispatched')->count(),
            'completed'  => Shipment::whereDate('shipment_date', today())
                ->where('status','completed')->count(),
            'trucks'     => Truck::where('status','available')->count(),
        ];

        return view('distribution.shipments.index', compact('shipments','todayStats'));
    }

    public function create(): View
    {
        $trucks  = Truck::where('status','available')->where('is_active',true)->get();
        $drivers = Driver::where('is_active',true)->get();
        return view('distribution.shipments.create', compact('trucks','drivers'));
    }

    public function store(StoreShipmentRequest $request): RedirectResponse
    {
        $dto      = CreateShipmentDTO::fromArray($request->validated());
        $shipment = $this->service->create($dto);

        return redirect()
            ->route('shipments.show', $shipment)
            ->with('success', __('shipments.created', ['number' => $shipment->shipment_number]));
    }

    public function show(Shipment $shipment): View
    {
        $shipment->load(['truck','driver','orders.customer','orders.items.product']);

        $readyOrders = \App\Models\Order::with('customer')
            ->where('status','ready')
            ->whereNull('shipment_id')
            ->orderBy('order_date')
            ->get();

        return view('distribution.shipments.show', compact('shipment','readyOrders'));
    }

    public function dispatch(Shipment $shipment): RedirectResponse
    {
        $this->authorize('dispatch', $shipment);
        $this->service->dispatch($shipment);

        return back()->with('success', __('shipments.dispatched'));
    }

    public function complete(Shipment $shipment): RedirectResponse
    {
        $this->authorize('updateStatus', $shipment);
        $this->service->complete($shipment);

        return back()->with('success', __('shipments.completed'));
    }

    public function cancel(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorize('update', $shipment);
        $this->service->cancel($shipment, $request->get('reason', ''));

        return redirect()
            ->route('shipments.index')
            ->with('success', __('shipments.cancelled'));
    }

    public function manifest(Shipment $shipment): Response
    {
        $this->authorize('viewManifest', $shipment);

        $path = $shipment->manifest_path
            ?? $this->pdf->generateManifest($shipment);

        return $this->pdf->download($path, "قائمة-{$shipment->shipment_number}.pdf");
    }
}
```

### `app/Http/Controllers/Distribution/ShipmentOrderController.php`
```php
<?php
namespace App\Http\Controllers\Distribution;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shipments\AttachOrdersRequest;
use App\Models\{Order, Shipment};
use App\Services\Distribution\ShipmentService;
use Illuminate\Http\RedirectResponse;

/**
 * Manages order ↔ shipment assignment operations.
 * Separated from ShipmentController (Single Responsibility).
 *
 * @package App\Http\Controllers\Distribution
 */
class ShipmentOrderController extends Controller
{
    public function __construct(private readonly ShipmentService $service) {}

    public function attach(AttachOrdersRequest $request, Shipment $shipment): RedirectResponse
    {
        $this->authorize('update', $shipment);
        $this->service->attachOrders($shipment, $request->validated('order_ids'));

        return back()->with('success', __('shipments.orders_attached'));
    }

    public function detach(Shipment $shipment, Order $order): RedirectResponse
    {
        $this->authorize('update', $shipment);
        $this->service->detachOrder($shipment, $order);

        return back()->with('success', __('shipments.order_detached'));
    }

    public function markDelivered(Shipment $shipment, Order $order): RedirectResponse
    {
        $this->authorize('updateStatus', $shipment);
        $this->service->markOrderDelivered($shipment, $order);

        return back()->with('success', __('orders.delivery_confirmed'));
    }
}
```

### `app/Http/Controllers/Invoices/InvoiceController.php`
```php
<?php
namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\VoidInvoiceRequest;
use App\Models\Invoice;
use App\Notifications\InvoiceIssued;
use App\Services\Invoices\InvoiceService;
use App\Services\PdfService;
use Illuminate\Http\{RedirectResponse, Request, Response};
use Illuminate\View\View;

/**
 * Invoice read, print, download, send, void operations.
 * Invoice creation is handled by InvoiceService::createFromOrder (internal).
 *
 * @package App\Http\Controllers\Invoices
 */
class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $service,
        private readonly PdfService     $pdf,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::with(['customer','order'])
            ->when($request->customer_id, fn($q,$v) => $q->where('customer_id',$v))
            ->when($request->status,      fn($q,$v) => $q->where('status',$v))
            ->when($request->date_from,   fn($q,$v) => $q->whereDate('issue_date','>=',$v))
            ->when($request->date_to,     fn($q,$v) => $q->whereDate('issue_date','<=',$v))
            ->latest('issue_date')->paginate(config('factory.pagination.per_page', 20));

        $kpis = [
            'total'   => Invoice::whereNotIn('status',['void'])->count(),
            'paid'    => Invoice::where('status','paid')->count(),
            'partial' => Invoice::where('status','partial')->count(),
            'unpaid'  => Invoice::where('status','issued')->count(),
        ];

        return view('invoices.index', compact('invoices','kpis'));
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);
        $invoice->load(['order.items.product','customer','payments']);

        return view('invoices.show', compact('invoice'));
    }

    public function print(Invoice $invoice): Response
    {
        $this->authorize('print', $invoice);
        $invoice->load(['order.items.product','customer']);

        return $this->pdf->stream('pdf.invoice', compact('invoice'),
            "فاتورة-{$invoice->invoice_number}.pdf");
    }

    public function download(Invoice $invoice): Response
    {
        $this->authorize('print', $invoice);

        $path = $invoice->pdf_path ?? $this->pdf->generateInvoice($invoice);
        return $this->pdf->download($path, "فاتورة-{$invoice->invoice_number}.pdf");
    }

    public function send(Invoice $invoice): RedirectResponse
    {
        $this->authorize('send', $invoice);

        if ($invoice->customer->email) {
            $invoice->customer->notify(new \App\Notifications\InvoiceIssued($invoice));
            $this->service->invoices->update($invoice, ['sent_at' => now(), 'status' => 'sent']);
        }

        return back()->with('success', __('invoices.sent_successfully'));
    }

    public function void(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('void', $invoice);
        $this->service->void($invoice, $request->get('reason', ''));

        return back()->with('success', __('invoices.voided'));
    }
}
```

### `app/Http/Controllers/Invoices/PaymentController.php`
```php
<?php
namespace App\Http\Controllers\Invoices;

use App\DTOs\Invoices\RecordPaymentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\RecordPaymentRequest;
use App\Models\{Invoice, Payment};
use App\Services\Invoices\InvoiceService;
use Illuminate\Http\RedirectResponse;

/**
 * Records and deletes payments against invoices.
 *
 * @package App\Http\Controllers\Invoices
 */
class PaymentController extends Controller
{
    public function __construct(private readonly InvoiceService $service) {}

    public function store(RecordPaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $dto = RecordPaymentDTO::fromArray(array_merge(
            $request->validated(),
            ['invoice_id' => $invoice->id]
        ));

        $this->service->recordPayment($dto);

        return back()->with('success', __('invoices.payment_recorded'));
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorize('delete', $payment);
        $this->service->deletePayment($payment);

        return back()->with('success', __('invoices.payment_deleted'));
    }
}
```

### `app/Http/Controllers/Admin/SettingController.php`
```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

/**
 * System settings management.
 * Grouped tabs: factory info, invoices, stock, customers, UI.
 *
 * @package App\Http\Controllers\Admin
 */
class SettingController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    public function index(): View
    {
        $this->authorize('system.settings.view', \App\Models\User::class);
        $settings = $this->settings->all();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('system.settings.edit', \App\Models\User::class);

        $data = $request->except(['_token','_method','factory_logo']);

        // Handle logo upload
        if ($request->hasFile('factory_logo')) {
            $path = $request->file('factory_logo')
                ->store('public/factory', 'local');
            $data['factory_logo'] = str_replace('public/', '', $path);
        }

        $this->settings->setMany($data);

        return back()->with('success', __('admin.settings_saved'));
    }
}
```

### `app/Http/Controllers/Admin/UserController.php`
```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * User management for super_admin.
 * Handles CRUD operations and password resets.
 *
 * @package App\Http\Controllers\Admin
 */
class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('system.users.view', User::class);

        $users = User::with('roles')->latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::where('name', '!=', 'customer')->get();
        return view('admin.users.form', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('system.users.create', User::class);

        $data = $request->validate([
            'name'     => ['required','string','max:100'],
            'email'    => ['required','email','unique:users,email'],
            'phone'    => ['nullable','string','max:20'],
            'role'     => ['required','exists:roles,name'],
            'password' => ['required','string','min:8','confirmed'],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active'=> true,
        ]);

        $user->assignRole($data['role']);

        return redirect()->route('admin.users.index')
            ->with('success', __('admin.user_created', ['name' => $user->name]));
    }

    public function edit(User $user): View
    {
        $roles = Role::where('name', '!=', 'customer')->get();
        $user->load('roles');
        return view('admin.users.form', compact('user','roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('system.users.edit', User::class);

        $data = $request->validate([
            'name'     => ['required','string','max:100'],
            'phone'    => ['nullable','string','max:20'],
            'role'     => ['required','exists:roles,name'],
            'is_active'=> ['boolean'],
        ]);

        $user->update($data);
        $user->syncRoles([$data['role']]);

        return redirect()->route('admin.users.index')
            ->with('success', __('admin.user_updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('system.users.delete', User::class);

        if ($user->id === auth()->id()) {
            return back()->with('error', __('admin.cannot_delete_self'));
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', __('admin.user_deleted'));
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $this->authorize('system.users.edit', User::class);

        $temp = Str::random(12);
        $user->update(['password' => Hash::make($temp)]);

        // Notify user via email
        $user->notify(new \App\Notifications\PasswordReset($temp));

        return back()->with('success', __('admin.password_reset_sent'));
    }
}
```

### `app/Http/Controllers/Admin/AuditLogController.php`
```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

/**
 * Displays the full audit trail (read-only).
 * Accessible to super_admin only.
 *
 * @package App\Http\Controllers\Admin
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('system.audit_log.view', \App\Models\User::class);

        $logs = Activity::with(['causer','subject'])
            ->when($request->log_name,    fn($q,$v) => $q->where('log_name',$v))
            ->when($request->causer_id,   fn($q,$v) => $q->where('causer_id',$v))
            ->when($request->event,       fn($q,$v) => $q->where('event',$v))
            ->when($request->date_from,   fn($q,$v) => $q->whereDate('created_at','>=',$v))
            ->when($request->date_to,     fn($q,$v) => $q->whereDate('created_at','<=',$v))
            ->latest()
            ->paginate(50);

        $logNames = Activity::distinct()->pluck('log_name');

        return view('admin.audit-log.index', compact('logs','logNames'));
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION G — REMAINING LIVEWIRE COMPONENTS           ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Livewire/Shared/NotificationBell.php`
```php
<?php
namespace App\Livewire\Shared;

use Livewire\Attributes\Polling;
use Livewire\Component;

/**
 * Real-time notification bell in the topbar.
 * Polls every 30 seconds for new notifications.
 * Renders the unread count badge and dropdown list.
 *
 * @package App\Livewire\Shared
 */
class NotificationBell extends Component
{
    public int $unreadCount = 0;

    /** @var array<int, array<string, mixed>> */
    public array $notifications = [];

    public bool $open = false;

    #[Polling('30s')]
    public function poll(): void
    {
        $this->loadNotifications();
    }

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
        if ($this->open) {
            $this->loadNotifications();
        }
    }

    public function markRead(string $notificationId): void
    {
        auth()->user()
            ->notifications()
            ->where('id', $notificationId)
            ->update(['read_at' => now()]);

        $this->loadNotifications();
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        $this->loadNotifications();
    }

    private function loadNotifications(): void
    {
        $user                = auth()->user();
        $this->unreadCount   = $user->unreadNotifications()->count();
        $this->notifications = $user->notifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($n) => [
                'id'        => $n->id,
                'message'   => $n->data['message'] ?? '',
                'url'       => $n->data['url'] ?? '#',
                'read'      => ! is_null($n->read_at),
                'time'      => $n->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.shared.notification-bell');
    }
}
```

### `app/Livewire/Products/ProductSearch.php`
```php
<?php
namespace App\Livewire\Products;

use App\Contracts\Repositories\ProductRepositoryInterface;
use Livewire\Attributes\{Modelable, On};
use Livewire\Component;

/**
 * Autocomplete product search widget for the order form.
 * Emits 'product-selected' event with product data for the parent to consume.
 *
 * @package App\Livewire\Products
 */
class ProductSearch extends Component
{
    public string  $search        = '';
    public ?int    $selectedId    = null;
    public ?string $selectedName  = null;
    public bool    $showDropdown  = false;

    /** @var array<int, array<string, mixed>> */
    public array $results = [];

    public function __construct(
        private readonly ProductRepositoryInterface $products,
    ) {}

    public function updatedSearch(): void
    {
        if (strlen($this->search) < 2) {
            $this->results     = [];
            $this->showDropdown = false;
            return;
        }

        $this->results = $this->products
            ->searchForOrder($this->search, 8)
            ->map(fn($p) => [
                'id'         => $p->id,
                'name'       => $p->name,
                'code'       => $p->code,
                'unit'       => $p->unit,
                'unit_price' => $p->unit_price,
                'stock'      => $p->stock_quantity,
                'is_low'     => $p->is_low_stock,
            ])
            ->toArray();

        $this->showDropdown = ! empty($this->results);
    }

    public function select(int $productId): void
    {
        $product = collect($this->results)->firstWhere('id', $productId);

        if ($product) {
            $this->selectedId    = $productId;
            $this->selectedName  = $product['name'];
            $this->search        = $product['name'];
            $this->showDropdown  = false;

            $this->dispatch('product-selected', product: $product);
        }
    }

    public function clear(): void
    {
        $this->reset(['search','selectedId','selectedName','results','showDropdown']);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.products.product-search');
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION H — EMAIL VIEW TEMPLATES                    ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/emails/layout.blade.php`
```blade
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
    direction: rtl; text-align: right;
    background: #f3f4f6; color: #1f2937;
}
.wrapper { max-width: 600px; margin: 30px auto; }
.header {
    background: #1e3a8a; color: #fff;
    padding: 24px 32px; border-radius: 12px 12px 0 0;
    text-align: center;
}
.header h1 { font-size: 22px; font-weight: 700; }
.header p  { font-size: 13px; opacity: 0.85; margin-top: 4px; }
.body {
    background: #fff; padding: 32px;
    border-left: 1px solid #e5e7eb;
    border-right: 1px solid #e5e7eb;
}
.footer {
    background: #f9fafb; padding: 16px 32px;
    border: 1px solid #e5e7eb;
    border-radius: 0 0 12px 12px;
    text-align: center; font-size: 11px; color: #6b7280;
}
.btn {
    display: inline-block; margin: 16px 0;
    padding: 12px 28px; background: #2563eb;
    color: #fff; text-decoration: none;
    border-radius: 8px; font-weight: 600;
}
.divider { border: none; border-top: 1px solid #e5e7eb; margin: 20px 0; }
.badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
.badge-green  { background: #d1fae5; color: #065f46; }
.badge-blue   { background: #dbeafe; color: #1e40af; }
.badge-yellow { background: #fef3c7; color: #92400e; }
.badge-red    { background: #fee2e2; color: #991b1b; }
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>{{ \App\Facades\Setting::get('factory_name', 'المعمل') }}</h1>
        <p>{{ \App\Facades\Setting::get('factory_address', '') }}</p>
    </div>

    <div class="body">
        @yield('content')
    </div>

    <div class="footer">
        <p>{{ __('emails.do_not_reply') }}</p>
        <p style="margin-top:6px;">
            {{ \App\Facades\Setting::get('factory_name') }} ·
            {{ \App\Facades\Setting::get('factory_phone', '') }}
        </p>
    </div>
</div>
</body>
</html>
```

### `resources/views/emails/order-status.blade.php`
```blade
@extends('emails.layout')

@section('content')
<p>مرحباً {{ $notifiable->name }}،</p>
<br>
<p>نود إعلامكم بأن حالة طلبيتكم قد تغيّرت:</p>

<div style="margin: 20px 0; padding: 16px; background: #f0f4f8; border-radius: 8px;">
    <p><strong>رقم الطلبية:</strong> {{ $order->order_number }}</p>
    <p style="margin-top: 8px;">
        <strong>الحالة الجديدة:</strong>
        <span class="badge badge-{{ in_array($order->status, ['delivered','paid']) ? 'green' : (in_array($order->status, ['cancelled','returned']) ? 'red' : 'blue') }}">
            {{ $statusLabel }}
        </span>
    </p>
    @if($order->order_date)
        <p style="margin-top:8px;"><strong>تاريخ الطلبية:</strong> {{ $order->order_date->format('Y/m/d') }}</p>
    @endif
    <p style="margin-top:8px;"><strong>الإجمالي:</strong> {{ money_format($order->total_amount) }}</p>
</div>

@if($order->status === 'delivered' && $order->invoice)
    <p>يمكنكم الاطلاع على فاتورتكم عبر البوابة الإلكترونية.</p>
@endif

@if($order->status === 'cancelled' && $order->cancel_reason)
    <p><strong>سبب الإلغاء:</strong> {{ $order->cancel_reason }}</p>
@endif

<hr class="divider">
<p style="color:#6b7280; font-size:13px;">شكراً لتعاملكم معنا.</p>
@endsection
```

### `resources/views/emails/invoice-issued.blade.php`
```blade
@extends('emails.layout')

@section('content')
<p>مرحباً {{ $notifiable->name }}،</p>
<br>
<p>نرسل إليكم فاتورة مبيعات جديدة:</p>

<div style="margin: 20px 0; padding: 16px; background: #f0f4f8; border-radius: 8px;">
    <p><strong>رقم الفاتورة:</strong> {{ $invoice->invoice_number }}</p>
    <p style="margin-top:8px;"><strong>تاريخ الإصدار:</strong> {{ $invoice->issue_date->format('Y/m/d') }}</p>
    <p style="margin-top:8px;"><strong>تاريخ الاستحقاق:</strong> {{ $invoice->due_date?->format('Y/m/d') ?? '—' }}</p>
    <p style="margin-top:8px;"><strong>الإجمالي الكلي:</strong> {{ money_format($invoice->total_amount) }}</p>
    <p style="margin-top:8px;">
        <strong>الرصيد المستحق:</strong>
        <span style="color:#dc2626; font-weight:bold;">{{ money_format($invoice->balance_due) }}</span>
    </p>
</div>

<a href="{{ route('portal.invoices.show', $invoice) }}" class="btn">
    عرض الفاتورة كاملة ←
</a>

<hr class="divider">
<p style="color:#6b7280; font-size:12px;">
    {{ \App\Facades\Setting::get('invoice_terms', '') }}
</p>
@endsection
```

### `resources/views/emails/payment-confirmed.blade.php`
```blade
@extends('emails.layout')

@section('content')
<p>مرحباً {{ $notifiable->name }}،</p>
<br>
<p>تم استلام دفعتكم بنجاح. شكراً جزيلاً!</p>

<div style="margin: 20px 0; padding: 16px; background: #ecfdf5; border-radius: 8px; border-right: 4px solid #10b981;">
    <p><strong>المبلغ المستلم:</strong>
        <span style="font-size: 18px; font-weight: bold; color: #059669;">
            {{ money_format($payment->amount) }}
        </span>
    </p>
    <p style="margin-top:8px;"><strong>التاريخ:</strong> {{ $payment->payment_date->format('Y/m/d') }}</p>
    <p style="margin-top:8px;"><strong>طريقة الدفع:</strong>
        {{ config("factory.payment_methods.{$payment->payment_method}", $payment->payment_method) }}
    </p>
    <p style="margin-top:8px;"><strong>رقم الفاتورة:</strong> {{ $payment->invoice->invoice_number }}</p>
    @if($payment->invoice->balance_due > 0)
        <p style="margin-top:8px;">
            <strong>الرصيد المتبقي:</strong> {{ money_format($payment->invoice->balance_due) }}
        </p>
    @else
        <p style="margin-top:8px; color:#059669;"><strong>✓ الفاتورة مسددة بالكامل</strong></p>
    @endif
</div>
@endsection
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION I — ERROR PAGE VIEWS                        ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/errors/404.blade.php`
```blade
@extends('layouts.app')
@section('title', 'الصفحة غير موجودة')
@section('content')
<div class="min-h-96 flex items-center justify-center">
    <div class="text-center">
        <div class="text-8xl font-bold text-gray-200">٤٠٤</div>
        <h1 class="text-2xl font-bold text-gray-800 mt-4">الصفحة غير موجودة</h1>
        <p class="text-gray-500 mt-2">الصفحة التي تبحث عنها غير موجودة أو تم نقلها.</p>
        <a href="{{ url('/') }}" class="btn-primary mt-6 inline-block">
            ← العودة للرئيسية
        </a>
    </div>
</div>
@endsection
```

### `resources/views/errors/403.blade.php`
```blade
@extends('layouts.app')
@section('title', 'غير مصرح')
@section('content')
<div class="min-h-96 flex items-center justify-center">
    <div class="text-center">
        <div class="text-8xl font-bold text-red-100">٤٠٣</div>
        <h1 class="text-2xl font-bold text-gray-800 mt-4">غير مصرح بالوصول</h1>
        <p class="text-gray-500 mt-2">ليس لديك صلاحية للوصول إلى هذه الصفحة.</p>
        <a href="{{ url()->previous() }}" class="btn-secondary mt-6 inline-block">
            ← العودة للصفحة السابقة
        </a>
    </div>
</div>
@endsection
```

### `resources/views/errors/500.blade.php`
```blade
@extends('layouts.auth')
@section('title', 'خطأ في الخادم')
@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center p-8">
        <div class="text-8xl font-bold text-gray-200">٥٠٠</div>
        <h1 class="text-2xl font-bold text-gray-800 mt-4">حدث خطأ في الخادم</h1>
        <p class="text-gray-500 mt-2">نعتذر، حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.</p>
        <a href="{{ url('/') }}" class="btn-primary mt-6 inline-block">
            ← العودة للرئيسية
        </a>
    </div>
</div>
@endsection
```

### `resources/views/errors/maintenance.blade.php`
```blade
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>صيانة النظام</title>
<style>
body { font-family: Cairo, sans-serif; direction: rtl; text-align: center;
       background: #f3f4f6; display:flex; align-items:center;
       justify-content:center; min-height:100vh; margin:0; }
.card { background:#fff; border-radius:12px; padding:48px; max-width:480px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
h1 { font-size:24px; color:#1e3a8a; }
p  { color:#6b7280; margin-top:12px; line-height:1.7; }
</style>
</head>
<body>
<div class="card">
    <div style="font-size:48px;">🔧</div>
    <h1>النظام تحت الصيانة</h1>
    <p>نقوم حالياً بتحديث النظام وإجراء تحسينات مهمة.<br>
       يرجى المحاولة مرة أخرى خلال دقائق قليلة.</p>
    <p style="margin-top:24px; font-size:13px; color:#9ca3af;">
        {{ \App\Facades\Setting::get('factory_name', 'النظام') }}
    </p>
</div>
</body>
</html>
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION J — EXPORT STRATEGY IMPLEMENTATIONS         ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Contracts/Export/ExportStrategyInterface.php`
```php
<?php
namespace App\Contracts\Export;

/**
 * Strategy interface for report data export.
 * Implementations: ExcelExportStrategy, CsvExportStrategy, PdfExportStrategy.
 *
 * @package App\Contracts\Export
 */
interface ExportStrategyInterface
{
    /**
     * Export the given data to a file and return the storage path.
     *
     * @param  array<int, array<string, mixed>> $data     Row data
     * @param  array<string>                    $headers  Column headers (Arabic)
     * @return string  Absolute path to the generated file
     */
    public function export(array $data, array $headers): string;

    /** MIME type for this format (e.g. application/vnd.openxmlformats-officedocument.spreadsheetml.sheet) */
    public function getMimeType(): string;

    /** File extension without dot (e.g. xlsx, csv, pdf) */
    public function getExtension(): string;
}
```

### `app/Services/Export/ExcelExportStrategy.php`
```php
<?php
namespace App\Services\Export;

use App\Contracts\Export\ExportStrategyInterface;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\{FromArray, WithHeadings, WithStyles, WithColumnWidths, ShouldAutoSize};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill};

/**
 * Excel export strategy using Laravel Excel.
 * Produces RTL-configured XLSX with Arabic headers and bold header row.
 *
 * @package App\Services\Export
 */
class ExcelExportStrategy implements ExportStrategyInterface,
    FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    private array $data    = [];
    private array $headers = [];

    public function export(array $data, array $headers): string
    {
        $this->data    = $data;
        $this->headers = $headers;

        $filename = 'exports/report-' . now()->format('Y-m-d-His') . '.xlsx';
        Excel::store($this, $filename, 'local');

        return Storage::path($filename);
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function styles(Worksheet $sheet): array
    {
        // Right-to-left sheet
        $sheet->setRightToLeft(true);

        // Style the header row
        return [
            1 => [
                'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ],
        ];
    }

    public function getMimeType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public function getExtension(): string
    {
        return 'xlsx';
    }
}
```

### `app/Services/Export/CsvExportStrategy.php`
```php
<?php
namespace App\Services\Export;

use App\Contracts\Export\ExportStrategyInterface;
use Illuminate\Support\Facades\Storage;

/**
 * CSV export strategy with UTF-8 BOM for Excel Arabic compatibility.
 *
 * @package App\Services\Export
 */
class CsvExportStrategy implements ExportStrategyInterface
{
    public function export(array $data, array $headers): string
    {
        $filename = 'exports/report-' . now()->format('Y-m-d-His') . '.csv';
        $path     = Storage::path($filename);

        Storage::makeDirectory('exports');

        $handle = fopen($path, 'w');

        // UTF-8 BOM — required for Arabic characters in Excel CSV
        fwrite($handle, "\xEF\xBB\xBF");

        // Header row
        fputcsv($handle, $headers);

        // Data rows
        foreach ($data as $row) {
            fputcsv($handle, is_array($row) ? array_values($row) : (array) $row);
        }

        fclose($handle);

        return $path;
    }

    public function getMimeType(): string
    {
        return 'text/csv; charset=UTF-8';
    }

    public function getExtension(): string
    {
        return 'csv';
    }
}
```

### `app/Facades/Setting.php`
```php
<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for SettingService.
 *
 * Usage:
 *   Setting::get('factory_name')
 *   Setting::set('invoice_tax_rate', 0.15)
 *   Setting::all()
 *
 * @method static mixed  get(string $key, mixed $default = null)
 * @method static void   set(string $key, mixed $value)
 * @method static void   setMany(array $pairs)
 * @method static array  all()
 *
 * @package App\Facades
 */
class Setting extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\SettingService::class;
    }
}
```

Register in `config/app.php` aliases:
```php
'aliases' => Facade::defaultAliases()->merge([
    'Setting' => \App\Facades\Setting::class,
])->toArray(),
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION K — CONFIGURATION FILES                     ║
## ╚══════════════════════════════════════════════════════════════╝

### `vite.config.js`
```javascript
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/charts.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // Split vendor libs for better caching
                    'vendor-alpine':  ['alpinejs'],
                    'vendor-charts':  ['chart.js'],
                    'vendor-flatpickr': ['flatpickr'],
                    'vendor-tomselect': ['tom-select'],
                },
            },
        },
    },
    server: {
        // Docker-compatible dev server config
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        },
    },
})
```

### `pest.php` (Pest configuration)
```php
<?php
/**
 * Pest PHP configuration.
 * Uses SQLite in-memory for fast test runs.
 * Seeds RolesAndPermissionsSeeder in every test that needs auth.
 */
uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class,
)->in('Feature');

uses(
    Tests\TestCase::class,
)->in('Unit');

/*
 * Enforce minimum coverage on Services and Models.
 * Run with: ./vendor/bin/pest --coverage --min=80
 */
covers(
    App\Services\Orders\OrderService::class,
    App\Services\Orders\OrderStatusService::class,
    App\Services\Orders\OrderFinancialsService::class,
    App\Services\Invoices\InvoiceService::class,
    App\Services\Products\StockService::class,
    App\Services\Customers\CustomerService::class,
    App\Services\Distribution\ShipmentService::class,
    App\ValueObjects\Money::class,
    App\StateMachines\OrderStateMachine::class,
    App\StateMachines\ShipmentStateMachine::class,
    App\Helpers\AmountToWords::class,
)->in('Unit');
```

### `tests/TestCase.php`
```php
<?php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Act as a specific role for convenience.
     */
    protected function actingAsRole(string $role): static
    {
        $user = \App\Models\User::factory()->create()->assignRole($role);
        return $this->actingAs($user);
    }

    /**
     * Assert that Arabic text appears in the response.
     */
    protected function assertArabicText(string $text): void
    {
        $this->assertStringContainsString($text, $this->response->getContent());
    }
}
```

### `phpunit.xml` — Test database config
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">

    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>

    <source>
        <include>
            <directory>app</directory>
        </include>
        <exclude>
            <directory>app/Console</directory>
            <directory>app/Exceptions</directory>
            <directory>app/Http/Middleware</directory>
        </exclude>
    </source>

    <php>
        <env name="APP_ENV"            value="testing"/>
        <env name="APP_DEBUG"          value="true"/>
        <env name="DB_CONNECTION"      value="sqlite"/>
        <env name="DB_DATABASE"        value=":memory:"/>
        <env name="CACHE_STORE"        value="array"/>
        <env name="SESSION_DRIVER"     value="array"/>
        <env name="QUEUE_CONNECTION"   value="sync"/>
        <env name="MAIL_MAILER"        value="log"/>
        <env name="BCRYPT_ROUNDS"      value="4"/>
    </php>

</phpunit>
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION L — SHIPMENT STATE MACHINE & REMAINING DTOs ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/StateMachines/ShipmentStateMachine.php`
```php
<?php
namespace App\StateMachines;

use App\Exceptions\InvalidStatusTransitionException;

/**
 * Manages valid shipment status transitions.
 *
 * @package App\StateMachines
 */
final class ShipmentStateMachine
{
    private const TRANSITIONS = [
        'planned'    => ['loading', 'dispatched', 'cancelled'],
        'loading'    => ['dispatched', 'cancelled'],
        'dispatched' => ['completed', 'cancelled'],
        'completed'  => [],
        'cancelled'  => [],
    ];

    /**
     * @throws InvalidStatusTransitionException
     */
    public function transition(string $currentStatus, string $newStatus): string
    {
        $allowed = self::TRANSITIONS[$currentStatus] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new InvalidStatusTransitionException(
                "Cannot transition shipment from [{$currentStatus}] to [{$newStatus}]. " .
                'Allowed: ' . implode(', ', $allowed)
            );
        }

        return $newStatus;
    }

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function isFinal(string $status): bool
    {
        return empty(self::TRANSITIONS[$status]);
    }
}
```

### `app/DTOs/Shipments/CreateShipmentDTO.php`
```php
<?php
namespace App\DTOs\Shipments;

use Carbon\Carbon;

/**
 * DTO for creating a new shipment.
 *
 * @package App\DTOs\Shipments
 */
final class CreateShipmentDTO
{
    public function __construct(
        public readonly int     $truckId,
        public readonly int     $driverId,
        public readonly Carbon  $shipmentDate,
        public readonly ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            truckId:      (int) $data['truck_id'],
            driverId:     (int) $data['driver_id'],
            shipmentDate: Carbon::parse($data['shipment_date']),
            notes:        $data['notes'] ?? null,
        );
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION M — REMAINING FORM REQUESTS                 ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Http/Requests/Invoices/RecordPaymentRequest.php`
```php
<?php
namespace App\Http\Requests\Invoices;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates payment recording payload.
 *
 * @package App\Http\Requests\Invoices
 */
class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('payments.create');
    }

    public function rules(): array
    {
        return [
            'amount'           => ['required', 'integer', 'min:1'],
            'payment_method'   => ['required', 'in:cash,credit,check,bank_transfer'],
            'payment_date'     => ['required', 'date', 'before_or_equal:today'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ];
    }
}
```

### `app/Http/Requests/Shipments/StoreShipmentRequest.php`
```php
<?php
namespace App\Http\Requests\Shipments;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates shipment creation payload.
 *
 * @package App\Http\Requests\Shipments
 */
class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('shipments.create');
    }

    public function rules(): array
    {
        return [
            'truck_id'      => ['required', 'integer', 'exists:trucks,id'],
            'driver_id'     => ['required', 'integer', 'exists:drivers,id'],
            'shipment_date' => ['required', 'date', 'after_or_equal:today'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ];
    }
}
```

### `app/Http/Requests/Shipments/AttachOrdersRequest.php`
```php
<?php
namespace App\Http\Requests\Shipments;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates order attachment to shipment.
 *
 * @package App\Http\Requests\Shipments
 */
class AttachOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('shipments.edit');
    }

    public function rules(): array
    {
        return [
            'order_ids'   => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
        ];
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION N — REMAINING LANGUAGE FILES                ║
## ╚══════════════════════════════════════════════════════════════╝

### `lang/ar/auth.php`
```php
<?php
return [
    'failed'             => 'بيانات الدخول غير صحيحة.',
    'throttle'           => 'محاولات كثيرة جداً. يرجى المحاولة بعد :minutes دقيقة.',
    'password'           => 'كلمة المرور غير صحيحة.',
    'unauthorized'       => 'غير مصرح لك بالوصول إلى هذا المورد.',
    'unauthenticated'    => 'يجب تسجيل الدخول أولاً.',
    'account_deactivated'=> 'حسابك غير مفعّل. يرجى التواصل مع المسؤول.',
    'portal_only'        => 'يمكنك الوصول إلى بوابة العملاء فقط.',
    'login_required'     => 'يجب إدخال البريد الإلكتروني أو رقم الهاتف.',
    'password_required'  => 'يجب إدخال كلمة المرور.',
    'remember_me'        => 'تذكرني',
    'forgot_password'    => 'نسيت كلمة المرور؟',
    'login'              => 'تسجيل الدخول',
    'logout'             => 'تسجيل الخروج',
];
```

### `lang/ar/app.php`
```php
<?php
return [
    // Module names
    'modules' => [
        'dashboard'    => 'الرئيسية',
        'inventory'    => 'المخزون',
        'products'     => 'المنتجات',
        'customers'    => 'العملاء',
        'orders'       => 'الطلبيات',
        'distribution' => 'التوزيع',
        'shipments'    => 'الشحنات',
        'invoices'     => 'الفواتير',
        'payments'     => 'المدفوعات',
        'erp'          => 'نظام المحاسبة',
        'expenses'     => 'المصروفات',
        'reports'      => 'التقارير',
        'admin'        => 'الإدارة',
        'users'        => 'المستخدمون',
        'settings'     => 'الإعدادات',
        'audit_log'    => 'سجل المراجعة',
    ],

    // Common actions
    'actions' => [
        'add'       => 'إضافة',
        'edit'      => 'تعديل',
        'delete'    => 'حذف',
        'view'      => 'عرض',
        'save'      => 'حفظ',
        'cancel'    => 'إلغاء',
        'confirm'   => 'تأكيد',
        'search'    => 'بحث',
        'filter'    => 'تصفية',
        'export'    => 'تصدير',
        'print'     => 'طباعة',
        'download'  => 'تنزيل',
        'send'      => 'إرسال',
        'restore'   => 'استعادة',
        'back'      => 'عودة',
        'next'      => 'التالي',
        'previous'  => 'السابق',
    ],

    // Common labels
    'labels' => [
        'yes'           => 'نعم',
        'no'            => 'لا',
        'active'        => 'مفعّل',
        'inactive'      => 'غير مفعّل',
        'total'         => 'الإجمالي',
        'subtotal'      => 'المجموع الفرعي',
        'discount'      => 'الخصم',
        'tax'           => 'الضريبة',
        'balance_due'   => 'المتبقي',
        'paid'          => 'المدفوع',
        'notes'         => 'ملاحظات',
        'date'          => 'التاريخ',
        'status'        => 'الحالة',
        'actions'       => 'الإجراءات',
        'created_at'    => 'تاريخ الإنشاء',
        'updated_at'    => 'تاريخ التحديث',
        'no_records'    => 'لا توجد سجلات',
        'loading'       => 'جاري التحميل...',
        'confirm_delete'=> 'هل أنت متأكد من الحذف؟ هذا الإجراء لا يمكن التراجع عنه.',
    ],
];
```

### `lang/ar/shipments.php`
```php
<?php
return [
    'created'                          => 'تم إنشاء الشحنة :number بنجاح',
    'dispatched'                       => 'تم إرسال الشحنة بنجاح',
    'completed'                        => 'تم إنهاء الرحلة بنجاح',
    'cancelled'                        => 'تم إلغاء الشحنة',
    'orders_attached'                  => 'تم إضافة الطلبيات للشحنة',
    'order_detached'                   => 'تم إزالة الطلبية من الشحنة',
    'truck_not_available'              => 'الشاحنة غير متاحة (حالتها: :status)',
    'driver_already_assigned'          => 'السائق لديه شحنة أخرى في نفس اليوم',
    'cannot_modify_dispatched'         => 'لا يمكن تعديل شحنة في الطريق',
    'order_not_in_shipment'            => 'هذه الطلبية لا تنتمي لهذه الشحنة',
    'cannot_complete_with_pending'     => 'لا يمكن إنهاء الرحلة — يوجد طلبيات لم تسلّم بعد',
];
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION O — FEATURE TESTS (REMAINING)               ║
## ╚══════════════════════════════════════════════════════════════╝

### `tests/Feature/ShipmentFlowTest.php`
```php
<?php
use App\Models\{Driver, Order, Shipment, Truck, User, Customer, Product};
use App\Services\Distribution\ShipmentService;
use App\DTOs\Shipments\CreateShipmentDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin    = User::factory()->create()->assignRole('super_admin');
    $this->truck    = Truck::factory()->create(['status' => 'available']);
    $this->driver   = Driver::factory()->create(['is_active' => true]);
    $this->customer = Customer::factory()->create();
    $this->product  = Product::factory()->create(['stock_quantity' => 100]);
    $this->actingAs($this->admin);
});

it('creates a shipment with truck and driver', function () {
    $dto = CreateShipmentDTO::fromArray([
        'truck_id'      => $this->truck->id,
        'driver_id'     => $this->driver->id,
        'shipment_date' => today()->toDateString(),
    ]);

    $shipment = app(ShipmentService::class)->create($dto);

    expect($shipment->status)->toBe('planned')
        ->and($shipment->shipment_number)->toMatch('/^SHP-\d{4}-\d{5}$/');
});

it('blocks shipment with unavailable truck', function () {
    $this->truck->update(['status' => 'maintenance']);

    $dto = CreateShipmentDTO::fromArray([
        'truck_id'      => $this->truck->id,
        'driver_id'     => $this->driver->id,
        'shipment_date' => today()->toDateString(),
    ]);

    expect(fn() => app(ShipmentService::class)->create($dto))
        ->toThrow(\DomainException::class);
});

it('attaches ready orders to a shipment', function () {
    $shipment = Shipment::factory()->create([
        'truck_id'  => $this->truck->id,
        'driver_id' => $this->driver->id,
        'status'    => 'planned',
    ]);

    $order = Order::factory()->create([
        'customer_id' => $this->customer->id,
        'status'      => 'ready',
    ]);

    app(ShipmentService::class)->attachOrders($shipment, [$order->id]);

    expect($order->fresh()->shipment_id)->toBe($shipment->id);
});

it('dispatches a shipment and changes order status to shipped', function () {
    $shipment = Shipment::factory()->create([
        'truck_id'  => $this->truck->id,
        'driver_id' => $this->driver->id,
        'status'    => 'planned',
    ]);

    $order = Order::factory()->create([
        'customer_id' => $this->customer->id,
        'status'      => 'ready',
        'shipment_id' => $shipment->id,
    ]);

    app(ShipmentService::class)->dispatch($shipment);

    expect($order->fresh()->status)->toBe('shipped')
        ->and($shipment->fresh()->status)->toBe('dispatched')
        ->and($this->truck->fresh()->status)->toBe('on_trip');
});

it('completes shipment and frees truck', function () {
    $shipment = Shipment::factory()->create([
        'truck_id'  => $this->truck->id,
        'driver_id' => $this->driver->id,
        'status'    => 'dispatched',
    ]);
    $this->truck->update(['status' => 'on_trip']);

    // No pending orders → can complete
    app(ShipmentService::class)->complete($shipment);

    expect($shipment->fresh()->status)->toBe('completed')
        ->and($this->truck->fresh()->status)->toBe('available');
});
```

### `tests/Feature/PdfDownloadTest.php`
```php
<?php
use App\Models\{Customer, Invoice, Order, User};
use App\Models\{Shipment, Truck, Driver};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin    = User::factory()->create()->assignRole('super_admin');
    $this->customer = Customer::factory()->create();
    $this->actingAs($this->admin);
});

it('streams invoice PDF with correct content type', function () {
    $invoice = Invoice::factory()->create([
        'customer_id'  => $this->customer->id,
        'status'       => 'issued',
        'total_amount' => 50_000,
        'paid_amount'  => 0,
        'balance_due'  => 50_000,
        'issue_date'   => today(),
    ]);

    // The invoice needs an order
    $order = Order::factory()->delivered()->for($this->customer)->create();
    $invoice->update(['order_id' => $order->id]);

    $response = $this->get(route('invoices.print', $invoice));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
});

it('requires authentication to download invoice PDF', function () {
    $invoice = Invoice::factory()->create(['customer_id' => $this->customer->id]);

    auth()->logout();

    $this->get(route('invoices.download', $invoice))
        ->assertRedirect(route('login'));
});

it('prevents customer from downloading other customers invoice', function () {
    $otherCustomer = Customer::factory()->create();
    $invoice       = Invoice::factory()->create(['customer_id' => $otherCustomer->id]);

    $customerUser = User::factory()->create()->assignRole('customer');
    $this->actingAs($customerUser);

    $this->get(route('portal.invoices.show', $invoice))->assertForbidden();
});
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION P — LOGIN VIEW & KEY PARTIALS               ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/auth/login.blade.php`
```blade
@extends('layouts.auth')
@section('title', __('auth.login'))

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-brand-900 to-brand-700 p-4">
    <div class="w-full max-w-md">

        {{-- Logo & Title --}}
        <div class="text-center mb-8">
            @if($logo = \App\Facades\Setting::get('factory_logo'))
                <img src="{{ asset('storage/'.$logo) }}" alt="logo" class="h-16 mx-auto mb-4">
            @else
                <div class="w-16 h-16 bg-white rounded-2xl mx-auto mb-4 flex items-center justify-center">
                    <span class="text-2xl text-brand-700">🏭</span>
                </div>
            @endif
            <h1 class="text-2xl font-bold text-white">
                {{ \App\Facades\Setting::get('factory_name', config('app.name')) }}
            </h1>
            <p class="text-brand-200 text-sm mt-1">نظام إدارة المعمل</p>
        </div>

        {{-- Login Card --}}
        <div class="card p-8">
            <h2 class="text-xl font-bold text-gray-800 mb-6 text-center">تسجيل الدخول</h2>

            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            @if(session('status'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="form-label" for="login">
                        البريد الإلكتروني أو رقم الهاتف
                    </label>
                    <input
                        id="login" name="login" type="text"
                        value="{{ old('login') }}"
                        required autocomplete="username"
                        class="form-input @error('login') border-red-500 @enderror"
                        placeholder="example@domain.com أو 09XXXXXXXX"
                        autofocus>
                    @error('login')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label" for="password">كلمة المرور</label>
                    <input
                        id="password" name="password" type="password"
                        required autocomplete="current-password"
                        class="form-input @error('password') border-red-500 @enderror"
                        placeholder="••••••••">
                    @error('password')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input name="remember" type="checkbox" class="rounded border-gray-300">
                        <span class="text-sm text-gray-600">{{ __('auth.remember_me') }}</span>
                    </label>
                    <a href="{{ route('password.request') }}"
                       class="text-sm text-brand-600 hover:underline">
                        {{ __('auth.forgot_password') }}
                    </a>
                </div>

                <button type="submit" class="btn-primary w-full py-3 text-base">
                    {{ __('auth.login') }}
                </button>
            </form>
        </div>

        <p class="text-center text-brand-300 text-xs mt-6">
            {{ config('app.name') }} &copy; {{ date('Y') }}
        </p>
    </div>
</div>
@endsection
```

### `resources/views/layouts/partials/sidebar.blade.php`
```blade
{{-- Main Navigation Sidebar — Arabic RTL --}}
<aside
    class="fixed inset-y-0 right-0 z-50 w-64 bg-white border-l border-gray-200 shadow-lg
           flex flex-col transform transition-transform duration-300"
    :class="$store.sidebar.open ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
    x-data>

    {{-- Header --}}
    <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
        @if($logo = \App\Facades\Setting::get('factory_logo'))
            <img src="{{ asset('storage/'.$logo) }}" class="h-8 w-8 object-contain rounded" alt="logo">
        @else
            <div class="h-8 w-8 bg-brand-600 rounded-lg flex items-center justify-center">
                <span class="text-white text-xs font-bold">م</span>
            </div>
        @endif
        <span class="font-bold text-gray-800 text-sm leading-tight">
            {{ \App\Facades\Setting::get('factory_name', 'المعمل') }}
        </span>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">

        {{-- Dashboard --}}
        @can('erp.dashboard.view')
        <a href="{{ route('erp.dashboard') }}"
           class="nav-item {{ request()->routeIs('erp.dashboard') ? 'active' : '' }}">
            <span>📊</span> {{ __('app.modules.dashboard') }}
        </a>
        @endcan

        {{-- Inventory --}}
        @can('products.view')
        <div x-data="{ open: {{ request()->routeIs('products.*','stock.*') ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="nav-item w-full flex justify-between">
                <span><span class="ml-2">📦</span>{{ __('app.modules.inventory') }}</span>
                <span x-show="!open">▸</span><span x-show="open">▾</span>
            </button>
            <div x-show="open" class="mr-4 mt-1 space-y-1 border-r-2 border-brand-100 pr-3">
                <a href="{{ route('products.index') }}"
                   class="nav-item text-xs {{ request()->routeIs('products.*') ? 'active' : '' }}">
                    المنتجات والبضاعة
                </a>
                <a href="{{ route('stock.low-alert') }}"
                   class="nav-item text-xs {{ request()->routeIs('stock.low-alert') ? 'active' : '' }}">
                    تنبيهات المخزون
                    @php($lowCount = cache()->remember('low_stock_count',300, fn()=>\App\Models\Product::whereColumn('stock_quantity','<=','low_stock_threshold')->count()))
                    @if($lowCount > 0)
                        <span class="mr-auto bg-red-100 text-red-700 text-xs px-1.5 py-0.5 rounded-full">
                            {{ $lowCount }}
                        </span>
                    @endif
                </a>
            </div>
        </div>
        @endcan

        {{-- Customers --}}
        @can('customers.view')
        <a href="{{ route('customers.index') }}"
           class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
            <span>👥</span> {{ __('app.modules.customers') }}
        </a>
        @endcan

        {{-- Orders --}}
        @can('orders.view')
        <div x-data="{ open: {{ request()->routeIs('orders.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="nav-item w-full flex justify-between">
                <span><span class="ml-2">📋</span>{{ __('app.modules.orders') }}</span>
                <span x-show="!open">▸</span><span x-show="open">▾</span>
            </button>
            <div x-show="open" class="mr-4 mt-1 space-y-1 border-r-2 border-brand-100 pr-3">
                <a href="{{ route('orders.index') }}" class="nav-item text-xs">جميع الطلبيات</a>
                <a href="{{ route('orders.create') }}" class="nav-item text-xs">إضافة طلبية</a>
                <a href="{{ route('orders.daily') }}" class="nav-item text-xs">طلبيات اليوم</a>
            </div>
        </div>
        @endcan

        {{-- Distribution --}}
        @can('shipments.view')
        <div x-data="{ open: {{ request()->routeIs('shipments.*','trucks.*','drivers.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="nav-item w-full flex justify-between">
                <span><span class="ml-2">🚛</span>{{ __('app.modules.distribution') }}</span>
                <span x-show="!open">▸</span><span x-show="open">▾</span>
            </button>
            <div x-show="open" class="mr-4 mt-1 space-y-1 border-r-2 border-brand-100 pr-3">
                <a href="{{ route('shipments.index') }}" class="nav-item text-xs">رحلات الشحن</a>
                <a href="{{ route('trucks.index') }}"   class="nav-item text-xs">الشاحنات</a>
                <a href="{{ route('drivers.index') }}"  class="nav-item text-xs">السائقون</a>
            </div>
        </div>
        @endcan

        {{-- Invoices --}}
        @can('invoices.view')
        <a href="{{ route('invoices.index') }}"
           class="nav-item {{ request()->routeIs('invoices.*','payments.*') ? 'active' : '' }}">
            <span>🧾</span> {{ __('app.modules.invoices') }}
        </a>
        @endcan

        {{-- ERP Separator --}}
        @can('erp.dashboard.view')
        <div class="nav-group-label">نظام المحاسبة</div>

        <a href="{{ route('erp.dashboard') }}"
           class="nav-item {{ request()->routeIs('erp.dashboard') ? 'active' : '' }}">
            <span>💹</span> لوحة التحكم المالية
        </a>
        <a href="{{ route('erp.expenses.index') }}"
           class="nav-item {{ request()->routeIs('erp.expenses.*') ? 'active' : '' }}">
            <span>💳</span> المصروفات
        </a>
        <div x-data="{ open: {{ request()->routeIs('erp.reports.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="nav-item w-full flex justify-between">
                <span><span class="ml-2">📈</span>التقارير</span>
                <span x-show="!open">▸</span><span x-show="open">▾</span>
            </button>
            <div x-show="open" class="mr-4 mt-1 space-y-1 border-r-2 border-brand-100 pr-3">
                <a href="{{ route('erp.reports.sales') }}"           class="nav-item text-xs">المبيعات</a>
                <a href="{{ route('erp.reports.receivables') }}"     class="nav-item text-xs">الديون</a>
                <a href="{{ route('erp.reports.stock-movements') }}" class="nav-item text-xs">حركة المخزون</a>
                <a href="{{ route('erp.reports.profit-loss') }}"     class="nav-item text-xs">الأرباح والخسائر</a>
            </div>
        </div>
        @endcan

        {{-- Admin --}}
        @can('system.users.view')
        <div class="nav-group-label">الإدارة</div>
        <a href="{{ route('admin.users.index') }}"
           class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <span>👤</span> المستخدمون
        </a>
        @endcan
        @can('system.settings.view')
        <a href="{{ route('admin.settings.index') }}"
           class="nav-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
            <span>⚙️</span> إعدادات النظام
        </a>
        @endcan
        @can('system.audit_log.view')
        <a href="{{ route('admin.audit-log.index') }}"
           class="nav-item {{ request()->routeIs('admin.audit-log.*') ? 'active' : '' }}">
            <span>📜</span> سجل المراجعة
        </a>
        @endcan
    </nav>

    {{-- User info at bottom --}}
    <div class="border-t border-gray-100 p-4">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-brand-100 flex items-center justify-center
                        text-brand-700 font-bold text-sm flex-shrink-0">
                {{ mb_substr(auth()->user()->name, 0, 1) }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-gray-800 truncate">{{ auth()->user()->name }}</div>
                <div class="text-xs text-gray-500">
                    {{ auth()->user()->getRoleNames()->first() ?? '' }}
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors"
                        title="{{ __('auth.logout') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- Mobile overlay --}}
<div x-show="$store.sidebar.open"
     @click="$store.sidebar.close()"
     class="fixed inset-0 bg-black/50 z-40 lg:hidden"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100">
</div>
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION Q — COMPLETE FILE MANIFEST                  ║
## ╚══════════════════════════════════════════════════════════════╝

### Master list — every file that must exist when build is complete:

```
MANAGEMENT FILES (7)
├── AGENT.md
├── PROGRESS.md
├── TODO.md
├── TASKS.md
├── DECISIONS.md
├── SKILLS.md
└── CHANGELOG.md

CONFIG FILES (6)
├── .env / .env.example
├── config/factory.php
├── config/money.php
├── config/pdf.php
├── config/app.php (updated aliases)
└── vite.config.js

CONTRACTS/INTERFACES (9)
├── app/Contracts/Repositories/ProductRepositoryInterface.php
├── app/Contracts/Repositories/CustomerRepositoryInterface.php
├── app/Contracts/Repositories/OrderRepositoryInterface.php
├── app/Contracts/Repositories/InvoiceRepositoryInterface.php
├── app/Contracts/Repositories/ShipmentRepositoryInterface.php
├── app/Contracts/Repositories/StockMovementRepositoryInterface.php
├── app/Contracts/Services/OrderServiceInterface.php
├── app/Contracts/Services/ProductServiceInterface.php
└── app/Contracts/Export/ExportStrategyInterface.php

VALUE OBJECTS / STATE MACHINES (3)
├── app/ValueObjects/Money.php
├── app/StateMachines/OrderStateMachine.php
└── app/StateMachines/ShipmentStateMachine.php

MODELS (18)
├── app/Models/User.php
├── app/Models/Customer.php
├── app/Models/Product.php
├── app/Models/ProductCategory.php
├── app/Models/StockMovement.php
├── app/Models/Order.php
├── app/Models/OrderItem.php
├── app/Models/Truck.php
├── app/Models/Driver.php
├── app/Models/Shipment.php
├── app/Models/Invoice.php
├── app/Models/Payment.php
├── app/Models/Expense.php
├── app/Models/SystemSetting.php
└── app/Models/Traits/
    ├── GeneratesSequentialCode.php
    ├── HasMoneyFormatting.php
    ├── HasSoftDeleteGuard.php
    └── HasStatusTransitions.php

DTOs (7)
├── app/DTOs/Orders/CreateOrderDTO.php
├── app/DTOs/Orders/OrderItemDTO.php
├── app/DTOs/Orders/UpdateOrderStatusDTO.php
├── app/DTOs/Invoices/RecordPaymentDTO.php
├── app/DTOs/Customers/CreateCustomerDTO.php
├── app/DTOs/Shipments/CreateShipmentDTO.php
└── app/DTOs/Products/CreateProductDTO.php

SERVICES (12)
├── app/Services/BaseService.php
├── app/Services/SettingService.php
├── app/Services/PdfService.php
├── app/Services/Products/ProductService.php
├── app/Services/Products/StockService.php
├── app/Services/Customers/CustomerService.php
├── app/Services/Orders/OrderService.php
├── app/Services/Orders/OrderStatusService.php
├── app/Services/Orders/OrderFinancialsService.php
├── app/Services/Distribution/ShipmentService.php
├── app/Services/Invoices/InvoiceService.php
└── app/Services/Erp/ReportService.php

REPOSITORIES (7)
├── app/Repositories/BaseRepository.php
├── app/Repositories/ProductRepository.php
├── app/Repositories/CustomerRepository.php
├── app/Repositories/OrderRepository.php
├── app/Repositories/InvoiceRepository.php
├── app/Repositories/ShipmentRepository.php
└── app/Repositories/StockMovementRepository.php

PIPELINES (3)
├── app/Pipelines/Order/ValidateCustomerCreditPipe.php
├── app/Pipelines/Order/ValidateStockAvailabilityPipe.php
└── app/Pipelines/Order/CalculateOrderTotalsPipe.php

CONTROLLERS (22)
└── app/Http/Controllers/
    ├── Auth/LoginController.php
    ├── Auth/PasswordResetController.php
    ├── Products/ProductController.php
    ├── Products/ProductCategoryController.php
    ├── Products/StockController.php
    ├── Customers/CustomerController.php
    ├── Orders/OrderController.php
    ├── Orders/OrderStatusController.php
    ├── Orders/OrderReturnController.php
    ├── Distribution/TruckController.php
    ├── Distribution/DriverController.php
    ├── Distribution/ShipmentController.php
    ├── Distribution/ShipmentOrderController.php
    ├── Invoices/InvoiceController.php
    ├── Invoices/PaymentController.php
    ├── Erp/DashboardController.php
    ├── Erp/ExpenseController.php
    ├── Erp/ReportController.php
    ├── Erp/ChartController.php
    ├── Admin/UserController.php
    ├── Admin/SettingController.php
    └── Admin/AuditLogController.php

POLICIES (7)
├── app/Policies/OrderPolicy.php
├── app/Policies/InvoicePolicy.php
├── app/Policies/PaymentPolicy.php
├── app/Policies/ProductPolicy.php
├── app/Policies/CustomerPolicy.php
├── app/Policies/ShipmentPolicy.php
└── app/Policies/ExpensePolicy.php

MIDDLEWARE (4)
├── app/Http/Middleware/SetLocale.php
├── app/Http/Middleware/CheckUserIsActive.php
├── app/Http/Middleware/CustomerPortalMiddleware.php
└── app/Http/Middleware/LastActivityMiddleware.php

FORM REQUESTS (11)
└── app/Http/Requests/
    ├── Products/StoreProductRequest.php
    ├── Products/UpdateProductRequest.php
    ├── Products/StockAdjustmentRequest.php
    ├── Orders/StoreOrderRequest.php
    ├── Orders/UpdateOrderRequest.php
    ├── Orders/CancelOrderRequest.php
    ├── Customers/StoreCustomerRequest.php
    ├── Customers/UpdateCustomerRequest.php
    ├── Invoices/RecordPaymentRequest.php
    ├── Shipments/StoreShipmentRequest.php
    └── Shipments/AttachOrdersRequest.php

LIVEWIRE COMPONENTS (8)
├── app/Livewire/Products/ProductSearch.php
├── app/Livewire/Orders/OrderItemsTable.php
├── app/Livewire/Orders/OrderFilters.php
├── app/Livewire/Orders/CustomerBalanceChecker.php
├── app/Livewire/Customers/CustomerSearch.php
├── app/Livewire/Shipments/ShipmentOrderAssignment.php
├── app/Livewire/Invoices/InvoiceFilters.php
└── app/Livewire/Shared/NotificationBell.php

OBSERVERS (4) + EVENTS (7) + LISTENERS (5)
[all listed in EventServiceProvider]

NOTIFICATIONS (5)
├── app/Notifications/OrderStatusChanged.php
├── app/Notifications/InvoiceIssued.php
├── app/Notifications/PaymentReceived.php
├── app/Notifications/LowStockAlert.php
└── app/Notifications/InvoiceOverdue.php

CONSOLE COMMANDS (3)
├── app/Console/Commands/SendOverdueInvoiceAlerts.php
├── app/Console/Commands/CheckLowStockLevels.php
└── app/Console/Commands/GenerateBackup.php

HELPERS (3)
├── app/Helpers/helpers.php
├── app/Helpers/MoneyHelper.php
└── app/Helpers/AmountToWords.php

FACADES (1)
└── app/Facades/Setting.php

EXPORT STRATEGIES (2)
├── app/Services/Export/ExcelExportStrategy.php
└── app/Services/Export/CsvExportStrategy.php

FACTORIES (9)
└── database/factories/
    ├── UserFactory.php
    ├── CustomerFactory.php
    ├── ProductFactory.php
    ├── OrderFactory.php
    ├── InvoiceFactory.php
    ├── ShipmentFactory.php
    ├── TruckFactory.php
    ├── DriverFactory.php
    └── ExpenseFactory.php

SEEDERS (6)
└── database/seeders/
    ├── DatabaseSeeder.php
    ├── RolesAndPermissionsSeeder.php
    ├── AdminUserSeeder.php
    ├── SystemSettingsSeeder.php
    ├── ProductCategorySeeder.php
    └── DemoDataSeeder.php

MIGRATIONS (17)  [see Part 1 for full list]

TESTS (18)
└── tests/
    ├── Unit/MoneyValueObjectTest.php
    ├── Unit/MoneyHelperTest.php
    ├── Unit/OrderStateMachineTest.php
    ├── Unit/StockServiceTest.php
    ├── Unit/OrderFinancialsServiceTest.php
    ├── Unit/AmountToWordsTest.php
    ├── Feature/AuthTest.php
    ├── Feature/ProductCrudTest.php
    ├── Feature/CustomerCrudTest.php
    ├── Feature/OrderLifecycleTest.php
    ├── Feature/OrderCancellationTest.php
    ├── Feature/InvoicePaymentTest.php
    ├── Feature/ShipmentFlowTest.php
    ├── Feature/RoleAccessTest.php
    ├── Feature/CustomerPortalTest.php
    ├── Feature/PdfDownloadTest.php
    ├── Feature/ExcelExportTest.php
    └── Feature/DashboardTest.php

BLADE VIEWS (60+) [see Part 1 directory tree for full list]

DEPLOYMENT FILES (6)
├── deploy.sh
├── docker-compose.yml
├── docker/php/Dockerfile
├── docker/nginx/default.conf
├── supervisor/factory.conf
└── .env.example

LANGUAGE FILES (7)
└── lang/ar/
    ├── auth.php
    ├── validation.php
    ├── pagination.php
    ├── app.php
    ├── orders.php
    ├── invoices.php
    ├── shipments.php
    └── notifications.php
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         ABSOLUTE FINAL AGENT INSTRUCTION                    ║
## ╚══════════════════════════════════════════════════════════════╝

```
THE COMPLETE BLUEPRINT IS NOW IN 4 FILES.

READING ORDER (mandatory before writing any code):
  [1] AGENT_PROMPT_FACTORY_SYSTEM.md      → Phases, architecture laws, patterns
  [2] AGENT_PROMPT_FACTORY_SYSTEM_PART2.md → DTOs, repos, services, controllers, routes
  [3] AGENT_PROMPT_FACTORY_SYSTEM_PART3.md → Models, observers, notifications, PDF, deploy
  [4] AGENT_PROMPT_FACTORY_SYSTEM_PART4.md → Policies, middleware, auth, remaining pieces

TOTAL SPECIFICATION:
  4 prompt files · ~12,000 lines · ~420KB
  170+ files to create
  18 execution phases
  18 test files (target ≥ 80% coverage)

SESSION START EVERY TIME:
  1. Read PROGRESS.md → know where you are
  2. Read TODO.md → know what's next
  3. Announce the session
  4. Code
  5. Test
  6. Update PROGRESS.md + TODO.md
  7. Announce session end

FINAL LAWS — NEVER BREAK:
  ✗ 400 lines per file — HARD LIMIT
  ✗ Money as BIGINT — ALWAYS
  ✗ Business logic in controllers — NEVER
  ✗ Eloquent in services — NEVER (use repositories)
  ✗ DB writes outside transaction() — NEVER
  ✗ Controller without authorize() — NEVER
  ✗ Arabic string hardcoded in PHP — NEVER
  ✗ ->get() on unbounded collections — NEVER

GO BUILD IT.
ابدأ الآن. اقرأ أولاً. ثم ابدأ الكود.
```

---

*PART 4 OF 4 — MASTER AGENT PROMPT v1.0.0 — COMPLETE*
*Factory Distribution & Shipping Management System*
*نظام إدارة معمل التوزيع والشحن*
*May 2026 · All 4 parts together = Complete Execution Blueprint*
