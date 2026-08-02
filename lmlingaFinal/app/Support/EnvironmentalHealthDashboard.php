<?php

namespace App\Support;

/**
 * Aggregates Household Amenities (ESOHP) records for the Environmental Health
 * monitoring dashboard. Statistics are computed once from resolved rows —
 * never inside Blade loops.
 */
final class EnvironmentalHealthDashboard
{
    public const RECORD_STATUS_COMPLETED = 'completed';

    public const RECORD_STATUS_PENDING = 'pending';

    /**
     * Build dashboard rows for every household that has (or can preview)
     * Household Amenities data for the current actor.
     *
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public static function rows(array $filters = []): array
    {
        $rows = [];

        foreach (self::candidateHouseholdNos() as $householdNo) {
            $row = self::buildRow($householdNo);
            if ($row === null) {
                continue;
            }

            if (! self::matchesFilters($row, $filters)) {
                continue;
            }

            $rows[] = $row;
        }

        usort(
            $rows,
            static fn (array $a, array $b): int => strcmp((string) $a['household_no'], (string) $b['household_no'])
        );

        return $rows;
    }

    /**
     * Compute summary statistics from an already-filtered row set.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public static function statistics(array $rows): array
    {
        $water = [
            DemoHouseholdWaterSupply::WATER_LEVEL_I => 0,
            DemoHouseholdWaterSupply::WATER_LEVEL_II => 0,
            DemoHouseholdWaterSupply::WATER_LEVEL_III => 0,
            DemoHouseholdWaterSupply::WATER_LEVEL_OTHERS => 0,
        ];

        $sanitation = [
            DemoHouseholdWaterSupply::TOILET_STATUS_SANITARY => 0,
            DemoHouseholdWaterSupply::TOILET_STATUS_UNSANITARY => 0,
            'not_yet_determined' => 0,
        ];

        $toiletPresence = [
            'with_toilet' => 0,
            'without_toilet' => 0,
            'unknown' => 0,
        ];

        $total = count($rows);
        $completed = 0;
        $pending = 0;
        $validatedWater = 0;
        $goodSolidWaste = 0;

        foreach ($rows as $row) {
            $level = (string) ($row['water_supply_status'] ?? '');
            if (array_key_exists($level, $water)) {
                $water[$level]++;
            }

            $toiletStatus = (string) ($row['toilet_status'] ?? '');
            if ($toiletStatus === DemoHouseholdWaterSupply::TOILET_STATUS_SANITARY
                || $toiletStatus === DemoHouseholdWaterSupply::TOILET_STATUS_UNSANITARY) {
                $sanitation[$toiletStatus]++;
            } else {
                $sanitation['not_yet_determined']++;
            }

            $presence = (string) ($row['toilet_presence'] ?? 'unknown');
            if (array_key_exists($presence, $toiletPresence)) {
                $toiletPresence[$presence]++;
            } else {
                $toiletPresence['unknown']++;
            }

            if (($row['record_status'] ?? '') === self::RECORD_STATUS_COMPLETED) {
                $completed++;
            } else {
                $pending++;
            }

            if (($row['validation_status'] ?? '') === 'completed') {
                $validatedWater++;
            }

            if (($row['solid_waste_status'] ?? '') === 'good_practice') {
                $goodSolidWaste++;
            }
        }

        return [
            'water_supply' => [
                'level_i' => $water[DemoHouseholdWaterSupply::WATER_LEVEL_I],
                'level_ii' => $water[DemoHouseholdWaterSupply::WATER_LEVEL_II],
                'level_iii' => $water[DemoHouseholdWaterSupply::WATER_LEVEL_III],
                'others' => $water[DemoHouseholdWaterSupply::WATER_LEVEL_OTHERS],
            ],
            'sanitation' => [
                'sanitary' => $sanitation[DemoHouseholdWaterSupply::TOILET_STATUS_SANITARY],
                'unsanitary' => $sanitation[DemoHouseholdWaterSupply::TOILET_STATUS_UNSANITARY],
                'not_yet_determined' => $sanitation['not_yet_determined'],
            ],
            'toilet_presence' => $toiletPresence,
            'overview' => [
                'total_households' => $total,
                'completed_amenities' => $completed,
                'pending_assessment' => $pending,
                'validated_water_sources' => $validatedWater,
                'good_solid_waste' => $goodSolidWaste,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function zonesFromRows(array $rows): array
    {
        return self::uniqueSortedColumn($rows, 'zone');
    }

    /**
     * @return list<string>
     */
    public static function streetsFromRows(array $rows): array
    {
        return self::uniqueSortedColumn($rows, 'street');
    }

    /**
     * Normalize incoming request filters.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public static function normalizeFilters(array $input): array
    {
        return [
            'household_no' => trim((string) ($input['household_no'] ?? '')),
            'house_head' => trim((string) ($input['house_head'] ?? '')),
            'zone' => self::filterValue((string) ($input['zone'] ?? 'all')),
            'street' => self::filterValue((string) ($input['street'] ?? 'all')),
            'water_supply' => self::filterValue((string) ($input['water_supply'] ?? 'all')),
            'sanitation' => self::filterValue((string) ($input['sanitation'] ?? 'all')),
            'validation' => self::filterValue((string) ($input['validation'] ?? 'all')),
            'record_status' => self::filterValue((string) ($input['record_status'] ?? 'all')),
        ];
    }

    /**
     * @return list<string>
     */
    private static function candidateHouseholdNos(): array
    {
        $nos = DemoHouseholdWaterSupply::profilingDemoHouseholdNos();

        foreach (array_keys(DemoHouseholdWaterSupply::all()) as $key) {
            $normalized = DemoHouseholdWaterSupply::normalizeHouseholdNo((string) $key);
            if ($normalized !== '' && ! in_array($normalized, $nos, true)) {
                $nos[] = $normalized;
            }
        }

        return $nos;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function buildRow(string $householdNo): ?array
    {
        $key = DemoCatalog::normalizeHouseholdNo($householdNo);
        $household = DemoCatalog::findHousehold($key);
        $record = DemoHouseholdWaterSupply::findForActor($key);

        if (! is_array($household) && ! is_array($record)) {
            return null;
        }

        $record = is_array($record) ? $record : [];
        $linked = DemoHouseholdWaterSupply::findLinkedForActor($key) ?? [];

        $waterSupplyStatus = strtolower(trim((string) ($record['water_supply_status'] ?? '')));
        $toiletType = strtolower(trim((string) ($record['toilet_type'] ?? '')));
        $toiletStatus = strtolower(trim((string) ($record['toilet_status'] ?? '')));
        if ($toiletStatus === '' && $toiletType !== '') {
            $toiletStatus = (string) (DemoHouseholdWaterSupply::deriveToiletStatus($toiletType) ?? '');
        }

        $managementStatus = strtolower(trim((string) ($record['management_status'] ?? '')));
        if ($managementStatus === '') {
            $managementStatus = DemoHouseholdWaterSupply::deriveManagementStatus(
                $toiletType !== '' ? $toiletType : null,
                isset($record['sewage_disposal_method']) ? (string) $record['sewage_disposal_method'] : null
            );
        }

        $validationStatus = DemoHouseholdWaterSupply::validationTestingStatus($record);
        $completeStatus = DemoHouseholdWaterSupply::deriveCompleteSanitationFacilityStatus($record);
        $solidWasteStatus = strtolower(trim((string) ($record['solid_waste_status'] ?? 'not_yet_determined')));
        $recordStatus = self::deriveRecordStatus($record, $waterSupplyStatus, $toiletType);
        $toiletPresence = self::deriveToiletPresence($toiletType);
        $actionMode = $recordStatus === self::RECORD_STATUS_COMPLETED ? 'edit' : 'add';

        $houseHead = trim((string) (
            $record['house_head']
            ?? $linked['house_head']
            ?? ($household['houseHead'] ?? '')
        ));

        return [
            'household_no' => $key,
            'house_head' => $houseHead !== '' ? $houseHead : 'Not available',
            'zone' => (string) ($household['zone'] ?? $linked['zone'] ?? ''),
            'street' => (string) ($household['street'] ?? ''),
            'water_supply_status' => $waterSupplyStatus,
            'water_supply_label' => DemoHouseholdWaterSupply::waterSupplyLevelLabel($waterSupplyStatus),
            'water_supply_short' => self::waterSupplyShortLabel($waterSupplyStatus),
            'toilet_type' => $toiletType,
            'toilet_presence' => $toiletPresence,
            'toilet_presence_label' => self::toiletPresenceLabel($toiletPresence),
            'toilet_status' => $toiletStatus,
            'toilet_status_label' => HouseholdAmenitiesPresentation::toiletStatusLabel($toiletStatus),
            'toilet_status_modifier' => HouseholdAmenitiesPresentation::toiletStatusModifier($toiletStatus),
            'sanitation_status' => $managementStatus,
            'sanitation_label' => DemoHouseholdWaterSupply::managementStatusBadgeLabel($managementStatus),
            'sanitation_modifier' => HouseholdAmenitiesPresentation::managementStatusModifier($managementStatus),
            'validation_status' => $validationStatus,
            'validation_label' => DemoHouseholdWaterSupply::validationTestingStatusLabel($validationStatus),
            'validation_modifier' => HouseholdAmenitiesPresentation::validationStatusModifier($validationStatus),
            'overall_status' => $completeStatus,
            'overall_label' => DemoHouseholdWaterSupply::managementStatusBadgeLabel($completeStatus),
            'overall_modifier' => HouseholdAmenitiesPresentation::managementStatusModifier($completeStatus),
            'record_status' => $recordStatus,
            'record_status_label' => $recordStatus === self::RECORD_STATUS_COMPLETED ? 'Completed' : 'Pending',
            'action_mode' => $actionMode,
            'solid_waste_status' => $solidWasteStatus,
            'solid_waste_label' => DemoHouseholdWaterSupply::solidWasteStatusLabel($solidWasteStatus),
            'view_url' => route('household-profiling.amenities.show', ['householdNo' => $key]),
            'edit_url' => route('household-profiling.amenities.edit', ['householdNo' => $key]),
        ];
    }

    /**
     * Presentation-only toilet presence for Figma With/Without Toilet cards.
     * Does not alter stored toilet_type or toilet_status values.
     */
    public static function deriveToiletPresence(string $toiletType): string
    {
        $normalized = strtolower(trim($toiletType));

        if ($normalized === '') {
            return 'unknown';
        }

        if (DemoHouseholdWaterSupply::isWithoutToilet($normalized)) {
            return 'without_toilet';
        }

        return 'with_toilet';
    }

    public static function toiletPresenceLabel(string $presence): string
    {
        return match ($presence) {
            'with_toilet' => 'With Toilet',
            'without_toilet' => 'Without Toilet',
            default => '—',
        };
    }

    public static function waterSupplyShortLabel(?string $value): string
    {
        return match (strtolower(trim((string) $value))) {
            DemoHouseholdWaterSupply::WATER_LEVEL_I => 'I',
            DemoHouseholdWaterSupply::WATER_LEVEL_II => 'II',
            DemoHouseholdWaterSupply::WATER_LEVEL_III => 'III',
            DemoHouseholdWaterSupply::WATER_LEVEL_OTHERS => 'Others',
            default => '—',
        };
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private static function deriveRecordStatus(array $record, string $waterSupplyStatus, string $toiletType): string
    {
        $step = (int) ($record['step'] ?? 0);
        if ($step >= 4) {
            return self::RECORD_STATUS_COMPLETED;
        }

        if ($waterSupplyStatus !== '' && $toiletType !== '') {
            return self::RECORD_STATUS_COMPLETED;
        }

        return self::RECORD_STATUS_PENDING;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $filters
     */
    private static function matchesFilters(array $row, array $filters): bool
    {
        if ($filters === []) {
            return true;
        }

        $hhQuery = strtolower(trim((string) ($filters['household_no'] ?? '')));
        if ($hhQuery !== '' && ! str_contains(strtolower((string) $row['household_no']), $hhQuery)) {
            return false;
        }

        $headQuery = strtolower(trim((string) ($filters['house_head'] ?? '')));
        if ($headQuery !== '' && ! str_contains(strtolower((string) $row['house_head']), $headQuery)) {
            return false;
        }

        $zone = (string) ($filters['zone'] ?? 'all');
        if ($zone !== 'all' && (string) $row['zone'] !== $zone) {
            return false;
        }

        $street = (string) ($filters['street'] ?? 'all');
        if ($street !== 'all' && (string) $row['street'] !== $street) {
            return false;
        }

        $water = (string) ($filters['water_supply'] ?? 'all');
        if ($water !== 'all' && (string) $row['water_supply_status'] !== $water) {
            return false;
        }

        $sanitation = (string) ($filters['sanitation'] ?? 'all');
        if ($sanitation !== 'all') {
            $presence = (string) ($row['toilet_presence'] ?? 'unknown');
            if ($presence !== $sanitation) {
                return false;
            }
        }

        // Validation / record_status query params are ignored in the UI but still
        // accepted for backward-compatible URLs without changing stored data.
        return true;
    }

    private static function filterValue(string $value): string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? 'all' : $trimmed;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<string>
     */
    private static function uniqueSortedColumn(array $rows, string $column): array
    {
        $values = [];
        foreach ($rows as $row) {
            $value = trim((string) ($row[$column] ?? ''));
            if ($value !== '') {
                $values[$value] = true;
            }
        }

        $list = array_keys($values);
        sort($list, SORT_NATURAL | SORT_FLAG_CASE);

        return $list;
    }
}
