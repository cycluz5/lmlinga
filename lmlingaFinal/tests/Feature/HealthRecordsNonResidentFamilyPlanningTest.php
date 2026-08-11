<?php

namespace Tests\Feature;

use App\Support\HealthRecordsNonResidentFamilyPlanning;
use App\Support\UiRole;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Health Records → Family Planning → Non-Resident / unregistered clients.
 */
class HealthRecordsNonResidentFamilyPlanningTest extends TestCase
{
    public function test_non_resident_routes_resolve(): void
    {
        $names = [
            'health-records.family-planning.non-residents.index',
            'health-records.family-planning.non-residents.create',
            'health-records.family-planning.non-residents.show',
            'health-records.family-planning.non-residents.visits.create',
            'health-records.family-planning.non-residents.visits.edit',
        ];

        foreach ($names as $name) {
            $this->assertTrue(Route::has($name), "Missing route: {$name}");
        }

        $index = Route::getRoutes()->getByName('health-records.family-planning.non-residents.index');
        $this->assertNotNull($index);
        $this->assertSame('health-records/family-planning/non-residents', $index->uri());
    }

    public function test_listing_renders_with_filters_and_table(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.family-planning.non-residents.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-lml-hr-fp-nr', $html);
        $this->assertStringContainsString('Non - Residents Client', $html);
        $this->assertStringContainsString(
            'Family planning clients from outside the barangay.',
            $html
        );
        $this->assertStringContainsString('data-hr-fp-nr-search', $html);
        $this->assertStringContainsString('data-hr-fp-nr-barangay', $html);
        $this->assertStringContainsString('data-hr-fp-nr-year', $html);
        $this->assertStringContainsString('for="lml-hr-fp-nr-search"', $html);

        foreach (['Full Name', 'Age', 'Method', 'Start Date', 'Last Visit'] as $header) {
            $this->assertStringContainsString($header, $html);
        }

        foreach (HealthRecordsNonResidentFamilyPlanning::clients() as $client) {
            $this->assertStringContainsString($client['full_name'], $html);
        }
    }

    public function test_sidebar_family_planning_active_on_listing(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bns'])
            ->get(route('health-records.family-planning.non-residents.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertSame('family-planning', UiRole::sidebarActiveKey());
        $this->assertMatchesRegularExpression(
            '/id="lml-sidebar-collapse-health-records"[^>]*\bis-open\b/u',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/lml-sidebar__sublink--active[^>]*aria-current="page"[^>]*>[\s\S]*>Family Planning</u',
            $html
        );
    }

    public function test_add_new_client_screen_renders(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.family-planning.non-residents.create'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('Add New Client', $html);
        $this->assertStringContainsString('PERSONAL INFORMATION', $html);
        $this->assertStringContainsString('Family Planning Service Record', $html);
        $this->assertStringContainsString('for="lml-hr-fp-nr-first-name"', $html);
        $this->assertStringContainsString('for="lml-hr-fp-nr-visit-date"', $html);
        $this->assertStringContainsString('data-hr-fp-nr-commodity-add', $html);
        $this->assertStringContainsString('Add Another Commodity', $html);
        $this->assertStringContainsString('data-hr-fp-nr-create-form', $html);
    }

    public function test_client_record_renders(): void
    {
        $client = HealthRecordsNonResidentFamilyPlanning::findClient('roselyn-a-mendoza');
        $this->assertNotNull($client);

        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.family-planning.non-residents.show', [
                'clientKey' => 'roselyn-a-mendoza',
            ]));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('Non Residents Client', $html);
        $this->assertStringContainsString('ROSELYN A. MENDOZA', $html);
        $this->assertStringContainsString('Client Information', $html);
        $this->assertStringContainsString('Visit History', $html);
        $this->assertStringContainsString('Commodities Given', $html);
        $this->assertStringContainsString('Add Visit', $html);
        $this->assertStringContainsString('No Complaints', $html);
        $this->assertStringContainsString(
            'href="'.e(route('health-records.family-planning.non-residents.visits.edit', [
                'clientKey' => 'roselyn-a-mendoza',
                'visitId' => 'NR-FP-001',
            ])).'"',
            $html
        );
    }

    public function test_add_visit_screen_renders(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.family-planning.non-residents.visits.create', [
                'clientKey' => 'roselyn-a-mendoza',
            ]));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('ADD RECORD', $html);
        $this->assertStringContainsString('Visit Information', $html);
        $this->assertStringContainsString('Commodities Given', $html);
        $this->assertStringContainsString('data-hr-fp-nr-visit-form', $html);
        $this->assertStringContainsString('data-hr-fp-nr-commodity-remove', $html);
    }

    public function test_edit_visit_screen_renders(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.family-planning.non-residents.visits.edit', [
                'clientKey' => 'roselyn-a-mendoza',
                'visitId' => 'NR-FP-001',
            ]));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('EDIT VISIT', $html);
        $this->assertStringContainsString('data-hr-fp-nr-delete-visit', $html);
        $this->assertStringContainsString('Delete Visit', $html);
        $this->assertStringContainsString('value="2024-02-09"', $html);
        $this->assertStringContainsString('No Complaints', $html);
    }

    public function test_frozen_summary_links_to_non_resident_listing(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.family-planning.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString(
            'href="'.e(route('health-records.family-planning.non-residents.index')).'"',
            $html
        );
        $this->assertStringContainsString('Total FP Patients', $html);
        $this->assertStringContainsString('data-hr-fp-add', $html);
    }

    public function test_household_profiling_family_planning_remains_separate(): void
    {
        $this->assertTrue(Route::has('household-profiling.members.family-planning.index'));
        $this->assertTrue(Route::has('health-records.family-planning.non-residents.index'));

        $hh = Route::getRoutes()->getByName('household-profiling.members.family-planning.index');
        $nr = Route::getRoutes()->getByName('health-records.family-planning.non-residents.index');

        $this->assertNotNull($hh);
        $this->assertNotNull($nr);
        $this->assertNotSame($hh->uri(), $nr->uri());
    }
}
