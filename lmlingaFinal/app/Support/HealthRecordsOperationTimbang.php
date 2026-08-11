<?php

namespace App\Support;

/**
 * Barangay-wide Operation Timbang monitoring summary (Health Records → Child Care).
 *
 * UI-PHASE ONLY: {@see monitoringRows()}, {@see summaryCards()} return Figma
 * screenshot preview/demo display values so the page can be visually compared.
 * These are NOT authoritative production aggregates, are NOT persisted, and must
 * NOT be treated as seed data or clinical-classification confirmation.
 *
 * Status labels (Below Normal / Normal / Above Normal) are literal Figma display
 * strings. Do NOT derive nutritional status from weight/height/MUAC until an
 * approved clinical algorithm exists.
 */
final class HealthRecordsOperationTimbang
{
    public const DEFAULT_YEAR = 2026;

    public const DEFAULT_MONTH = 1;

    /**
     * UI-phase summary metric display values from the Operation Timbang Figma frame.
     *
     * @return array{
     *     ps_0_23: string,
     *     measured_0_23: string,
     *     over_age: string,
     *     transferred: string,
     *     dead: string,
     *     not_available: string,
     *     new_cases: string,
     *     total_male: string,
     *     total_female: string
     * }
     */
    public static function summaryCards(): array
    {
        return [
            // Figma preview/demo values only — not production aggregates.
            'ps_0_23' => '33',
            'measured_0_23' => '33',
            'over_age' => '0',
            'transferred' => '0',
            'dead' => '0',
            'not_available' => '0',
            'new_cases' => '0',
            'total_male' => '17',
            'total_female' => '16',
        ];
    }

    /**
     * Years available in the UI-phase year selector.
     *
     * @return list<int>
     */
    public static function years(): array
    {
        return [2025, 2026, 2027];
    }

    /**
     * Month session labels for a given year (full calendar year — not limited to
     * the eight months visible in the Figma crop).
     *
     * @return list<array{month: int, year: int, key: string, label: string}>
     */
    public static function monthSessions(int $year = self::DEFAULT_YEAR): array
    {
        $sessions = [];

        for ($month = 1; $month <= 12; $month++) {
            $sessions[] = [
                'month' => $month,
                'year' => $year,
                'key' => sprintf('%04d-%02d', $year, $month),
                'label' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
            ];
        }

        return $sessions;
    }

    /**
     * UI-phase child weigh-in rows with Figma preview/demo display values.
     *
     * Filter metadata (zone / sex / status) is attached only so client-side
     * preview filtering can operate on displayed rows. It is not authoritative.
     *
     * @return list<array<string, mixed>>
     */
    public static function monitoringRows(): array
    {
        $zones = self::zones();
        $zoneFallback = $zones[0] ?? 'Zone 1';

        // Figma preview/demo rows only — not household catalog members.
        // Varied measurements from the primary Operation Timbang Figma reference.
        return [
            self::row('kristine-b-reyes', 'Kristine B. Reyes', '2 months', '3.4 kg', '43.5 cm', '14.5', 'below-normal', [
                'zone' => $zones[0] ?? $zoneFallback,
                'sex' => 'female',
            ]),
            self::row('jacob-a-magistrado', 'Jacob A. Magistrado', '18 months', '12.3 kg', '86.7 cm', '14.5', 'normal', [
                'zone' => $zones[1] ?? $zoneFallback,
                'sex' => 'male',
            ]),
            self::row('haziel-h-santos', 'Haziel H. Santos', '5 months', '8.6 kg', '68 cm', '15.5', 'above-normal', [
                'zone' => $zones[2] ?? $zoneFallback,
                'sex' => 'female',
            ]),
            self::row('andrei-b-malaya', 'Andrei B. Malaya', '7 months', '7.2 kg', '65 cm', '13.0', 'below-normal', [
                'zone' => $zones[0] ?? $zoneFallback,
                'sex' => 'male',
            ]),
            self::row('crisley-f-fernando', 'Crisley F. Fernando', '12 months', '9.5 kg', '75 cm', '14.0', 'normal', [
                'zone' => $zones[1] ?? $zoneFallback,
                'sex' => 'female',
            ]),
            self::row('gabriel-allan-s-chua', 'Gabriel Allan S. Chua', '3 months', '5.1 kg', '58 cm', '15.0', 'above-normal', [
                'zone' => $zones[2] ?? $zoneFallback,
                'sex' => 'male',
            ]),
        ];
    }

    /**
     * Status filter options for the Operation Timbang UI-phase toolbar.
     *
     * @return array<string, string>
     */
    public static function statusFilterOptions(): array
    {
        return [
            'all' => 'Status',
            'below-normal' => 'Below Normal',
            'normal' => 'Normal',
            'above-normal' => 'Above Normal',
        ];
    }

    /**
     * Human-readable status label for a status key.
     */
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'below-normal' => 'Below Normal',
            'above-normal' => 'Above Normal',
            default => 'Normal',
        };
    }

    /**
     * Zones available for the Operation Timbang filter (household demo catalog).
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
     * @param  array{zone?: string, sex?: string}  $meta
     * @return array<string, mixed>
     */
    private static function row(
        string $key,
        string $fullName,
        string $ageLabel,
        string $weight,
        string $height,
        string $muac,
        string $status,
        array $meta = []
    ): array {
        return [
            'key' => $key,
            'full_name' => $fullName,
            'age_label' => $ageLabel,
            'weight' => $weight,
            'height' => $height,
            'muac' => $muac,
            'status' => $status,
            'status_label' => self::statusLabel($status),
            'zone' => (string) ($meta['zone'] ?? ''),
            'sex' => (string) ($meta['sex'] ?? ''),
        ];
    }
}
