<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_addition_and_subtraction_are_exact(): void
    {
        $this->assertSame('300.35', Money::add('100.10', '200.25'));
        $this->assertSame('99.75', Money::sub('100.00', '0.25'));
        $this->assertSame('0.00', Money::sub('100.00', '100.00'));
    }

    public function test_multiplication_rounds_to_two_places(): void
    {
        $this->assertSame('333.33', Money::mul('100.00', '3.3333'));
    }

    public function test_percent_of_amount(): void
    {
        $this->assertSame('50.00', Money::percent('1000.00', '5'));
        $this->assertSame('20.00', Money::percent('1000.00', '2'));
        $this->assertSame('0.00', Money::percent('1000.00', '0'));
    }

    public function test_comparison_helpers(): void
    {
        $this->assertSame(0, Money::compare('10.00', '10.00'));
        $this->assertSame(1, Money::compare('10.01', '10.00'));
        $this->assertSame(-1, Money::compare('9.99', '10.00'));

        $this->assertTrue(Money::isZero('0.00'));
        $this->assertTrue(Money::isNegative('-1.00'));
        $this->assertFalse(Money::isNegative('0.00'));

        $this->assertSame('10.00', Money::max('10.00', '5.00'));
        $this->assertSame('5.00', Money::min('10.00', '5.00'));
    }

    public function test_normalizes_scalar_inputs(): void
    {
        $this->assertSame('5.00', Money::of(5));
        $this->assertSame('5.50', Money::of('5.5'));
        $this->assertSame('0.00', Money::of(null));
    }
}
