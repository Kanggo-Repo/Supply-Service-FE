<?php

namespace App\Helpers;

class NumberHelper
{
    private const DEFAULT_DECIMALS = 11;
    private const RESULT_DECIMALS = 11;
    private const FIXED_DECIMALS = 2;

    private static function resolveDecimals(?int $decimals, int $fallback): int
    {
        return max(0, $decimals ?? $fallback);
    }

    private static function formatNumber(
        ?float $number,
        int $decimals,
        string $decimalSeparator,
        string $thousandsSeparator,
    ): string {
        if ($number === null || ! is_finite($number)) {
            return '0';
        }

        $decimals = max(0, $decimals);
        $formatted = number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);

        if ($decimals === 0) {
            return $formatted;
        }

        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, $decimalSeparator);

        return $formatted;
    }

    public static function format(
        mixed $number,
        ?int $decimals = null,
        string $decimalSeparator = ',',
        string $thousandsSeparator = '.',
    ): string {
        $decimals = self::resolveDecimals($decimals, self::DEFAULT_DECIMALS);

        return self::formatPlain($number, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    public static function formatResult(
        mixed $number,
        ?int $decimals = null,
        string $decimalSeparator = ',',
        string $thousandsSeparator = '.',
    ): string {
        $decimals = self::resolveDecimals($decimals, self::RESULT_DECIMALS);

        return self::formatPlain($number, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    public static function formatFixed(
        mixed $number,
        ?int $decimals = null,
        string $decimalSeparator = ',',
        string $thousandsSeparator = '.',
    ): string {
        $decimals = self::resolveDecimals($decimals, self::FIXED_DECIMALS);
        $parsed = self::parseNullable($number);

        return self::formatNumber($parsed, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    public static function formatPlain(
        mixed $number,
        int $maxDecimals = 11,
        string $decimalSeparator = ',',
        string $thousandsSeparator = '.',
    ): string {
        $parsed = self::parseNullable($number);
        if ($parsed === null || ! is_finite($parsed)) {
            return '0';
        }

        $maxDecimals = max(0, $maxDecimals);
        $base = $parsed;
        $bestDecimals = $maxDecimals;
        $bestValue = round($base, $maxDecimals);
        $tolerance = max(abs($base) * 1e-12, 1e-12);

        for ($d = $maxDecimals - 1; $d >= 0; $d--) {
            $candidate = round($base, $d);
            if (abs($candidate - $base) <= $tolerance) {
                $bestDecimals = $d;
                $bestValue = $candidate;

                continue;
            }

            break;
        }

        $formatted = number_format($bestValue, $bestDecimals, '.', '');
        if ($bestDecimals > 0) {
            $formatted = rtrim($formatted, '0');
            $formatted = rtrim($formatted, '.');
        }

        if ($formatted === '' || $formatted === '-0') {
            $formatted = '0';
        }

        $sign = '';
        if (str_starts_with($formatted, '-')) {
            $sign = '-';
            $formatted = substr($formatted, 1);
        }

        $parts = explode('.', $formatted, 2);
        $intPart = $parts[0] ?? '0';
        $decPart = $parts[1] ?? '';

        if ($thousandsSeparator !== '') {
            $intPart = preg_replace('/\B(?=(\d{3})+(?!\d))/', $thousandsSeparator, $intPart);
        }

        $formatted = $sign.$intPart;
        if ($decPart !== '') {
            $formatted .= $decimalSeparator.$decPart;
        }

        return $formatted;
    }

    public static function normalize(?float $number, ?int $decimals = null): float
    {
        if ($number === null || ! is_finite($number)) {
            return 0.0;
        }

        if ($decimals === null) {
            return (float) $number;
        }

        return (float) round($number, max(0, $decimals));
    }

    public static function truncate(mixed $number, ?int $decimals = null): float
    {
        $parsed = self::parseNullable($number);
        if ($parsed === null || ! is_finite($parsed)) {
            return 0.0;
        }

        $decimals = max(0, $decimals ?? self::FIXED_DECIMALS);
        if ($decimals === 0) {
            return $parsed < 0 ? (float) ceil($parsed) : (float) floor($parsed);
        }

        $factor = 10 ** $decimals;
        $adjusted = $parsed < 0
            ? ceil(($parsed - PHP_FLOAT_EPSILON) * $factor)
            : floor(($parsed + PHP_FLOAT_EPSILON) * $factor);

        return $adjusted / $factor;
    }

    public static function formatTruncated(
        mixed $number,
        ?int $decimals = null,
        string $decimalSeparator = ',',
        string $thousandsSeparator = '.',
    ): string {
        $decimals = self::resolveDecimals($decimals, self::FIXED_DECIMALS);

        return self::formatNumber(
            self::truncate($number, $decimals),
            $decimals,
            $decimalSeparator,
            $thousandsSeparator,
        );
    }

    public static function currency(mixed $number): string
    {
        if ($number === null || $number === '') {
            return 'Rp 0';
        }

        return 'Rp '.self::formatFixed($number, 0, ',', '.');
    }

    public static function weight(mixed $number): string
    {
        if ($number === null || $number === '') {
            return '0 Kg';
        }

        return self::format($number, self::DEFAULT_DECIMALS, ',', '.').' Kg';
    }

    public static function volume(mixed $number): string
    {
        if ($number === null || $number === '') {
            return '0 M3';
        }

        return self::format($number, self::DEFAULT_DECIMALS, ',', '.').' M3';
    }

    public static function parse(mixed $value): float
    {
        return self::parseNullable($value) ?? 0.0;
    }

    public static function parseNullable(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }

        $string = str_replace(['Rp', 'rp', ' ', "\xc2\xa0"], '', $string);

        $negative = false;
        if (str_starts_with($string, '-')) {
            $negative = true;
            $string = substr($string, 1);
        }

        $hasComma = str_contains($string, ',');
        $hasDot = str_contains($string, '.');

        if ($hasComma && $hasDot) {
            $lastComma = strrpos($string, ',');
            $lastDot = strrpos($string, '.');

            if ($lastComma > $lastDot) {
                $string = str_replace('.', '', $string);
                $string = str_replace(',', '.', $string);
            } else {
                $string = str_replace(',', '', $string);
            }
        } elseif ($hasComma) {
            if (preg_match('/^\d{1,3}(,\d{3})+$/', $string)) {
                $string = str_replace(',', '', $string);
            } else {
                $string = str_replace(',', '.', $string);
            }
        } elseif ($hasDot) {
            if (
                ! str_starts_with($string, '0.')
                && ! str_starts_with($string, '-0.')
                && preg_match('/^\d{1,3}(\.\d{3})+$/', $string)
            ) {
                $string = str_replace('.', '', $string);
            }
        }

        $string = preg_replace('/[^0-9.]/', '', $string);

        if ($string === '' || $string === '.') {
            return null;
        }

        if (! is_numeric($string)) {
            return null;
        }

        $result = (float) $string;

        return $negative ? -$result : $result;
    }
}
