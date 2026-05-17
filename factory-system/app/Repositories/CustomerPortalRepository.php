<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Customer portal read/write data access scoped to a single customer.
 */
class CustomerPortalRepository
{
    public function customerForUser(User $user): Customer
    {
        $customer = Customer::query()->where('user_id', $user->id)->first();

        if (! $customer) {
            abort(403, __('portal.customer_record_not_found'));
        }

        return $customer;
    }

    /** @return array<string, mixed> */
    public function dashboard(Customer $customer): array
    {
        $customer->loadCount(['orders', 'invoices']);

        return [
            'customer' => $customer,
            'recentOrders' => $customer->orders()
                ->with('invoice')
                ->latest('order_date')
                ->limit(5)
                ->get(),
            'unpaidInvoices' => $customer->invoices()
                ->whereNotIn('status', ['paid', 'void'])
                ->latest('issue_date')
                ->limit(5)
                ->get(),
        ];
    }

    public function paginateOrders(Customer $customer, int $perPage = 15): LengthAwarePaginator
    {
        return Order::with('invoice')
            ->where('customer_id', $customer->id)
            ->latest('order_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function loadOrder(Order $order): Order
    {
        return $order->load(['items.product', 'invoice', 'shipment']);
    }

    public function paginateInvoices(Customer $customer, int $perPage = 15): LengthAwarePaginator
    {
        return Invoice::with('order')
            ->where('customer_id', $customer->id)
            ->latest('issue_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function loadInvoice(Invoice $invoice): Invoice
    {
        return $invoice->load(['order.items.product', 'payments']);
    }

    /** @return Collection<int, Product> */
    public function availableProducts(int $limit = 100): Collection
    {
        return Product::with('category')
            ->active()
            ->where('stock_quantity', '>', 0)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<int, int>  $ids
     * @return SupportCollection<int, Product>
     */
    public function productsByIds(array $ids): SupportCollection
    {
        return Product::active()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    /** @param array<string, mixed> $data */
    public function updateProfile(Customer $customer, array $data): Customer
    {
        $customer->fill($data);
        $customer->save();

        if ($customer->user && isset($data['phone'])) {
            $customer->user->update(['phone' => $data['phone']]);
        }

        return $customer->refresh();
    }
}
