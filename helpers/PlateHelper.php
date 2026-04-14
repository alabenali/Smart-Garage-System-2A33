<?php

declare(strict_types=1);

if (!function_exists('formatPlate')) {
    function normalizePlateString(string $immatriculation): string
    {
        $raw = strtoupper(trim($immatriculation));
        $normalized = preg_replace('/\s+/', '', $raw);
        return str_replace(['-', '.'], '', $normalized);
    }

    function formatTUPlate(string $normalized): string
    {
        $parts = preg_split('/TU/i', $normalized);
        $left = (is_array($parts) && isset($parts[0])) ? trim($parts[0]) : '';
        $right = (is_array($parts) && isset($parts[1])) ? trim($parts[1]) : '';

        return '<span class="tn-plate">'
            . '<span class="tn-plate-left">' . htmlspecialchars($left, ENT_QUOTES, 'UTF-8') . '</span>'
            . '<span class="tn-plate-center">تونس</span>'
            . '<span class="tn-plate-right">' . htmlspecialchars($right, ENT_QUOTES, 'UTF-8') . '</span>'
            . '</span>';
    }

    function formatRSPlate(string $normalized): string
    {
        $parts = preg_split('/RS/i', $normalized);
        $left = (is_array($parts) && isset($parts[0])) ? trim($parts[0]) : '';
        $right = (is_array($parts) && isset($parts[1])) ? trim($parts[1]) : '';

        return '<span class="tn-plate tn-plate-rs" title="Série RS">'
            . '<span class="tn-plate-rs-ar">ن.ت</span>'
            . '<span class="tn-plate-rs-sep"></span>'
            . '<span class="tn-plate-rs-right">' . htmlspecialchars($right, ENT_QUOTES, 'UTF-8') . '</span>'
            . '<span class="tn-plate-rs-left">' . htmlspecialchars($left, ENT_QUOTES, 'UTF-8') . '</span>'
            . '</span>';
    }

    function formatNeutralPlate(string $immatriculation): string
    {
        return '<span class="tn-plate-neutral">'
            . htmlspecialchars(trim($immatriculation), ENT_QUOTES, 'UTF-8')
            . '</span>';
    }

    function formatPlate(string $immatriculation): string
    {
        $normalized = normalizePlateString($immatriculation);

        if (preg_match('/^\d{1,3}TU\d{1,4}$/i', $normalized) === 1) {
            return formatTUPlate($normalized);
        }

        if (preg_match('/^\d{1,3}RS\d{1,4}$/i', $normalized) === 1) {
            return formatRSPlate($normalized);
        }

        return formatNeutralPlate($immatriculation);
    }
}
