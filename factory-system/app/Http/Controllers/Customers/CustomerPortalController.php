<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\StorePortalOrderRequest;
use App\Http\Requests\Customers\UpdatePortalProfileRequest;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\Customers\CustomerPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerPortalController extends Controller
{
    public function __construct(private readonly CustomerPortalService $portal) {}

    public function dashboard(Request $request): View
    {
        $customer = $this->portal->customerForUser($request->user());
        $this->authorize('viewPortal', $customer);

        return view('portal.dashboard', $this->portal->dashboard($customer));
    }

    public function orders(Request $request): View
    {
        $this->authorize('viewAny', Order::class);
        $customer = $this->portal->customerForUser($request->user());
        $orders = $this->portal->orders($customer);

        return view('portal.orders.index', compact('customer', 'orders'));
    }

    public function showOrder(Order $order): View
    {
        $this->authorize('view', $order);

        $order = $this->portal->orderDetails($order);
        $timeline = $this->buildOrderTimeline($order);

        return view('portal.orders.show', compact('order', 'timeline'));
    }

    /** @return array<string, mixed> */
    private function buildOrderTimeline(Order $order): array
    {
        $statusOrder = ['pending', 'accepted', 'preparing', 'shipped', 'delivered'];
        $currentStatus = $order->status;
        $currentIndex = array_search($currentStatus, $statusOrder);

        return [
            'steps' => [
                ['key' => 'pending', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['key' => 'accepted', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['key' => 'preparing', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                ['key' => 'shipped', 'icon' => 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0'],
                ['key' => 'delivered', 'icon' => 'M5 13l4 4L19 7'],
            ],
            'currentIndex' => $currentIndex,
            'isCancelled' => $currentStatus === 'cancelled',
            'isReturned' => $currentStatus === 'returned',
            'cancelReason' => $order->cancel_reason,
            'statusOrder' => $statusOrder,
        ];
    }

    public function createOrder(Request $request): View
    {
        $this->authorize('create', Order::class);
        $customer = $this->portal->customerForUser($request->user());

        return view('portal.orders.create', compact('customer'));
    }

    public function storeOrder(StorePortalOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', Order::class);
        $customer = $this->portal->customerForUser($request->user());
        $order = $this->portal->createOrder($customer, $request->validated(), $request->user());

        return redirect()
            ->route('portal.orders.show', $order)
            ->with('success', __('portal.order_created', ['number' => $order->order_number]));
    }

    public function invoices(Request $request): View
    {
        $this->authorize('viewAny', Invoice::class);
        $customer = $this->portal->customerForUser($request->user());
        $invoices = $this->portal->invoices($customer);

        return view('portal.invoices.index', compact('customer', 'invoices'));
    }

    public function showInvoice(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice = $this->portal->invoiceDetails($invoice);

        return view('portal.invoices.show', compact('invoice'));
    }

    public function profile(Request $request): View
    {
        $customer = $this->portal->customerForUser($request->user());
        $this->authorize('viewPortal', $customer);

        return view('portal.profile', compact('customer'));
    }

    public function updateProfile(UpdatePortalProfileRequest $request): RedirectResponse
    {
        $customer = $this->portal->customerForUser($request->user());
        $this->authorize('updatePortal', $customer);
        $this->portal->updateProfile($customer, $request->validated());

        return back()->with('success', __('portal.profile_updated'));
    }
}
