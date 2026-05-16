<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Erp\StoreExpenseRequest;
use App\Http\Requests\Erp\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\Erp\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Expense CRUD for operational cost tracking.
 */
class ExpenseController extends Controller
{
    public function __construct(private readonly ExpenseService $expenses)
    {
        $this->authorizeResource(Expense::class, 'expense');
    }

    public function index(Request $request): View
    {
        $expenses = $this->expenses->list($request->only([
            'category', 'date_from', 'date_to', 'min_amount', 'max_amount',
        ]));

        $total = $this->expenses->getTotalForPeriod(
            $request->input('date_from', now()->startOfMonth()),
            $request->input('date_to', now()->endOfMonth())
        );

        return view('erp.expenses.index', compact('expenses', 'total'));
    }

    public function create(): View
    {
        return view('erp.expenses.create');
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $expense = $this->expenses->create($request->validated());

        return redirect()
            ->route('erp.expenses.show', $expense)
            ->with('success', __('expenses.created'));
    }

    public function show(Expense $expense): View
    {
        $expense->load('createdByUser');

        return view('erp.expenses.show', compact('expense'));
    }

    public function edit(Expense $expense): View
    {
        return view('erp.expenses.edit', compact('expense'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->expenses->update($expense, $request->validated());

        return redirect()
            ->route('erp.expenses.show', $expense)
            ->with('success', __('expenses.updated'));
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->expenses->delete($expense);

        return redirect()
            ->route('erp.expenses.index')
            ->with('success', __('expenses.deleted'));
    }
}
