<?php

namespace Tests\Feature;

use App\Support\DemoHouseholdWaterSupply;
use App\Support\DemoSpotMappingHandoff;
use App\Support\UiRole;
use Tests\TestCase;

class HouseholdAmenitiesDetailsFlowTest extends TestCase
{
    private bool $seedSessionReady = false;

    private function seedStep4Record(string $householdNo, array $overrides = []): void
    {
        if (! $this->seedSessionReady) {
            $this->withSession([UiRole::SESSION_KEY => 'bhw']);
            $this->seedSessionReady = true;
        }

        $payload = [
            'household_no' => $householdNo,
            'house_head' => 'Test Head',
            'household_type' => 'HHTS',
            'zone' => '1',
            'lat' => 13.3811,
            'lng' => 123.4306,
            'consent' => true,
            'client_marker_id' => 'client-marker-amenities-'.$householdNo.'-'.uniqid('', true),
        ];

        $issue = $this->postJson(route('spot-mapping.plot-handoff'), $payload)->assertOk();
        $token = (string) $issue->json('handoff_token');
        $this->get(route('environmental-health.household-water-supply', ['handoff' => $token]))->assertRedirect();

        DemoHouseholdWaterSupply::saveStep1($householdNo, array_merge([
            'household_no' => $householdNo,
            'water_supply_status' => 'level_i',
            'water_source_location' => 'yes',
            'water_availability' => 'yes',
            'specify_water_source' => null,
        ], $overrides['step1'] ?? []));

        DemoHouseholdWaterSupply::saveStep2($householdNo, array_merge([
            'household_no' => $householdNo,
            'microbiological_test_date' => '2026-07-20',
            'microbiological_result' => 'passed',
            'physicochemical_test_date' => '2026-07-22',
            'physicochemical_result' => 'failed',
        ], $overrides['step2'] ?? []));

        DemoHouseholdWaterSupply::saveStep3($householdNo, array_merge([
            'household_no' => $householdNo,
            'toilet_type' => 'pour_flush_with_septic_tank',
            'open_defecation_practiced' => 'no',
            'shared_toilet' => 'no',
            'sewage_disposal_method' => 'on_site_safely_managed',
        ], $overrides['step3'] ?? []));

        DemoHouseholdWaterSupply::saveStep4($householdNo, array_merge([
            'household_no' => $householdNo,
            'solid_waste_practices' => DemoHouseholdWaterSupply::solidWastePracticeValues(),
        ], $overrides['step4'] ?? []));
    }

    public function test_valid_household_can_open_amenities_details(): void
    {
        $response = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-151']));
        $response->assertOk();
        $response->assertSee('Household Amenities Details', false);
    }

    public function test_view_household_details_button_points_to_named_route(): void
    {
        $response = $this->get(route('household-profiling.view', ['householdNo' => 'HH-151']));
        $response->assertSee('href="'.e(route('household-profiling.amenities.show', ['householdNo' => 'HH-151'])).'"', false);
    }

    public function test_unknown_household_is_handled_safely(): void
    {
        $response = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-999']));
        $response->assertOk();
        $response->assertSee('Household not found', false);
    }

    public function test_cross_actor_cannot_access_other_actor_saved_record(): void
    {
        $this->seedStep4Record('HH-152');
        $record = DemoHouseholdWaterSupply::find('HH-152');
        $this->assertNotNull($record);

        $this->withSession([
            UiRole::SESSION_KEY => 'bhw',
            DemoSpotMappingHandoff::ACTOR_SESSION_KEY => '00000000-0000-4000-8000-00000000aaaa',
        ]);

        $response = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-152']));
        $response->assertOk();
        $response->assertDontSee('2026-07-20', false);
        $response->assertDontSee('data-water-level="level_i" aria-current="true"', false);
    }

    public function test_level_i_appears_selected_on_details(): void
    {
        $this->seedStep4Record('HH-151', ['step1' => ['water_supply_status' => 'level_i']]);
        $html = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-151']))->getContent();

        $this->assertStringContainsString('data-water-level="level_i"', $html);
        $this->assertMatchesRegularExpression('/data-water-level="level_i"[^>]*aria-current="true"/', $html);
        $this->assertMatchesRegularExpression('/lml-amenities__level-card is-selected[^>]*data-water-level="level_i"|data-water-level="level_i"[^>]*class="[^"]*is-selected/', $html);
    }

    public function test_level_ii_appears_selected_on_details(): void
    {
        $this->seedStep4Record('HH-152', ['step1' => ['water_supply_status' => 'level_ii']]);
        $html = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-152']))->getContent();

        $this->assertMatchesRegularExpression('/data-water-level="level_ii"[^>]*aria-current="true"/', $html);
        $this->assertStringContainsString('With Basic Safe Water', $html);
    }

    public function test_level_iii_appears_selected_on_details(): void
    {
        $this->seedStep4Record('HH-153', ['step1' => ['water_supply_status' => 'level_iii']]);
        $html = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-153']))->getContent();

        $this->assertMatchesRegularExpression('/data-water-level="level_iii"[^>]*aria-current="true"/', $html);
        $this->assertStringContainsString('With Basic Safe Water', $html);
    }

    public function test_others_with_specification_renders_on_details(): void
    {
        $this->seedStep4Record('HH-154', [
            'step1' => [
                'water_supply_status' => 'others',
                'specify_water_source' => 'Deep dug well',
            ],
        ]);
        $response = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-154']));
        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/data-water-level="others"[^>]*aria-current="true"/', $html);
        $response->assertSee('Without Basic Safe Water', false);
        $response->assertSee('Deep dug well', false);
        $response->assertSee('doubtful source', false);
    }

    public function test_water_location_and_availability_yes_no_render_selected(): void
    {
        $this->seedStep4Record('HH-155', [
            'step1' => [
                'water_source_location' => 'no',
                'water_availability' => 'yes',
            ],
        ]);
        $html = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-155']))->getContent();

        $this->assertStringContainsString('aria-label="Water Source Location"', $html);
        $this->assertMatchesRegularExpression(
            '/aria-label="Water Source Location"[\s\S]*?is-selected[\s\S]*?>\s*No\s*</',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/aria-label="Water Availability"[\s\S]*?is-selected[\s\S]*?>\s*Yes\s*</',
            $html
        );
    }

    public function test_microbiological_and_physico_values_render(): void
    {
        $this->seedStep4Record('HH-156');
        $response = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-156']));
        $response->assertSee('2026-07-20', false);
        $response->assertSee('Passed', false);
        $response->assertSee('2026-07-22', false);
        $response->assertSee('Failed', false);
        $response->assertSee('Completed', false);
    }

    public function test_not_conducted_displays_when_tests_absent(): void
    {
        $this->seedStep4Record('HH-151', [
            'step2' => [
                'microbiological_test_date' => null,
                'microbiological_result' => null,
                'physicochemical_test_date' => null,
                'physicochemical_result' => null,
            ],
        ]);
        $response = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-151']));
        $response->assertSee('Not Conducted', false);
    }

    public function test_sanitary_in_site_disposed_displays_safely_managed(): void
    {
        $this->seedStep4Record('HH-152', [
            'step3' => [
                'toilet_type' => 'pour_flush_with_septic_tank',
                'sewage_disposal_method' => 'on_site_safely_managed',
            ],
        ]);
        $response = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-152']));
        $response->assertSee('Pour/Flush Type with Septic Tank', false);
        $response->assertSee('In-site Disposed', false);
        $response->assertSee('Safely Managed', false);
    }

    public function test_sanitary_off_site_disposed_displays_safely_managed(): void
    {
        $this->seedStep4Record('HH-153', [
            'step3' => [
                'toilet_type' => 'pour_flush_connected_to_septic_or_sewer',
                'sewage_disposal_method' => 'off_site_collected_and_treated',
            ],
        ]);
        $response = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-153']));
        $response->assertSee('Off-site Disposed', false);
        $response->assertSee('Safely Managed', false);
    }

    public function test_unsanitary_displays_not_safely_managed(): void
    {
        $this->seedStep4Record('HH-154', ['step3' => ['toilet_type' => 'open_pit_latrine']]);
        $response = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-154']));
        $response->assertSee('Open Pit Latrine', false);
        $response->assertSee('Not Safely Managed', false);
    }

    public function test_solid_waste_checked_and_unchecked_states_render(): void
    {
        $this->seedStep4Record('HH-155', [
            'step4' => [
                'solid_waste_practices' => [
                    DemoHouseholdWaterSupply::SOLID_WASTE_WASTE_SEGREGATION,
                    DemoHouseholdWaterSupply::SOLID_WASTE_RECYCLING_REUSE,
                ],
            ],
        ]);
        $html = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-155']))->getContent();

        $this->assertMatchesRegularExpression(
            '/class="[^"]*is-checked[^"]*"\s+data-practice="waste_segregation"/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/class="[^"]*is-checked[^"]*"\s+data-practice="recycling_reuse"/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/class="[^"]*is-checked[^"]*"\s+data-practice="backyard_composting"/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/class="[^"]*is-checked[^"]*"\s+data-practice="municipal_collection"/',
            $html
        );
        $this->assertStringContainsString('Waste Segregation', $html);
        $this->assertStringContainsString('Backyard Composting', $html);
    }

    public function test_household_a_cannot_display_household_b_amenities(): void
    {
        $this->seedStep4Record('HH-151', [
            'step1' => [
                'water_supply_status' => 'level_i',
                'specify_water_source' => null,
            ],
            'step2' => [
                'microbiological_test_date' => '2026-01-01',
                'microbiological_result' => 'passed',
            ],
        ]);
        $this->seedStep4Record('HH-152', [
            'step1' => [
                'water_supply_status' => 'others',
                'specify_water_source' => 'Household B Spring',
            ],
            'step2' => [
                'microbiological_test_date' => '2026-02-02',
                'microbiological_result' => 'failed',
            ],
        ]);

        $this->assertNotNull(DemoHouseholdWaterSupply::findForActor('HH-151'));
        $this->assertNotNull(DemoHouseholdWaterSupply::findForActor('HH-152'));

        $a = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-151']))->getContent();
        $b = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-152']))->getContent();

        $this->assertStringContainsString('2026-01-01', $a);
        $this->assertStringNotContainsString('Household B Spring', $a);
        $this->assertStringContainsString('Household B Spring', $b);
        $this->assertStringNotContainsString('2026-01-01', $b);
        $this->assertNotSame($a, $b);
    }

    public function test_with_basic_safe_water_displays_for_level_i_to_iii(): void
    {
        $this->seedStep4Record('HH-153', ['step1' => ['water_supply_status' => 'level_ii']]);
        $response = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-153']));
        $response->assertSee('With Basic Safe Water', false);
    }

    public function test_without_basic_safe_water_displays_for_others(): void
    {
        $this->seedStep4Record('HH-154', ['step1' => ['water_supply_status' => 'others', 'specify_water_source' => 'Spring']]);
        $response = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-154']));
        $response->assertSee('Without Basic Safe Water', false);
    }

    public function test_all_four_solid_waste_practices_render(): void
    {
        $this->seedStep4Record('HH-151');
        $response = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-151']));
        $response->assertSee('Waste Segregation', false);
        $response->assertSee('Backyard Composting', false);
        $response->assertSee('Recycling / Reuse', false);
        $response->assertSee('Collected by Municipality / Municipal Collection and Disposal System', false);
    }

    public function test_summary_values_are_data_driven(): void
    {
        $this->seedStep4Record('HH-152', ['step3' => ['toilet_type' => 'open_pit_latrine']]);
        $this->seedStep4Record('HH-153', ['step3' => ['toilet_type' => 'pour_flush_with_septic_tank']]);

        $a = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-152']))->getContent();
        $b = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-153']))->getContent();

        $this->assertNotSame($a, $b);
        $this->assertStringContainsString('Not Safely Managed', $a);
        $this->assertStringContainsString('Safely Managed', $b);
    }

    public function test_edit_page_loads_existing_values(): void
    {
        $this->seedStep4Record('HH-154', [
            'step1' => [
                'water_supply_status' => 'level_iii',
                'water_source_location' => 'no',
                'water_availability' => 'yes',
            ],
        ]);
        $response = $this->get(route('household-profiling.amenities.edit', ['householdNo' => 'HH-154']));
        $response->assertOk();
        $response->assertSee('value="2026-07-20"', false);
        $response->assertSee('on_site_safely_managed', false);
        $response->assertSee('value="level_iii"', false);
        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/name="water_source_location"\s+value="no"[^>]*checked|value="no"[^>]*name="water_source_location"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/name="water_availability"\s+value="yes"[^>]*checked|value="yes"[^>]*name="water_availability"[^>]*checked/', $html);
    }

    public function test_valid_update_persists_and_redirects_to_details(): void
    {
        $this->seedStep4Record('HH-155');
        $response = $this->put(route('household-profiling.amenities.update', ['householdNo' => 'HH-155']), [
            'household_no' => 'HH-155',
            'water_supply_status' => 'others',
            'specify_water_source' => 'Open well',
            'water_source_location' => 'yes',
            'water_availability' => 'yes',
            'microbiological_test_date' => '',
            'microbiological_result' => '',
            'physicochemical_test_date' => '',
            'physicochemical_result' => '',
            'toilet_type' => 'open_pit_latrine',
            'open_defecation_practiced' => 'yes',
            'shared_toilet' => 'no',
            'sewage_disposal_method' => 'off_site_collected_and_treated',
            'solid_waste_practices' => ['waste_segregation'],
        ]);

        $response->assertRedirect(route('household-profiling.amenities.show', ['householdNo' => 'HH-155']));
        $this->assertSame('others', DemoHouseholdWaterSupply::find('HH-155')['water_supply_status'] ?? null);

        $details = $this->followRedirects($response);
        $details->assertSee('Open well', false);
        $details->assertSee('Without Basic Safe Water', false);
        $details->assertSee('Not Safely Managed', false);
    }

    public function test_invalid_update_returns_errors_and_preserves_input(): void
    {
        $this->seedStep4Record('HH-156');
        $response = $this->from(route('household-profiling.amenities.edit', ['householdNo' => 'HH-156']))
            ->put(route('household-profiling.amenities.update', ['householdNo' => 'HH-156']), [
                'household_no' => 'HH-156',
                'water_supply_status' => 'others',
                'specify_water_source' => '',
                'water_source_location' => '',
                'water_availability' => '',
                'toilet_type' => '',
                'open_defecation_practiced' => '',
                'solid_waste_practices' => [],
            ]);

        $response->assertRedirect(route('household-profiling.amenities.edit', ['householdNo' => 'HH-156']));
        $response->assertSessionHasErrors(['specify_water_source', 'water_source_location', 'toilet_type']);
    }

    public function test_browser_submitted_computed_statuses_cannot_override_server_derivation(): void
    {
        $this->seedStep4Record('HH-151');

        $this->put(route('household-profiling.amenities.update', ['householdNo' => 'HH-151']), [
            'household_no' => 'HH-151',
            'water_supply_status' => 'level_i',
            'water_source_location' => 'yes',
            'water_availability' => 'yes',
            'microbiological_test_date' => '',
            'microbiological_result' => '',
            'physicochemical_test_date' => '',
            'physicochemical_result' => '',
            'toilet_type' => 'open_pit_latrine',
            'open_defecation_practiced' => 'yes',
            'shared_toilet' => 'no',
            'sewage_disposal_method' => 'off_site_collected_and_treated',
            'solid_waste_practices' => ['waste_segregation'],
            'basic_safe_water_status' => 'without_basic_safe_water',
            'management_status' => 'safely_managed',
            'solid_waste_status' => 'not_yet_determined',
        ])->assertRedirect();

        $record = DemoHouseholdWaterSupply::find('HH-151');
        $this->assertSame(DemoHouseholdWaterSupply::BASIC_SAFE_WATER_WITH, $record['basic_safe_water_status'] ?? null);
        $this->assertSame(DemoHouseholdWaterSupply::MANAGEMENT_STATUS_NOT_SAFELY_MANAGED, $record['management_status'] ?? null);
    }

    public function test_close_and_back_links_resolve_correctly(): void
    {
        $show = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-151']));
        $show->assertSee(route('household-profiling.view', ['householdNo' => 'HH-151']), false);
        $show->assertSee(route('household-profiling.amenities.edit', ['householdNo' => 'HH-151']), false);

        $edit = $this->get(route('household-profiling.amenities.edit', ['householdNo' => 'HH-151']));
        $edit->assertSee(route('household-profiling.amenities.show', ['householdNo' => 'HH-151']), false);
    }

    public function test_socioeconomic_status_comes_from_handoff_link(): void
    {
        $this->seedStep4Record('HH-152');
        $response = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-152']));
        $response->assertSee('NHTS', false);
        $response->assertSee('class="visually-hidden">Socioeconomic Status</span>', false);
        $response->assertSee('class="visually-hidden">Household Number</span>', false);
        $response->assertSee('class="visually-hidden">Household Head</span>', false);
        $response->assertSee('class="visually-hidden">Zone / Street</span>', false);
        $response->assertDontSee('aria-label="Socioeconomic Status:', false);
        $response->assertDontSee('aria-label="Household Number:', false);
        $response->assertSee('bi-house-door-fill', false);
        $response->assertSee('bi-person-vcard', false);
        $response->assertSee('bi-person-fill', false);
        $response->assertSee('bi-geo-alt-fill', false);
    }

    public function test_each_profiling_household_opens_amenities_details(): void
    {
        foreach (DemoHouseholdWaterSupply::profilingDemoHouseholdNos() as $householdNo) {
            $this->get(route('household-profiling.amenities.show', ['householdNo' => $householdNo]))
                ->assertOk()
                ->assertSee('Household Amenities Details', false)
                ->assertDontSee('Household not found', false);
        }
    }

    public function test_each_profiling_household_shows_own_head_and_context(): void
    {
        $heads = [
            'HH-151' => 'Kristine Reyes',
            'HH-152' => 'Carlo Evangelista',
            'HH-153' => 'Adrian Corporal',
            'HH-154' => 'Maria Santos',
            'HH-155' => 'Juan dela Cruz',
            'HH-156' => 'Rosa Lim',
        ];

        foreach ($heads as $householdNo => $head) {
            $html = $this->get(route('household-profiling.amenities.show', ['householdNo' => $householdNo]))->getContent();
            $this->assertStringContainsString($head, $html);
            foreach ($heads as $otherNo => $otherHead) {
                if ($otherNo === $householdNo) {
                    continue;
                }
                $this->assertStringNotContainsString($otherHead, $html);
            }
        }
    }

    public function test_profiling_demo_households_have_distinct_amenities_values(): void
    {
        $markers = [
            'HH-151' => ['level_iii', 'With Basic Safe Water', '2026-06-10', 'Safely Managed', 'Good Practice'],
            'HH-152' => ['others', 'Open dug well', 'Without Basic Safe Water', 'Not Conducted', 'Not Safely Managed'],
            'HH-153' => ['level_ii', '2026-05-01', 'Open Pit Latrine', 'Not Safely Managed'],
            'HH-154' => ['level_i', 'Partially Recorded', 'Safely Managed'],
            'HH-155' => ['level_iii', '2026-03-08', 'Safely Managed', 'Good Practice'],
            'HH-156' => ['Not yet determined', 'Not Conducted', 'Not Yet Determined'],
        ];

        $pages = [];
        foreach ($markers as $householdNo => $needles) {
            $html = $this->get(route('household-profiling.amenities.show', ['householdNo' => $householdNo]))->getContent();
            $pages[$householdNo] = $html;
            foreach ($needles as $needle) {
                $this->assertStringContainsString($needle, $html, $householdNo.' missing '.$needle);
            }
        }

        $this->assertStringNotContainsString('Open dug well', $pages['HH-151']);
        $this->assertStringNotContainsString('2026-06-10', $pages['HH-152']);
        $this->assertStringNotContainsString('Open dug well', $pages['HH-153']);
        $this->assertStringNotContainsString('2026-05-01', $pages['HH-152']);
        $this->assertNotSame($pages['HH-151'], $pages['HH-152']);
        $this->assertNotSame($pages['HH-152'], $pages['HH-153']);
    }

    public function test_details_button_resolves_to_matching_household_for_each_listing(): void
    {
        foreach (DemoHouseholdWaterSupply::profilingDemoHouseholdNos() as $householdNo) {
            $this->get(route('household-profiling.view', ['householdNo' => $householdNo]))
                ->assertSee(
                    'href="'.e(route('household-profiling.amenities.show', ['householdNo' => $householdNo])).'"',
                    false
                );
        }
    }

    public function test_edit_page_loads_profiling_demo_record_for_same_household(): void
    {
        $show = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-152']))->getContent();
        $edit = $this->get(route('household-profiling.amenities.edit', ['householdNo' => 'HH-152']));
        $edit->assertOk();
        $editHtml = $edit->getContent();

        $this->assertStringContainsString('Open dug well', $show);
        $this->assertStringContainsString('Open dug well', $editHtml);
        $this->assertStringContainsString('value="others"', $editHtml);
        $this->assertStringContainsString('Carlo Evangelista', $editHtml);
        $this->assertMatchesRegularExpression(
            '/data-water-level="others"[^>]*aria-current="true"|value="others"[^>]*checked/',
            $show."\n".$editHtml
        );
    }

    public function test_cross_actor_does_not_leak_saved_eh_record_into_profiling_demo(): void
    {
        $this->seedStep4Record('HH-152', [
            'step1' => [
                'water_supply_status' => 'level_i',
                'specify_water_source' => null,
            ],
            'step2' => [
                'microbiological_test_date' => '2026-07-20',
                'microbiological_result' => 'passed',
            ],
        ]);

        $this->withSession([
            UiRole::SESSION_KEY => 'bhw',
            DemoSpotMappingHandoff::ACTOR_SESSION_KEY => '00000000-0000-4000-8000-00000000bbbb',
        ]);

        $html = $this->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-152']))->getContent();
        $this->assertStringNotContainsString('2026-07-20', $html);
        $this->assertDoesNotMatchRegularExpression('/data-water-level="level_i"[^>]*aria-current="true"/', $html);
    }
}
