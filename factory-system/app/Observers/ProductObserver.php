<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    public function created(Product $product): void
    {
        activity('products')
            ->performedOn($product)
            ->causedBy(auth()->user())
            ->withProperties(['code' => $product->code, 'name' => $product->name])
            ->log(__('activity.products.created'));
    }

    public function updated(Product $product): void
    {
        $changes = $product->getChanges();

        if (array_key_exists('unit_price', $changes) || array_key_exists('cost_price', $changes)) {
            activity('products')
                ->performedOn($product)
                ->causedBy(auth()->user())
                ->withProperties([
                    'unit_price_before' => $product->getOriginal('unit_price'),
                    'unit_price_after' => $product->unit_price,
                    'cost_price_before' => $product->getOriginal('cost_price'),
                    'cost_price_after' => $product->cost_price,
                ])
                ->log(__('activity.products.price_changed'));
        }

        if (array_key_exists('stock_quantity', $changes)) {
            activity('products')
                ->performedOn($product)
                ->causedBy(auth()->user())
                ->withProperties([
                    'before' => $product->getOriginal('stock_quantity'),
                    'after' => $product->stock_quantity,
                ])
                ->log(__('activity.products.stock_changed'));
        }
    }

    public function deleted(Product $product): void
    {
        activity('products')
            ->performedOn($product)
            ->causedBy(auth()->user())
            ->withProperties(['code' => $product->code, 'name' => $product->name])
            ->log(__('activity.products.deleted'));
    }
}
