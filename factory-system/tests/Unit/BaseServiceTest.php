<?php

namespace Tests\Unit;

use App\Services\BaseService;
use App\ValueObjects\Money;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BaseServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_exposes_shared_service_primitives(): void
    {
        config(['factory.currency' => 'USD']);

        $service = new ExposedBaseService;

        $this->assertSame('committed', $service->runTransaction(fn () => 'committed'));
        $this->assertSame(125_000, $service->parseIntegerMoney('SYP 125,000'));
        $this->assertSame(-7_500, $service->parseIntegerMoney('-7,500'));
        $this->assertSame('USD', $service->toMoney(100)->currency());
    }

    /**
     * @test
     */
    public function it_rejects_empty_money_strings(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ExposedBaseService)->parseIntegerMoney('   ');
    }
}

class ExposedBaseService extends BaseService
{
    public function runTransaction(Closure $callback): mixed
    {
        return $this->transaction($callback);
    }

    public function parseIntegerMoney(string|int $amount): int
    {
        return $this->parseMoney($amount);
    }

    public function toMoney(int $amount): Money
    {
        return $this->money($amount);
    }
}
