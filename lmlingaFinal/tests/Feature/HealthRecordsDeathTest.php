<?php

namespace Tests\Feature;

use App\Support\HealthRecordsDeath;
use App\Support\UiRole;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Health Records → Death barangay-wide listing.
 */
class HealthRecordsDeathTest extends TestCase
{
    /** @return list<string> */
    private function names(array $rows): array
    {
        return array_values(array_map(
            static fn (array $row): string => (string) $row['full_name'],
            $rows
        ));
    }

    public function test_death_route_resolves(): void
    {
        $this->assertTrue(Route::has('health-records.death.index'));
        $this->assertFalse(Route::has('health-records.death'));

        $route = Route::getRoutes()->getByName('health-records.death.index');
        $this->assertNotNull($route);
        $this->assertSame('health-records/death', $route->uri());
    }

    public function test_death_page_renders_successfully(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bns'])
            ->get(route('health-records.death.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-lml-hr-death', $html);
        $this->assertStringContainsString('data-death-data-mode="ui-phase-fixture"', $html);
        $this->assertMatchesRegularExpression(
            '/id="lml-hr-death-heading"[^>]*>\s*Death\s*</u',
            $html
        );
        $this->assertStringContainsString(
            'Record and management of death details for monitoring and tracking mortality status.',
            $html
        );
    }

    public function test_populated_fixture_rows_and_derived_summary_counts_render(): void
    {
        $rows = HealthRecordsDeath::rows();
        $summary = HealthRecordsDeath::summaryCounts($rows);

        $this->assertCount(6, $rows);
        $this->assertSame(6, $summary['total']);
        $this->assertSame(3, $summary['female']);
        $this->assertSame(3, $summary['male']);
        $this->assertSame($summary, HealthRecordsDeath::summaryCounts());

        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/data-death-stat="total"[^>]*>\s*6\s*</u',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/data-death-stat="female"[^>]*>\s*3\s*</u',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/data-death-stat="male"[^>]*>\s*3\s*</u',
            $html
        );

        foreach ($rows as $row) {
            $this->assertStringContainsString($row['full_name'], $html);
            $this->assertStringContainsString($row['cause_of_death'], $html);
            $this->assertStringContainsString($row['date_of_death'], $html);
            $this->assertStringContainsString('data-zone="'.$row['zone'].'"', $html);
            $this->assertStringContainsString('data-sex="'.$row['sex_filter'].'"', $html);
            $this->assertStringContainsString('data-year="'.$row['year'].'"', $html);
        }

        $this->assertStringContainsString('>Kidney Failure</option>', $html);
        $this->assertStringContainsString('>Accident</option>', $html);
        $this->assertStringContainsString('>Stroke</option>', $html);
        $this->assertStringContainsString('>Heart Attack</option>', $html);
        $this->assertStringContainsString('>2026</option>', $html);
        $this->assertStringContainsString('>2025</option>', $html);
    }

    public function test_empty_collection_still_derives_zero_counts_and_empty_markup_exists(): void
    {
        $emptySummary = HealthRecordsDeath::summaryCounts([]);
        $this->assertSame(['total' => 0, 'female' => 0, 'male' => 0], $emptySummary);

        $html = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index'))
            ->getContent();

        $this->assertStringContainsString('data-hr-death-empty', $html);
        $this->assertStringContainsString('No death records match the selected filters.', $html);
    }

    public function test_search_zone_cause_sex_and_year_filters_match_fixture_rows(): void
    {
        $rows = HealthRecordsDeath::rows();

        $this->assertSame(
            ['Kristine B. Reyes'],
            $this->names(HealthRecordsDeath::filterRows($rows, ['search' => 'Kristine']))
        );

        $this->assertSame(
            ['Kristine B. Reyes', 'Haziel H. Santos', 'Crisley F. Fernando'],
            $this->names(HealthRecordsDeath::filterRows($rows, ['sex' => 'female']))
        );

        $this->assertSame(
            ['Jacob A. Magistrado', 'Andrei B. Malaya', 'Gabriel Allan S. Chua'],
            $this->names(HealthRecordsDeath::filterRows($rows, ['sex' => 'male']))
        );

        $this->assertSame(
            ['Haziel H. Santos', 'Gabriel Allan S. Chua'],
            $this->names(HealthRecordsDeath::filterRows($rows, ['cause' => 'Stroke']))
        );

        $this->assertSame(
            ['Kristine B. Reyes', 'Jacob A. Magistrado'],
            $this->names(HealthRecordsDeath::filterRows($rows, ['year' => '2026']))
        );

        $this->assertSame(
            ['Haziel H. Santos', 'Andrei B. Malaya', 'Crisley F. Fernando', 'Gabriel Allan S. Chua'],
            $this->names(HealthRecordsDeath::filterRows($rows, ['year' => '2025']))
        );

        $this->assertSame(
            ['Jacob A. Magistrado'],
            $this->names(HealthRecordsDeath::filterRows($rows, ['zone' => 'Zone 2']))
        );

        $this->assertSame(
            [],
            $this->names(HealthRecordsDeath::filterRows($rows, ['search' => 'zzz-no-match']))
        );
    }

    public function test_filter_controls_render(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-hr-death-search', $html);
        $this->assertStringContainsString('placeholder="Search Name"', $html);
        $this->assertStringContainsString('for="lml-hr-death-search"', $html);
        $this->assertStringContainsString('data-hr-death-zone', $html);
        $this->assertStringContainsString('>All Zones</option>', $html);
        $this->assertStringContainsString('for="lml-hr-death-zone"', $html);
        $this->assertStringContainsString('data-hr-death-cause', $html);
        $this->assertStringContainsString('for="lml-hr-death-cause"', $html);
        $this->assertStringContainsString('data-hr-death-sex', $html);
        $this->assertStringContainsString('for="lml-hr-death-sex"', $html);
        $this->assertStringContainsString('data-hr-death-year', $html);
        $this->assertStringContainsString('for="lml-hr-death-year"', $html);
    }

    public function test_table_headers_render(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index'));

        $response->assertOk();
        $html = $response->getContent();

        preg_match_all('/<th scope="col">([^<]+)<\/th>/u', $html, $headerMatches);
        $this->assertSame([
            'Full Name',
            'Age',
            'Cause of Death',
            'Date of Death',
        ], $headerMatches[1]);
        $this->assertStringContainsString('<caption class="visually-hidden">', $html);
        $this->assertStringContainsString('data-hr-death-empty', $html);
    }

    public function test_export_control_is_present_and_disabled(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-hr-death-export', $html);
        $this->assertStringContainsString('aria-label="Export Death data"', $html);
        $this->assertMatchesRegularExpression('/>\s*Export Data\s*<\/span>/u', $html);
        $this->assertMatchesRegularExpression(
            '/data-hr-death-export[^>]*\bdisabled\b/u',
            $html
        );
        $this->assertStringContainsString('aria-disabled="true"', $html);
        $this->assertStringNotContainsString('data-hr-death-toast', $html);
        $this->assertFalse(Route::has('health-records.death.export'));
    }

    public function test_death_sidebar_is_active_and_health_records_expanded(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index'));

        $response->assertOk();
        $this->assertSame('death', UiRole::sidebarActiveKey());
        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/id="lml-sidebar-collapse-health-records"[^>]*\bis-open\b/u',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/lml-sidebar__sublink--active[^>]*aria-current="page"[^>]*>[\s\S]*>Death</u',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/lml-sidebar__sublink--active[^>]*>[\s\S]*>Maternal</u',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/lml-sidebar__sublink--unavailable[^>]*>[\s\S]*?<span>\s*Death\s*<\/span>/u',
            $html
        );
    }

    public function test_remains_independent_of_household_profiling_death(): void
    {
        $this->assertTrue(Route::has('household-profiling.members.death.index'));
        $this->assertTrue(Route::has('health-records.death.index'));

        $hh = Route::getRoutes()->getByName('household-profiling.members.death.index');
        $hr = Route::getRoutes()->getByName('health-records.death.index');

        $this->assertNotNull($hh);
        $this->assertNotNull($hr);
        $this->assertNotSame($hh->uri(), $hr->uri());

        $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->post(route('household-profiling.members.death.store', [
                'householdNo' => 'HH-151',
                'memberId' => 'MB-002',
            ]), [
                'cause_of_death' => 'Pneumonia',
                'date_of_death' => '2026-03-15',
            ])
            ->assertRedirect();

        $listing = $this->get(route('health-records.death.index'));
        $listing->assertOk();
        $html = $listing->getContent();

        $this->assertStringContainsString('Kristine B. Reyes', $html);
        $this->assertStringContainsString('Kidney Failure', $html);
        $this->assertStringNotContainsString('Pneumonia', $html);
        $this->assertMatchesRegularExpression(
            '/data-death-stat="total"[^>]*>\s*6\s*</u',
            $html
        );
    }
}
