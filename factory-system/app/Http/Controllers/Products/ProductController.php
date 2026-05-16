<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Services\Products\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $service)
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request): View
    {
        $products = $this->service->list($request->only([
            'search', 'category_id', 'is_active', 'low_stock',
        ]));
        $categories = ProductCategory::active()->orderBy('sort_order')->get();
        $lowCount = Product::lowStock()->count();

        return view('products.index', compact('products', 'categories', 'lowCount'));
    }

    public function create(): View
    {
        $categories = ProductCategory::active()->orderBy('sort_order')->get();

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
        $product->load('category');
        $movements = StockMovement::where('product_id', $product->id)
            ->with('createdByUser')
            ->latest()
            ->limit(50)
            ->get();

        return view('products.show', compact('product', 'movements'));
    }

    public function edit(Product $product): View
    {
        $categories = ProductCategory::active()->orderBy('sort_order')->get();

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
