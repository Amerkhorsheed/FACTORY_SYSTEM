<?php

namespace App\Exceptions;

use DomainException;

/**
 * Exception thrown when a lifecycle status transition is not allowed.
 */
class InvalidStatusTransitionException extends DomainException {}
