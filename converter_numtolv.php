<?php

/**
 * Converts numeric values into Latvian language words representation.
 *
 * Supports integers and floats (treated as currency), negative values, 
 * and numbers up to 10^12 (triljons). For currency values, outputs 
 * "eiro" and "centi" denominations.
 *
 * @license MIT
 * @author 5 Reasons
 * @link https://github.com/5-reasons/transcriptor-sum-to-words
 */
class NumberToLatvianWordsConverter
{
    private const BASE_NUMBERS = [
        0 => 'nulle',
        1 => 'viens',
        2 => 'divi',
        3 => 'trīs',
        4 => 'četri',
        5 => 'pieci',
        6 => 'seši',
        7 => 'septiņi',
        8 => 'astoņi',
        9 => 'deviņi',
        10 => 'desmit',
        11 => 'vienpadsmit',
        12 => 'divpadsmit',
        13 => 'trīspadsmit',
        14 => 'četrpadsmit',
        15 => 'piecpadsmit',
        16 => 'sešpadsmit',
        17 => 'septiņpadsmit',
        18 => 'astoņpadsmit',
        19 => 'deviņpadsmit',
        20 => 'divdesmit',
        30 => 'trīsdesmit',
        40 => 'četrdesmit',
        50 => 'piecdesmit',
        60 => 'sešdesmit',
        70 => 'septiņdesmit',
        80 => 'astoņdesmit',
        90 => 'deviņdesmit',
    ];

    private const LARGE_NUMBERS = [
        100 => 'simts',
        1000 => 'tūkstotis',
        1_000_000 => 'miljons',
        1_000_000_000 => 'miljards',
        1_000_000_000_000 => 'triljons',
    ];

    private const CURRENCY_MAIN = 'eiro';
    private const CURRENCY_SUB = 'centi';
    private const CURRENCY_CONNECTOR = 'un';

    /**
     * Converts numeric value to Latvian words representation.
     *
     * @param int|float $number Number to convert
     * @return string Latvian words representation
     * @throws InvalidArgumentException For non-numeric values
     * @throws RangeException For numbers exceeding PHP_INT_MAX
     */
    public function convert($number): string
    {
        $this->validateInput($number);

        if ($this->isCurrencyValue($number)) {
            return $this->handleCurrencyConversion($number);
        }

        return $this->convertInteger((int) $number);
    }

    /**
     * Validates input parameters.
     */
    private function validateInput($number): void
    {
        if (!is_numeric($number)) {
            throw new InvalidArgumentException('Input must be numeric');
        }

        if ($number > PHP_INT_MAX || $number < -PHP_INT_MAX) {
            throw new RangeException('Number exceeds PHP integer limits');
        }
    }

    /**
     * Handles currency conversion with decimal handling.
     */
    private function handleCurrencyConversion(float $number): string
    {
        $parts = explode('.', (string) abs($number));
        $integerPart = (int) $parts[0];
        $decimalPart = (int) ($parts[1] ?? 0);

        $result = $this->convertInteger($integerPart) . ' ' . self::CURRENCY_MAIN;
        
        if ($decimalPart > 0) {
            $result .= ' ' . self::CURRENCY_CONNECTOR . ' ' 
                . $this->convertInteger($decimalPart) . ' ' . self::CURRENCY_SUB;
        }

        return $number < 0 ? 'mīnus ' . $result : $result;
    }

    /**
     * Main conversion logic for integer values.
     */
    private function convertInteger(int $number): string
    {
        if ($number < 0) {
            return 'mīnus ' . $this->convertInteger(abs($number));
        }

        if ($this->isDirectMappedNumber($number)) {
            return $this->getDirectMappedNumber($number);
        }

        foreach (array_reverse(self::LARGE_NUMBERS, true) as $value => $name) {
            if ($number >= $value) {
                return $this->handleLargeNumber($number, $value, $name);
            }
        }

        return $this->convertCompoundNumber($number);
    }

    /**
     * Handles numbers that can be directly mapped from dictionaries.
     */
    private function isDirectMappedNumber(int $number): bool
    {
        return isset(self::BASE_NUMBERS[$number]) 
            || isset(self::LARGE_NUMBERS[$number]);
    }

    /**
     * Returns directly mapped number from dictionaries.
     */
    private function getDirectMappedNumber(int $number): string
    {
        return self::BASE_NUMBERS[$number] ?? self::LARGE_NUMBERS[$number];
    }

    /**
     * Handles large numbers with pluralization rules.
     */
    private function handleLargeNumber(int $number, int $divisor, string $name): string
    {
        $quotient = (int) ($number / $divisor);
        $remainder = $number % $divisor;

        $converted = $this->convertLargeNumberPart($quotient, $divisor, $name);
        
        if ($remainder > 0) {
            $converted .= ' ' . $this->convertInteger($remainder);
        }

        return $converted;
    }

    /**
     * Converts the large number part with proper pluralization.
     */
    private function convertLargeNumberPart(int $quotient, int $divisor, string $name): string
    {
        $base = $this->convertInteger($quotient) . ' ';

        if ($quotient === 1) {
            return $base . $name;
        }

        return $base . $this->pluralizeLargeNumber($name);
    }

    /**
     * Applies Latvian pluralization rules to large numbers.
     */
    private function pluralizeLargeNumber(string $name): string
    {
        return match($name) {
            'simts' => 'simti',
            'tūkstotis' => 'tūkstoši',
            'miljons' => 'miljoni',
            'miljards' => 'miljardi',
            'triljons' => 'triljoni',
            default => $name,
        };
    }

    /**
     * Handles compound numbers between 21-99 and 101-999.
     */
    private function convertCompoundNumber(int $number): string
    {
        if ($number < 100) {
            $tens = (int) ($number / 10) * 10;
            $units = $number % 10;
            return self::BASE_NUMBERS[$tens] . ' ' . self::BASE_NUMBERS[$units];
        }

        // Handle hundreds (100-999)
        $hundreds = (int) ($number / 100);
        $remainder = $number % 100;
        
        $result = $hundreds === 1 
            ? 'simts' 
            : self::BASE_NUMBERS[$hundreds] . ' simti';

        if ($remainder > 0) {
            $result .= ' ' . $this->convertInteger($remainder);
        }

        return $result;
    }

    /**
     * Determines if the input should be treated as currency value.
     */
    private function isCurrencyValue($number): bool
    {
        return is_float($number) && floor($number) != $number;
    }
}
