<?php

namespace Tests\Feature;

use App\Support\DemoHouseholdWaterSupply;
use App\Support\DemoSpotMappingHandoff;
use App\Support\UiRole;
use Tests\TestCase;

class HouseholdWaterSupplyStep2Test extends TestCase
{
    private function seedStep1Household(string $householdNo = 'HH-601'): string
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
            'client_marker_id' => 'client-marker-step2',
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

        return $householdNo;
    }

    public function test_blank_step2_submission_succeeds(): void
    {
        $householdNo = $this->seedStep1Household();

        $response = $this->post(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
        ]);

        $response->assertRedirect(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertNotNull($record);
        $this->assertSame(2, (int) ($record['step'] ?? 0));
        $this->assertNull($record['microbiological_test_date'] ?? null);
        $this->assertNull($record['microbiological_result'] ?? null);
        $this->assertNull($record['physicochemical_test_date'] ?? null);
        $this->assertNull($record['physicochemical_result'] ?? null);
        $this->assertSame('not_conducted', DemoHouseholdWaterSupply::validationTestingStatus($record));
    }

    public function test_microbiological_only_succeeds(): void
    {
        $householdNo = $this->seedStep1Household('HH-602');

        $response = $this->post(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'microbiological_test_date' => '2026-07-15',
            'microbiological_result' => 'passed',
        ]);

        $response->assertRedirect(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame('2026-07-15', $record['microbiological_test_date'] ?? null);
        $this->assertSame('passed', $record['microbiological_result'] ?? null);
        $this->assertNull($record['physicochemical_test_date'] ?? null);
        $this->assertNull($record['physicochemical_result'] ?? null);
        $this->assertSame('partially_recorded', DemoHouseholdWaterSupply::validationTestingStatus($record));
    }

    public function test_physicochemical_only_succeeds(): void
    {
        $householdNo = $this->seedStep1Household('HH-603');

        $response = $this->post(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'physicochemical_test_date' => '2026-07-16',
            'physicochemical_result' => 'failed',
        ]);

        $response->assertRedirect(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertNull($record['microbiological_test_date'] ?? null);
        $this->assertNull($record['microbiological_result'] ?? null);
        $this->assertSame('2026-07-16', $record['physicochemical_test_date'] ?? null);
        $this->assertSame('failed', $record['physicochemical_result'] ?? null);
        $this->assertSame('partially_recorded', DemoHouseholdWaterSupply::validationTestingStatus($record));
    }

    public function test_both_complete_succeeds(): void
    {
        $householdNo = $this->seedStep1Household('HH-604');

        $response = $this->post(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'microbiological_test_date' => '2026-07-10',
            'microbiological_result' => 'passed',
            'physicochemical_test_date' => '2026-07-11',
            'physicochemical_result' => 'failed',
        ]);

        $response->assertRedirect(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame('2026-07-10', $record['microbiological_test_date'] ?? null);
        $this->assertSame('passed', $record['microbiological_result'] ?? null);
        $this->assertSame('2026-07-11', $record['physicochemical_test_date'] ?? null);
        $this->assertSame('failed', $record['physicochemical_result'] ?? null);
        $this->assertSame('completed', DemoHouseholdWaterSupply::validationTestingStatus($record));
    }

    public function test_microbiological_date_without_result_fails(): void
    {
        $householdNo = $this->seedStep1Household('HH-605');

        $response = $this->from(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]))->post(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'microbiological_test_date' => '2026-07-15',
        ]);

        $response->assertRedirect(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]));
        $response->assertSessionHasErrors([
            'microbiological_result' => 'Please select the microbiological test result.',
        ]);
    }

    public function test_microbiological_result_without_date_fails(): void
    {
        $householdNo = $this->seedStep1Household('HH-606');

        $response = $this->from(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]))->post(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'microbiological_result' => 'passed',
        ]);

        $response->assertRedirect(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]));
        $response->assertSessionHasErrors([
            'microbiological_test_date' => 'Please select the microbiological test date.',
        ]);
    }

    public function test_physicochemical_date_without_result_fails(): void
    {
        $householdNo = $this->seedStep1Household('HH-607');

        $response = $this->from(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]))->post(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'physicochemical_test_date' => '2026-07-15',
        ]);

        $response->assertRedirect(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]));
        $response->assertSessionHasErrors([
            'physicochemical_result' => 'Please select the physico-chemical test result.',
        ]);
    }

    public function test_physicochemical_result_without_date_fails(): void
    {
        $householdNo = $this->seedStep1Household('HH-608');

        $response = $this->from(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]))->post(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'physicochemical_result' => 'failed',
        ]);

        $response->assertRedirect(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]));
        $response->assertSessionHasErrors([
            'physicochemical_test_date' => 'Please select the physico-chemical test date.',
        ]);
    }

    public function test_existing_saved_values_reload_correctly(): void
    {
        $householdNo = $this->seedStep1Household('HH-609');

        $this->post(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'microbiological_test_date' => '2026-07-12',
            'microbiological_result' => 'failed',
            'physicochemical_test_date' => '2026-07-13',
            'physicochemical_result' => 'passed',
        ])->assertRedirect();

        $page = $this->get(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]));

        $page->assertOk();
        $page->assertSee('Validation / Random Sampling / Testing', false);
        $page->assertSee('Step 1.2 of 4, current', false);
        $page->assertSee('Step 1 of 4, completed', false);
        $page->assertSee('1.2', false);
        $page->assertSee('value="2026-07-12"', false);
        $page->assertSee('value="2026-07-13"', false);
        $page->assertSee('name="microbiological_result"', false);
        $page->assertSee('value="failed"', false);
        $page->assertSee('name="physicochemical_result"', false);
        $page->assertSee('value="passed"', false);
        $page->assertSee('checked', false);
    }

    public function test_clearing_a_section_stores_null_values(): void
    {
        $householdNo = $this->seedStep1Household('HH-610');

        $this->post(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'microbiological_test_date' => '2026-07-12',
            'microbiological_result' => 'passed',
            'physicochemical_test_date' => '2026-07-13',
            'physicochemical_result' => 'failed',
        ])->assertRedirect();

        $response = $this->post(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'microbiological_test_date' => '',
            'microbiological_result' => '',
            'physicochemical_test_date' => '2026-07-13',
            'physicochemical_result' => 'failed',
        ]);

        $response->assertRedirect(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertNull($record['microbiological_test_date'] ?? null);
        $this->assertNull($record['microbiological_result'] ?? null);
        $this->assertSame('2026-07-13', $record['physicochemical_test_date'] ?? null);
        $this->assertSame('failed', $record['physicochemical_result'] ?? null);
        $this->assertSame('partially_recorded', DemoHouseholdWaterSupply::validationTestingStatus($record));
    }

    public function test_unauthorized_or_unlinked_household_access_remains_blocked(): void
    {
        $this->withSession([
            UiRole::SESSION_KEY => 'bhw',
        ]);

        $this->get(route('environmental-health.household-water-supply.step2', [
            'householdNo' => 'HH-999',
        ]))->assertRedirect(route('spot-mapping.index'));

        $this->post(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => 'HH-999',
        ]), [
            'household_no' => 'HH-999',
        ])->assertSessionHasErrors(['household_no']);

        $this->get(route('environmental-health.household-water-supply.step3', [
            'householdNo' => 'HH-999',
        ]))->assertRedirect(route('spot-mapping.index'));
    }

    public function test_cross_actor_cannot_view_or_submit_step2_for_another_actors_household(): void
    {
        $householdNo = $this->seedStep1Household('HH-612');

        $actorACanView = $this->get(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]));
        $actorACanView->assertOk();

        $actorARecord = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertNotNull($actorARecord);
        $actorAId = (string) ($actorARecord['actor_id'] ?? '');
        $this->assertNotSame('', $actorAId);

        // Switch to a different session actor while keeping Actor A's stored records.
        $this->withSession([
            UiRole::SESSION_KEY => 'bhw',
            DemoSpotMappingHandoff::ACTOR_SESSION_KEY => '00000000-0000-4000-8000-00000000bbbb',
        ]);

        $this->assertNotSame($actorAId, DemoSpotMappingHandoff::actorKey());

        $viewAsActorB = $this->get(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]));
        $viewAsActorB->assertRedirect(route('spot-mapping.index'));

        $submitAsActorB = $this->from(route('spot-mapping.index'))->post(
            route('environmental-health.household-water-supply.step2.store', [
                'householdNo' => $householdNo,
            ]),
            [
                'household_no' => $householdNo,
                'microbiological_test_date' => '2026-07-20',
                'microbiological_result' => 'passed',
            ]
        );
        $submitAsActorB->assertSessionHasErrors([
            'household_no' => DemoSpotMappingHandoff::INVALID_MESSAGE,
        ]);

        // Actor A's stored Step 1/2 payload must remain untouched.
        $unchanged = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertNotNull($unchanged);
        $this->assertSame($actorAId, (string) ($unchanged['actor_id'] ?? ''));
        $this->assertSame(1, (int) ($unchanged['step'] ?? 0));
        $this->assertNull($unchanged['microbiological_test_date'] ?? null);
        $this->assertNull($unchanged['microbiological_result'] ?? null);
    }

    public function test_previous_and_next_workflow_routes_resolve_correctly(): void
    {
        $householdNo = $this->seedStep1Household('HH-611');

        $step2 = $this->get(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]));
        $step2->assertOk();
        $step2->assertSee(route('environmental-health.household-water-supply', [
            'household' => $householdNo,
        ]), false);
        $step2->assertSee(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => $householdNo,
        ]), false);

        $this->post(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
        ])->assertRedirect(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));

        $step3 = $this->get(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));
        $step3->assertOk();
        $step3->assertSee('Basic Sanitation Facility', false);
        $step3->assertSee(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]), false);
        $step3->assertSee('Step 2 of 4, current', false);

        $this->assertTrue(DemoHouseholdWaterSupply::hasCompletedStep2($householdNo));
        $this->assertNotSame(
            DemoSpotMappingHandoff::INVALID_MESSAGE,
            ''
        );
    }

    public function test_matching_route_and_body_household_number_succeeds(): void
    {
        $householdNo = $this->seedStep1Household('HH-620');

        $response = $this->post(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'microbiological_test_date' => '2026-07-21',
            'microbiological_result' => 'passed',
        ]);

        $response->assertRedirect(route('environmental-health.household-water-supply.step3', [
            'householdNo' => $householdNo,
        ]));
        $response->assertSessionDoesntHaveErrors();

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertNotNull($record);
        $this->assertSame(2, (int) ($record['step'] ?? 0));
        $this->assertSame('2026-07-21', $record['microbiological_test_date'] ?? null);
        $this->assertSame('passed', $record['microbiological_result'] ?? null);
    }

    public function test_mismatched_route_and_body_household_number_is_rejected(): void
    {
        $routeHouseholdNo = $this->seedStep1Household('HH-621');
        $bodyHouseholdNo = $this->seedStep1Household('HH-622');

        $beforeRoute = DemoHouseholdWaterSupply::find($routeHouseholdNo);
        $beforeBody = DemoHouseholdWaterSupply::find($bodyHouseholdNo);
        $this->assertNotNull($beforeRoute);
        $this->assertNotNull($beforeBody);
        $this->assertSame(1, (int) ($beforeRoute['step'] ?? 0));
        $this->assertSame(1, (int) ($beforeBody['step'] ?? 0));

        $response = $this->from(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $routeHouseholdNo,
        ]))->post(route('environmental-health.household-water-supply.step2.store', [
            'householdNo' => $routeHouseholdNo,
        ]), [
            'household_no' => $bodyHouseholdNo,
            'microbiological_test_date' => '2026-07-22',
            'microbiological_result' => 'failed',
        ]);

        $response->assertRedirect(route('environmental-health.household-water-supply.step2', [
            'householdNo' => $routeHouseholdNo,
        ]));
        $response->assertSessionHasErrors([
            'household_no' => 'The household number does not match this form URL.',
        ]);

        $afterRoute = DemoHouseholdWaterSupply::find($routeHouseholdNo);
        $afterBody = DemoHouseholdWaterSupply::find($bodyHouseholdNo);

        $this->assertNotNull($afterRoute);
        $this->assertNotNull($afterBody);
        $this->assertSame(1, (int) ($afterRoute['step'] ?? 0));
        $this->assertSame(1, (int) ($afterBody['step'] ?? 0));
        $this->assertNull($afterRoute['microbiological_test_date'] ?? null);
        $this->assertNull($afterRoute['microbiological_result'] ?? null);
        $this->assertNull($afterBody['microbiological_test_date'] ?? null);
        $this->assertNull($afterBody['microbiological_result'] ?? null);
        $this->assertFalse(DemoHouseholdWaterSupply::hasCompletedStep2($routeHouseholdNo));
        $this->assertFalse(DemoHouseholdWaterSupply::hasCompletedStep2($bodyHouseholdNo));
    }
}
