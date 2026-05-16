<?php

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable money value object backed by integer storage units.
 */
final class Money
{
    /**
     * Create a money object.
     *
     * @param  int  $amount  Amount in the smallest currency unit.
     * @param  string  $currency  ISO-style currency code.
     */
    public function __construct(
        private readonly int $amount,
        private readonly string $currency = 'SYP'
    ) {}

    /**
     * Create a money object from an integer amount.
     *
     * @param  int  $amount  Amount in the smallest currency unit.
     * @param  string  $currency  ISO-style currency code.
     */
    public static function of(int $amount, string $currency = 'SYP'): self
    {
        return new self($amount, $currency);
    }

    /**
     * Get the integer amount.
     */
    public function amount(): int
    {
        return $this->amount;
    }

    /**
     * Get the currency code.
     */
    public function currency(): string
    {
        return $this->currency;
    }

    /**
     * Add another money object with the same currency.
     *
     * @param  self  $other  Money object to add.
     *
     * @throws InvalidArgumentException
     */
    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    /**
     * Subtract another money object with the same currency.
     *
     * @param  self  $other  Money object to subtract.
     *
     * @throws InvalidArgumentException
     */
    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    /**
     * Multiply the amount by a numeric factor.
     *
     * @param  int|float  $factor  Multiplication factor.
     */
    public function multiply(int|float $factor): self
    {
        return new self((int) round($this->amount * $factor), $this->currency);
    }

    /**
     * Determine whether this amount is greater than another amount.
     *
     * @param  self  $other  Money object to compare.
     *
     * @throws InvalidArgumentException
     */
    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount > $other->amount;
    }

    /**
     * Determine whether this amount is zero.
     */
    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    /**
     * Format the amount for display.
     */
    public function format(): string
    {
        $symbol = $this->currency === 'SYP' ? 'ل.س' : $this->currency;

        return number_format($this->amount).' '.$symbol;
    }

    /**
     * Ensure currency matches before arithmetic or comparisons.
     *
     * @param  self  $other  Money object to validate.
     *
     * @throws InvalidArgumentException
     */
    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}"
            );
        }
    }
}
