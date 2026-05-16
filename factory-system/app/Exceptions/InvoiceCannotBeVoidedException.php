<?php

namespace App\Exceptions;

use DomainException;

/**
 * Thrown when an invoice cannot be voided due to existing payments.
 */
class InvoiceCannotBeVoidedException extends DomainException
{
    public function __construct(string $message = 'هذه الفاتورة لا يمكن إلغاؤها.')
    {
        parent::__construct($message);
    }
}
