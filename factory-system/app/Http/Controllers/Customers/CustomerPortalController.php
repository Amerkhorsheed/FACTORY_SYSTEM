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

        return view('portal.orders.show', compact('order'));
    }

    public function createOrder(Request $request): View
    {
        $this->authorize('create', Order::class);
        $customer = $this->portal->customerForUser($request->user());
        $products = $this->portal->availableProducts();

        return view('portal.orders.create', compact('customer', 'products'));
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
