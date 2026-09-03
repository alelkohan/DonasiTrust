<?php

namespace App\Support;

class Rupiah
{
    /** 1500000 -> "Rp1.500.000" */
    public static function format(int|float|null $amount, bool $withPrefix = true): string
    {
        $formatted = number_format((int) $amount, 0, ',', '.');

        return $withPrefix ? 'Rp'.$formatted : $formatted;
    }

    /** 1500000 -> "Rp1,5 jt" — untuk kartu ringkas & grafik. */
    public static function compact(int|float|null $amount): string
    {
        $amount = (int) $amount;

        return match (true) {
            $amount >= 1_000_000_000 => 'Rp'.self::trim($amount / 1_000_000_000).' M',
            $amount >= 1_000_000 => 'Rp'.self::trim($amount / 1_000_000).' jt',
            $amount >= 1_000 => 'Rp'.self::trim($amount / 1_000).' rb',
            default => 'Rp'.number_format($amount, 0, ',', '.'),
        };
    }

    /** Ubah input berformat "1.500.000" atau "Rp 1.500.000" jadi integer. */
    public static function parse(mixed $value): int
    {
        return (int) preg_replace('/\D/', '', (string) $value);
    }

    private static function trim(float $number): string
    {
        return rtrim(rtrim(number_format($number, 1, ',', '.'), '0'), ',');
    }
}
