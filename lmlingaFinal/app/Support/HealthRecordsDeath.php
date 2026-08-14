<?php

namespace App\Support;

/**
 * Barangay-wide Death listing (Health Records → Death).
 *
 * UI-PHASE ONLY: fixture rows and derived summary counts for Figma-aligned
 * preview. Not persisted. Not mapped from Household Profiling DemoDeath.
 */
final class HealthRecordsDeath
{
    public const EMPTY = '—';

    /**
     * @return list<string>
     */
    public static function zones(): array
    {
        return [
            'Zone 1',
            'Zone 2',
            'Zone 3',
            'Zone 4',
            'Zone 5',
        ];
    }

    /**
     * UI-phase monitoring rows. Summary cards and filter options derive from these.
     *
     * @return list<array<string, mixed>>
     */
    public static function rows(): array
    {
        return [
            self::row('kristine-b-reyes', 'Kristine B. Reyes', 32, 'Female', 'Zone 1', 'Kidney Failure', '2026-03-12'),
            self::row('jacob-a-magistrado', 'Jacob A. Magistrado', 40, 'Male', 'Zone 2', 'Accident', '2026-01-30'),
            self::row('haziel-h-santos', 'Haziel H. Santos', 60, 'Female', 'Zone 3', 'Stroke', '2025-01-04'),
            self::row('andrei-b-malaya', 'Andrei B. Malaya', 50, 'Male', 'Zone 4', 'Kidney Failure', '2025-02-07'),
            self::row('crisley-f-fernando', 'Crisley F. Fernando', 44, 'Female', 'Zone 5', 'Heart Attack', '2025-05-14'),
            self::row('gabriel-allan-s-chua', 'Gabriel Allan S. Chua', 60, 'Male', 'Zone 1', 'Stroke', '2025-04-29'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>|null  $rows
     * @return array{total: int, female: int, male: int}
     */
    public static function summaryCounts(?array $rows = null): array
    {
        $rows ??= self::rows();
        $female = 0;
        $male = 0;

        foreach ($rows as $row) {
            $sex = (string) ($row['sex'] ?? '');
            if (HealthRecordsMaternal::isFemaleSex($sex)) {
                $female++;
            } elseif (HealthRecordsMaternal::isMaleSex($sex)) {
                $male++;
            }
        }

        return [
            'total' => count($rows),
            'female' => $female,
            'male' => $male,
        ];
    }

    /**
     * @param  list<array<string, mixed>>|null  $rows
     * @return list<string>
     */
    public static function years(?array $rows = null): array
    {
        $rows ??= self::rows();
        $years = [];

        foreach ($rows as $row) {
            $year = trim((string) ($row['year'] ?? ''));
            if ($year !== '') {
                $years[$year] = true;
            }
        }

        $list = array_map('strval', array_keys($years));
        rsort($list, SORT_NUMERIC);

        return $list;
    }

    /**
     * @param  list<array<string, mixed>>|null  $rows
     * @return list<string>
     */
    public static function causes(?array $rows = null): array
    {
        $rows ??= self::rows();
        $causes = [];

        foreach ($rows as $row) {
            $cause = trim((string) ($row['cause_of_death'] ?? ''));
            if ($cause !== '' && $cause !== self::EMPTY) {
                $causes[$cause] = true;
            }
        }

        $list = array_keys($causes);
        natcasesort($list);

        return array_values($list);
    }

    /**
     * Same matching rules as the listing's client-side filters.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array{search?: string, zone?: string, cause?: string, sex?: string, year?: string}  $filters
     * @return list<array<string, mixed>>
     */
    public static function filterRows(array $rows, array $filters): array
    {
        $search = strtolower(trim((string) ($filters['search'] ?? '')));
        $zone = (string) ($filters['zone'] ?? 'all');
        $cause = (string) ($filters['cause'] ?? 'all');
        $sex = (string) ($filters['sex'] ?? 'all');
        $year = (string) ($filters['year'] ?? 'all');

        $matched = [];

        foreach ($rows as $row) {
            $name = strtolower((string) ($row['full_name'] ?? ''));
            $rowZone = (string) ($row['zone'] ?? '');
            $rowCause = (string) ($row['cause_of_death'] ?? '');
            $rowSex = (string) ($row['sex_filter'] ?? '');
            $rowYear = (string) ($row['year'] ?? '');

            $matchesSearch = $search === '' || str_contains($name, $search);
            $matchesZone = $zone === 'all' || $rowZone === $zone;
            $matchesCause = $cause === 'all' || $rowCause === $cause;
            $matchesSex = $sex === 'all' || $rowSex === $sex;
            $matchesYear = $year === 'all' || $rowYear === $year;

            if ($matchesSearch && $matchesZone && $matchesCause && $matchesSex && $matchesYear) {
                $matched[] = $row;
            }
        }

        return $matched;
    }

    /**
     * @return array<string, mixed>
     */
    private static function row(
        string $key,
        string $fullName,
        int $age,
        string $sex,
        string $zone,
        string $cause,
        string $dateIso
    ): array {
        $year = preg_match('/^(\d{4})-/', $dateIso, $match) ? $match[1] : '';

        return [
            'key' => $key,
            'full_name' => $fullName,
            'age' => (string) $age,
            'sex' => $sex,
            'sex_filter' => HealthRecordsMaternal::isFemaleSex($sex)
                ? 'female'
                : (HealthRecordsMaternal::isMaleSex($sex) ? 'male' : ''),
            'zone' => $zone,
            'cause_of_death' => $cause,
            'date_of_death' => DemoDeath::formatDateForDisplay($dateIso),
            'date_of_death_iso' => $dateIso,
            'year' => $year,
        ];
    }
}
