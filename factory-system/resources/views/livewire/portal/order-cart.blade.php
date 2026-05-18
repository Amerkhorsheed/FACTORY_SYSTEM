@php
    $money = fn ($amount) => number_format((int) $amount).' '.__('ui.currency.syp');
    $creditColor = match(true) {
        $this->creditUsedPercent < 50 => 'bg-emerald-500',
        $this->creditUsedPercent < 80 => 'bg-amber-500',
        default => 'bg-rose-500',
    };
@endphp

<div x-data="{ showCart: window.innerWidth >= 1024 }" class="grid gap-6 lg:grid-cols-3">
    {{-- Product Discovery Panel --}}
    <div class="lg:col-span-2 space-y-5">
        {{-- Search & Filters --}}
        <x-card class="sticky top-4 z-10">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <svg class="absolute start-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="searchQuery"
                        placeholder="{{ __('portal.search_products') }}"
                        class="w-full rounded-lg border-gray-300 ps-10 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                </div>
                @if(count($categories) > 1)
                    <select
                        wire:model.live="selectedCategory"
                        class="rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">{{ __('portal.all_categories') }}</option>
                        @foreach($categories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </x-card>

        {{-- Product Grid --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($filteredProducts as $product)
                <div
                    class="group relative rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
                    wire:key="product-{{ $product->id }}"
                >
                    <div class="aspect-square mb-3 overflow-hidden rounded-lg bg-gray-100">
                        @if($product->image)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover" loading="lazy" />
                        @else
                            <div class="flex h-full items-center justify-center text-gray-400">
                                <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                        @endif
                    </div>

                    <h3 class="font-semibold text-gray-900 line-clamp-1">{{ $product->name }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $product->category?->name }}</p>

                    <div class="mt-3 flex items-center justify-between">
                        <span class="text-lg font-bold text-brand-600">{{ $money($product->unit_price) }}</span>
                        @if($product->stock_quantity > 0)
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">
                                {{ __('portal.in_stock', ['qty' => $product->stock_quantity]) }}
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700">
                                {{ __('portal.out_of_stock') }}
                            </span>
                        @endif
                    </div>

                    <button
                        type="button"
                        wire:click="addProduct({{ $product->id }})"
                        @disabled($product->stock_quantity <= 0)
                        class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('portal.add_to_cart') }}
                    </button>
                </div>
            @empty
                <div class="col-span-full py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 10l4 4m0-4l-4 4"/></svg>
                    <p class="mt-4 text-gray-500">{{ __('portal.no_products_found') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Sticky Cart Panel --}}
    <div
        x-show="showCart"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-x-4"
        x-transition:enter-end="opacity-100 translate-x-0"
        class="space-y-5"
    >
        {{-- Credit Meter --}}
        <x-card :title="__('portal.your_credit')">
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">{{ __('portal.used') }}</span>
                    <span class="font-semibold {{ $this->showCreditWarning ? 'text-rose-600' : 'text-gray-900' }}">
                        {{ $money($this->customer->outstanding_balance + $this->grandTotal) }}
                        / {{ $money($this->customer->credit_limit) }}
                    </span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200">
                    <div
                        class="h-full rounded-full transition-all duration-500 {{ $creditColor }}"
                        style="width: {{ $this->creditUsedPercent }}%"
                    ></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500">
                    <span>{{ __('portal.available') }}: {{ $money($this->availableCredit) }}</span>
                    <span>{{ $this->creditUsedPercent }}%</span>
                </div>
            </div>

            @if($this->showCreditWarning)
                <div class="mt-3 flex items-start gap-2 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p>{{ __('portal.credit_limit_exceeded') }}</p>
                </div>
            @endif
        </x-card>

        {{-- Cart Items --}}
        <x-card :title="__('portal.cart') . ' (' . count($items) . ')'">
            @if(count($items) === 0)
                <div class="py-8 text-center">
                    <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <p class="mt-2 text-sm text-gray-500">{{ __('portal.cart_empty') }}</p>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($items as $index => $item)
                        <div class="flex items-center gap-3 py-3" wire:key="cart-item-{{ $index }}">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-gray-900">{{ $item['name'] }}</p>
                                <p class="text-sm text-gray-500">{{ $money($item['unit_price']) }} {{ __('portal.per_unit') }}</p>
                            </div>

                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50"
                                >
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                </button>
                                <span class="w-8 text-center text-sm font-medium">{{ $item['quantity'] }}</span>
                                <button
                                    type="button"
                                    wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50"
                                >
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </button>
                            </div>

                            <div class="text-right">
                                <p class="font-semibold text-gray-900">{{ $money($item['unit_price'] * $item['quantity']) }}</p>
                            </div>

                            <button
                                type="button"
                                wire:click="removeProduct({{ $index }})"
                                class="text-gray-400 hover:text-rose-500"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    @endforeach
                </div>

                {{-- Order Summary --}}
                <div class="mt-4 space-y-2 border-t border-gray-100 pt-4 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>{{ __('portal.subtotal') }}</span>
                        <span>{{ $money($this->subtotal) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>{{ __('portal.tax') }}</span>
                        <span>{{ $money($this->taxAmount) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-gray-900">
                        <span>{{ __('portal.total') }}</span>
                        <span>{{ $money($this->grandTotal) }}</span>
                    </div>
                </div>
            @endif
        </x-card>

        {{-- Order Details --}}
        <x-card :title="__('portal.order_details')">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('portal.requested_delivery_date') }}</label>
                    <input
                        type="date"
                        wire:model="requestedDeliveryDate"
                        min="{{ today()->toDateString() }}"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('portal.notes') }}</label>
                    <textarea
                        wire:model="notes"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="{{ __('portal.notes_placeholder') }}"
                    ></textarea>
                </div>
            </div>
        </x-card>

        {{-- Checkout Button --}}
        <button
            type="button"
            wire:click="checkout"
            wire:loading.attr="disabled"
            @disabled(! $this->canCheckout)
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-6 py-4 text-base font-bold text-white shadow-lg transition-all hover:bg-brand-700 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:shadow-none"
        >
            <svg class="h-5 w-5" wire:loading.remove wire:target="checkout" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <svg class="h-5 w-5 animate-spin" wire:loading wire:target="checkout" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            {{ __('portal.place_order') }}
        </button>
    </div>

    {{-- Mobile Cart Toggle --}}
    <button
        type="button"
        @click="showCart = !showCart"
        class="fixed bottom-4 end-4 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-brand-600 text-white shadow-xl lg:hidden"
    >
        <div class="relative">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            @if(count($items) > 0)
                <span class="absolute -end-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-rose-500 text-xs font-bold">
                    {{ count($items) }}
                </span>
            @endif
        </div>
    </button>
</div>
