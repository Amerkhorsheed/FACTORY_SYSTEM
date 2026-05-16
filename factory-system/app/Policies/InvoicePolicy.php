<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization policy for Invoice model.
 */
class InvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if (! $user->hasPermissionTo('invoices.view')) {
            return false;
        }

        if ($user->hasRole('customer')) {
            return $user->customer?->id === $invoice->customer_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('invoices.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.create')
            && in_array($invoice->status, ['draft', 'issued'], true);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.void')
            && $invoice->canBeVoided();
    }

    public function issue(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.create')
            && $invoice->status === 'draft';
    }

    public function void(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.void')
            && $invoice->canBeVoided();
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.send')
            && in_array($invoice->status, ['issued', 'paid', 'partial'], true);
    }

    public function recordPayment(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('payments.create')
            && ! in_array($invoice->status, ['paid', 'void', 'draft'], true);
    }

    public function deletePayment(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('payments.delete');
    }

    public function viewAll(User $user): bool
    {
        return $user->hasPermissionTo('invoices.view_all');
    }
}
