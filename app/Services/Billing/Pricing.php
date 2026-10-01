<?php

namespace App\Services\Billing;

use App\Enums\BillingPeriod;
use App\Enums\PlacementUnit;
use App\Models\Category;
use App\Models\City;
use App\Models\Neighborhood;
use App\Models\PlacementRate;
use App\Models\Plan;

/**
 * Every price comes from the database (plans and placement rates edited in
 * the admin). Nothing the browser sends is ever used as an amount.
 */
class Pricing
{
    public function plan(Plan $plan, BillingPeriod $period): string
    {
        return number_format((float) ($period === BillingPeriod::Yearly ? $plan->price_yearly : $plan->price_monthly), 2, '.', '');
    }

    /**
     * Null when no active rate exists for this slot (not for sale).
     */
    public function placement(City $city, Category $category, ?Neighborhood $neighborhood, int $position, PlacementUnit $unit, int $units): ?string
    {
        $rate = $this->rate($city, $category, $position);

        if ($rate === null || $units < 1) {
            return null;
        }

        $price = (float) ($unit === PlacementUnit::Month ? $rate->price_month : $rate->price_week) * $units;

        if ($neighborhood !== null) {
            $price = $price * $rate->neighborhood_percent / 100;
        }

        return number_format(round($price, 2), 2, '.', '');
    }

    /**
     * Most specific active rate: (city, category) > (city, *) > (*, category) > (*, *).
     */
    public function rate(City $city, Category $category, int $position): ?PlacementRate
    {
        return PlacementRate::query()
            ->where('position', $position)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('city_id')->orWhere('city_id', $city->id))
            ->where(fn ($q) => $q->whereNull('category_id')->orWhere('category_id', $category->id))
            ->get()
            ->sortByDesc(fn (PlacementRate $rate) => ($rate->city_id !== null ? 2 : 0) + ($rate->category_id !== null ? 1 : 0))
            ->first();
    }

    /**
     * VAT split of an amount, per config('billing.invoice').
     *
     * @return array{subtotal: string, tax_rate: string, tax_amount: string, total: string}
     */
    public function vat(string $amount): array
    {
        $rate = (float) config('billing.invoice.vat_rate');
        $amount = (float) $amount;

        if (config('billing.invoice.prices_include_vat')) {
            $total = $amount;
            $subtotal = round($total / (1 + $rate / 100), 2);
        } else {
            $subtotal = $amount;
            $total = round($subtotal * (1 + $rate / 100), 2);
        }

        $format = fn (float $value) => number_format($value, 2, '.', '');

        return [
            'subtotal' => $format($subtotal),
            'tax_rate' => $format($rate),
            'tax_amount' => $format($total - $subtotal),
            'total' => $format($total),
        ];
    }
}
