<?php

namespace App\Support;

/**
 * Precise money arithmetic using bcmath on decimal strings. All amounts are
 * INR major units with 2 decimal places. Never do money math with PHP floats;
 * always route through this helper so rounding is deterministic.
 */
class Money
{
    public const SCALE = 2;

    public static function of(string|int|float|null $value): string
    {
        return self::normalize((string) ($value ?? '0'));
    }

    public static function normalize(string $value): string
    {
        return bcadd($value === '' ? '0' : $value, '0', self::SCALE);
    }

    public static function add(string $a, string $b): string
    {
        return bcadd(self::of($a), self::of($b), self::SCALE);
    }

    public static function sub(string $a, string $b): string
    {
        return bcsub(self::of($a), self::of($b), self::SCALE);
    }

    public static function mul(string $a, string $b): string
    {
        // Use a higher intermediate scale then round to 2 dp.
        return self::round(bcmul(self::of($a), $b, 6));
    }

    /** Divide an amount, rounding to money scale. Divisor must be non-zero. */
    public static function divide(string $amount, string $divisor): string
    {
        if (bccomp($divisor, '0', self::SCALE) === 0) {
            return '0.00';
        }

        return self::round(bcdiv(self::of($amount), $divisor, 6));
    }

    /** Percentage of an amount, e.g. percent("1000.00", "5") => "50.00". */
    public static function percent(string $amount, string $percent): string
    {
        $raw = bcmul(self::of($amount), $percent, 6);

        return self::round(bcdiv($raw, '100', 6));
    }

    /** Round a higher-scale string to money scale using half-up rounding. */
    public static function round(string $value): string
    {
        if (! str_contains($value, '.')) {
            return self::normalize($value);
        }

        $negative = str_starts_with($value, '-');
        $abs = ltrim($value, '-');
        // Add 0.005 then truncate to 2dp for half-up rounding.
        $rounded = bcadd($abs, '0.005', self::SCALE);
        $result = self::normalize($rounded);

        return ($negative && $result !== '0.00') ? '-'.$result : $result;
    }

    public static function compare(string $a, string $b): int
    {
        return bccomp(self::of($a), self::of($b), self::SCALE);
    }

    public static function isZero(string $a): bool
    {
        return self::compare($a, '0') === 0;
    }

    public static function isNegative(string $a): bool
    {
        return self::compare($a, '0') < 0;
    }

    public static function max(string $a, string $b): string
    {
        return self::compare($a, $b) >= 0 ? self::of($a) : self::of($b);
    }

    public static function min(string $a, string $b): string
    {
        return self::compare($a, $b) <= 0 ? self::of($a) : self::of($b);
    }
}
