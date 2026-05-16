<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a customer's credit limit would be exceeded by an order.
 */
class CreditLimitExceededException extends Exception {}
