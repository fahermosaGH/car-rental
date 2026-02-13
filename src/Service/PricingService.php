<?php

namespace App\Service;

use App\Entity\Vehicle;

final class PricingService
{
    /**
     * REGLA:
     * - Seguro: % del dailyRate por día * días
     * - Extras: % del BASE (dailyRate*días) * quantity
     *
     * Ajustá estos % como quieras.
     */

    private const INSURANCES = [
        // id del front => label + % por día del dailyRate
        'smart'  => ['label' => 'SMART COVER',     'pct_per_day' => 0.05], // 5%
        'plus'   => ['label' => 'PLUS COVER',      'pct_per_day' => 0.08], // 8%
        'tyres'  => ['label' => 'CUBIERTAS COVER', 'pct_per_day' => 0.03], // 3% (ejemplo)
    ];

    // Extras: % del BASE (dailyRate*días). Si querés 3% fijo: 0.03
    private const EXTRAS_PCT_OF_BASE = 0.03;

    public function days(\DateTimeInterface $startAt, \DateTimeInterface $endAt): int
    {
        $start = (new \DateTimeImmutable($startAt->format('c')))->setTime(0, 0, 0);
        $end   = (new \DateTimeImmutable($endAt->format('c')))->setTime(0, 0, 0);

        $diffSeconds = $end->getTimestamp() - $start->getTimestamp();
        $days = (int) ceil($diffSeconds / 86400);

        return max(1, $days);
    }

    public function dailyRateOf(Vehicle $vehicle): float
    {
        $raw = $vehicle->getDailyPriceOverride();
        return $raw !== null ? (float) $raw : 0.0;
    }

    /**
     * pricingPayload esperado:
     * [
     *   'insuranceCode' => 'smart'|'plus'|'tyres'|null,
     *   'extras' => [
     *      ['code' => 'booster', 'quantity' => 1, 'billing' => 'per_day'|'per_reservation', 'price' => 2800], // price lo ignoramos
     *   ]
     * ]
     */
    public function compute(Vehicle $vehicle, \DateTimeInterface $startAt, \DateTimeInterface $endAt, array $pricingPayload): array
    {
        $days = $this->days($startAt, $endAt);
        $dailyRate = $this->dailyRateOf($vehicle);

        if ($dailyRate <= 0) {
            return [
                'ok' => false,
                'error' => 'El vehículo no tiene tarifa diaria configurada (dailyRate).',
                'code' => 'DAILY_RATE_MISSING',
            ];
        }

        $insuranceCode = $pricingPayload['insuranceCode'] ?? null;
        if (!$insuranceCode || !isset(self::INSURANCES[$insuranceCode])) {
            return [
                'ok' => false,
                'error' => 'Debés seleccionar un seguro válido.',
                'code' => 'INSURANCE_REQUIRED',
            ];
        }

        $base = $dailyRate * $days;

        // Seguro = % por día del dailyRate * días
        $insCfg = self::INSURANCES[$insuranceCode];
        $insuranceAmount = ($dailyRate * (float) $insCfg['pct_per_day']) * $days;

        // Extras = % del BASE * quantity
        $extrasLines = [];
        $extrasTotal = 0.0;

        $extras = $pricingPayload['extras'] ?? [];
        if (is_array($extras)) {
            foreach ($extras as $x) {
                if (!is_array($x)) continue;

                $code = isset($x['code']) ? (string)$x['code'] : '';
                $qty  = isset($x['quantity']) ? (int)$x['quantity'] : 0;

                if ($code === '' || $qty <= 0) continue;

                $amount = ($base * self::EXTRAS_PCT_OF_BASE) * $qty;

                // label “linda” (podés mapear si querés). Por ahora mostramos el code.
                $label = $this->prettyExtraLabel($code);

                $extrasLines[] = [
                    'name'  => $label,
                    'price' => number_format($amount, 2, '.', ''),
                ];
                $extrasTotal += $amount;
            }
        }

        $insuranceLine = [
            'name'  => $insCfg['label'],
            'price' => number_format($insuranceAmount, 2, '.', ''),
        ];

        $total = $base + $insuranceAmount + $extrasTotal;

        return [
            'ok' => true,
            'days' => $days,
            'dailyRate' => $dailyRate,
            'base' => number_format($base, 2, '.', ''),
            'insurance' => $insuranceLine,
            'extras' => $extrasLines,
            'total' => number_format($total, 2, '.', ''),
        ];
    }

    private function prettyExtraLabel(string $code): string
    {
        return match ($code) {
            'booster' => 'Booster (4–10 años)',
            'young_driver' => 'Conductor joven',
            'additional_driver' => 'Conductor adicional',
            'baby_seat' => 'Silla de bebé (1–3 años)',
            'border_cross' => 'Cruce de frontera',
            default => $code,
        };
    }
}