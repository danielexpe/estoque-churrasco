<?php

declare(strict_types=1);

namespace App\Core;

final class Money
{
    public static function round(string $value, int $scale = 2): string
    {
        $negative = str_starts_with($value, '-');

        if ($negative) {
            $value = substr($value, 1);
        }

        $parts = explode('.', $value, 2);
        $integer = $parts[0] !== '' ? $parts[0] : '0';
        $decimal = str_pad($parts[1] ?? '', $scale + 1, '0');
        $roundDigit = (int) ($decimal[$scale] ?? 0);
        $kept = substr($decimal, 0, $scale);
        $base = $integer . ($scale > 0 ? '.' . $kept : '');

        if ($roundDigit >= 5) {
            $increment = $scale === 0 ? '1' : '0.' . str_repeat('0', $scale - 1) . '1';
            $base = bcadd($base, $increment, $scale);
        }

        return ($negative ? '-' : '') . $base;
    }

    public static function multiply(string $a, string $b, int $scale = 2): string
    {
        return self::round(bcmul($a, $b, 6), $scale);
    }

    public static function add(string $a, string $b, int $scale = 2): string
    {
        return bcadd($a, $b, $scale);
    }

    public static function subtract(string $a, string $b, int $scale = 3): string
    {
        return bcsub($a, $b, $scale);
    }

    public static function compare(string $a, string $b, int $scale = 3): int
    {
        return bccomp($a, $b, $scale);
    }
}
