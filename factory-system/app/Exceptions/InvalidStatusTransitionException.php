<?php

namespace App\Exceptions;

use DomainException;

/**
 * Exception thrown when a lifecycle status transition is not allowed.
 *
 * @package App\Exceptions
 */
class InvalidStatusTransitionException extends DomainException
{
}
