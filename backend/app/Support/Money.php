<?php

namespace App\Support;

final class Money
{
    public static function dollarsToCents(string|float|int $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function centsToDollars(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    public static function format(int $amount): string
    {
        return '$' . self::centsToDollars($amount);
    }
}
