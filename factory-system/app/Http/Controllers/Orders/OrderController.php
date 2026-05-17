<?php

namespace App\Http\Controllers\Orders;

use App\DTOs\Orders\CreateOrderDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreOrderRequest;
use App\Http\Requests\Orders\UpdateOrderRequest;
use App\Models\Order;
use App\Services\Orders\OrderService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handles Order CRUD operations.
 */
class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
        $this->authorizeResource(Order::class, 'order');
    }

    public function index(Request $request): View
    {
        $orders = $this->orders->list($request->only([
            'search', 'status', 'customer_id', 'date_from', 'date_to', 'region',
        ]));

        return view('orders.index', compact('orders'));
    }

    public function create(): View
    {
        return view('orders.create');
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $dto = CreateOrderDTO::fromArray($request->validated());
        $order = $this->orders->create($dto);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', __('orders.created_successfully', [
                'number' => $order->order_number,
            ]));
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'items.product', 'invoice.payments', 'shipment.truck']);

        return view('orders.show', compact('order'));
    }

    public function edit(Order $order): View|RedirectResponse
    {
        if (! $order->isEditable()) {
            return redirect()->route('orders.show', $order)
                ->with('error', __('orders.not_editable'));
        }

        $order->load(['items.product', 'customer']);

        return view('orders.edit', compact('order'));
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $dto = CreateOrderDTO::fromArray($request->validated());
        $this->orders->update($order, $dto);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', __('orders.updated_successfully'));
    }

    public function destroy(Order $order): RedirectResponse
    {
        try {
            $this->orders->delete($order);
        } catch (\DomainException $e) {
            return redirect()
                ->route('orders.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('orders.index')
            ->with('success', __('orders.deleted_successfully'));
    }

    public function daily(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $date = Carbon::parse($request->get('date', today()));
        $orders = $this->orders->daily($date);

        return view('orders.daily', compact('orders', 'date'));
    }
}
