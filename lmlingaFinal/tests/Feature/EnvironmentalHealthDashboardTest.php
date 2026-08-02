<?php

namespace Tests\Feature;

use App\Support\DemoHouseholdWaterSupply;
use App\Support\EnvironmentalHealthDashboard;
use App\Support\UiRole;
use Tests\TestCase;

class EnvironmentalHealthDashboardTest extends TestCase
{
    public function test_dashboard_loads_with_computed_statistics(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bns'])
            ->get(route('environmental-health.index'));

        $response->assertOk();
        $response->assertSee('Water Supply Status', false);
        $response->assertSee('Sanitation Services', false);
        $response->assertSee('With Toilet', false);
        $response->assertSee('Without Toilet', false);
        $response->assertSee('Export Data', false);
        $response->assertSee('HH-151', false);
        $response->assertSee('lml-eh-dashboard__level', false);
        $response->assertSee('Household Number', false);
        $response->assertSee('>Zone</label>', false);
        $response->assertSee('>Street</label>', false);
        $response->assertSee('lml-eh-toilet-icon--with', false);
        $response->assertSee('lml-eh-toilet-icon--without', false);
        $response->assertDontSee('bi-badge-wc', false);
        $response->assertDontSee('Sanitation Status', false);
        $response->assertDontSee('Water Level', false);
        $response->assertDontSee('data-eh-filter="sanitation"', false);
        $response->assertDontSee('data-eh-filter="water_supply"', false);
        $this->assertMatchesRegularExpression(
            '/lml-eh-dashboard__level[^>]*>\s*I\s*</',
            $response->getContent()
        );
        $response->assertDontSee('Environmental Health module content will be added', false);
        $response->assertDontSee('Not yet determined:', false);
        $response->assertDontSee('Not Yet Determined', false);
        $response->assertDontSee('More Filters', false);
        $response->assertDontSee('Record Status', false);
        $response->assertDontSee('Validation Status', false);
        $response->assertDontSee('Household Head', false);
        $response->assertDontSee('All Sanitation Statuses', false);
        $response->assertDontSee('Environmental Health Overview', false);
    }

    public function test_statistics_are_derived_from_amenities_records(): void
    {
        $this->withSession([UiRole::SESSION_KEY => 'bhw']);

        $rows = EnvironmentalHealthDashboard::rows();
        $stats = EnvironmentalHealthDashboard::statistics($rows);

        $this->assertGreaterThanOrEqual(6, count($rows));
        $this->assertSame(
            count($rows),
            $stats['overview']['total_households']
        );
        $this->assertSame(
            $stats['overview']['total_households'],
            $stats['overview']['completed_amenities'] + $stats['overview']['pending_assessment']
        );
        $this->assertSame(
            $stats['overview']['total_households'],
            $stats['sanitation']['sanitary']
            + $stats['sanitation']['unsanitary']
            + $stats['sanitation']['not_yet_determined']
        );

        $this->assertSame(
            $stats['overview']['total_households'],
            $stats['toilet_presence']['with_toilet']
            + $stats['toilet_presence']['without_toilet']
            + $stats['toilet_presence']['unknown']
        );
        $this->assertGreaterThan(0, $stats['toilet_presence']['with_toilet']);

        $waterTotal = $stats['water_supply']['level_i']
            + $stats['water_supply']['level_ii']
            + $stats['water_supply']['level_iii']
            + $stats['water_supply']['others'];

        $this->assertLessThanOrEqual($stats['overview']['total_households'], $waterTotal);
        $this->assertGreaterThan(0, $stats['water_supply']['level_i'] + $stats['water_supply']['level_iii']);
    }

    public function test_edit_and_add_action_labels_follow_record_status(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('environmental-health.index'));

        $response->assertOk();
        $response->assertSee('aria-label="Edit amenities for HH-151"', false);
        $response->assertSee('aria-label="Add amenities for HH-156"', false);
        $response->assertSee('lml-eh-dashboard__dash', false);
    }

    public function test_filters_narrow_dashboard_rows(): void
    {
        $this->withSession([UiRole::SESSION_KEY => 'bns']);

        $levelI = EnvironmentalHealthDashboard::rows([
            'household_no' => '',
            'house_head' => '',
            'zone' => 'all',
            'street' => 'all',
            'water_supply' => DemoHouseholdWaterSupply::WATER_LEVEL_I,
            'sanitation' => 'all',
            'validation' => 'all',
            'record_status' => 'all',
        ]);

        foreach ($levelI as $row) {
            $this->assertSame(DemoHouseholdWaterSupply::WATER_LEVEL_I, $row['water_supply_status']);
        }

        $withToilet = EnvironmentalHealthDashboard::rows([
            'household_no' => '',
            'house_head' => '',
            'zone' => 'all',
            'street' => 'all',
            'water_supply' => 'all',
            'sanitation' => 'with_toilet',
            'validation' => 'all',
            'record_status' => 'all',
        ]);

        foreach ($withToilet as $row) {
            $this->assertSame('with_toilet', $row['toilet_presence']);
        }

        $response = $this->get(route('environmental-health.index', [
            'water_supply' => DemoHouseholdWaterSupply::WATER_LEVEL_I,
        ]));
        $response->assertOk();
        $response->assertSee('data-stat="water-level_i"', false);
        $this->assertMatchesRegularExpression(
            '/data-stat="water-level_i"[^>]*>\s*'.preg_quote((string) count($levelI), '/').'/',
            $response->getContent()
        );
    }

    public function test_view_and_edit_actions_use_existing_amenities_routes(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('environmental-health.index'));

        $response->assertOk();
        $response->assertSee(
            'href="'.e(route('household-profiling.amenities.show', ['householdNo' => 'HH-151'])).'"',
            false
        );
        $response->assertSee(
            'href="'.e(route('household-profiling.amenities.edit', ['householdNo' => 'HH-151'])).'"',
            false
        );
    }

    public function test_csv_export_downloads_filtered_results(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bns'])
            ->get(route('environmental-health.export', [
                'format' => 'csv',
                'water_supply' => DemoHouseholdWaterSupply::WATER_LEVEL_III,
            ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $content = $response->streamedContent();
        $this->assertStringContainsString('Household Number', $content);
        $this->assertStringContainsString('HH-151', $content);
        $this->assertStringContainsString('HH-155', $content);
        $this->assertStringNotContainsString('HH-154', $content);
    }

    public function test_excel_and_pdf_export_formats_are_available(): void
    {
        $excel = $this->withSession([UiRole::SESSION_KEY => 'bns'])
            ->get(route('environmental-health.export', ['format' => 'excel']));
        $excel->assertOk();
        $excel->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $excel->assertSee('Household Number', false);

        $pdf = $this->withSession([UiRole::SESSION_KEY => 'bns'])
            ->get(route('environmental-health.export', ['format' => 'pdf']));
        $pdf->assertOk();
        $pdf->assertSee('Environmental Health Overview', false);
        $pdf->assertSee('Print / Save as PDF', false);
    }

    public function test_amenities_details_page_is_unchanged_by_dashboard(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('household-profiling.amenities.show', ['householdNo' => 'HH-151']));

        $response->assertOk();
        $response->assertSee('Household Amenities Details', false);
        $response->assertDontSee('data-lml-eh-dashboard', false);
    }

    public function test_household_profiling_list_still_loads(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('household-profiling.index'));

        $response->assertOk();
        $response->assertSee('Total Households', false);
    }
}
