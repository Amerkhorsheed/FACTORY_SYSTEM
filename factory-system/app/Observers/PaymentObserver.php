<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        activity('payments')
            ->performedOn($payment)
            ->causedBy(auth()->user())
            ->withProperties([
                'amount' => $payment->amount,
                'method' => $payment->payment_method,
                'invoice_number' => $payment->invoice()->value('invoice_number'),
                'customer' => $payment->customer()->value('name'),
            ])
            ->log(__('activity.payments.created'));
    }

    public function deleted(Payment $payment): void
    {
        activity('payments')
            ->performedOn($payment)
            ->causedBy(auth()->user())
            ->withProperties([
                'amount' => $payment->amount,
                'invoice_number' => $payment->invoice()->value('invoice_number'),
            ])
            ->log(__('activity.payments.deleted'));
    }
}
