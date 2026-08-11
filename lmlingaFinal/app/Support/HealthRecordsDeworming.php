<?php

namespace App\Support;

/**
 * Barangay-wide Deworming monitoring summary structure (Health Records → Child Care).
 *
 * UI-PHASE ONLY: {@see monitoringRows()}, {@see summaryCards()} return Figma
 * screenshot preview/demo display values so the page can be visually compared.
 * These are NOT authoritative production aggregates, are NOT persisted, and must
 * NOT be treated as seed data or business-rule confirmation.
 *
 * Status percentages are literal Figma display strings (e.g. "84%").
 * Do NOT derive percentages from round counts until an approved formula exists.
 */
final class HealthRecordsDeworming
{
    public const EMPTY_CELL = '';

    /**
     * UI-phase summary card display values from the Deworming Figma frame.
     *
     * @return array{
     *     first_round: string,
     *     second_round: string,
     *     received_1_dose_pct: string,
     *     received_2_dose_pct: string
     * }
     */
    public static function summaryCards(): array
    {
        return [
            // Figma preview/demo values only — not production aggregates.
            'first_round' => '60',
            'second_round' => '0',
            'received_1_dose_pct' => '0%', // literal Figma display — do not derive
            'received_2_dose_pct' => '84%', // literal Figma display — do not derive
        ];
    }

    /**
     * UI-phase child monitoring rows with Figma preview/demo display values.
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
        return [
            self::row('kristine-b-reyes', 'Kristine B. Reyes', '3 yrs old', 'July 1, 2026', 'January 20, 2026', [
                'zone' => $zones[0] ?? $zoneFallback,
                'sex' => 'female',
                'status' => '2-doses',
            ]),
            self::row('jacob-a-magistrado', 'Jacob A. Magistrado', '5 yrs old', 'July 1, 2026', 'January 20, 2026', [
                'zone' => $zones[1] ?? $zoneFallback,
                'sex' => 'male',
                'status' => '2-doses',
            ]),
            self::row('haziel-h-santos', 'Haziel H. Santos', '4 yrs old', 'July 1, 2026', 'January 20, 2026', [
                'zone' => $zones[2] ?? $zoneFallback,
                'sex' => 'female',
                'status' => '2-doses',
            ]),
            self::row('andrei-b-malaya', 'Andrei B. Malaya', '3 yrs old', 'July 1, 2026', 'January 20, 2026', [
                'zone' => $zones[0] ?? $zoneFallback,
                'sex' => 'male',
                'status' => '2-doses',
            ]),
            self::row('crisley-f-fernando', 'Crisley F. Fernando', '3 yrs old', 'July 1, 2026', 'January 20, 2026', [
                'zone' => $zones[1] ?? $zoneFallback,
                'sex' => 'female',
                'status' => '2-doses',
            ]),
            self::row('gabriel-allan-s-chua', 'Gabriel Allan S. Chua', '4 yrs old', 'July 1, 2026', 'January 20, 2026', [
                'zone' => $zones[2] ?? $zoneFallback,
                'sex' => 'male',
                'status' => '2-doses',
            ]),
        ];
    }

    /**
     * Status filter options for the Deworming UI-phase toolbar.
     *
     * @return array<string, string>
     */
    public static function statusFilterOptions(): array
    {
        return [
            'all' => 'Status',
            '1-dose' => 'Received 1 dose/year',
            '2-doses' => 'Received 2 dose/year',
            'none' => 'No dose recorded',
        ];
    }

    /**
     * Zones available for the Deworming filter (household demo catalog).
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
     * @param  array{zone?: string, sex?: string, status?: string}  $meta
     * @return array<string, mixed>
     */
    private static function row(
        string $key,
        string $fullName,
        string $ageLabel,
        string $julyRound,
        string $januaryRound,
        array $meta = []
    ): array {
        return [
            'key' => $key,
            'full_name' => $fullName,
            'age_label' => $ageLabel,
            'july_round' => $julyRound,
            'january_round' => $januaryRound,
            'zone' => (string) ($meta['zone'] ?? ''),
            'sex' => (string) ($meta['sex'] ?? ''),
            'status' => (string) ($meta['status'] ?? 'all'),
        ];
    }
}
