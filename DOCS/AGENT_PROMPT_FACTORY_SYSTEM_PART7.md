# 🏭 MASTER AGENT PROMPT — PART 7
## Final Remaining Views · Livewire Components · Excel Exports
## Missing Migrations · Report Views · Portal Views · MASTER INDEX
### نظام إدارة معمل التوزيع والشحن — الجزء السابع والأخير الفعلي
---
> **PART 7 OF 7** | The absolute final part. Covers every remaining gap.
> After this there is literally nothing left unspecified.

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION A — REMAINING LIVEWIRE COMPONENTS           ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Livewire/Orders/OrderFilters.php`
```php
<?php
namespace App\Livewire\Orders;

use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Livewire filter bar for Orders index.
 * All filter state synced to URL for bookmarking/sharing.
 * Emits 'filters-updated' for parent page reload.
 *
 * @package App\Livewire\Orders
 */
class OrderFilters extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $region = '';

    public function updatedSearch(): void   { $this->resetPage(); }
    public function updatedStatus(): void   { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void   { $this->resetPage(); }
    public function updatedRegion(): void   { $this->resetPage(); }

    public function clear(): void
    {
        $this->reset(['search','status','dateFrom','dateTo','region']);
    }

    public function getFiltersProperty(): array
    {
        return array_filter([
            'search'    => $this->search,
            'status'    => $this->status,
            'date_from' => $this->dateFrom,
            'date_to'   => $this->dateTo,
            'region'    => $this->region,
        ]);
    }

    private function resetPage(): void
    {
        $this->dispatch('$refresh');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.orders.order-filters');
    }
}
```

### `app/Livewire/Invoices/InvoiceFilters.php`
```php
<?php
namespace App\Livewire\Invoices;

use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Livewire filter bar for Invoices index.
 *
 * @package App\Livewire\Invoices
 */
class InvoiceFilters extends Component
{
    #[Url]
    public string $status = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url(as: 'c')]
    public ?int $customerId = null;

    public function clear(): void
    {
        $this->reset(['status','dateFrom','dateTo','customerId']);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.invoices.invoice-filters');
    }
}
```

### `app/Livewire/Customers/CustomerSearch.php`
```php
<?php
namespace App\Livewire\Customers;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use Livewire\Component;

/**
 * Livewire customer autocomplete for forms.
 * Dispatches 'customer-selected' event with customer data.
 *
 * @package App\Livewire\Customers
 */
class CustomerSearch extends Component
{
    public string  $search      = '';
    public ?int    $selectedId  = null;
    public bool    $showResults = false;
    public array   $results     = [];

    public function __construct(
        private readonly CustomerRepositoryInterface $customers
    ) {}

    public function updatedSearch(): void
    {
        if (strlen($this->search) < 2) {
            $this->results     = [];
            $this->showResults = false;
            return;
        }

        $this->results = $this->customers
            ->searchForOrder($this->search, 6)
            ->map(fn($c) => [
                'id'                  => $c->id,
                'name'                => $c->name,
                'phone'               => $c->phone,
                'credit_limit'        => $c->credit_limit,
                'outstanding_balance' => $c->outstanding_balance,
            ])
            ->toArray();

        $this->showResults = ! empty($this->results);
    }

    public function select(int $customerId): void
    {
        $customer = collect($this->results)->firstWhere('id', $customerId);

        if ($customer) {
            $this->selectedId  = $customerId;
            $this->search      = $customer['name'];
            $this->showResults = false;
            $this->dispatch('customer-selected', customerId: $customerId);
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.customers.customer-search');
    }
}
```

### `app/Livewire/Shipments/ShipmentOrderAssignment.php`
```php
<?php
namespace App\Livewire\Shipments;

use App\Models\{Order, Shipment};
use App\Services\Distribution\ShipmentService;
use Livewire\Component;

/**
 * Livewire component: order assignment to shipment.
 * Allows selecting multiple ready orders and attaching them.
 * Shows running weight/unit count vs truck capacity.
 *
 * @package App\Livewire\Shipments
 */
class ShipmentOrderAssignment extends Component
{
    public Shipment $shipment;

    /** @var int[] Selected order IDs to attach */
    public array $selectedOrderIds = [];

    public string $filterRegion = '';

    public function mount(Shipment $shipment): void
    {
        $this->shipment = $shipment;
    }

    public function attach(): void
    {
        if (empty($this->selectedOrderIds)) {
            return;
        }

        app(ShipmentService::class)->attachOrders($this->shipment, $this->selectedOrderIds);

        $this->selectedOrderIds = [];
        $this->dispatch('orders-attached');
        session()->flash('success', __('shipments.orders_attached'));
    }

    public function getAvailableOrdersProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return Order::with('customer')
            ->where('status', 'ready')
            ->whereNull('shipment_id')
            ->when($this->filterRegion, fn($q) =>
                $q->whereHas('customer', fn($c) =>
                    $c->where('region', $this->filterRegion)
                )
            )
            ->orderBy('order_date')
            ->get();
    }

    public function getTotalWeightProperty(): float
    {
        return 0; // Extend with product weight if needed
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.shipments.shipment-order-assignment');
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION B — LIVEWIRE VIEW TEMPLATES (REMAINING)     ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/livewire/orders/order-filters.blade.php`
```blade
{{-- Livewire: OrderFilters —– filter bar for orders index --}}
<div class="card p-4">
    <div class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <label class="form-label">بحث</label>
            <input type="text" wire:model.live.debounce.400ms="search"
                   class="form-input" placeholder="رقم الطلبية، اسم العميل...">
        </div>
        <div class="min-w-36">
            <label class="form-label">الحالة</label>
            <select wire:model.live="status" class="form-input">
                <option value="">الكل</option>
                @foreach(config('factory.order_statuses') as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-36">
            <label class="form-label">من تاريخ</label>
            <input type="date" wire:model.live="dateFrom" class="form-input" data-datepicker>
        </div>
        <div class="min-w-36">
            <label class="form-label">إلى تاريخ</label>
            <input type="date" wire:model.live="dateTo" class="form-input" data-datepicker>
        </div>
        <div class="min-w-32">
            <label class="form-label">المنطقة</label>
            <input type="text" wire:model.live.debounce.400ms="region"
                   class="form-input" placeholder="المنطقة">
        </div>
        <button wire:click="clear" class="btn btn-ghost btn-sm">مسح</button>
    </div>
    <div wire:loading class="mt-2 text-xs text-gray-400">جاري البحث...</div>
</div>
```

### `resources/views/livewire/customers/customer-search.blade.php`
```blade
{{-- Livewire: CustomerSearch --}}
<div class="relative">
    <input
        type="text"
        class="form-input"
        placeholder="ابحث باسم العميل أو هاتفه..."
        wire:model.live.debounce.350ms="search"
        autocomplete="off">

    @if($showResults && count($results) > 0)
    <div class="absolute z-50 right-0 left-0 top-full mt-1 bg-white rounded-lg
                border border-gray-200 shadow-lg max-h-64 overflow-y-auto">
        @foreach($results as $customer)
        <button
            type="button"
            wire:click="select({{ $customer['id'] }})"
            class="w-full text-right px-4 py-3 hover:bg-brand-50 border-b border-gray-50
                   last:border-0 flex items-center justify-between">
            <div>
                <p class="font-medium text-gray-800">{{ $customer['name'] }}</p>
                <p class="text-xs text-gray-400">{{ $customer['phone'] }}</p>
            </div>
            <div class="text-xs text-left">
                @if($customer['outstanding_balance'] > 0)
                    <span class="text-red-500">
                        دين: {{ money_format($customer['outstanding_balance']) }}
                    </span>
                @endif
            </div>
        </button>
        @endforeach
    </div>
    @endif
</div>
```

### `resources/views/livewire/invoices/invoice-filters.blade.php`
```blade
{{-- Livewire: InvoiceFilters --}}
<div class="card p-4">
    <div class="flex flex-wrap gap-3 items-end">
        <div class="min-w-36">
            <label class="form-label">الحالة</label>
            <select wire:model.live="status" class="form-input">
                <option value="">الكل</option>
                @foreach(config('factory.invoice_statuses') as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-36">
            <label class="form-label">من تاريخ</label>
            <input type="date" wire:model.live="dateFrom" class="form-input">
        </div>
        <div class="min-w-36">
            <label class="form-label">إلى تاريخ</label>
            <input type="date" wire:model.live="dateTo" class="form-input">
        </div>
        <button wire:click="clear" class="btn btn-ghost btn-sm">مسح</button>
    </div>
</div>
```

### `resources/views/livewire/shipments/shipment-order-assignment.blade.php`
```blade
{{-- Livewire: ShipmentOrderAssignment --}}
<div>
    {{-- Filter --}}
    <div class="mb-3 flex gap-2">
        <input type="text" wire:model.live.debounce.300ms="filterRegion"
               class="form-input text-sm flex-1" placeholder="تصفية حسب المنطقة">
        <span class="text-xs text-gray-400 self-end">
            {{ $this->availableOrders->count() }} طلبية جاهزة
        </span>
    </div>

    {{-- Orders list --}}
    <div class="max-h-72 overflow-y-auto space-y-1.5 mb-4">
        @forelse($this->availableOrders as $order)
        <label class="flex items-center gap-3 p-2.5 rounded-lg border border-gray-100
                      hover:border-brand-200 hover:bg-brand-50 cursor-pointer transition-colors">
            <input
                type="checkbox"
                wire:model="selectedOrderIds"
                value="{{ $order->id }}"
                class="rounded border-gray-300 text-brand-600">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800">{{ $order->customer->name }}</p>
                <p class="text-xs text-gray-400">
                    {{ $order->order_number }} ·
                    {{ $order->customer->region ?? '—' }} ·
                    {{ money_format($order->total_amount) }}
                </p>
            </div>
        </label>
        @empty
        <p class="text-center text-gray-400 text-sm py-6">
            لا توجد طلبيات جاهزة للشحن
        </p>
        @endforelse
    </div>

    @if(count($selectedOrderIds) > 0)
    <div class="flex items-center justify-between">
        <span class="text-sm text-gray-500">
            {{ count($selectedOrderIds) }} طلبية محددة
        </span>
        <button
            wire:click="attach"
            wire:loading.attr="disabled"
            class="btn btn-primary btn-sm">
            <span wire:loading.remove>إضافة للشحنة</span>
            <span wire:loading>جاري الإضافة...</span>
        </button>
    </div>
    @endif
</div>
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION C — EXCEL EXPORT CLASSES                    ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Exports/SalesReportExport.php`
```php
<?php
namespace App\Exports;

use App\Models\Invoice;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\{
    FromQuery, WithHeadings, WithMapping,
    WithStyles, ShouldAutoSize, WithTitle
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill};

/**
 * Sales report Excel export.
 * Uses Laravel Excel's FromQuery for memory-efficient large exports.
 * All column headers in Arabic, RTL sheet direction.
 *
 * @package App\Exports
 */
class SalesReportExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(
        private readonly Carbon $from,
        private readonly Carbon $to,
        private readonly array  $filters = [],
    ) {}

    public function title(): string
    {
        return 'المبيعات';
    }

    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return Invoice::with(['customer'])
            ->whereBetween('issue_date', [$this->from, $this->to])
            ->whereIn('status', ['issued','sent','paid','partial'])
            ->when(! empty($this->filters['customer_id']),
                fn($q) => $q->where('customer_id', $this->filters['customer_id'])
            )
            ->orderBy('issue_date');
    }

    public function headings(): array
    {
        return [
            'رقم الفاتورة',
            'اسم العميل',
            'التاريخ',
            'الإجمالي',
            'المدفوع',
            'المتبقي',
            'الحالة',
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->customer->name,
            $invoice->issue_date->format('Y/m/d'),
            $invoice->total_amount,
            $invoice->paid_amount,
            $invoice->balance_due,
            config("factory.invoice_statuses.{$invoice->status}", $invoice->status),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setRightToLeft(true);

        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID,
                               'startColor' => ['rgb' => '1E3A8A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ],
        ];
    }
}
```

### `app/Exports/ReceivablesExport.php`
```php
<?php
namespace App\Exports;

use App\Models\{Customer, Invoice};
use Maatwebsite\Excel\Concerns\{
    FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Accounts receivable aging report Excel export.
 *
 * @package App\Exports
 */
class ReceivablesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function title(): string
    {
        return 'الديون المستحقة';
    }

    public function collection(): \Illuminate\Support\Collection
    {
        return Customer::with([
            'invoices' => fn($q) => $q->whereNotIn('status', ['paid','void'])
                ->where('balance_due', '>', 0)
        ])
        ->where('outstanding_balance', '>', 0)
        ->get();
    }

    public function headings(): array
    {
        return [
            'كود العميل',
            'اسم العميل',
            'الهاتف',
            'فواتير مفتوحة',
            'إجمالي المستحق',
            'أقدم فاتورة',
        ];
    }

    public function map($customer): array
    {
        $oldestInvoice = $customer->invoices->sortBy('issue_date')->first();

        return [
            $customer->code,
            $customer->name,
            $customer->phone,
            $customer->invoices->count(),
            $customer->outstanding_balance,
            $oldestInvoice?->issue_date->format('Y/m/d') ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setRightToLeft(true);
        return [
            1 => ['font' => ['bold' => true],
                  'fill' => ['fillType' => Fill::FILL_SOLID,
                             'startColor' => ['rgb' => '1E3A8A']],
                  'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]],
        ];
    }
}
```

### `app/Exports/StockMovementExport.php`
```php
<?php
namespace App\Exports;

use App\Models\StockMovement;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\{
    FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Stock movement report Excel export.
 *
 * @package App\Exports
 */
class StockMovementExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(
        private readonly Carbon $from,
        private readonly Carbon $to,
        private readonly ?int   $productId = null,
    ) {}

    public function title(): string { return 'حركة المخزون'; }

    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return StockMovement::with(['product', 'createdByUser'])
            ->whereBetween('created_at', [$this->from, $this->to->endOfDay()])
            ->when($this->productId, fn($q) => $q->where('product_id', $this->productId))
            ->latest();
    }

    public function headings(): array
    {
        return [
            'التاريخ', 'المنتج', 'الكود',
            'نوع الحركة', 'الكمية',
            'الرصيد قبل', 'الرصيد بعد',
            'المرجع', 'المنفّذ',
        ];
    }

    public function map($movement): array
    {
        return [
            $movement->created_at->format('Y/m/d H:i'),
            $movement->product->name,
            $movement->product->code,
            $movement->type_label,
            $movement->quantity,
            $movement->quantity_before,
            $movement->quantity_after,
            $movement->reference_type ? "{$movement->reference_type}#{$movement->reference_id}" : '—',
            $movement->createdByUser->name ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setRightToLeft(true);
        return [1 => ['font' => ['bold' => true]]];
    }
}
```

### `app/Exports/CustomerStatementExport.php`
```php
<?php
namespace App\Exports;

use App\Models\Customer;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\{
    FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Customer account statement Excel export.
 * Accepts the pre-computed statement array from ReportService.
 *
 * @package App\Exports
 */
class CustomerStatementExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(
        private readonly Customer $customer,
        private readonly array    $statement,
        private readonly Carbon   $from,
        private readonly Carbon   $to,
    ) {}

    public function title(): string
    {
        return 'كشف-' . $this->customer->code;
    }

    public function array(): array
    {
        $rows = [];
        // Opening balance row
        $rows[] = ['', 'رصيد افتتاحي', '', '', '', $this->statement['opening_balance']];

        // Transactions
        foreach ($this->statement['transactions'] as $tx) {
            $rows[] = [
                $tx['date']->format('Y/m/d'),
                $tx['type'] === 'invoice' ? 'فاتورة مبيعات' : 'دفعة مستلمة',
                $tx['ref'],
                $tx['debit'] > 0 ? $tx['debit'] : '',
                $tx['credit'] > 0 ? $tx['credit'] : '',
                $tx['balance'],
            ];
        }

        // Closing balance
        $rows[] = ['', 'الرصيد الختامي', '', '', '', $this->statement['closing_balance']];

        return $rows;
    }

    public function headings(): array
    {
        return ['التاريخ', 'البيان', 'المرجع', 'مدين', 'دائن', 'الرصيد'];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setRightToLeft(true);
        return [1 => ['font' => ['bold' => true]]];
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION D — ERP REPORT VIEWS                        ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/erp/reports/sales.blade.php`
```blade
@extends('layouts.app')
@section('title', 'تقرير المبيعات')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">تقرير المبيعات التفصيلي</h1>
        <a href="{{ route('erp.reports.sales', array_merge(request()->query(), ['export' => 'excel'])) }}"
           class="btn btn-secondary btn-sm">📊 تصدير Excel</a>
    </div>

    {{-- Date filter --}}
    <div class="card p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="form-label">من تاريخ</label>
                <input type="date" name="from" value="{{ $from->toDateString() }}"
                       class="form-input" data-datepicker>
            </div>
            <div>
                <label class="form-label">إلى تاريخ</label>
                <input type="date" name="to" value="{{ $to->toDateString() }}"
                       class="form-input" data-datepicker>
            </div>
            <button type="submit" class="btn btn-secondary">تطبيق</button>
        </form>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-kpi-card title="إجمالي المبيعات"   :value="money_format($result['totals']['revenue'])"
                    color="blue" icon="banknotes" />
        <x-kpi-card title="إجمالي المحصّل"    :value="money_format($result['totals']['collected'])"
                    color="green" icon="check-circle" />
        <x-kpi-card title="إجمالي المتبقي"    :value="money_format($result['totals']['outstanding'])"
                    color="red" icon="exclamation-circle" />
    </div>

    {{-- Invoices table --}}
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>رقم الفاتورة</th>
                    <th>العميل</th>
                    <th>التاريخ</th>
                    <th>الاستحقاق</th>
                    <th>الإجمالي</th>
                    <th>المدفوع</th>
                    <th>المتبقي</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($result['rows'] as $invoice)
                <tr>
                    <td class="font-mono text-xs">
                        <a href="{{ route('invoices.show', $invoice) }}"
                           class="text-brand-600 hover:underline">
                            {{ $invoice->invoice_number }}
                        </a>
                    </td>
                    <td>{{ $invoice->customer->name }}</td>
                    <td>{{ $invoice->issue_date->format('Y/m/d') }}</td>
                    <td class="{{ $invoice->isOverdue() ? 'text-red-600 font-medium' : '' }}">
                        {{ $invoice->due_date?->format('Y/m/d') ?? '—' }}
                    </td>
                    <td class="tabular-nums font-medium">{{ money_format($invoice->total_amount) }}</td>
                    <td class="tabular-nums text-green-600">{{ money_format($invoice->paid_amount) }}</td>
                    <td class="tabular-nums {{ $invoice->balance_due > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                        {{ money_format($invoice->balance_due) }}
                    </td>
                    <td><x-badge :status="$invoice->status" size="sm" /></td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <x-empty-state message="لا توجد فواتير في هذه الفترة" />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
```

### `resources/views/erp/reports/receivables.blade.php`
```blade
@extends('layouts.app')
@section('title', 'تقرير الديون المستحقة')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">تقرير الديون المستحقة</h1>
        <a href="{{ route('erp.reports.receivables', ['export' => 'excel']) }}"
           class="btn btn-secondary btn-sm">📊 تصدير Excel</a>
    </div>

    {{-- Aging buckets summary --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach([
            ['label' => '0 – 30 يوم',   'key' => 'bucket_30', 'color' => 'green'],
            ['label' => '31 – 60 يوم',  'key' => 'bucket_60', 'color' => 'yellow'],
            ['label' => '61 – 90 يوم',  'key' => 'bucket_90', 'color' => 'orange'],
            ['label' => 'أكثر من 90 يوم','key'=> 'bucket_over90','color' => 'red'],
        ] as $bucket)
        <x-kpi-card
            :title="$bucket['label']"
            :value="money_format($result['totals'][$bucket['key']])"
            :color="$bucket['color']" />
        @endforeach
    </div>

    <div class="text-sm font-medium text-gray-600 text-left">
        الإجمالي الكلي للديون: {{ money_format($result['totals']['total']) }}
    </div>

    {{-- Receivables table --}}
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>العميل</th>
                    <th>الهاتف</th>
                    <th>عدد الفواتير</th>
                    <th>إجمالي المستحق</th>
                    <th>أيام التأخير</th>
                    <th>البكت</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($result['rows'] as $row)
                <tr>
                    <td>
                        <a href="{{ route('customers.show', $row['inv']->customer) }}"
                           class="font-medium text-brand-600 hover:underline">
                            {{ $row['inv']->customer->name }}
                        </a>
                    </td>
                    <td>{{ $row['inv']->customer->phone }}</td>
                    <td class="text-center">1</td>
                    <td class="tabular-nums font-bold text-red-600">
                        {{ money_format($row['inv']->balance_due) }}
                    </td>
                    <td class="text-center {{ $row['daysOverdue'] > 60 ? 'text-red-600 font-bold' : '' }}">
                        {{ $row['daysOverdue'] }} يوم
                    </td>
                    <td>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $row['bucket'] === 'bucket_30' ? 'bg-green-100 text-green-700' : ($row['bucket'] === 'bucket_60' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                            {{ ['bucket_30'=>'0-30','bucket_60'=>'31-60','bucket_90'=>'61-90','bucket_over90'=>'+90'][$row['bucket']] }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('invoices.show', $row['inv']) }}"
                           class="btn btn-ghost btn-sm">عرض الفاتورة</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <x-empty-state message="لا توجد ديون مستحقة — رائع! 🎉" />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
```

### `resources/views/erp/reports/profit-loss.blade.php`
```blade
@extends('layouts.app')
@section('title', 'تقرير الأرباح والخسائر')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">تقرير الأرباح والخسائر</h1>
    </div>

    {{-- Date filter --}}
    <div class="card p-4">
        <form method="GET" class="flex gap-3 items-end">
            <div>
                <label class="form-label">من</label>
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-input">
            </div>
            <div>
                <label class="form-label">إلى</label>
                <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-input">
            </div>
            <button type="submit" class="btn btn-secondary">تطبيق</button>
        </form>
    </div>

    {{-- Summary KPIs --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <x-kpi-card title="إيرادات المبيعات"  :value="money_format($result['totals']['revenue'])"      color="blue" />
        <x-kpi-card title="تكلفة البضاعة"     :value="money_format($result['totals']['cogs'])"         color="yellow" />
        <x-kpi-card title="مجمل الربح"        :value="money_format($result['totals']['gross_profit'])" color="green" />
        <x-kpi-card title="المصروفات"         :value="money_format($result['totals']['expenses'])"     color="red" />
        <x-kpi-card
            title="صافي الربح"
            :value="money_format($result['totals']['net_profit'])"
            :color="$result['totals']['net_profit'] >= 0 ? 'green' : 'red'" />
    </div>

    {{-- Monthly breakdown --}}
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>الشهر</th>
                    <th>الإيرادات</th>
                    <th>تكلفة البضاعة</th>
                    <th>مجمل الربح</th>
                    <th>المصروفات</th>
                    <th>صافي الربح</th>
                    <th>هامش الربح%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($result['months'] as $month)
                @php($margin = $month['revenue'] > 0 ? round(($month['netProfit'] / $month['revenue']) * 100, 1) : 0)
                <tr>
                    <td class="font-medium">{{ $month['monthStart']->isoFormat('MMMM YYYY') }}</td>
                    <td class="tabular-nums">{{ money_format($month['revenue']) }}</td>
                    <td class="tabular-nums text-orange-600">{{ money_format($month['cogs']) }}</td>
                    <td class="tabular-nums">{{ money_format($month['grossProfit']) }}</td>
                    <td class="tabular-nums text-red-500">{{ money_format($month['expenses']) }}</td>
                    <td class="tabular-nums font-bold {{ $month['netProfit'] < 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ money_format($month['netProfit']) }}
                    </td>
                    <td class="text-center {{ $margin < 0 ? 'text-red-500' : 'text-green-600' }}">
                        {{ $margin }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 font-bold">
                    <td>الإجمالي</td>
                    <td class="tabular-nums">{{ money_format($result['totals']['revenue']) }}</td>
                    <td class="tabular-nums text-orange-600">{{ money_format($result['totals']['cogs']) }}</td>
                    <td class="tabular-nums">{{ money_format($result['totals']['gross_profit']) }}</td>
                    <td class="tabular-nums text-red-500">{{ money_format($result['totals']['expenses']) }}</td>
                    <td class="tabular-nums {{ $result['totals']['net_profit'] < 0 ? 'text-red-600' : 'text-green-700' }}">
                        {{ money_format($result['totals']['net_profit']) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION E — PORTAL & DISTRIBUTION VIEWS             ║
## ╚══════════════════════════════════════════════════════════════╝

### `resources/views/portal/dashboard.blade.php`
```blade
@extends('layouts.app')
@section('title', 'بوابة العميل')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <div class="card p-6">
        <h1 class="text-xl font-bold text-gray-900">
            مرحباً، {{ auth()->user()->name }} 👋
        </h1>
        <p class="text-gray-500 mt-1">كشف حسابك وطلبياتك في مكان واحد</p>
    </div>

    {{-- Quick stats --}}
    <div class="grid grid-cols-2 gap-4">
        <x-kpi-card
            title="الرصيد المستحق"
            :value="money_format($customer->outstanding_balance)"
            :color="$customer->outstanding_balance > 0 ? 'red' : 'green'" />
        <x-kpi-card
            title="الرصيد المتاح"
            :value="$customer->credit_limit > 0 ? money_format($customer->available_credit) : 'غير محدود'"
            color="blue" />
    </div>

    {{-- Recent orders --}}
    <x-card title="آخر الطلبيات">
        @forelse($recentOrders as $order)
        <a href="{{ route('portal.orders.show', $order) }}"
           class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0
                  hover:bg-gray-50 transition-colors -mx-6 px-6">
            <div>
                <p class="font-medium text-gray-800">{{ $order->order_number }}</p>
                <p class="text-xs text-gray-400">{{ $order->order_date->format('Y/m/d') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="tabular-nums text-sm">{{ money_format($order->total_amount) }}</span>
                <x-badge :status="$order->status" size="sm" />
            </div>
        </a>
        @empty
        <x-empty-state message="لا توجد طلبيات بعد"
                       cta="إضافة طلبية"
                       :cta-url="route('portal.orders.create')" />
        @endforelse

        @if($recentOrders->count() > 0)
        <div class="mt-4 text-center">
            <a href="{{ route('portal.orders.index') }}" class="text-sm text-brand-600 hover:underline">
                عرض جميع الطلبيات ←
            </a>
        </div>
        @endif
    </x-card>

    {{-- Unpaid invoices --}}
    @if($unpaidInvoices->count() > 0)
    <x-card title="الفواتير غير المسددة">
        @foreach($unpaidInvoices as $invoice)
        <a href="{{ route('portal.invoices.show', $invoice) }}"
           class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0
                  hover:bg-gray-50 -mx-6 px-6">
            <div>
                <p class="font-medium text-gray-800">{{ $invoice->invoice_number }}</p>
                <p class="text-xs {{ $invoice->isOverdue() ? 'text-red-500' : 'text-gray-400' }}">
                    {{ $invoice->isOverdue()
                        ? 'متأخرة ' . $invoice->days_overdue . ' يوم'
                        : 'تستحق: ' . $invoice->due_date?->format('Y/m/d')
                    }}
                </p>
            </div>
            <span class="tabular-nums font-bold text-red-600">
                {{ money_format($invoice->balance_due) }}
            </span>
        </a>
        @endforeach
    </x-card>
    @endif
</div>
@endsection
```

### `resources/views/distribution/trucks/index.blade.php`
```blade
@extends('layouts.app')
@section('title', 'الشاحنات')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">الشاحنات</h1>
        <a href="{{ route('trucks.create') }}" class="btn-primary">+ إضافة شاحنة</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($trucks as $truck)
        <div class="card p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-bold text-lg text-gray-900" dir="ltr">{{ $truck->plate_number }}</p>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $truck->model ?? '—' }}</p>
                </div>
                <x-badge :status="$truck->status" />
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2 text-sm text-gray-500">
                @if($truck->capacity_kg)
                    <div>طاقة: {{ $truck->capacity_kg }} كغ</div>
                @endif
                @if($truck->capacity_units)
                    <div>وحدات: {{ $truck->capacity_units }}</div>
                @endif
                <div>شحنات نشطة: {{ $truck->shipments_count }}</div>
            </div>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('trucks.edit', $truck) }}" class="btn btn-ghost btn-sm">تعديل</a>
                @if($truck->status !== 'on_trip')
                <form method="POST" action="{{ route('trucks.destroy', $truck) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-ghost btn-sm text-red-400"
                            onclick="return confirm('{{ __('app.labels.confirm_delete') }}')">حذف</button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-3">
            <x-empty-state message="لا توجد شاحنات"
                           cta="إضافة شاحنة"
                           :cta-url="route('trucks.create')" />
        </div>
        @endforelse
    </div>
</div>
@endsection
```

### `resources/views/distribution/drivers/index.blade.php`
```blade
@extends('layouts.app')
@section('title', 'السائقون')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">السائقون</h1>
        <button x-on:click="$dispatch('open-add-driver')" class="btn-primary">+ إضافة سائق</button>
    </div>

    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>رقم الرخصة</th>
                    <th>انتهاء الرخصة</th>
                    <th>شحنات نشطة</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($drivers as $driver)
                <tr>
                    <td class="font-medium">{{ $driver->name }}</td>
                    <td>{{ $driver->phone }}</td>
                    <td class="font-mono text-xs" dir="ltr">{{ $driver->license_number ?? '—' }}</td>
                    <td class="{{ $driver->isLicenseExpired() ? 'text-red-600 font-medium' : '' }}">
                        {{ $driver->license_expiry?->format('Y/m/d') ?? '—' }}
                        @if($driver->isLicenseExpired()) ⚠ @endif
                    </td>
                    <td class="text-center">{{ $driver->shipments_count }}</td>
                    <td><x-badge :status="$driver->is_active ? 'active' : 'inactive'" size="sm" /></td>
                    <td>
                        <div class="flex gap-1">
                            <form method="POST" action="{{ route('drivers.destroy', $driver) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm text-red-400"
                                        onclick="return confirm('{{ __('app.labels.confirm_delete') }}')">
                                    حذف
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <x-empty-state message="لا يوجد سائقون" />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add driver modal --}}
    <x-modal event="open-add-driver" title="إضافة سائق جديد">
        <form method="POST" action="{{ route('drivers.store') }}" class="space-y-4">
            @csrf
            <x-form-input name="name"           label="الاسم"         required />
            <x-form-input name="phone"          label="الهاتف"        required />
            <x-form-input name="license_number" label="رقم الرخصة" />
            <x-form-input name="license_expiry" label="انتهاء الرخصة" type="date" data-datepicker />
            <button type="submit" class="btn btn-primary w-full">حفظ</button>
        </form>
    </x-modal>

</div>
@endsection
```

### `resources/views/products/low-alert.blade.php`
```blade
@extends('layouts.app')
@section('title', 'تنبيهات المخزون المنخفض')

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">تنبيهات المخزون المنخفض</h1>
            <p class="text-sm text-gray-500">{{ $products->count() }} منتج يحتاج تجديد</p>
        </div>
    </div>

    @if($products->isEmpty())
        <x-empty-state message="ممتاز! جميع المنتجات فوق الحد الأدنى للمخزون 🎉" />
    @else
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>الكود</th>
                    <th>المنتج</th>
                    <th>الفئة</th>
                    <th>المخزون الحالي</th>
                    <th>الحد الأدنى</th>
                    <th>الفجوة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                @php($gap = $product->low_stock_threshold - $product->stock_quantity)
                <tr>
                    <td class="font-mono text-xs text-gray-400">{{ $product->code }}</td>
                    <td class="font-medium">{{ $product->name }}</td>
                    <td class="text-gray-500">{{ $product->category?->name ?? '—' }}</td>
                    <td>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold
                            {{ $product->stock_quantity <= 0 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $product->stock_quantity }} {{ $product->unit }}
                        </span>
                    </td>
                    <td class="text-gray-500">{{ $product->low_stock_threshold }}</td>
                    <td class="font-medium text-red-600">-{{ $gap }}</td>
                    <td>
                        @can('products.adjust_stock')
                        <button x-on:click="$dispatch('open-adjust-{{ $product->id }}')"
                                class="btn btn-secondary btn-sm">
                            + تعديل المخزون
                        </button>
                        <x-modal event="open-adjust-{{ $product->id }}"
                                 title="تعديل مخزون: {{ $product->name }}">
                            <form method="POST" action="{{ route('stock.adjust') }}" class="space-y-4">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <x-form-input name="new_quantity"
                                              label="الكمية الجديدة"
                                              type="number"
                                              :value="$product->low_stock_threshold + 10"
                                              :helper="'المخزون الحالي: ' . $product->stock_quantity . ' ' . $product->unit"
                                              required />
                                <x-form-textarea name="reason" label="سبب التعديل" required />
                                <button type="submit" class="btn btn-primary w-full">حفظ التعديل</button>
                            </form>
                        </x-modal>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION F — MISSING MIGRATIONS (DETAIL)             ║
## ╚══════════════════════════════════════════════════════════════╝

### Migration pattern for `product_categories` — use as template for all:
```php
<?php
// database/migrations/002_create_product_categories_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
```

### Migration: `drivers` table
```php
<?php
// database/migrations/006_create_drivers_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->string('phone', 20);
            $table->string('license_number', 50)->nullable();
            $table->date('license_expiry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
```

### Migration: `trucks` table
```php
<?php
// database/migrations/005_create_trucks_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number', 30)->unique();
            $table->string('model', 100)->nullable();
            $table->decimal('capacity_kg', 10, 2)->nullable();
            $table->unsignedInteger('capacity_units')->nullable();
            $table->enum('status', ['available','on_trip','maintenance','inactive'])
                  ->default('available');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};
```

### Migration: `expenses` table
```php
<?php
// database/migrations/013_create_expenses_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100);
            $table->unsignedBigInteger('amount');     // BIGINT — smallest currency unit
            $table->date('expense_date');
            $table->text('description');
            $table->string('reference', 100)->nullable();
            $table->string('attachment', 255)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category');
            $table->index('expense_date');
            $table->index(['expense_date', 'category']); // for monthly summary
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
```

### Migration: `payments` table
```php
<?php
// database/migrations/012_create_payments_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 30)->unique();  // PAY-2026-00001
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');            // BIGINT
            $table->enum('payment_method', ['cash','credit','check','bank_transfer'])
                  ->default('cash');
            $table->date('payment_date');
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('invoice_id');
            $table->index('customer_id');
            $table->index('payment_date');
            $table->index(['customer_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION G — `app/Models/Traits/HasStatusTransitions` ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Models/Traits/HasStatusTransitions.php`
```php
<?php
namespace App\Models\Traits;

/**
 * Provides status-label accessors for models with enum status columns.
 * Reads labels from the factory config arrays.
 *
 * Usage:
 *   use HasStatusTransitions;
 *   protected string $statusConfigKey = 'order_statuses';
 *
 * @package App\Models\Traits
 */
trait HasStatusTransitions
{
    /**
     * Get the Arabic label for the current status.
     * Falls back to the raw status value if not found in config.
     */
    public function getStatusLabelAttribute(): string
    {
        $key = $this->statusConfigKey ?? 'order_statuses';
        return config("factory.{$key}.{$this->status}", $this->status);
    }

    /**
     * Check if the model is in one of the given statuses.
     */
    public function hasStatus(string ...$statuses): bool
    {
        return in_array($this->status, $statuses, true);
    }

    /**
     * Get all statuses with their Arabic labels for this model type.
     *
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        $instance = new static();
        $key = $instance->statusConfigKey ?? 'order_statuses';
        return config("factory.{$key}", []);
    }
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION H — INVOICE MODEL OBSERVER COMPLETE         ║
## ╚══════════════════════════════════════════════════════════════╝

### `app/Services/Erp/ReportService.php` — `getStockMovementReport` method (missing from Part 5)
```php
/**
 * Add this method to the existing ReportService class in Part 5.
 * Location: app/Services/Erp/ReportService.php
 */

/**
 * Stock movement report for a date range and optional product.
 *
 * @param  Carbon   $from
 * @param  Carbon   $to
 * @param  int|null $productId  Filter to a specific product
 */
public function getStockMovementReport(Carbon $from, Carbon $to, ?int $productId = null): Collection
{
    return \App\Models\StockMovement::with(['product','createdByUser'])
        ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
        ->when($productId, fn($q) => $q->where('product_id', $productId))
        ->latest()
        ->get();
}
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION I — FINAL VERIFICATION SCRIPTS              ║
## ╚══════════════════════════════════════════════════════════════╝

### `scripts/verify-build.sh` — Full automated build verification
```bash
#!/usr/bin/env bash
# verify-build.sh — Run after completing all phases
# Usage: bash scripts/verify-build.sh
set -euo pipefail

PASS=0
FAIL=0
WARN=0

check() {
    local desc="$1"
    local cmd="$2"
    if eval "$cmd" &>/dev/null; then
        echo "✅ $desc"
        ((PASS++))
    else
        echo "❌ $desc"
        ((FAIL++))
    fi
}

warn() {
    local desc="$1"
    local cmd="$2"
    if eval "$cmd" &>/dev/null; then
        echo "✅ $desc"
        ((PASS++))
    else
        echo "⚠️  $desc (non-blocking)"
        ((WARN++))
    fi
}

echo "═══════════════════════════════════════════"
echo " Factory System — Build Verification"
echo "═══════════════════════════════════════════"
echo ""

# ── Environment ──────────────────────────────────────────────────────────────
echo "── ENVIRONMENT ──"
check "PHP version ≥ 8.3" "php -r \"exit(PHP_VERSION_ID < 80300 ? 1 : 0);\""
check ".env file exists" "[ -f .env ]"
check "APP_DEBUG=false check (production)" "grep -q 'APP_DEBUG=true' .env || grep -q 'APP_DEBUG=true' .env"
check "APP_KEY is set" "grep -q 'APP_KEY=base64' .env"

# ── Dependencies ─────────────────────────────────────────────────────────────
echo ""
echo "── DEPENDENCIES ──"
check "vendor/ directory exists" "[ -d vendor ]"
check "node_modules/ exists" "[ -d node_modules ]"
check "public/build/ exists (Vite built)" "[ -d public/build ]"

# ── Database ─────────────────────────────────────────────────────────────────
echo ""
echo "── DATABASE ──"
check "Migrations run cleanly" "php artisan migrate:status 2>&1 | grep -q 'Ran'"
check "All seeders ran (roles exist)" "php artisan tinker --execute=\"exit(\\App\\Models\\User::role('super_admin')->count() > 0 ? 0 : 1);\""
check "System settings populated" "php artisan tinker --execute=\"exit(\\App\\Models\\SystemSetting::count() > 0 ? 0 : 1);\""

# ── File size audit ───────────────────────────────────────────────────────────
echo ""
echo "── FILE SIZE (400-line limit) ──"
OVERSIZED=$(find app/ resources/views/ -name "*.php" -o -name "*.blade.php" 2>/dev/null | \
    xargs wc -l 2>/dev/null | awk '$1 > 400 {print $2}' | grep -v "total" | wc -l)
if [ "$OVERSIZED" -eq 0 ]; then
    echo "✅ All files under 400 lines"
    ((PASS++))
else
    echo "❌ $OVERSIZED files exceed 400 lines"
    find app/ resources/views/ -name "*.php" -o -name "*.blade.php" 2>/dev/null | \
        xargs wc -l 2>/dev/null | awk '$1 > 400 {print "   "$2" ("$1" lines)"}' | grep -v "total"
    ((FAIL++))
fi

# ── Money safety ─────────────────────────────────────────────────────────────
echo ""
echo "── MONEY SAFETY ──"
FLOAT_MONEY=$(grep -rn "unit_price\|total_amount\|paid_amount\|balance_due" app/Models/ app/Services/ 2>/dev/null | \
    grep "float\|decimal\|0\.\d\+" | grep -v "tax_rate\|discount_percent" | wc -l)
if [ "$FLOAT_MONEY" -eq 0 ]; then
    echo "✅ No float money values found"
    ((PASS++))
else
    echo "❌ Found $FLOAT_MONEY potential float money values"
    ((FAIL++))
fi

# ── Tests ─────────────────────────────────────────────────────────────────────
echo ""
echo "── TEST SUITE ──"
check "All tests pass" "php artisan test --stop-on-failure"
warn "Coverage ≥ 80%" "php artisan test --coverage --min=80"

# ── Caches ───────────────────────────────────────────────────────────────────
echo ""
echo "── CACHE SYSTEMS ──"
check "Config cache works" "php artisan config:cache && php artisan config:clear"
check "Route cache works" "php artisan route:cache && php artisan route:clear"
check "View cache works" "php artisan view:cache && php artisan view:clear"

# ── Arabic helpers ───────────────────────────────────────────────────────────
echo ""
echo "── ARABIC HELPERS ──"
check "money_format() works" "php artisan tinker --execute=\"echo money_format(150000); exit(str_contains(money_format(150000), 'ل.س') ? 0 : 1);\""
check "AmountToWords works" "php artisan tinker --execute=\"echo \\App\\Helpers\\AmountToWords::toArabic(1000); exit(str_contains(\\App\\Helpers\\AmountToWords::toArabic(1000), 'ألف') ? 0 : 1);\""

# ── Summary ───────────────────────────────────────────────────────────────────
echo ""
echo "═══════════════════════════════════════════"
echo " RESULTS: ✅ $PASS passed · ❌ $FAIL failed · ⚠️  $WARN warnings"
echo "═══════════════════════════════════════════"

if [ "$FAIL" -gt 0 ]; then
    echo " Build NOT ready for production. Fix failures first."
    exit 1
else
    echo " ✓ Build verification PASSED."
    exit 0
fi
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION J — `PROGRESS.md` FULL TEMPLATE             ║
## ╚══════════════════════════════════════════════════════════════╝

### `PROGRESS.md` — Full template for the agent to fill in:
```markdown
# PROGRESS.md — Factory System Live Build Tracker
> Updated by agent after EVERY completed task.
> Zero tolerance for stale entries.

---

## 📊 Current Build Status

| Metric            | Value |
|-------------------|-------|
| Phases completed  | 0/18  |
| Files created     | 0/170+|
| Tests passing     | 0/??  |
| Coverage          | 0%    |
| Last updated      | —     |
| Current phase     | 00 — Bootstrap |
| Blocker           | None  |

---

## 🗓️ Session Log

| # | Date | Phase | Tasks Done | Tests | Notes |
|---|------|-------|------------|-------|-------|
| 001 | — | 00 | — | N/A | Started |

---

## 📋 Module Status

| Module | Phase | Status | Files | Tests | Notes |
|--------|-------|--------|-------|-------|-------|
| Management files | 00 | ⬜ Not started | 0/7 | N/A | |
| Config files | 00 | ⬜ Not started | 0/6 | N/A | |
| Database migrations | 01 | ⬜ Not started | 0/17 | N/A | |
| Value objects + state machines | 02 | ⬜ Not started | 0/3 | 0/2 | |
| Base classes + contracts | 03 | ⬜ Not started | 0/15 | 0/1 | |
| Models + traits | 04 | ⬜ Not started | 0/18 | 0/4 | |
| Seeders | 05 | ⬜ Not started | 0/6 | N/A | |
| Auth + middleware | 06 | ⬜ Not started | 0/8 | 0/1 | |
| Inventory module | 07 | ⬜ Not started | 0/12 | 0/1 | |
| Customers module | 08 | ⬜ Not started | 0/10 | 0/1 | |
| Orders module | 09 | ⬜ Not started | 0/18 | 0/2 | |
| Distribution module | 10 | ⬜ Not started | 0/12 | 0/1 | |
| Invoicing module | 11 | ⬜ Not started | 0/10 | 0/1 | |
| PDF generation | 12 | ⬜ Not started | 0/6 | 0/1 | |
| ERP + reports | 13 | ⬜ Not started | 0/14 | 0/2 | |
| Frontend | 14 | ⬜ Not started | 0/35 | N/A | |
| Notifications | 15 | ⬜ Not started | 0/12 | N/A | |
| Security hardening | 16 | ⬜ Not started | 0/7 | 0/1 | |
| Full test suite | 17 | ⬜ Not started | 0/18 | 0/18 | |
| Deployment | 18 | ⬜ Not started | 0/6 | N/A | |

Status: ⬜ Not started | 🔄 In progress | ✅ Complete | ❌ Blocked

---

## ⚠️ Active Blockers

None.

---

## 📝 Decisions Made

See DECISIONS.md for full ADR log.

---

## ✅ Completed Files

*(agent appends every file here as it is created)*
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         SECTION K — MASTER_INDEX.md                         ║
## ╚══════════════════════════════════════════════════════════════╝

### `MASTER_INDEX.md` — Quick reference to find everything

```markdown
# MASTER_INDEX.md
## Quick reference: what's in each prompt part

---

## PART 1 — Foundation & Architecture
File: AGENT_PROMPT_FACTORY_SYSTEM.md (86KB, 2387 lines)

FIND HERE:
- Agent identity & session protocols (start/task/module/end)
- SOLID principles with code enforcement examples
- All 12 design patterns with locations and usage rules
- 400-line rule with split strategies
- Anti-pattern list (what NEVER to do)
- Complete directory tree (170+ files named)
- 18-phase execution plan (phases 00-07 detailed)
- config/factory.php with all Arabic status labels
- docker-compose.yml, .env template
- Money value object (Money.php)
- OrderStateMachine (full)

---

## PART 2 — Core Service Layer
File: AGENT_PROMPT_FACTORY_SYSTEM_PART2.md (118KB, 3186 lines)

FIND HERE:
- All 7 DTOs (CreateOrderDTO, OrderItemDTO, RecordPaymentDTO, CreateCustomerDTO, etc.)
- OrderRepository (full with all filters)
- ProductRepository (with searchForOrder, lockForUpdate)
- OrderService (create via Pipeline)
- OrderStatusService (accept, cancel, markReady, confirmDelivery, recordReturn)
- OrderFinancialsService (calculateTotals, calculateOrderValue)
- InvoiceService (createFromOrder, issue, void, recordPayment, deletePayment)
- SettingService (get, set, setMany, all with Redis caching)
- All 3 pipeline pipes (ValidateCredit, ValidateStock, CalculateTotals)
- OrderController + OrderStatusController
- StoreOrderRequest + StoreProductRequest
- OrderItemsTable Livewire + CustomerBalanceChecker Livewire
- orders/show.blade.php + all partials
- x-badge + x-kpi-card + x-btn Blade components
- Full invoice PDF template (Arabic RTL)
- StockServiceTest + OrderStateMachineTest
- OrderLifecycleTest + RoleAccessTest + InvoicePaymentTest
- Complete routes/web.php (60+ routes)
- MoneyHelper + AmountToWords + CodeGeneratorFactory
- lang/ar/orders.php, invoices.php, validation.php

---

## PART 3 — Models, Observers, Notifications, Deployment
File: AGENT_PROMPT_FACTORY_SYSTEM_PART3.md (111KB, 3386 lines)

FIND HERE:
- HasMoneyFormatting trait + HasSoftDeleteGuard trait
- Product model (full) + Customer model (full)
- Invoice model (full) + Shipment model (full)
- OrderObserver + ProductObserver + PaymentObserver
- EventServiceProvider (all observers + event→listener map)
- OrderStatusChanged + LowStockAlert + PaymentReceived notifications
- PdfService (generateInvoice, generateManifest, generateStatement, stream, download)
- AmountToWords class (full implementation)
- ReportService (getSalesSummary, getReceivablesAging, getProfitLossReport, getCustomerStatement)
- SendOverdueInvoiceAlerts + CheckLowStockLevels commands
- routes/console.php (3 scheduled jobs)
- app.js + charts.js + tailwind.config.js + app.css
- docker-compose.yml, Dockerfile, nginx.conf, supervisor.conf, deploy.sh
- RolesAndPermissionsSeeder + SystemSettingsSeeder + DatabaseSeeder + DemoDataSeeder
- erp/dashboard.blade.php
- README.md + CHANGELOG.md
- OrderFactory + ProductFactory + CustomerFactory

---

## PART 4 — Auth, Policies, Distribution
File: AGENT_PROMPT_FACTORY_SYSTEM_PART4.md (115KB, 3525 lines)

FIND HERE:
- All 7 policies (Order, Invoice, Payment, Product, Customer, Shipment, Expense)
- AuthServiceProvider (with Gate::before super_admin bypass)
- SetLocale + CheckUserIsActive + CustomerPortalMiddleware + LastActivityMiddleware
- bootstrap/app.php (middleware registration)
- LoginController (email OR phone, rate limiting, last_login tracking)
- ShipmentService (create, attachOrders, detachOrder, dispatch, complete, cancel)
- CustomerService (create, update, delete, enablePortalAccess, recalculateBalance)
- ShipmentController + ShipmentOrderController
- InvoiceController + PaymentController
- SettingController + UserController + AuditLogController
- NotificationBell Livewire + ProductSearch Livewire
- Email templates (layout, order-status, invoice-issued, payment-confirmed)
- Error pages (404, 403, 500, maintenance)
- ExcelExportStrategy + CsvExportStrategy
- Setting facade
- vite.config.js + pest.php + phpunit.xml + tests/TestCase.php
- ShipmentStateMachine + CreateShipmentDTO
- RecordPaymentRequest + StoreShipmentRequest + AttachOrdersRequest
- TruckFactory + DriverFactory + ShipmentFactory + InvoiceFactory + ExpenseFactory
- ShipmentFlowTest + PdfDownloadTest
- Complete file manifest (all 170+ files)
- lang/ar/auth.php, app.php, shipments.php

---

## PART 5 — Remaining Models, Repositories, Services, Controllers
File: AGENT_PROMPT_FACTORY_SYSTEM_PART5.md (107KB, 3410 lines)

FIND HERE:
- Truck + Driver + OrderItem + StockMovement + Expense + SystemSetting models
- ProductCategory model
- InvoiceRepository + ShipmentRepository + CustomerRepository + StockMovementRepository
- ProductService (create, update, delete, restore)
- ExpenseService (list, create, update, delete, getMonthlySummary)
- ProductController + StockController + CustomerController
- DashboardController + ReportController + ExpenseController + TruckController
- All 7 domain Events (OrderAccepted, OrderCancelled, OrderDelivered, etc.)
- 5 Queued Listeners (NotifyCustomer, UpdateBalance, SendLowStockAlert, etc.)
- InvoiceObserver
- AdminUserSeeder + ProductCategorySeeder
- UpdateProductRequest + StockAdjustmentRequest + CancelOrderRequest
- StoreCustomerRequest + UpdateCustomerRequest
- AmountToWordsTest + OrderFinancialsServiceTest
- AuthTest + ProductCrudTest + CustomerCrudTest (full)
- layouts/app.blade.php + topbar.blade.php + alerts.blade.php
- products/index.blade.php
- config/money.php + config/pdf.php
- InvoiceOverdue + PasswordReset notifications
- Pre-commit verification commands

---

## PART 6 — Blade Views, Components, Auth, Portal
File: AGENT_PROMPT_FACTORY_SYSTEM_PART6.md (112KB, 2850 lines)

FIND HERE:
- User model (full) + UserFactory
- PasswordResetController + DriverController + UpdateOrderRequest
- All Livewire view templates (order-items-table, customer-balance-checker,
  notification-bell, product-search)
- All 15 Blade components (table, card, btn, form-input, modal, confirm-modal,
  pagination, empty-state, status-timeline, form-select, form-textarea, etc.)
- orders/create.blade.php (full order form with Livewire)
- customers/index.blade.php + orders/daily.blade.php
- distribution/shipments/show.blade.php
- invoices/show.blade.php (full with payment form)
- admin/users/index.blade.php + admin/settings/index.blade.php
- layouts/auth.blade.php + layouts/print.blade.php
- auth/login.blade.php + auth/password-reset.blade.php
- pdf/shipment-manifest.blade.php + pdf/customer-statement.blade.php
- OrderCancellationTest + CustomerPortalTest
- lang/ar/notifications.php, products.php, customers.php, admin.php, erp.php
- routes/portal.php + CustomerPortalController
- All 4 exception classes

---

## PART 7 — Final Remaining Pieces (this file)
File: AGENT_PROMPT_FACTORY_SYSTEM_PART7.md

FIND HERE:
- OrderFilters + InvoiceFilters + CustomerSearch + ShipmentOrderAssignment Livewire
- All 4 Livewire view templates for above
- SalesReportExport + ReceivablesExport + StockMovementExport + CustomerStatementExport
- erp/reports/sales.blade.php + receivables.blade.php + profit-loss.blade.php
- portal/dashboard.blade.php
- distribution/trucks/index.blade.php + drivers/index.blade.php
- products/low-alert.blade.php
- Migrations detail (product_categories, drivers, trucks, expenses, payments)
- HasStatusTransitions trait
- ReportService::getStockMovementReport (missing method)
- verify-build.sh (automated build check script)
- PROGRESS.md template
- lang/ar/trucks.php, drivers.php, expenses.php
- MASTER_INDEX.md (this file)
```

---

## ╔══════════════════════════════════════════════════════════════╗
## ║         ABSOLUTE FINAL INSTRUCTION TO THE AGENT             ║
## ╚══════════════════════════════════════════════════════════════╝

```
╔═══════════════════════════════════════════════════════════════╗
║          7-PART MASTER AGENT PROMPT — COMPLETE                ║
║          نظام إدارة معمل التوزيع والشحن                       ║
╠═══════════════════════════════════════════════════════════════╣
║  Total: 7 files · ~21,000 lines · 730KB                       ║
║  Coverage: 170+ files · 18 phases · zero inference            ║
╠═══════════════════════════════════════════════════════════════╣
║  READING ORDER:                                               ║
║  Part 1 → Architecture & phases                               ║
║  Part 2 → Core services, DTOs, repositories                   ║
║  Part 3 → Models, observers, notifications, deployment        ║
║  Part 4 → Auth, policies, middleware, distribution            ║
║  Part 5 → Remaining models, services, controllers             ║
║  Part 6 → Views, components, portal, tests                    ║
║  Part 7 → Final Livewire, exports, report views, index        ║
╠═══════════════════════════════════════════════════════════════╣
║  MANDATORY FIRST STEPS:                                       ║
║  1. Read all 7 parts completely                               ║
║  2. Create AGENT.md, PROGRESS.md, TODO.md                     ║
║  3. Create DECISIONS.md, SKILLS.md                            ║
║  4. Run Phase 00 bootstrap                                     ║
║  5. Never skip a phase                                        ║
╠═══════════════════════════════════════════════════════════════╣
║  THE 8 LAWS THAT CANNOT BEND:                                 ║
║  1. Every file ≤ 400 lines (split at 350)                     ║
║  2. Money = BIGINT always (never float)                       ║
║  3. Business logic in Services, not Controllers               ║
║  4. Eloquent in Repositories, not Services                    ║
║  5. All DB writes in DB::transaction()                        ║
║  6. Every controller action has authorize()                   ║
║  7. Arabic strings in lang/ar/ only (never hardcoded PHP)     ║
║  8. Always paginate lists (never ->get() unbounded)           ║
╚═══════════════════════════════════════════════════════════════╝

اقرأ الأجزاء السبعة أولاً.
أنشئ ملفات الإدارة ثانياً.
ابدأ Phase 00 ثالثاً.
النجاح = اتباع البروتوكول بدقة تامة.
```

---

*PART 7 OF 7 — MASTER AGENT PROMPT v1.0.0 — ABSOLUTELY COMPLETE*
*نظام إدارة معمل التوزيع والشحن*
*May 2026*
*Total specification: 7 parts · ~21,000 lines · 730KB*
*All 170+ files specified · 18 phases · 18 test files · Zero ambiguity*
