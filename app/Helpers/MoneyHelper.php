<?php

namespace App\Helpers;

class MoneyHelper
{
    /**
     * Format số tiền theo currency.
     * - VND, JPY, KRW: integer
     * - USD, EUR, ...: giữ tối đa 2 số thập phân
     */
    public static function formatCurrency($amount, $currency)
    {
        $currency = strtoupper($currency);
        $noDecimalCurrencies = ['VND', 'JPY', 'KRW'];

        if (in_array($currency, $noDecimalCurrencies)) {
            return (int) round($amount);
        }
        // Giữ tối đa 2 số thập phân, dùng dấu . (chuẩn JSON)
        return number_format(round($amount, 2), 2, '.', '');
    }
}
