<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Barangay-wide Risk Assessment summary (Health Records → Risk Assessment).
 *
 * Resident candidates come only from Household Profiling DemoCatalog members.
 * Eligibility is age 19+ derived from the member birthday (not a stored age field).
 *
 * UI-PHASE: display status cells remain preview vocabulary for the listing.
 * Not persisted. Not mapped from frozen Household Profiling DemoRiskAssessment.
 */
final class HealthRecordsRiskAssessment
{
    public const MIN_AGE_YEARS = 19;

    /**
     * Fixed zone labels shown on summary cards and in the Zone filter.
     *
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
     * Eligible assessed-client rows for the listing, search, and year filter.
     *
     * @return list<array{
     *     key: string,
     *     household_no: string,
     *     member_id: string,
     *     full_name: string,
     *     birthday: string,
     *     zone: string,
     *     year: string,
     *     bmi_status: string,
     *     bp_status: string,
     *     smoking_status: string,
     *     alcohol_status: string,
     *     physical_activity_risk: string,
     *     family_history_risk: string,
     *     chronic_disease: string
     * }>
     */
    public static function rows(): array
    {
        $rows = [];

        foreach (self::eligibleResidents() as $resident) {
            $rows[] = self::toDisplayRow($resident);
        }

        usort(
            $rows,
            static function (array $a, array $b): int {
                $byName = strcasecmp((string) $a['full_name'], (string) $b['full_name']);

                return $byName !== 0
                    ? $byName
                    : strcasecmp((string) $a['member_id'], (string) $b['member_id']);
            }
        );

        return $rows;
    }

    /**
     * Household Profiling residents who have reached age 19.
     * This is the single candidate supplier for Health Records → Risk Assessment.
     *
     * @return list<array{household_no: string, zone: string, member: array<string, mixed>}>
     */
    public static function eligibleResidents(): array
    {
        $eligible = [];

        foreach (self::catalogResidents() as $resident) {
            if (self::isEligibleResident($resident['member'])) {
                $eligible[] = $resident;
            }
        }

        return $eligible;
    }

    /**
     * Every Household Profiling catalog member (unfiltered). Used to prove exclusions.
     *
     * @return list<array{household_no: string, zone: string, member: array<string, mixed>}>
     */
    public static function catalogResidents(): array
    {
        $residents = [];

        foreach (DemoCatalog::households() as $household) {
            if (! is_array($household)) {
                continue;
            }

            $householdNo = (string) ($household['householdNo'] ?? '');
            $zone = (string) ($household['zone'] ?? '');

            foreach ($household['memberList'] ?? [] as $member) {
                if (! is_array($member)) {
                    continue;
                }

                $memberId = trim((string) ($member['id'] ?? ''));
                if ($memberId === '') {
                    continue;
                }

                $residents[] = [
                    'household_no' => $householdNo,
                    'zone' => $zone,
                    'member' => $member,
                ];
            }
        }

        return $residents;
    }

    /**
     * @param  array<string, mixed>  $member
     */
    public static function isEligibleResident(array $member, ?Carbon $on = null): bool
    {
        $age = self::ageInYears($member, $on);

        return $age !== null && $age >= self::MIN_AGE_YEARS;
    }

    /**
     * Completed years of age from recorded birthday (Carbon age / birthday-aware).
     * Does not read a stored numeric age field.
     *
     * @param  array<string, mixed>  $member
     */
    public static function ageInYears(array $member, ?Carbon $on = null): ?int
    {
        $birthday = $member['birthday'] ?? null;
        if (! is_string($birthday) || trim($birthday) === '') {
            return null;
        }

        try {
            $born = Carbon::parse($birthday)->startOfDay();
            $asOf = ($on ?? Carbon::now())->copy()->startOfDay();

            if ($born->greaterThan($asOf)) {
                return 0;
            }

            $age = (int) $asOf->year - (int) $born->year;
            if ($asOf->lt($born->copy()->year($asOf->year))) {
                $age--;
            }

            return max(0, $age);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Overall dataset summary — not affected by client-side table filters.
     *
     * @param  list<array<string, mixed>>|null  $rows
     * @return array{total: int, zones: array<string, int>}
     */
    public static function summaryCounts(?array $rows = null): array
    {
        $rows ??= self::rows();
        $zoneCounts = [];

        foreach (self::zones() as $zone) {
            $zoneCounts[$zone] = 0;
        }

        foreach ($rows as $row) {
            $zone = (string) ($row['zone'] ?? '');
            if (array_key_exists($zone, $zoneCounts)) {
                $zoneCounts[$zone]++;
            }
        }

        return [
            'total' => count($rows),
            'zones' => $zoneCounts,
        ];
    }

    /**
     * Distinct years from listing rows (descending), for the Year filter.
     *
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
     * @param  array{household_no: string, zone: string, member: array<string, mixed>}  $resident
     * @return array{
     *     key: string,
     *     household_no: string,
     *     member_id: string,
     *     full_name: string,
     *     birthday: string,
     *     zone: string,
     *     year: string,
     *     bmi_status: string,
     *     bp_status: string,
     *     smoking_status: string,
     *     alcohol_status: string,
     *     physical_activity_risk: string,
     *     family_history_risk: string,
     *     chronic_disease: string
     * }
     */
    private static function toDisplayRow(array $resident): array
    {
        $member = $resident['member'];
        $memberId = strtoupper(trim((string) ($member['id'] ?? '')));
        $overlay = self::statusOverlay()[$memberId] ?? [];

        return [
            'key' => strtolower($memberId),
            'household_no' => (string) $resident['household_no'],
            'member_id' => $memberId,
            'full_name' => self::displayName($member),
            'birthday' => (string) ($member['birthday'] ?? ''),
            'zone' => (string) $resident['zone'],
            'year' => (string) ($overlay['year'] ?? '2026'),
            'bmi_status' => (string) ($overlay['bmi_status'] ?? '—'),
            'bp_status' => (string) ($overlay['bp_status'] ?? '—'),
            'smoking_status' => (string) ($overlay['smoking_status'] ?? '—'),
            'alcohol_status' => (string) ($overlay['alcohol_status'] ?? '—'),
            'physical_activity_risk' => (string) ($overlay['physical_activity_risk'] ?? '—'),
            'family_history_risk' => (string) ($overlay['family_history_risk'] ?? '—'),
            'chronic_disease' => (string) ($overlay['chronic_disease'] ?? '—'),
        ];
    }

    /**
     * @param  array<string, mixed>  $member
     */
    private static function displayName(array $member): string
    {
        $name = trim((string) ($member['name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        $parts = array_filter([
            $member['first_name'] ?? null,
            $member['middle_name'] ?? null,
            $member['last_name'] ?? null,
        ], static fn ($part) => filled($part));

        return $parts !== [] ? implode(' ', $parts) : 'Unknown';
    }

    /**
     * UI-phase listing vocabulary keyed by catalog member id.
     * Overlay is never applied to ineligible members because rows() never receives them.
     *
     * @return array<string, array{
     *     year: string,
     *     bmi_status: string,
     *     bp_status: string,
     *     smoking_status: string,
     *     alcohol_status: string,
     *     physical_activity_risk: string,
     *     family_history_risk: string,
     *     chronic_disease: string
     * }>
     */
    private static function statusOverlay(): array
    {
        return [
            'MB-001' => self::statusSet('2026', 'Normal', 'Normal', 'Never', 'None', 'Active', 'No', 'None'),
            'MB-002' => self::statusSet('2026', 'Overweight', 'Pre-HTN', 'Current', 'Moderate', 'Inactive', 'Yes', 'Diabetes'),
            'MB-005' => self::statusSet('2026', 'Normal', 'Normal', 'Never', 'None', 'Active', 'No', 'None'),
            'MB-004' => self::statusSet('2025', 'Underweight', 'Normal', 'Quit', 'None', 'Active', 'Yes', 'CVD'),
            'MB-006' => self::statusSet('2026', 'Obese', 'HTN Stage 1', 'Current', 'Excessive', 'Inactive', 'Yes', 'Diabetes'),
            'MB-007' => self::statusSet('2025', 'Normal', 'Normal', 'Never', 'Moderate', 'Active', 'No', 'None'),
            'MB-008' => self::statusSet('2026', 'Overweight', 'HTN Stage 2', 'Never', 'None', 'Inactive', 'Yes', 'CVD'),
            'MB-012' => self::statusSet('2024', 'Normal', 'Pre-HTN', 'Quit', 'Moderate', 'Active', 'No', 'None'),
        ];
    }

    /**
     * @return array{
     *     year: string,
     *     bmi_status: string,
     *     bp_status: string,
     *     smoking_status: string,
     *     alcohol_status: string,
     *     physical_activity_risk: string,
     *     family_history_risk: string,
     *     chronic_disease: string
     * }
     */
    private static function statusSet(
        string $year,
        string $bmiStatus,
        string $bpStatus,
        string $smokingStatus,
        string $alcoholStatus,
        string $physicalActivityRisk,
        string $familyHistoryRisk,
        string $chronicDisease
    ): array {
        return [
            'year' => $year,
            'bmi_status' => $bmiStatus,
            'bp_status' => $bpStatus,
            'smoking_status' => $smokingStatus,
            'alcohol_status' => $alcoholStatus,
            'physical_activity_risk' => $physicalActivityRisk,
            'family_history_risk' => $familyHistoryRisk,
            'chronic_disease' => $chronicDisease,
        ];
    }
}
