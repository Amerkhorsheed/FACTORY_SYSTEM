<?php

namespace App\StateMachines;

use App\Exceptions\InvalidStatusTransitionException;

/**
 * Validates shipment lifecycle status transitions.
 *
 * @package App\StateMachines
 */
final class ShipmentStateMachine
{
    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        'planned' => ['loading', 'dispatched', 'cancelled'],
        'loading' => ['dispatched', 'cancelled'],
        'dispatched' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    /**
     * Validate and return the requested new status.
     *
     * @param string $currentStatus Current shipment status.
     * @param string $newStatus Requested shipment status.
     * @return string
     * @throws InvalidStatusTransitionException
     */
    public function transition(string $currentStatus, string $newStatus): string
    {
        $allowed = self::TRANSITIONS[$currentStatus] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new InvalidStatusTransitionException(
                "Cannot transition shipment from [{$currentStatus}] to [{$newStatus}]. Allowed: " . implode(', ', $allowed)
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
     * @param string $status Shipment status.
     * @return bool
     */
    public function isFinal(string $status): bool
    {
        return empty(self::TRANSITIONS[$status] ?? []);
    }
}
