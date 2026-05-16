<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Models\Product;
use App\Services\Products\ProductService;
use App\Services\Products\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $service,
        private readonly StockService $stock,
    ) {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request): View
    {
        $products = $this->service->list($request->only([
            'search', 'category_id', 'is_active', 'low_stock',
        ]));
        $categories = $this->service->getActiveCategories();
        $lowCount = $this->service->getLowStockCount();

        return view('products.index', compact('products', 'categories', 'lowCount'));
    }

    public function create(): View
    {
        $categories = $this->service->getActiveCategories();

        return view('products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->service->create(
            $request->except('image'),
            $request->file('image')
        );

        return redirect()
            ->route('products.show', $product)
            ->with('success', __('products.created', ['name' => $product->name]));
    }

    public function show(Product $product): View
    {
        $this->service->loadDetails($product);
        $movements = $this->stock->getRecentMovements($product);

        return view('products.show', compact('product', 'movements'));
    }

    public function edit(Product $product): View
    {
        $categories = $this->service->getActiveCategories();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->service->update(
            $product,
            $request->except('image'),
            $request->file('image')
        );

        return redirect()
            ->route('products.show', $product)
            ->with('success', __('products.updated'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        try {
            $this->service->delete($product);
        } catch (\DomainException $e) {
            return redirect()
                ->route('products.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('products.index')
            ->with('success', __('products.deleted'));
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('create', Product::class);
        $product = $this->service->restore($id);

        return redirect()
            ->route('products.show', $product)
            ->with('success', __('products.restored'));
    }
}
