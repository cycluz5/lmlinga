<?php

namespace Tests\Feature;

use App\Support\DemoHouseholdWaterSupply;
use App\Support\UiRole;
use Tests\TestCase;

class HouseholdWaterSupplyStep1Test extends TestCase
{
    private function seedLinkedHousehold(string $householdNo = 'HH-601'): string
    {
        $this->withSession([
            UiRole::SESSION_KEY => 'bhw',
        ]);

        $payload = [
            'household_no' => $householdNo,
            'house_head' => 'Ana Reyes',
            'household_type' => 'HHTS',
            'zone' => '2',
            'lat' => 13.3811,
            'lng' => 123.4306,
            'consent' => true,
            'client_marker_id' => 'client-marker-step1',
        ];

        $issue = $this->postJson(route('spot-mapping.plot-handoff'), $payload);
        $issue->assertOk();

        $token = (string) $issue->json('handoff_token');

        $this->get(route('environmental-health.household-water-supply', [
            'handoff' => $token,
        ]))->assertRedirect(route('environmental-health.household-water-supply', [
            'household' => $householdNo,
        ]));

        return $householdNo;
    }

    /**
     * @return array<string, mixed>
     */
    private function validStep1Payload(string $householdNo, array $overrides = []): array
    {
        return array_merge([
            'household_no' => $householdNo,
            'water_supply_status' => 'level_i',
            'specify_water_source' => null,
            'water_source_location' => 'yes',
            'water_availability' => 'yes',
        ], $overrides);
    }

    public function test_level_i_derives_with_basic_safe_water(): void
    {
        $householdNo = $this->seedLinkedHousehold('HH-611');

        $this->post(route('environmental-health.household-water-supply.store'), $this->validStep1Payload($householdNo, [
            'water_supply_status' => 'level_i',
            'specify_water_source' => 'stale should clear',
        ]))->assertRedirect(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame('level_i', $record['water_supply_status'] ?? null);
        $this->assertSame(DemoHouseholdWaterSupply::BASIC_SAFE_WATER_WITH, $record['basic_safe_water_status'] ?? null);
        $this->assertArrayHasKey('specify_water_source', $record);
        $this->assertNull($record['specify_water_source']);
    }

    public function test_level_ii_derives_with_basic_safe_water(): void
    {
        $householdNo = $this->seedLinkedHousehold('HH-612');

        $this->post(route('environmental-health.household-water-supply.store'), $this->validStep1Payload($householdNo, [
            'water_supply_status' => 'level_ii',
        ]))->assertRedirect();

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame(DemoHouseholdWaterSupply::BASIC_SAFE_WATER_WITH, $record['basic_safe_water_status'] ?? null);
    }

    public function test_level_iii_derives_with_basic_safe_water(): void
    {
        $householdNo = $this->seedLinkedHousehold('HH-613');

        $this->post(route('environmental-health.household-water-supply.store'), $this->validStep1Payload($householdNo, [
            'water_supply_status' => 'level_iii',
        ]))->assertRedirect();

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame(DemoHouseholdWaterSupply::BASIC_SAFE_WATER_WITH, $record['basic_safe_water_status'] ?? null);
    }

    public function test_others_derives_without_basic_safe_water_and_requires_specify(): void
    {
        $householdNo = $this->seedLinkedHousehold('HH-614');

        $this->from(route('environmental-health.household-water-supply', [
            'household' => $householdNo,
        ]))->post(route('environmental-health.household-water-supply.store'), $this->validStep1Payload($householdNo, [
            'water_supply_status' => 'others',
            'specify_water_source' => null,
        ]))->assertRedirect(route('environmental-health.household-water-supply', [
            'household' => $householdNo,
        ]))->assertSessionHasErrors('specify_water_source');

        $this->post(route('environmental-health.household-water-supply.store'), $this->validStep1Payload($householdNo, [
            'water_supply_status' => 'others',
            'specify_water_source' => '  Open Dug Well  ',
        ]))->assertRedirect(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame('others', $record['water_supply_status'] ?? null);
        $this->assertSame('Open Dug Well', $record['specify_water_source'] ?? null);
        $this->assertSame(DemoHouseholdWaterSupply::BASIC_SAFE_WATER_WITHOUT, $record['basic_safe_water_status'] ?? null);
    }

    public function test_browser_supplied_status_cannot_override_server_derivation(): void
    {
        $householdNo = $this->seedLinkedHousehold('HH-615');

        $this->post(route('environmental-health.household-water-supply.store'), $this->validStep1Payload($householdNo, [
            'water_supply_status' => 'level_ii',
            'basic_safe_water_status' => 'without_basic_safe_water',
            'water_status' => 'without_basic_safe_water',
            'safe_water_status' => 'without_basic_safe_water',
        ]))->assertRedirect(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame(DemoHouseholdWaterSupply::BASIC_SAFE_WATER_WITH, $record['basic_safe_water_status'] ?? null);
    }

    public function test_invalid_water_level_machine_value_is_rejected(): void
    {
        $householdNo = $this->seedLinkedHousehold('HH-616');

        $this->from(route('environmental-health.household-water-supply', [
            'household' => $householdNo,
        ]))->post(route('environmental-health.household-water-supply.store'), $this->validStep1Payload($householdNo, [
            'water_supply_status' => 'level_1',
        ]))->assertSessionHasErrors('water_supply_status');
    }

    public function test_saved_value_reloads_and_step2_navigation_remains(): void
    {
        $householdNo = $this->seedLinkedHousehold('HH-617');

        $this->post(route('environmental-health.household-water-supply.store'), $this->validStep1Payload($householdNo, [
            'water_supply_status' => 'others',
            'specify_water_source' => 'Deep well',
        ]))->assertRedirect(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]));

        $this->assertTrue(DemoHouseholdWaterSupply::hasCompletedStep1($householdNo));

        $this->get(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]))->assertOk();
    }

    public function test_unrecognized_household_cannot_store_step1(): void
    {
        $this->withSession([
            UiRole::SESSION_KEY => 'bhw',
        ]);

        $this->post(route('environmental-health.household-water-supply.store'), $this->validStep1Payload('HH-UNKNOWN'))
            ->assertSessionHasErrors('household_no');
    }

    public function test_derive_helpers_match_display_rules(): void
    {
        $this->assertSame(
            DemoHouseholdWaterSupply::BASIC_SAFE_WATER_PENDING,
            DemoHouseholdWaterSupply::deriveBasicSafeWaterStatus(null)
        );
        $this->assertSame(
            DemoHouseholdWaterSupply::BASIC_SAFE_WATER_WITH,
            DemoHouseholdWaterSupply::deriveBasicSafeWaterStatus('level_i')
        );
        $this->assertSame(
            DemoHouseholdWaterSupply::BASIC_SAFE_WATER_WITHOUT,
            DemoHouseholdWaterSupply::deriveBasicSafeWaterStatus('others')
        );
        $this->assertSame(
            'With Basic Safe Water',
            DemoHouseholdWaterSupply::basicSafeWaterStatusLabel(DemoHouseholdWaterSupply::BASIC_SAFE_WATER_WITH)
        );
        $this->assertSame(
            'Without Basic Safe Water',
            DemoHouseholdWaterSupply::basicSafeWaterStatusLabel(DemoHouseholdWaterSupply::BASIC_SAFE_WATER_WITHOUT)
        );
    }
}
