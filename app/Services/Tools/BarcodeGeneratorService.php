<?php

namespace App\Services\Tools;

class BarcodeGeneratorService
{
    public function generateEan13(string $baseNumber): string
    {
        $baseNumber = str_pad(substr($baseNumber, 0, 12), 12, '0', STR_PAD_LEFT);

        $digits = str_split($baseNumber);
        $sum = 0;

        foreach ($digits as $index => $digit) {
            $multiplier = ($index % 2 === 0) ? 1 : 3;
            $sum += $digit * $multiplier;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $baseNumber.$checkDigit;
    }

    public function generateEan8(string $baseNumber): string
    {
        $baseNumber = str_pad(substr($baseNumber, 0, 7), 7, '0', STR_PAD_LEFT);

        $digits = str_split($baseNumber);
        $sum = 0;

        foreach ($digits as $index => $digit) {
            $multiplier = ($index % 2 === 0) ? 3 : 1;
            $sum += $digit * $multiplier;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $baseNumber.$checkDigit;
    }

    public function validateEan(string $ean): bool
    {
        $ean = preg_replace('/[^0-9]/', '', $ean);

        if (strlen($ean) !== 13 && strlen($ean) !== 8) {
            return false;
        }

        $digits = str_split($ean);
        $checkDigit = array_pop($digits);

        $sum = 0;
        foreach ($digits as $index => $digit) {
            $multiplier = (strlen($ean) === 13)
                ? (($index % 2 === 0) ? 1 : 3)
                : (($index % 2 === 0) ? 3 : 1);
            $sum += $digit * $multiplier;
        }

        $calculatedCheck = (10 - ($sum % 10)) % 10;

        return $checkDigit === $calculatedCheck;
    }

    public function generateUpc(string $baseNumber): string
    {
        $baseNumber = str_pad(substr($baseNumber, 0, 11), 11, '0', STR_PAD_LEFT);

        $digits = str_split($baseNumber);
        $sum = 0;

        foreach ($digits as $index => $digit) {
            $multiplier = ($index % 2 === 0) ? 3 : 1;
            $sum += $digit * $multiplier;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $baseNumber.$checkDigit;
    }

    public function generateCode128(string $data): string
    {
        return $data;
    }

    public function generateCode128Barcodes(string $data): array
    {
        $barcodes = [
            'CODE128' => $data,
            'CODE128A' => $data,
            'CODE128B' => $data,
            'CODE128C' => preg_replace('/[^0-9]/', '', $data),
        ];

        return $barcodes;
    }
}
