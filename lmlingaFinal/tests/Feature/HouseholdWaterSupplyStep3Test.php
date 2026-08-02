<?php

namespace Tests\Feature;

use App\Support\DemoHouseholdWaterSupply;
use App\Support\DemoSpotMappingHandoff;
use App\Support\UiRole;
use Tests\TestCase;

class HouseholdWaterSupplyStep3Test extends TestCase
{
    private function seedThroughStep12(string $householdNo = 'HH-701'): string
    {
        $this->withSession([
            UiRole::SESSION_KEY => 'bhw',
        ]);

        $payload = [
            'household_no' => $householdNo,
            'house_head' => 'Juan Dela Cruz',
            'household_type' => 'HHTS',
            'zone' => '1',
            'lat' => 13.3811,
            'lng' => 123.4306,
            'consent' => true,
            'client_marker_id' => 'client-marker-step3',
        ];

        $issue = $this->postJson(route('spot-mapping.plot-handoff'), $payload);
        $issue->assertOk();

        $token = (string) $issue->json('handoff_token');

        $this->get(route('environmental-health.household-water-supply', [
            'handoff' => $token,
        ]))->assertRedirect(route('environmental-health.household-water-supply', [
            'household' => $householdNo,
        ]));

        $this->post(route('environmental-health.household-water-supply.store'), [
            'household_no' => $householdNo,
            'water_supply_status' => 'level_i',
            'water_source_location' => 'yes',
            'water_availability' => 'yes',
        ])->assertRedirect(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]));

        $this->post(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
        ])->assertRedirect(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));

        return $householdNo;
    }

    private function validSanitationPayload(string $householdNo, array $overrides = []): array
    {
        return array_merge([
            'household_no' => $householdNo,
            'toilet_type' => 'pour_flush_with_septic_tank',
            'open_defecation_practiced' => 'no',
            'shared_toilet' => 'yes',
            'sewage_disposal_method' => 'on_site_safely_managed',
        ], $overrides);
    }

    public function test_blank_part2_submission_fails(): void
    {
        $householdNo = $this->seedThroughStep12();

        $response = $this->from(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]))->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
        ]);

        $response->assertRedirect(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));
        $response->assertSessionHasErrors([
            'toilet_type',
            'open_defecation_practiced',
            'shared_toilet',
            'sewage_disposal_method',
        ]);
    }

    public function test_missing_toilet_type_fails(): void
    {
        $householdNo = $this->seedThroughStep12('HH-702');

        $response = $this->from(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]))->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), $this->validSanitationPayload($householdNo, [
            'toilet_type' => '',
        ]));

        $response->assertSessionHasErrors([
            'toilet_type' => 'Please select the type of toilet.',
        ]);
    }

    /**
     * @dataProvider sanitaryToiletProvider
     */
    public function test_each_sanitary_toilet_choice_derives_sanitary(string $toiletType, string $householdNo): void
    {
        $this->seedThroughStep12($householdNo);

        $this->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), $this->validSanitationPayload($householdNo, [
            'toilet_type' => $toiletType,
        ]))->assertRedirect(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame($toiletType, $record['toilet_type'] ?? null);
        $this->assertSame(DemoHouseholdWaterSupply::TOILET_STATUS_SANITARY, $record['toilet_status'] ?? null);
        $this->assertSame(
            DemoHouseholdWaterSupply::MANAGEMENT_STATUS_SAFELY_MANAGED,
            $record['management_status'] ?? null
        );
        $this->assertSame(
            DemoHouseholdWaterSupply::TOILET_STATUS_SANITARY,
            DemoHouseholdWaterSupply::deriveToiletStatus($toiletType)
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function sanitaryToiletProvider(): array
    {
        return [
            'pour_flush_with_septic_tank' => ['pour_flush_with_septic_tank', 'HH-721'],
            'pour_flush_connected_to_septic_or_sewer' => ['pour_flush_connected_to_septic_or_sewer', 'HH-722'],
            'ventilated_improved_pit_latrine' => ['ventilated_improved_pit_latrine', 'HH-723'],
        ];
    }

    /**
     * @dataProvider unsanitaryToiletProvider
     */
    public function test_each_unsanitary_toilet_choice_derives_unsanitary(string $toiletType, string $householdNo): void
    {
        $this->seedThroughStep12($householdNo);

        $payload = $this->validSanitationPayload($householdNo, [
            'toilet_type' => $toiletType,
            'open_defecation_practiced' => 'yes',
        ]);

        if ($toiletType === DemoHouseholdWaterSupply::TOILET_TYPE_WITHOUT) {
            unset($payload['shared_toilet'], $payload['sewage_disposal_method']);
        }

        $this->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), $payload)->assertRedirect(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame($toiletType, $record['toilet_type'] ?? null);
        $this->assertSame(DemoHouseholdWaterSupply::TOILET_STATUS_UNSANITARY, $record['toilet_status'] ?? null);
        $this->assertSame(
            DemoHouseholdWaterSupply::MANAGEMENT_STATUS_NOT_SAFELY_MANAGED,
            $record['management_status'] ?? null
        );
        $this->assertSame(
            DemoHouseholdWaterSupply::TOILET_STATUS_UNSANITARY,
            DemoHouseholdWaterSupply::deriveToiletStatus($toiletType)
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function unsanitaryToiletProvider(): array
    {
        return [
            'water_sealed_without_septic_tank' => ['water_sealed_without_septic_tank', 'HH-724'],
            'overhung_latrine' => ['overhung_latrine', 'HH-725'],
            'open_pit_latrine' => ['open_pit_latrine', 'HH-726'],
            'without_toilet' => ['without_toilet', 'HH-727'],
        ];
    }

    public function test_browser_supplied_status_cannot_override_server_derived_status(): void
    {
        $householdNo = $this->seedThroughStep12('HH-703');

        $this->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), $this->validSanitationPayload($householdNo, [
            'toilet_type' => 'open_pit_latrine',
            'toilet_status' => 'sanitary',
            'management_status' => 'safely_managed',
            'facility_status' => 'safely_managed',
            'safely_managed' => '1',
        ]))->assertRedirect(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame(DemoHouseholdWaterSupply::TOILET_STATUS_UNSANITARY, $record['toilet_status'] ?? null);
        $this->assertNotSame('sanitary', $record['toilet_status'] ?? null);
        $this->assertSame(
            DemoHouseholdWaterSupply::MANAGEMENT_STATUS_NOT_SAFELY_MANAGED,
            $record['management_status'] ?? null
        );
        $this->assertNotSame('safely_managed', $record['management_status'] ?? null);
    }

    public function test_open_defecation_response_is_required(): void
    {
        $householdNo = $this->seedThroughStep12('HH-704');

        $response = $this->from(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]))->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), $this->validSanitationPayload($householdNo, [
            'open_defecation_practiced' => '',
        ]));

        $response->assertSessionHasErrors([
            'open_defecation_practiced' => 'Please indicate whether open defecation is practiced.',
        ]);
    }

    public function test_shared_toilet_response_is_required_when_applicable(): void
    {
        $householdNo = $this->seedThroughStep12('HH-705');

        $response = $this->from(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]))->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), $this->validSanitationPayload($householdNo, [
            'shared_toilet' => '',
        ]));

        $response->assertSessionHasErrors([
            'shared_toilet' => 'Please indicate whether the toilet facility is shared.',
        ]);
    }

    public function test_disposal_method_is_required_when_applicable(): void
    {
        $householdNo = $this->seedThroughStep12('HH-706');

        $response = $this->from(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]))->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), $this->validSanitationPayload($householdNo, [
            'sewage_disposal_method' => '',
        ]));

        $response->assertSessionHasErrors([
            'sewage_disposal_method' => 'Please select the excreta or sewage disposal method.',
        ]);
    }

    public function test_without_toilet_permits_null_disposal_method(): void
    {
        $householdNo = $this->seedThroughStep12('HH-707');

        $this->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'toilet_type' => 'without_toilet',
            'open_defecation_practiced' => 'no',
            'shared_toilet' => 'yes',
            'sewage_disposal_method' => 'on_site_safely_managed',
        ])->assertRedirect(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertTrue(array_key_exists('sewage_disposal_method', $record ?? []));
        $this->assertNull($record['sewage_disposal_method']);
        $this->assertSame('no', $record['shared_toilet'] ?? null);
        $this->assertSame(DemoHouseholdWaterSupply::TOILET_STATUS_UNSANITARY, $record['toilet_status'] ?? null);
        $this->assertSame(
            DemoHouseholdWaterSupply::MANAGEMENT_STATUS_NOT_SAFELY_MANAGED,
            $record['management_status'] ?? null
        );
    }

    public function test_without_toilet_normalizes_shared_toilet_to_no(): void
    {
        $householdNo = $this->seedThroughStep12('HH-708');

        $this->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'toilet_type' => 'without_toilet',
            'open_defecation_practiced' => 'yes',
            'shared_toilet' => 'yes',
        ])->assertRedirect();

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame('no', $record['shared_toilet'] ?? null);
    }

    public function test_switching_to_without_toilet_clears_stale_disposal_method(): void
    {
        $householdNo = $this->seedThroughStep12('HH-709');

        $this->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), $this->validSanitationPayload($householdNo, [
            'toilet_type' => 'pour_flush_with_septic_tank',
            'sewage_disposal_method' => 'off_site_collected_and_treated',
            'shared_toilet' => 'yes',
        ]))->assertRedirect();

        $first = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame('off_site_collected_and_treated', $first['sewage_disposal_method'] ?? null);
        $this->assertSame('yes', $first['shared_toilet'] ?? null);

        $this->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'toilet_type' => 'without_toilet',
            'open_defecation_practiced' => 'yes',
            'shared_toilet' => 'yes',
            'sewage_disposal_method' => 'off_site_collected_and_treated',
        ])->assertRedirect(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame('without_toilet', $record['toilet_type'] ?? null);
        $this->assertTrue(array_key_exists('sewage_disposal_method', $record ?? []));
        $this->assertNull($record['sewage_disposal_method']);
        $this->assertSame('no', $record['shared_toilet'] ?? null);
    }

    public function test_saved_values_reload_correctly(): void
    {
        $householdNo = $this->seedThroughStep12('HH-710');

        $this->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), $this->validSanitationPayload($householdNo, [
            'toilet_type' => 'ventilated_improved_pit_latrine',
            'open_defecation_practiced' => 'no',
            'shared_toilet' => 'no',
            'sewage_disposal_method' => 'off_site_collected_and_treated',
        ]))->assertRedirect();

        $page = $this->get(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));

        $page->assertOk();
        $page->assertSee('Basic Sanitation Facility', false);
        $page->assertSee('Step 2 of 4, current', false);
        $page->assertSee('Step 1 of 4, completed', false);
        $page->assertSee('Step 1.2 of 4, completed', false);
        $page->assertSee('value="ventilated_improved_pit_latrine"', false);
        $page->assertSee('selected', false);
        $page->assertSee('name="open_defecation_practiced"', false);
        $page->assertSee('value="no"', false);
        $page->assertSee('name="shared_toilet"', false);
        $page->assertSee('value="off_site_collected_and_treated"', false);
        $page->assertSee('SANITARY', false);
        $page->assertSee('Safely Managed', false);
        $page->assertSee('aria-live="polite"', false);
        $page->assertSee('lml-hws__management-badge', false);
        $page->assertSee('data-hws-management-badge', false);
    }

    public function test_existing_values_can_be_edited(): void
    {
        $householdNo = $this->seedThroughStep12('HH-711');

        $this->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), $this->validSanitationPayload($householdNo))->assertRedirect();

        $this->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), $this->validSanitationPayload($householdNo, [
            'toilet_type' => 'water_sealed_without_septic_tank',
            'open_defecation_practiced' => 'yes',
            'shared_toilet' => 'no',
            'sewage_disposal_method' => 'off_site_collected_and_treated',
        ]))->assertRedirect(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame('water_sealed_without_septic_tank', $record['toilet_type'] ?? null);
        $this->assertSame(DemoHouseholdWaterSupply::TOILET_STATUS_UNSANITARY, $record['toilet_status'] ?? null);
        $this->assertSame('yes', $record['open_defecation_practiced'] ?? null);
        $this->assertSame('no', $record['shared_toilet'] ?? null);
        $this->assertSame('off_site_collected_and_treated', $record['sewage_disposal_method'] ?? null);
    }

    public function test_cross_actor_access_is_rejected(): void
    {
        $householdNo = $this->seedThroughStep12('HH-712');

        $actorACanView = $this->get(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));
        $actorACanView->assertOk();

        $actorARecord = DemoHouseholdWaterSupply::find($householdNo);
        $actorAId = (string) ($actorARecord['actor_id'] ?? '');
        $this->assertNotSame('', $actorAId);

        $this->withSession([
            UiRole::SESSION_KEY => 'bhw',
            DemoSpotMappingHandoff::ACTOR_SESSION_KEY => '00000000-0000-4000-8000-00000000bbbb',
        ]);

        $this->assertNotSame($actorAId, DemoSpotMappingHandoff::actorKey());

        $this->get(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]))->assertRedirect(route('spot-mapping.index'));

        $this->from(route('spot-mapping.index'))->post(
            route('environmental-health.household-water-supply.step3.store', [
                'householdNo' => $householdNo,
            ]),
            $this->validSanitationPayload($householdNo)
        )->assertSessionHasErrors([
            'household_no' => DemoSpotMappingHandoff::INVALID_MESSAGE,
        ]);

        $unchanged = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame($actorAId, (string) ($unchanged['actor_id'] ?? ''));
        $this->assertSame(2, (int) ($unchanged['step'] ?? 0));
        $this->assertNull($unchanged['toilet_type'] ?? null);
    }

    public function test_unlinked_household_access_is_rejected(): void
    {
        $this->withSession([
            UiRole::SESSION_KEY => 'bhw',
        ]);

        $this->get(route('environmental-health.household-water-supply.step3', [
            'householdNo' => 'HH-999',
        ]))->assertRedirect(route('spot-mapping.index'));

        $this->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => 'HH-999',
        ]), $this->validSanitationPayload('HH-999'))->assertSessionHasErrors(['household_no']);
    }

    public function test_direct_step3_access_is_blocked_before_part2_completion(): void
    {
        $householdNo = $this->seedThroughStep12('HH-713');

        $this->get(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]))->assertRedirect(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));
    }

    public function test_successful_completion_redirects_to_part3(): void
    {
        $householdNo = $this->seedThroughStep12('HH-714');

        $response = $this->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), $this->validSanitationPayload($householdNo));

        $response->assertRedirect(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]));

        $this->assertTrue(DemoHouseholdWaterSupply::hasCompletedStep3($householdNo));

        $part3 = $this->get(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]));
        $part3->assertOk();
        $part3->assertSee('Step 3 of 4, current', false);
        $part3->assertSee(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]), false);
        $part3->assertSee('Solid Waste Management', false);
        $part3->assertSee('Waste Management Practices', false);
    }

    public function test_stepper_renders_labeled_steps_with_step_2_current(): void
    {
        $householdNo = $this->seedThroughStep12('HH-715');

        $page = $this->get(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));

        $page->assertOk();
        $page->assertSee('Step 1 of 4, completed', false);
        $page->assertSee('Step 1.2 of 4, completed', false);
        $page->assertSee('Step 2 of 4, current', false);
        $page->assertSee('Step 3 of 4, upcoming', false);
        $page->assertSee('lml-hws__stepper--labeled', false);
        $page->assertSee('Basic Sanitation Facility', false);
    }

    public function test_unsupported_toilet_type_is_rejected(): void
    {
        $householdNo = $this->seedThroughStep12('HH-716');

        $response = $this->from(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]))->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), $this->validSanitationPayload($householdNo, [
            'toilet_type' => 'not_a_real_toilet',
        ]));

        $response->assertSessionHasErrors(['toilet_type']);
    }

    public function test_pending_management_status_when_no_toilet_selected(): void
    {
        $householdNo = $this->seedThroughStep12('HH-730');

        $this->assertSame(
            DemoHouseholdWaterSupply::MANAGEMENT_STATUS_PENDING,
            DemoHouseholdWaterSupply::deriveManagementStatus('', null)
        );

        $page = $this->get(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));

        $page->assertOk();
        $page->assertSee('Not Yet Determined', false);
        $page->assertSee('Not yet determined', false);
        $page->assertSee('is-pending', false);
        $page->assertSee('lml-hws__management-badge', false);
    }

    public function test_pending_when_sanitary_toilet_without_disposal_method(): void
    {
        $this->assertSame(
            DemoHouseholdWaterSupply::MANAGEMENT_STATUS_PENDING,
            DemoHouseholdWaterSupply::deriveManagementStatus(
                'pour_flush_with_septic_tank',
                null
            )
        );
        $this->assertSame(
            DemoHouseholdWaterSupply::MANAGEMENT_STATUS_PENDING,
            DemoHouseholdWaterSupply::deriveManagementStatus(
                'pour_flush_with_septic_tank',
                ''
            )
        );
    }

    public function test_sanitary_plus_in_site_disposal_is_safely_managed(): void
    {
        $householdNo = $this->seedThroughStep12('HH-731');

        $this->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), $this->validSanitationPayload($householdNo, [
            'toilet_type' => 'pour_flush_with_septic_tank',
            'sewage_disposal_method' => DemoHouseholdWaterSupply::SEWAGE_ON_SITE,
        ]))->assertRedirect();

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame(
            DemoHouseholdWaterSupply::MANAGEMENT_STATUS_SAFELY_MANAGED,
            $record['management_status'] ?? null
        );
        $this->assertSame(
            DemoHouseholdWaterSupply::MANAGEMENT_STATUS_SAFELY_MANAGED,
            DemoHouseholdWaterSupply::deriveManagementStatus(
                'pour_flush_with_septic_tank',
                DemoHouseholdWaterSupply::SEWAGE_ON_SITE
            )
        );

        $page = $this->get(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));
        $page->assertOk();
        $page->assertSee('Safely Managed', false);
        $page->assertSee('SANITARY', false);
        $page->assertSee('is-safely-managed', false);
    }

    public function test_sanitary_plus_off_site_disposal_is_safely_managed(): void
    {
        $householdNo = $this->seedThroughStep12('HH-732');

        $this->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), $this->validSanitationPayload($householdNo, [
            'toilet_type' => 'ventilated_improved_pit_latrine',
            'sewage_disposal_method' => DemoHouseholdWaterSupply::SEWAGE_OFF_SITE,
        ]))->assertRedirect();

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame(
            DemoHouseholdWaterSupply::MANAGEMENT_STATUS_SAFELY_MANAGED,
            $record['management_status'] ?? null
        );

        $page = $this->get(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));
        $page->assertOk();
        $page->assertSee('Safely Managed', false);
        $page->assertSee('SANITARY', false);
    }

    public function test_unsanitary_toilet_is_not_safely_managed_regardless_of_disposal(): void
    {
        $householdNo = $this->seedThroughStep12('HH-733');

        $this->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), $this->validSanitationPayload($householdNo, [
            'toilet_type' => 'open_pit_latrine',
            'sewage_disposal_method' => DemoHouseholdWaterSupply::SEWAGE_ON_SITE,
        ]))->assertRedirect();

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame(
            DemoHouseholdWaterSupply::MANAGEMENT_STATUS_NOT_SAFELY_MANAGED,
            $record['management_status'] ?? null
        );
        $this->assertSame(
            DemoHouseholdWaterSupply::MANAGEMENT_STATUS_NOT_SAFELY_MANAGED,
            DemoHouseholdWaterSupply::deriveManagementStatus(
                'open_pit_latrine',
                DemoHouseholdWaterSupply::SEWAGE_OFF_SITE
            )
        );

        $page = $this->get(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));
        $page->assertOk();
        $page->assertSee('Not Safely Managed', false);
        $page->assertSee('UNSANITARY', false);
        $page->assertSee('is-not-safely-managed', false);
    }

    public function test_js_management_derivation_helpers_mirror_server_rules(): void
    {
        $jsPath = resource_path('js/pages/household-water-supply.js');
        $this->assertFileExists($jsPath);
        $js = file_get_contents($jsPath);
        $this->assertIsString($js);

        $this->assertStringContainsString('function deriveManagementStatus(toiletType, sewageDisposalMethod)', $js);
        $this->assertStringContainsString("return 'safely_managed'", $js);
        $this->assertStringContainsString("return 'not_safely_managed'", $js);
        $this->assertStringContainsString("return 'not_yet_determined'", $js);
        $this->assertStringContainsString('SEWAGE_DISPOSAL_METHODS', $js);
        $this->assertStringContainsString('data-hws-management-badge', $js);
        $this->assertStringContainsString('refreshStatusCard', $js);
    }

    public function test_status_card_responsive_classes_remain_present(): void
    {
        $householdNo = $this->seedThroughStep12('HH-734');

        $page = $this->get(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));

        $page->assertOk();
        $page->assertSee('lml-hws__toilet-top', false);
        $page->assertSee('lml-hws__toilet-status-header', false);
        $page->assertSee('lml-hws__management-badge', false);
        $page->assertSee('aria-live="polite"', false);
        $page->assertSee('aria-atomic="true"', false);
    }
}
