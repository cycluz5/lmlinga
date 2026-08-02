<?php

namespace App\Support;

/**
 * Server-owned Spot Mapping plot records (session-backed demo store).
 * The browser may request creation; only the server assigns plot identity and status.
 */
final class DemoSpotMappingPlot
{
    public const SESSION_KEY = 'lml.demo.spot_mapping_plots.v1';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_HANDOFF_ISSUED = 'handoff_issued';

    public const STATUS_WATER_SUPPLY_LINKED = 'water_supply_linked';

    /**
     * Validate and create a trusted plot. Returns the server-owned record or null.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>|null
     */
    public static function create(array $input): ?array
    {
        $normalized = self::normalizeAndValidate($input);
        if ($normalized === null) {
            return null;
        }

        $plotId = 'SMP-'.bin2hex(random_bytes(16));
        $now = now();

        $record = [
            'plot_id' => $plotId,
            'actor_id' => DemoSpotMappingHandoff::actorKey(),
            'household_no' => $normalized['household_no'],
            'house_head' => $normalized['house_head'],
            'household_type' => $normalized['household_type'],
            'zone' => $normalized['zone'],
            'lat' => $normalized['lat'],
            'lng' => $normalized['lng'],
            'consent' => true,
            'client_marker_id' => $normalized['client_marker_id'],
            'status' => self::STATUS_CONFIRMED,
            'created_at' => $now->toIso8601String(),
            'updated_at' => $now->toIso8601String(),
            'handoff_issued_at' => null,
            'water_supply_linked_at' => null,
        ];

        $plots = self::all();
        $plots[$plotId] = $record;
        session([self::SESSION_KEY => $plots]);

        return $record;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $plotId): ?array
    {
        $plotId = trim($plotId);
        if ($plotId === '') {
            return null;
        }

        $record = self::all()[$plotId] ?? null;

        return is_array($record) ? $record : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findForActor(string $plotId): ?array
    {
        $record = self::find($plotId);
        if ($record === null) {
            return null;
        }

        if ((string) ($record['actor_id'] ?? '') !== DemoSpotMappingHandoff::actorKey()) {
            return null;
        }

        return $record;
    }

    /**
     * Mark plot as handoff-issued. Rejects unknown / wrong-actor / already-transitioned plots.
     *
     * @return array<string, mixed>|null
     */
    public static function markHandoffIssued(string $plotId): ?array
    {
        $record = self::findForActor($plotId);
        if ($record === null) {
            return null;
        }

        $status = (string) ($record['status'] ?? '');
        if ($status !== self::STATUS_CONFIRMED) {
            return null;
        }

        $record['status'] = self::STATUS_HANDOFF_ISSUED;
        $record['handoff_issued_at'] = now()->toIso8601String();
        $record['updated_at'] = $record['handoff_issued_at'];

        return self::put($record);
    }

    /**
     * Mark plot as linked into Household Water Supply after token consume.
     *
     * @return array<string, mixed>|null
     */
    public static function markWaterSupplyLinked(string $plotId): ?array
    {
        $record = self::findForActor($plotId);
        if ($record === null) {
            return null;
        }

        if ((string) ($record['status'] ?? '') !== self::STATUS_HANDOFF_ISSUED) {
            return null;
        }

        $record['status'] = self::STATUS_WATER_SUPPLY_LINKED;
        $record['water_supply_linked_at'] = now()->toIso8601String();
        $record['updated_at'] = $record['water_supply_linked_at'];

        return self::put($record);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     household_no: string,
     *     house_head: string,
     *     household_type: string,
     *     zone: string,
     *     lat: float,
     *     lng: float,
     *     client_marker_id: string|null
     * }|null
     */
    private static function normalizeAndValidate(array $input): ?array
    {
        $householdNo = DemoHouseholdWaterSupply::normalizeHouseholdNo((string) ($input['household_no'] ?? ''));
        $houseHead = trim((string) ($input['house_head'] ?? ''));
        $householdType = trim((string) ($input['household_type'] ?? ''));
        $zoneRaw = $input['zone'] ?? '';
        $zone = is_numeric($zoneRaw)
            ? (string) (int) $zoneRaw
            : trim((string) $zoneRaw);
        $lat = $input['lat'] ?? null;
        $lng = $input['lng'] ?? null;
        $consent = filter_var($input['consent'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $clientMarkerId = trim((string) ($input['client_marker_id'] ?? ''));

        if (! DemoHouseholdWaterSupply::isValidHouseholdNo($householdNo)) {
            return null;
        }

        if ($houseHead === '' || strlen($houseHead) > 255) {
            return null;
        }

        if (! in_array($householdType, ['HHTS', 'Non-HHTS'], true)) {
            return null;
        }

        if (! in_array($zone, ['1', '2', '3', '4', '5', 'Zone 1', 'Zone 2', 'Zone 3', 'Zone 4', 'Zone 5'], true)) {
            return null;
        }

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        // Demo geofence around Barangay La Medalla (approximate).
        if ($lat < 13.35 || $lat > 13.42 || $lng < 123.40 || $lng > 123.46) {
            return null;
        }

        if (! $consent) {
            return null;
        }

        if ($clientMarkerId !== '' && strlen($clientMarkerId) > 128) {
            return null;
        }

        return [
            'household_no' => $householdNo,
            'house_head' => $houseHead,
            'household_type' => $householdType,
            'zone' => $zone,
            'lat' => $lat,
            'lng' => $lng,
            'client_marker_id' => $clientMarkerId !== '' ? $clientMarkerId : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private static function put(array $record): array
    {
        $plotId = (string) ($record['plot_id'] ?? '');
        $plots = self::all();
        $plots[$plotId] = $record;
        session([self::SESSION_KEY => $plots]);

        return $record;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function all(): array
    {
        /** @var array<string, array<string, mixed>> $plots */
        $plots = session(self::SESSION_KEY, []);

        return is_array($plots) ? $plots : [];
    }
}
