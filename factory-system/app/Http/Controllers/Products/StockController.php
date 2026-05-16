<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StockAdjustmentRequest;
use App\Models\Product;
use App\Services\Products\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $movements = $this->stock->listMovements($request->only([
            'product_id', 'type', 'date_from', 'date_to',
        ]));

        return view('products.stock-movements', compact('movements'));
    }

    public function adjust(StockAdjustmentRequest $request): RedirectResponse
    {
        $product = Product::findOrFail($request->validated('product_id'));
        $this->authorize('adjustStock', $product);

        $this->stock->adjustStock(
            $product,
            $request->validated('new_quantity'),
            $request->validated('reason')
        );

        return back()->with('success', __('products.stock_adjusted', [
            'name' => $product->name,
        ]));
    }

    public function lowAlert(): View
    {
        $this->authorize('viewAny', Product::class);
        $products = $this->stock->getLowStockProducts();

        return view('products.low-alert', compact('products'));
    }
}
