<?php

namespace App\Services;

class InvoiceTotalsCalculator
{
    /**
     * @param array<int, array{quantity: float, rate: float, tax_percent: float}> $lineItems
     * @param string $taxType 'cgst_sgst' or 'igst'
     */
    public function calculate(array $lineItems, string $taxType = 'cgst_sgst'): array
    {
        $subtotal = 0.0;
        $taxAmount = 0.0;
        $calculatedItems = [];

        foreach ($lineItems as $item) {
            $amount = round($item['quantity'] * $item['rate'], 2);
            $itemTax = round($amount * ($item['tax_percent'] / 100), 2);

            $subtotal += $amount;
            $taxAmount += $itemTax;

            $calculatedItems[] = [
                ...$item,
                'amount' => $amount,
                'tax_amount' => $itemTax,
            ];
        }

        $subtotal = round($subtotal, 2);
        $taxAmount = round($taxAmount, 2);
        $totalAmount = round($subtotal + $taxAmount, 2);

        // Effective overall GST rate, derived from the actual tax collected,
        // used only to label the CGST/SGST/IGST split on the invoice.
        $effectiveRate = $subtotal > 0 ? round(($taxAmount / $subtotal) * 100, 2) : 0.0;

        $cgstRate = 0.0;
        $cgstAmount = 0.0;
        $sgstRate = 0.0;
        $sgstAmount = 0.0;
        $igstRate = 0.0;
        $igstAmount = 0.0;

        if ($taxType === 'igst') {
            $igstRate = $effectiveRate;
            $igstAmount = $taxAmount;
        } else {
            // cgst_sgst: split the rate and amount evenly
            $cgstRate = round($effectiveRate / 2, 2);
            $sgstRate = round($effectiveRate / 2, 2);
            $cgstAmount = round($taxAmount / 2, 2);
            // Give any rounding remainder to SGST so cgst+sgst always equals taxAmount exactly
            $sgstAmount = round($taxAmount - $cgstAmount, 2);
        }

        return [
            'items' => $calculatedItems,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'cgst_rate' => $cgstRate,
            'cgst_amount' => $cgstAmount,
            'sgst_rate' => $sgstRate,
            'sgst_amount' => $sgstAmount,
            'igst_rate' => $igstRate,
            'igst_amount' => $igstAmount,
            'total_amount' => $totalAmount,
            'amount_in_words' => $this->numberToIndianWords($totalAmount),
            'tax_amount_in_words' => $taxAmount > 0 ? $this->numberToIndianWords($taxAmount) : null,
        ];
    }

    public function numberToIndianWords(float $amount): string
    {
        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);

        $words = 'INR ' . $this->convertRupees($rupees) . ' ONLY';

        if ($paise > 0) {
            $words = 'INR ' . $this->convertRupees($rupees) . ' AND ' . $this->convertRupees($paise) . ' PAISE ONLY';
        }

        return $words;
    }

    private function convertRupees(int $number): string
    {
        if ($number === 0) {
            return 'ZERO';
        }

        $ones = ['', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE',
            'TEN', 'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN',
            'SEVENTEEN', 'EIGHTEEN', 'NINETEEN'];
        $tens = ['', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'];

        $convertBelowThousand = function (int $n) use ($ones, $tens): string {
            $result = '';
            if ($n >= 100) {
                $result .= $ones[intdiv($n, 100)] . ' HUNDRED';
                $n %= 100;
                if ($n > 0) $result .= ' ';
            }
            if ($n >= 20) {
                $result .= $tens[intdiv($n, 10)];
                if ($n % 10 > 0) $result .= ' ' . $ones[$n % 10];
            } elseif ($n > 0) {
                $result .= $ones[$n];
            }
            return $result;
        };

        $crore = intdiv($number, 10000000);
        $number %= 10000000;
        $lakh = intdiv($number, 100000);
        $number %= 100000;
        $thousand = intdiv($number, 1000);
        $number %= 1000;
        $hundred = $number;

        $parts = [];
        if ($crore > 0) $parts[] = $convertBelowThousand($crore) . ' CRORE';
        if ($lakh > 0) $parts[] = $convertBelowThousand($lakh) . ' LAKH';
        if ($thousand > 0) $parts[] = $convertBelowThousand($thousand) . ' THOUSAND';
        if ($hundred > 0) {
            if (!empty($parts)) {
                $parts[] = 'AND';
            }
            $parts[] = $convertBelowThousand($hundred);
        }

        return implode(' ', $parts);
    }
}