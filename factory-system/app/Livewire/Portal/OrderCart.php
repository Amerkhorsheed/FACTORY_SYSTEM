<?php

namespace App\Livewire\Portal;

use App\DTOs\Orders\OrderItemDTO;
use App\Models\Customer;
use App\Models\Product;
use App\Services\Customers\CustomerPortalService;
use App\Services\Orders\OrderFinancialsService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Interactive shopping cart for customer portal order creation.
 * Manages multi-item cart state, real-time totals, and credit validation.
 */
class OrderCart extends Component
{
    public Customer $customer;

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public string $searchQuery = '';

    public ?int $selectedCategory = null;

    public ?string $notes = null;

    public ?string $requestedDeliveryDate = null;

    public bool $showCreditWarning = false;

    /** @var array<int, string> */
    public array $categories = [];

    /** @var Collection<int, Product>|null */
    private ?Collection $cachedProducts = null;

    public function mount(Customer $customer, CustomerPortalService $portal): void
    {
        $this->customer = $customer;
        $this->loadCategories($portal);
    }

    public function addProduct(int $productId, CustomerPortalService $portal): void
    {
        $product = $portal->getProductById($productId);

        if (! $product || $product->stock_quantity <= 0) {
            $this->dispatch('notify', type: 'error', message: __('portal.product_unavailable'));

            return;
        }

        $existingIndex = $this->findItemIndex($productId);

        if ($existingIndex !== null) {
            $this->updateQuantity($existingIndex, $this->items[$existingIndex]['quantity'] + 1);

            return;
        }

        $this->items[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'unit_price' => $product->unit_price,
            'quantity' => 1,
            'stock_quantity' => $product->stock_quantity,
            'notes' => '',
        ];

        $this->validateCredit();
        $this->dispatch('notify', type: 'success', message: __('portal.added_to_cart'));
    }

    public function removeProduct(int $index): void
    {
        if (isset($this->items[$index])) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
            $this->validateCredit();
        }
    }

    public function updateQuantity(int $index, int $quantity): void
    {
        if (! isset($this->items[$index]) || $quantity < 1) {
            return;
        }

        if ($quantity > $this->items[$index]['stock_quantity']) {
            $this->dispatch('notify', type: 'warning', message: __('portal.max_stock_reached'));

            return;
        }

        $this->items[$index]['quantity'] = $quantity;
        $this->validateCredit();
    }

    public function updatedSearchQuery(): void
    {
        // Search is handled client-side via filteredProducts property
    }

    public function updatedSelectedCategory(): void
    {
        // Filter is handled client-side via filteredProducts property
    }

    public function getSubtotalProperty(): int
    {
        return collect($this->items)->sum(fn (array $item) => $item['unit_price'] * $item['quantity']);
    }

    public function getTaxAmountProperty(): int
    {
        $financials = app(OrderFinancialsService::class);
        $dtoItems = $this->buildDtoItems();

        return $dtoItems->isEmpty() ? 0 : $financials->calculateTotals($dtoItems)['tax'];
    }

    public function getGrandTotalProperty(): int
    {
        return $this->subtotal + $this->taxAmount;
    }

    public function getAvailableCreditProperty(): int
    {
        return max(0, $this->customer->available_credit - $this->grandTotal);
    }

    public function getCreditUsedPercentProperty(): int
    {
        if ($this->customer->credit_limit <= 0) {
            return 0;
        }

        $used = $this->customer->outstanding_balance + $this->grandTotal;

        return (int) min(100, ($used / $this->customer->credit_limit) * 100);
    }

    public function getCanCheckoutProperty(): bool
    {
        return count($this->items) > 0
            && ! $this->showCreditWarning
            && $this->customer->canAcceptOrder($this->grandTotal);
    }

    public function checkout(CustomerPortalService $portal): void
    {
        if (! $this->canCheckout) {
            $this->dispatch('notify', type: 'error', message: __('portal.checkout_blocked'));

            return;
        }

        try {
            $order = $portal->createOrder(
                $this->customer,
                [
                    'items' => $this->buildCartArrays(),
                    'notes' => $this->notes,
                    'requested_delivery_date' => $this->requestedDeliveryDate,
                ],
                auth()->user()
            );

            $this->dispatch('orderCreated', orderId: $order->id);
            $this->reset(['items', 'notes', 'requestedDeliveryDate', 'showCreditWarning']);
            $this->cachedProducts = null;
        } catch (ValidationException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function render(CustomerPortalService $portal): View
    {
        if ($this->cachedProducts === null) {
            $this->cachedProducts = $portal->availableProducts();
        }

        return view('livewire.portal.order-cart', [
            'filteredProducts' => $this->filteredProducts($this->cachedProducts),
        ]);
    }

    private function validateCredit(): void
    {
        $this->showCreditWarning = ! $this->customer->canAcceptOrder($this->grandTotal);
    }

    private function findItemIndex(int $productId): ?int
    {
        foreach ($this->items as $index => $item) {
            if ($item['product_id'] === $productId) {
                return $index;
            }
        }

        return null;
    }

    /** @return Collection<int, OrderItemDTO> */
    private function buildDtoItems(): Collection
    {
        return collect($this->items)->map(fn (array $item) => new OrderItemDTO(
            productId: $item['product_id'],
            quantity: $item['quantity'],
            unitPrice: $item['unit_price'],
            discountBasisPoints: 0,
            notes: $item['notes'] ?: null,
        ));
    }

    /** @return array<int, array<string, mixed>> */
    private function buildCartArrays(): array
    {
        return collect($this->items)->map(fn (array $item) => [
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'notes' => $item['notes'] ?: null,
        ])->all();
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return Collection<int, Product>
     */
    private function filteredProducts(Collection $products): Collection
    {
        return $products
            ->when($this->selectedCategory, fn ($q) => $q->where('category_id', (int) $this->selectedCategory))
            ->when($this->searchQuery, function ($q) {
                $term = mb_strtolower($this->searchQuery);

                return $q->filter(fn (Product $p) => str_contains(mb_strtolower($p->name), $term));
            })
            ->values();
    }

    private function loadCategories(CustomerPortalService $portal): void
    {
        $products = $portal->availableProducts();
        $this->cachedProducts = $products;

        $this->categories = $products
            ->pluck('category.name', 'category_id')
            ->filter()
            ->unique()
            ->all();
    }
}
