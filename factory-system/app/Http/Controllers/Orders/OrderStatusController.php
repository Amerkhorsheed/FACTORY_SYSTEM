<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\CancelOrderRequest;
use App\Models\Order;
use App\Services\Orders\OrderStatusService;
use Illuminate\Http\RedirectResponse;

/**
 * Handles order status transitions.
 */
class OrderStatusController extends Controller
{
    public function __construct(private readonly OrderStatusService $statusService) {}

    public function accept(Order $order): RedirectResponse
    {
        $this->authorize('changeStatus', $order);
        $this->statusService->accept($order, auth()->user());

        return back()->with('success', __('orders.status_accepted'));
    }

    public function preparing(Order $order): RedirectResponse
    {
        $this->authorize('changeStatus', $order);
        $this->statusService->markPreparing($order);

        return back()->with('success', __('orders.status_preparing'));
    }

    public function ready(Order $order): RedirectResponse
    {
        $this->authorize('changeStatus', $order);
        $this->statusService->markReady($order);

        return back()->with('success', __('orders.status_ready'));
    }

    public function deliver(Order $order): RedirectResponse
    {
        $this->authorize('confirmDelivery', $order);
        $this->statusService->confirmDelivery($order, auth()->user());

        return back()->with('success', __('orders.status_delivered'));
    }

    public function cancel(CancelOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('cancel', $order);
        $this->statusService->cancel($order, $request->reason, auth()->user());

        return back()->with('success', __('orders.status_cancelled'));
    }

    public function returned(Order $order): RedirectResponse
    {
        $this->authorize('changeStatus', $order);
        $this->statusService->recordReturn($order, '');

        return back()->with('success', __('orders.status_returned'));
    }
}
