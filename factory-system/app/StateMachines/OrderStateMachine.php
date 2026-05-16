<?php

namespace App\StateMachines;

use App\Exceptions\InvalidStatusTransitionException;

/**
 * Validates order lifecycle status transitions.
 *
 * @package App\StateMachines
 */
final class OrderStateMachine
{
    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        'pending' => ['accepted', 'cancelled'],
        'accepted' => ['preparing', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'returned'],
        'delivered' => [],
        'cancelled' => [],
        'returned' => [],
    ];

    /**
     * Validate and return the requested new status.
     *
     * @param string $currentStatus Current order status.
     * @param string $newStatus Requested order status.
     * @return string
     * @throws InvalidStatusTransitionException
     */
    public function transition(string $currentStatus, string $newStatus): string
    {
        $allowed = self::TRANSITIONS[$currentStatus] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new InvalidStatusTransitionException(
                "Cannot transition order from [{$currentStatus}] to [{$newStatus}]. Allowed: " . implode(', ', $allowed)
            );
        }

        return $newStatus;
    }

    /**
     * Determine whether a transition is allowed.
     *
     * @param string $from Current status.
     * @param string $to Requested status.
     * @return bool
     */
    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * Get allowed transitions for a status.
     *
     * @param string $from Current status.
     * @return array<int, string>
     */
    public function allowedTransitions(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    /**
     * Determine whether a status is final.
     *
     * @param string $status Order status.
     * @return bool
     */
    public function isFinal(string $status): bool
    {
        return empty(self::TRANSITIONS[$status] ?? []);
    }

    /**
     * Determine whether an order can be cancelled from the status.
     *
     * @param string $status Order status.
     * @return bool
     */
    public function canBeCancelled(string $status): bool
    {
        return $this->canTransition($status, 'cancelled');
    }
}
