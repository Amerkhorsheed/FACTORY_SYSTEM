<?php

namespace App\Pipelines\Order;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\DTOs\Orders\CreateOrderDTO;
use App\Exceptions\CreditLimitExceededException;
use App\Services\Orders\OrderFinancialsService;
use App\ValueObjects\Money;
use Closure;

/**
 * Validates the customer has sufficient credit for this order.
 */
class ValidateCustomerCreditPipe
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly OrderFinancialsService $financials,
    ) {}

    /**
     * @throws CreditLimitExceededException
     */
    public function handle(CreateOrderDTO $dto, Closure $next): CreateOrderDTO
    {
        $customer = $this->customers->findByIdOrFail($dto->customerId);
        $orderValue = $this->financials->calculateOrderValue($dto->items);

        $availableCredit = $customer->credit_limit - $customer->outstanding_balance;

        if ($customer->credit_limit > 0 && $orderValue > $availableCredit) {
            throw new CreditLimitExceededException(
                __('orders.credit_limit_exceeded', [
                    'available' => Money::of($availableCredit)->format(),
                    'required' => Money::of($orderValue)->format(),
                ])
            );
        }

        return $next($dto);
    }
}
