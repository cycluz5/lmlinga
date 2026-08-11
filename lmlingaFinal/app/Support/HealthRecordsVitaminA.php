<?php

namespace App\Support;

/**
 * Barangay-wide Vitamin A monitoring summary structure (Health Records → Child Care).
 *
 * Age groups follow the Vitamin A Figma frame (6–11, 12–59, 60–71 months).
 *
 * UI-PHASE ONLY: {@see monitoringRows()} returns Figma screenshot preview/demo
 * display values so the table can be visually compared. These are NOT
 * authoritative production aggregates, are NOT persisted, and must NOT be
 * treated as seed data or business-rule confirmation.
 *
 * Percentage cells are literal Figma display strings (e.g. "91%").
 * Do NOT derive percentages from dose/target ratios until an approved
 * accomplishment formula exists.
 */
final class HealthRecordsVitaminA
{
    public const EMPTY_CELL = '';

    /**
     * Figma / DOH-aligned display age groups for this monitoring table.
     * Note: Child Care summary population scope remains 0–59 months
     * ({@see HealthRecordsChildCare::MAX_AGE_MONTHS}); Vitamin A monitoring
     * extends through 60–71 months per the Figma target header (6mos to 6yrs).
     *
     * @return list<array{key: string, label: string, min_months: int, max_months: int}>
     */
    public static function ageGroups(): array
    {
        return [
            [
                'key' => '6-11',
                'label' => '6 – 11 mos. old',
                'min_months' => 6,
                'max_months' => 11,
            ],
            [
                'key' => '12-59',
                'label' => '12 – 59 mos. old',
                'min_months' => 12,
                'max_months' => 59,
            ],
            [
                'key' => '60-71',
                'label' => '60 – 71 mos. old',
                'min_months' => 60,
                'max_months' => 71,
            ],
        ];
    }

    /**
     * UI-phase monitoring rows with Figma preview/demo display values.
     *
     * Empty string = intentionally blank (matches Figma). Total row metrics
     * stay empty — do not invent or sum totals.
     *
     * @return list<array<string, mixed>>
     */
    public static function monitoringRows(): array
    {
        return [
            // Figma preview/demo values only — not production aggregates.
            self::metricRow('6-11', '6 – 11 mos. old', false, [
                'target' => '32',
                'va_100k_male' => '15',
                'va_100k_female' => '14',
                'va_100k_total' => '29',
                // 200k IU intentionally empty in Figma for this age band.
                'percentage' => '91%', // literal Figma display — do not derive
            ]),
            self::metricRow('12-59', '12 – 59 mos. old', false, [
                'target' => '248',
                // 100k IU intentionally empty in Figma for this age band.
                'va_200k_male' => '122',
                'va_200k_female' => '128',
                'va_200k_total' => '250',
                'percentage' => '89%', // literal Figma display — do not derive
            ]),
            self::metricRow('60-71', '60 – 71 mos. old', false, [
                'target' => '70',
                // 100k IU intentionally empty in Figma for this age band.
                'va_200k_male' => '40',
                'va_200k_female' => '33',
                'va_200k_total' => '73',
                'percentage' => '90%', // literal Figma display — do not derive
            ]),
            // Total label only — Figma shows no authoritative total figures.
            self::metricRow('total', 'Total', true, []),
        ];
    }

    /**
     * Zones available for the Vitamin A filter (household demo catalog).
     *
     * @return list<string>
     */
    public static function zones(): array
    {
        $zones = [];

        foreach (DemoCatalog::households() as $household) {
            $zone = trim((string) ($household['zone'] ?? ''));
            if ($zone !== '') {
                $zones[$zone] = true;
            }
        }

        $list = array_keys($zones);
        sort($list, SORT_NATURAL | SORT_FLAG_CASE);

        return $list;
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, mixed>
     */
    private static function metricRow(string $key, string $label, bool $isTotal, array $overrides): array
    {
        return array_merge([
            'key' => $key,
            'label' => $label,
            'is_total' => $isTotal,
            'target' => self::EMPTY_CELL,
            'va_100k_male' => self::EMPTY_CELL,
            'va_100k_female' => self::EMPTY_CELL,
            'va_100k_total' => self::EMPTY_CELL,
            'va_200k_male' => self::EMPTY_CELL,
            'va_200k_female' => self::EMPTY_CELL,
            'va_200k_total' => self::EMPTY_CELL,
            'percentage' => self::EMPTY_CELL,
        ], $overrides);
    }
}
