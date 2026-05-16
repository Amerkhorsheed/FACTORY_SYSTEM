<?php

namespace Tests\Unit;

use App\Exceptions\InvalidStatusTransitionException;
use App\StateMachines\OrderStateMachine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OrderStateMachineTest extends TestCase
{
    private OrderStateMachine $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateMachine = new OrderStateMachine();
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function validTransitions(): array
    {
        return [
            ['pending', 'accepted'],
            ['accepted', 'preparing'],
            ['preparing', 'ready'],
            ['ready', 'shipped'],
            ['shipped', 'delivered'],
            ['shipped', 'returned'],
        ];
    }

    #[DataProvider('validTransitions')]
    public function test_it_allows_valid_forward_transitions(string $from, string $to): void
    {
        $this->assertSame($to, $this->stateMachine->transition($from, $to));
    }

    /**
     * @test
     */
    public function test_it_allows_cancellation_before_shipping(): void
    {
        foreach (['pending', 'accepted', 'preparing', 'ready'] as $status) {
            $this->assertTrue($this->stateMachine->canBeCancelled($status));
        }
    }

    /**
     * @test
     */
    public function test_it_rejects_cancellation_after_shipping(): void
    {
        foreach (['shipped', 'delivered', 'returned', 'cancelled'] as $status) {
            $this->assertFalse($this->stateMachine->canBeCancelled($status));
        }
    }

    /**
     * @test
     */
    public function test_it_throws_for_illegal_transitions(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);

        $this->stateMachine->transition('pending', 'delivered');
    }

    /**
     * @test
     */
    public function test_it_identifies_final_states(): void
    {
        foreach (['delivered', 'cancelled', 'returned'] as $status) {
            $this->assertTrue($this->stateMachine->isFinal($status));
        }
    }

    /**
     * @test
     */
    public function test_it_returns_allowed_transitions(): void
    {
        $this->assertSame(['accepted', 'cancelled'], $this->stateMachine->allowedTransitions('pending'));
        $this->assertSame([], $this->stateMachine->allowedTransitions('delivered'));
    }
}
