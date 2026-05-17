<?php

namespace App\Http\Controllers\Customers;

use App\Contracts\Services\PdfServiceInterface;
use App\DTOs\Customers\CreateCustomerDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\Customers\CustomerService;
use App\Services\Erp\ReportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Customer CRUD and portal access management.
 */
class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $service,
        private readonly ReportService $reports,
        private readonly PdfServiceInterface $pdfService,
    ) {
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index(Request $request): View
    {
        $customers = $this->service->list($request->only([
            'search', 'category', 'region', 'is_active', 'has_balance',
        ]));

        $kpis = $this->service->getKpis();

        return view('customers.index', compact('customers', 'kpis'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $dto = CreateCustomerDTO::fromArray($request->validated());
        $customer = $this->service->create($dto);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', __('customers.created', ['name' => $customer->name]));
    }

    public function show(Customer $customer): View
    {
        $this->service->loadDetails($customer);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $dto = CreateCustomerDTO::fromArray($request->validated());
        $this->service->update($customer, $dto);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', __('customers.updated'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        try {
            $this->service->delete($customer);
        } catch (\DomainException $e) {
            return redirect()
                ->route('customers.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('customers.index')
            ->with('success', __('customers.deleted'));
    }

    public function activate(Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);
        $customer->update(['is_active' => ! $customer->is_active]);

        return back()->with('success', $customer->is_active
            ? __('customers.activated')
            : __('customers.deactivated')
        );
    }

    public function togglePortalAccess(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        if ($customer->portal_access) {
            $this->service->disablePortalAccess($customer);
        } else {
            $request->validate(['password' => ['required', 'string', 'min:8']]);
            $this->service->enablePortalAccess($customer, $request->password);
        }

        return back()->with('success', __('customers.portal_access_updated'));
    }

    public function statement(Request $request, Customer $customer): View
    {
        $this->authorize('view', $customer);

        $from = Carbon::parse($request->get('from', now()->startOfMonth()));
        $to = Carbon::parse($request->get('to', now()->endOfMonth()));

        $statement = $this->reports->getCustomerStatement($customer, $from, $to);

        return view('customers.statement', compact('customer', 'statement', 'from', 'to'));
    }

    public function statementPdf(Request $request, Customer $customer): Response
    {
        $this->authorize('view', $customer);

        $from = Carbon::parse($request->get('from', now()->startOfMonth()));
        $to = Carbon::parse($request->get('to', now()->endOfMonth()));

        return $this->pdfService->downloadStatement($customer, $from, $to);
    }
}
