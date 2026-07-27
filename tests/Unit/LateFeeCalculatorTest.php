<?php

namespace Tests\Unit;

use App\Domain\Installments\LateFeeCalculator;
use App\Models\Chitty;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class LateFeeCalculatorTest extends TestCase
{
    private function chitty(string $type, string $value, int $grace = 5): Chitty
    {
        return new Chitty([
            'late_fee_type' => $type,
            'late_fee_value' => $value,
            'grace_period_days' => $grace,
        ]);
    }

    public function test_no_fee_within_grace_period(): void
    {
        $calc = new LateFeeCalculator();
        $due = Carbon::parse('2026-01-10');
        $asOf = Carbon::parse('2026-01-14'); // within 5-day grace

        $this->assertSame('0.00', $calc->calculate($this->chitty('percent', '2'), '5000.00', $due, $asOf));
    }

    public function test_percent_fee_after_grace(): void
    {
        $calc = new LateFeeCalculator();
        $due = Carbon::parse('2026-01-10');
        $asOf = Carbon::parse('2026-01-20'); // past grace

        $this->assertSame('100.00', $calc->calculate($this->chitty('percent', '2'), '5000.00', $due, $asOf));
    }

    public function test_fixed_fee_after_grace(): void
    {
        $calc = new LateFeeCalculator();
        $due = Carbon::parse('2026-01-10');
        $asOf = Carbon::parse('2026-01-20');

        $this->assertSame('250.00', $calc->calculate($this->chitty('fixed', '250'), '5000.00', $due, $asOf));
    }

    public function test_none_type_never_charges(): void
    {
        $calc = new LateFeeCalculator();
        $due = Carbon::parse('2026-01-10');
        $asOf = Carbon::parse('2026-03-01');

        $this->assertSame('0.00', $calc->calculate($this->chitty('none', '0'), '5000.00', $due, $asOf));
    }
}
