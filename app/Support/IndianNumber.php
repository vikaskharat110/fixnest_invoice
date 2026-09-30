<?php

namespace App\Support;

class IndianNumber
{
    public static function format(float $amount, int $decimals = 2): string
    {
        $amount = round($amount, $decimals);
        $isNegative = $amount < 0;
        $amount = abs($amount);

        $parts = explode('.', number_format($amount, $decimals, '.', ''));
        $integerPart = $parts[0];
        $decimalPart = $decimals > 0 ? '.' . $parts[1] : '';

        // Last 3 digits stay together; everything before that groups in pairs of 2
        // (the lakh/crore pattern: 1,50,000 not 150,000).
        if (strlen($integerPart) > 3) {
            $lastThree = substr($integerPart, -3);
            $rest = substr($integerPart, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $integerPart = $rest . ',' . $lastThree;
        }

        return ($isNegative ? '-' : '') . $integerPart . $decimalPart;
    }
}