<?php

namespace Tests\Unit;

use App\Exceptions\InvalidStatusTransitionException;
use App\StateMachines\ShipmentStateMachine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ShipmentStateMachineTest extends TestCase
{
    private ShipmentStateMachine $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateMachine = new ShipmentStateMachine;
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function validTransitions(): array
    {
        return [
            ['planned', 'loading'],
            ['planned', 'dispatched'],
            ['planned', 'cancelled'],
            ['loading', 'dispatched'],
            ['loading', 'cancelled'],
            ['dispatched', 'completed'],
            ['dispatched', 'cancelled'],
        ];
    }

    #[DataProvider('validTransitions')]
    public function test_it_allows_valid_transitions(string $from, string $to): void
    {
        $this->assertSame($to, $this->stateMachine->transition($from, $to));
    }

    /**
     * @test
     */
    public function test_it_throws_for_illegal_transitions(): void
    {
        $this->expectException(InvalidStatusTransitionException::class);

        $this->stateMachine->transition('completed', 'planned');
    }

    /**
     * @test
     */
    public function test_it_identifies_final_states(): void
    {
        $this->assertTrue($this->stateMachine->isFinal('completed'));
        $this->assertTrue($this->stateMachine->isFinal('cancelled'));
        $this->assertFalse($this->stateMachine->isFinal('planned'));
    }

    /**
     * @test
     */
    public function test_it_returns_allowed_transitions(): void
    {
        $this->assertSame(['loading', 'dispatched', 'cancelled'], $this->stateMachine->allowedTransitions('planned'));
        $this->assertSame([], $this->stateMachine->allowedTransitions('cancelled'));
    }
}
