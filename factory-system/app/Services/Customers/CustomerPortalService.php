<?php

namespace App\Services\Customers;

use App\DTOs\Orders\CreateOrderDTO;
use App\Events\Orders\OrderPlacedByCustomer;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Repositories\CustomerPortalRepository;
use App\Services\BaseService;
use App\Services\Orders\OrderService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CustomerPortalService extends BaseService
{
    public function __construct(
        private readonly CustomerPortalRepository $portal,
        private readonly OrderService $orders,
    ) {}

    public function customerForUser(User $user): Customer
    {
        return $this->portal->customerForUser($user);
    }

    /** @return array<string, mixed> */
    public function dashboard(Customer $customer): array
    {
        return $this->portal->dashboard($customer);
    }

    public function orders(Customer $customer): LengthAwarePaginator
    {
        return $this->portal->paginateOrders($customer);
    }

    public function orderDetails(Order $order): Order
    {
        return $this->portal->loadOrder($order);
    }

    public function invoices(Customer $customer): LengthAwarePaginator
    {
        return $this->portal->paginateInvoices($customer);
    }

    public function invoiceDetails(Invoice $invoice): Invoice
    {
        return $this->portal->loadInvoice($invoice);
    }

    /** @return Collection<int, Product> */
    public function availableProducts(): Collection
    {
        return $this->portal->availableProducts();
    }

    public function getProductById(int $id): ?Product
    {
        return Product::active()->where('id', $id)->first();
    }

    /**
     * Create an order from a validated cart array.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function createOrderFromCart(Customer $customer, array $data, User $actor): Order
    {
        $items = collect($data['items'] ?? []);
        $totalAmount = $items->sum(fn ($item) => $item['unit_price'] * $item['quantity']);

        if (! $customer->canAcceptOrder($totalAmount)) {
            throw ValidationException::withMessages([
                'cart' => __('portal.insufficient_credit'),
            ]);
        }

        return $this->transaction(function () use ($customer, $data, $actor, $items) {
            $preparedItems = $items->map(fn ($item) => [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount_percent' => 0,
                'notes' => $item['notes'] ?? null,
            ])->all();

            $order = $this->orders->create(CreateOrderDTO::fromArray([
                'customer_id' => $customer->id,
                'order_date' => today()->toDateString(),
                'requested_delivery_date' => $data['requested_delivery_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
                'items' => $preparedItems,
            ]));

            OrderPlacedByCustomer::dispatch($order);

            return $order;
        });
    }

    /** @param array<string, mixed> $data */
    public function updateProfile(Customer $customer, array $data): Customer
    {
        return $this->transaction(fn () => $this->portal->updateProfile($customer, $data));
    }

    /** @param array<string, mixed> $data */
    public function createOrder(Customer $customer, array $data, User $actor): Order
    {
        $items = collect($data['items']);
        $products = $this->portal->productsByIds($items->pluck('product_id')->map(fn ($id) => (int) $id)->all());

        $preparedItems = $items->map(function (array $item) use ($products) {
            $product = $products->get((int) $item['product_id']);

            if (! $product) {
                throw ValidationException::withMessages(['items' => __('portal.product_unavailable')]);
            }

            return [
                'product_id' => $product->id,
                'quantity' => (int) $item['quantity'],
                'unit_price' => $product->unit_price,
                'discount_percent' => 0,
                'notes' => $item['notes'] ?? null,
            ];
        })->all();

        return $this->orders->create(CreateOrderDTO::fromArray([
            'customer_id' => $customer->id,
            'order_date' => today()->toDateString(),
            'requested_delivery_date' => $data['requested_delivery_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $actor->id,
            'items' => $preparedItems,
        ]));
    }
}
