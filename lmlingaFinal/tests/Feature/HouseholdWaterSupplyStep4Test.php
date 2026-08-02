<?php

namespace Tests\Feature;

use App\Support\DemoHouseholdWaterSupply;
use App\Support\DemoSpotMappingHandoff;
use App\Support\UiRole;
use Tests\TestCase;

class HouseholdWaterSupplyStep4Test extends TestCase
{
    private function seedThroughStep3(string $householdNo = 'HH-801'): string
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
            'client_marker_id' => 'client-marker-step4',
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

        $this->post(route('environmental-health.household-water-supply.step3.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'toilet_type' => 'pour_flush_with_septic_tank',
            'open_defecation_practiced' => 'no',
            'shared_toilet' => 'no',
            'sewage_disposal_method' => 'on_site_safely_managed',
        ])->assertRedirect(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]));

        return $householdNo;
    }

    public function test_at_least_one_practice_is_required(): void
    {
        $householdNo = $this->seedThroughStep3('HH-802');

        $response = $this->from(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]))->post(route('environmental-health.household-water-supply.step4.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
        ]);

        $response->assertRedirect(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]));
        $response->assertSessionHasErrors([
            'solid_waste_practices' => 'Please select at least one solid waste management practice.',
        ]);
    }

    public function test_invalid_practice_value_is_rejected(): void
    {
        $householdNo = $this->seedThroughStep3('HH-803');

        $response = $this->from(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]))->post(route('environmental-health.household-water-supply.step4.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'solid_waste_practices' => ['waste_segregation', 'bad_value'],
        ]);

        $response->assertRedirect(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]));
        $response->assertSessionHasErrors(['solid_waste_practices.1']);
    }

    public function test_multiple_practices_are_persisted_and_marked_good_practice(): void
    {
        $householdNo = $this->seedThroughStep3('HH-804');

        $this->post(route('environmental-health.household-water-supply.step4.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'solid_waste_practices' => [
                DemoHouseholdWaterSupply::SOLID_WASTE_WASTE_SEGREGATION,
                DemoHouseholdWaterSupply::SOLID_WASTE_RECYCLING_REUSE,
                DemoHouseholdWaterSupply::SOLID_WASTE_MUNICIPAL_COLLECTION,
            ],
        ])->assertRedirect(route('spot-mapping.index'));

        $record = DemoHouseholdWaterSupply::find($householdNo);

        $this->assertSame([
            DemoHouseholdWaterSupply::SOLID_WASTE_WASTE_SEGREGATION,
            DemoHouseholdWaterSupply::SOLID_WASTE_RECYCLING_REUSE,
            DemoHouseholdWaterSupply::SOLID_WASTE_MUNICIPAL_COLLECTION,
        ], $record['solid_waste_practices'] ?? null);
        $this->assertSame('good_practice', $record['solid_waste_status'] ?? null);
        $this->assertSame(4, (int) ($record['step'] ?? 0));
        $this->assertTrue(DemoHouseholdWaterSupply::hasCompletedStep4($householdNo));
    }

    public function test_saved_practices_reload_on_step4_page(): void
    {
        $householdNo = $this->seedThroughStep3('HH-805');

        DemoHouseholdWaterSupply::saveStep4($householdNo, [
            'household_no' => $householdNo,
            'solid_waste_practices' => [
                DemoHouseholdWaterSupply::SOLID_WASTE_BACKYARD_COMPOSTING,
            ],
        ]);

        $page = $this->get(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]));

        $page->assertOk();
        $page->assertSee('Solid Waste Management', false);
        $page->assertSee('Waste Management Practices', false);
        $page->assertSee('value="backyard_composting"', false);
        $page->assertSee('GOOD PRACTICE', false);
    }

    public function test_single_valid_selection_persists_as_good_practice(): void
    {
        $householdNo = $this->seedThroughStep3('HH-806');

        $this->post(route('environmental-health.household-water-supply.step4.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'solid_waste_practices' => [
                DemoHouseholdWaterSupply::SOLID_WASTE_WASTE_SEGREGATION,
            ],
        ])->assertRedirect(route('spot-mapping.index'));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame([
            DemoHouseholdWaterSupply::SOLID_WASTE_WASTE_SEGREGATION,
        ], $record['solid_waste_practices'] ?? null);
        $this->assertSame('good_practice', $record['solid_waste_status'] ?? null);
    }

    public function test_all_four_selections_persist_as_good_practice(): void
    {
        $householdNo = $this->seedThroughStep3('HH-807');

        $this->post(route('environmental-health.household-water-supply.step4.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'solid_waste_practices' => DemoHouseholdWaterSupply::solidWastePracticeValues(),
        ])->assertRedirect(route('spot-mapping.index'));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame(DemoHouseholdWaterSupply::solidWastePracticeValues(), $record['solid_waste_practices'] ?? null);
        $this->assertSame('good_practice', $record['solid_waste_status'] ?? null);
    }

    public function test_duplicate_practice_submissions_are_normalized_to_one_value(): void
    {
        $householdNo = $this->seedThroughStep3('HH-808');

        $this->post(route('environmental-health.household-water-supply.step4.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'solid_waste_practices' => [
                DemoHouseholdWaterSupply::SOLID_WASTE_WASTE_SEGREGATION,
                DemoHouseholdWaterSupply::SOLID_WASTE_WASTE_SEGREGATION,
            ],
        ])->assertRedirect(route('spot-mapping.index'));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame([
            DemoHouseholdWaterSupply::SOLID_WASTE_WASTE_SEGREGATION,
        ], $record['solid_waste_practices'] ?? null);
        $this->assertSame('good_practice', $record['solid_waste_status'] ?? null);
    }

    public function test_browser_supplied_solid_waste_status_cannot_override_server_derivation(): void
    {
        $householdNo = $this->seedThroughStep3('HH-809');

        $this->post(route('environmental-health.household-water-supply.step4.store', [
            'householdNo' => $householdNo,
        ]), [
            'household_no' => $householdNo,
            'solid_waste_practices' => [
                DemoHouseholdWaterSupply::SOLID_WASTE_MUNICIPAL_COLLECTION,
            ],
            'solid_waste_status' => 'good_practice',
        ])->assertRedirect(route('spot-mapping.index'));

        $record = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame('good_practice', $record['solid_waste_status'] ?? null);
        $this->assertSame([
            DemoHouseholdWaterSupply::SOLID_WASTE_MUNICIPAL_COLLECTION,
        ], $record['solid_waste_practices'] ?? null);

        $householdNoTwo = $this->seedThroughStep3('HH-809B');

        $this->from(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNoTwo,
        ]))->post(route('environmental-health.household-water-supply.step4.store', [
            'householdNo' => $householdNoTwo,
        ]), [
            'household_no' => $householdNoTwo,
            'solid_waste_status' => 'good_practice',
        ])->assertSessionHasErrors(['solid_waste_practices']);

        $this->assertNull(DemoHouseholdWaterSupply::find($householdNoTwo)['solid_waste_status'] ?? null);
    }

    public function test_cross_actor_access_is_rejected_for_step4(): void
    {
        $householdNo = $this->seedThroughStep3('HH-810');

        $this->get(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]))->assertOk();

        $actorARecord = DemoHouseholdWaterSupply::find($householdNo);
        $actorAId = (string) ($actorARecord['actor_id'] ?? '');
        $this->assertNotSame('', $actorAId);

        $this->withSession([
            UiRole::SESSION_KEY => 'bhw',
            DemoSpotMappingHandoff::ACTOR_SESSION_KEY => '00000000-0000-4000-8000-00000000cccc',
        ]);

        $this->assertNotSame($actorAId, DemoSpotMappingHandoff::actorKey());

        $this->get(route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]))->assertRedirect(route('spot-mapping.index'));

        $this->from(route('spot-mapping.index'))->post(
            route('environmental-health.household-water-supply.step4.store', [
                'householdNo' => $householdNo,
            ]),
            [
                'household_no' => $householdNo,
                'solid_waste_practices' => [
                    DemoHouseholdWaterSupply::SOLID_WASTE_WASTE_SEGREGATION,
                ],
            ]
        )->assertSessionHasErrors([
            'household_no' => DemoSpotMappingHandoff::INVALID_MESSAGE,
        ]);

        $unchanged = DemoHouseholdWaterSupply::find($householdNo);
        $this->assertSame($actorAId, (string) ($unchanged['actor_id'] ?? ''));
        $this->assertSame(3, (int) ($unchanged['step'] ?? 0));
        $this->assertNull($unchanged['solid_waste_practices'] ?? null);
    }
}
