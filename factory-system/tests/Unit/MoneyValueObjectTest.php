<?php

namespace Tests\Unit;

use App\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyValueObjectTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_money_object_with_amount_and_currency(): void
    {
        $money = Money::of(50_000, 'SYP');

        $this->assertSame(50_000, $money->amount());
        $this->assertSame('SYP', $money->currency());
    }

    /**
     * @test
     */
    public function it_adds_two_money_objects_with_the_same_currency(): void
    {
        $result = Money::of(30_000)->add(Money::of(20_000));

        $this->assertSame(50_000, $result->amount());
    }

    /**
     * @test
     */
    public function it_subtracts_two_money_objects(): void
    {
        $result = Money::of(50_000)->subtract(Money::of(15_000));

        $this->assertSame(35_000, $result->amount());
    }

    /**
     * @test
     */
    public function it_multiplies_amounts_by_an_integer_factor(): void
    {
        $money = Money::of(10_000);

        $this->assertSame(30_000, $money->multiply(3)->amount());
    }

    /**
     * @test
     */
    public function it_calculates_percentages_with_basis_points(): void
    {
        $money = Money::of(10_000);

        $this->assertSame(1_500, $money->percentage(1500)->amount());
    }

    /**
     * @test
     */
    public function it_rejects_arithmetic_between_different_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::of(100, 'SYP')->add(Money::of(100, 'USD'));
    }

    /**
     * @test
     */
    public function it_compares_amounts_with_the_same_currency(): void
    {
        $this->assertTrue(Money::of(200)->isGreaterThan(Money::of(100)));
        $this->assertFalse(Money::of(100)->isGreaterThan(Money::of(200)));
    }

    /**
     * @test
     */
    public function it_detects_zero_amounts(): void
    {
        $this->assertTrue(Money::of(0)->isZero());
        $this->assertFalse(Money::of(1)->isZero());
    }

    /**
     * @test
     */
    public function it_formats_syp_amounts(): void
    {
        $formatted = Money::of(150_000, 'SYP')->format();

        $this->assertStringContainsString('150,000', $formatted);
        $this->assertStringContainsString('ل.س', $formatted);
    }

    /**
     * @test
     */
    public function it_is_immutable(): void
    {
        $original = Money::of(10_000);
        $new = $original->add(Money::of(5_000));

        $this->assertSame(10_000, $original->amount());
        $this->assertSame(15_000, $new->amount());
    }
}
