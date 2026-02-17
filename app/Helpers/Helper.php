<?php

use Carbon\Carbon;
use Illuminate\Support\HtmlString;

if (! function_exists('sanitizedLink')) {
    function sanitizedLink($url)
    {
        return '//' . preg_replace('/^(https?:\/\/)?(www\.)?/', '', $url);
    }
}

if (! function_exists('currencySymbol')) {
    function currencySymbol(): HtmlString
    {
        return new HtmlString('<span class="dirham">D</span>');
    }
}

if (! function_exists('formatPrice')) {
    function formatPrice($price, bool $float = true): HtmlString
    {
        $val = $float
            ? number_format($price, 2, '.', ',')
            : number_format($price, 0, '.', ',');

        return new HtmlString(currencySymbol()->toHtml() . $val);
    }
}
if (! function_exists('formatDateTime')) {
    function formatDateTime($date)
    {
        if (empty($date)) {
            return '-';
        }

        return \Carbon\Carbon::parse($date)->format('M j, Y - g:i A');
    }
}
if (! function_exists('formatDate')) {
    function formatDate($date)
    {
        return Carbon::parse($date)->format('M j, Y');
    }
}
if (! function_exists('formatKey')) {
    function formatKey($value)
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}
if (! function_exists('sanitizeBulletText')) {
    function sanitizeBulletText(string $text)
    {
        // Decode any HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Normalize bullet symbols to a single delimiter
        $text = preg_replace('/[•●▪◦]/u', '||', $text);

        // Normalize line breaks
        $text = preg_replace("/\r\n|\r|\n/", '||', $text);

        // Split into items
        $items = array_filter(array_map('trim', explode('||', $text)));

        // Build HTML list
        $html = '<ul class="tour-terms">';
        foreach ($items as $item) {
            $html .= '<li>' . e($item) . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }
}

function calculatePriceWithCommission($basePrice, $commissionPercentage = 10)
{
    return $basePrice + ($commissionPercentage / 100) * $basePrice;
}


function yalagoFinalPrice(array $board, float $commissionPercent): float
{
    if (!empty($board['IsBindingPrice'])) {
        return round($board['GrossCost']['Amount'], 2);
    }

    $net = $board['NetCost']['Amount'];
    $commission = ($net * $commissionPercent) / 100;

    return round($net + $commission, 2);
}
