<?php

namespace Tests\Feature;

use App\Models\DeathRequest;
use App\Support\HealthRecordsDeath;
use App\Support\ResidentVitalStatus;
use App\Support\UiRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HealthRecordsDeathTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('death_certificates');
    }

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
        $this->assertTrue(Route::has('health-records.death.show'));
        $this->assertTrue(Route::has('health-records.death.store'));
        $this->assertTrue(Route::has('health-records.death.residents'));
        $this->assertTrue(Route::has('health-records.death.export'));

        $route = Route::getRoutes()->getByName('health-records.death.index');
        $this->assertNotNull($route);
        $this->assertSame('health-records/death', $route->uri());
        $this->assertSame('health-records/death/residents', Route::getRoutes()->getByName('health-records.death.residents')?->uri());
        $this->assertSame('health-records/death/export', Route::getRoutes()->getByName('health-records.death.export')?->uri());
    }

    public function test_death_listing_does_not_render_resident_selection(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-lml-hr-death', $html);
        $this->assertStringContainsString('data-death-data-mode="persisted"', $html);
        $this->assertStringNotContainsString('id="lml-hr-death-residents"', $html);
        $this->assertStringNotContainsString('data-hr-death-resident-search', $html);
        $this->assertStringNotContainsString('Select a resident', $html);
        $this->assertStringContainsString(route('health-records.death.residents'), $html);
        $this->assertStringContainsString(route('health-records.death.export'), $html);
    }

    public function test_record_death_opens_dedicated_resident_selection_page(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.residents'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertSame('death', UiRole::sidebarActiveKey());
        $this->assertStringContainsString('id="lml-hr-death-residents"', $html);
        $this->assertStringContainsString('Select a resident', $html);
        $this->assertStringContainsString('Kristine Reyes', $html);
        $this->assertStringContainsString(
            route('health-records.death.show', ['householdNo' => 'HH-151', 'memberId' => 'MB-002']),
            $html
        );
        $this->assertStringContainsString(route('health-records.death.index'), $html);
    }

    public function test_resident_picker_distinguishes_same_name_catalog_members(): void
    {
        $candidates = HealthRecordsDeath::residentCandidates();
        $byId = [];
        foreach ($candidates as $row) {
            $byId[(string) $row['member_id']] = $row;
        }

        $this->assertArrayHasKey('MB-001', $byId);
        $this->assertArrayHasKey('MB-002', $byId);
        $this->assertSame('HH-151', $byId['MB-001']['household_no']);
        $this->assertSame('HH-151', $byId['MB-002']['household_no']);
        $this->assertSame('Kristine Reyes', $byId['MB-001']['full_name']);
        $this->assertSame('Kristine Reyes', $byId['MB-002']['full_name']);
        $this->assertSame('Male', $byId['MB-001']['sex']);
        $this->assertSame('Female', $byId['MB-002']['sex']);
        $this->assertSame('Head', $byId['MB-001']['relationship']);
        $this->assertSame('Wife', $byId['MB-002']['relationship']);
        $this->assertSame('May 4, 1991', $byId['MB-001']['birthday_display']);
        $this->assertSame('August 12, 1991', $byId['MB-002']['birthday_display']);

        $html = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.residents'))
            ->getContent();

        $this->assertStringContainsString('MB-001', $html);
        $this->assertStringContainsString('MB-002', $html);
        $this->assertStringContainsString('Born May 4, 1991', $html);
        $this->assertStringContainsString('Born August 12, 1991', $html);
        $this->assertStringContainsString(
            'aria-label="Record death for Kristine Reyes, Male, Head, MB-001"',
            $html
        );
        $this->assertStringContainsString(
            'aria-label="Record death for Kristine Reyes, Female, Wife, MB-002"',
            $html
        );
    }

    public function test_empty_collection_still_derives_zero_counts_and_empty_markup_exists(): void
    {
        $emptySummary = HealthRecordsDeath::summaryCounts([]);
        $this->assertSame(['total' => 0, 'female' => 0, 'male' => 0], $emptySummary);

        $html = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index'))
            ->getContent();

        $this->assertStringContainsString('data-hr-death-empty', $html);
        $this->assertStringContainsString('No death records have been recorded yet.', $html);
        $this->assertMatchesRegularExpression(
            '/data-death-stat="total"[^>]*>\s*0\s*</u',
            $html
        );
    }

    public function test_approved_records_render_in_listing_and_summary(): void
    {
        $this->createApprovedRequest('HH-151', 'MB-002', 'Kristine Reyes', 'Female', 'Cardiac arrest');

        $rows = HealthRecordsDeath::listingRows();
        $summary = HealthRecordsDeath::summaryCounts(
            array_values(array_filter(
                $rows,
                static fn (array $row): bool => $row['status'] === DeathRequest::STATUS_APPROVED
            ))
        );

        $this->assertCount(1, $rows);
        $this->assertSame(1, $summary['total']);
        $this->assertSame(1, $summary['female']);
        $this->assertSame(0, $summary['male']);

        $html = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index'))
            ->getContent();

        $this->assertStringContainsString('Kristine Reyes', $html);
        $this->assertStringContainsString('Cardiac arrest', $html);
        $this->assertStringContainsString('Approved', $html);
        $this->assertMatchesRegularExpression(
            '/data-death-stat="total"[^>]*>\s*1\s*</u',
            $html
        );
    }

    public function test_search_zone_cause_sex_and_year_filters_match_rows(): void
    {
        $rows = [
            $this->filterRow('Kristine Reyes', 'Female', 'Zone 1', 'Kidney Failure', '2026-03-12'),
            $this->filterRow('Jacob Magistrado', 'Male', 'Zone 2', 'Accident', '2026-01-30'),
            $this->filterRow('Haziel Santos', 'Female', 'Zone 3', 'Stroke', '2025-01-04'),
        ];

        $this->assertSame(
            ['Kristine Reyes'],
            $this->names(HealthRecordsDeath::filterRows($rows, ['search' => 'Kristine']))
        );
        $this->assertSame(
            ['Kristine Reyes', 'Haziel Santos'],
            $this->names(HealthRecordsDeath::filterRows($rows, ['sex' => 'female']))
        );
        $this->assertSame(
            ['Jacob Magistrado'],
            $this->names(HealthRecordsDeath::filterRows($rows, ['zone' => 'Zone 2']))
        );
        $this->assertSame(
            ['Haziel Santos'],
            $this->names(HealthRecordsDeath::filterRows($rows, ['year' => '2025']))
        );
    }

    public function test_table_headers_render(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index'));

        $response->assertOk();
        $html = $response->getContent();

        preg_match_all('/<th scope="col">([^<]+)<\/th>/u', $html, $headerMatches);
        $this->assertContains('Full Name', $headerMatches[1]);
        $this->assertContains('Cause of Death', $headerMatches[1]);
        $this->assertContains('Status', $headerMatches[1]);
        $this->assertStringContainsString('<caption class="visually-hidden">', $html);
    }

    public function test_export_control_downloads_filtered_pdf(): void
    {
        $this->createListingRequest('HH-153', 'MB-005', 'Adrian Corporal', 'Male', 'SILOS', 'Zone 2', now()->subDay());
        $this->createListingRequest('HH-151', 'MB-001', 'Kristine Reyes', 'Male', 'Cardiac arrest', 'Zone 1', now()->subDays(2));
        $this->createListingRequest('HH-151', 'MB-002', 'Haziel Santos', 'Female', 'Stroke', 'Zone 3', now()->subDays(3));

        $listing = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index'));
        $listing->assertOk();
        $html = $listing->getContent();
        $this->assertStringContainsString('data-hr-death-export', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/data-hr-death-export[^>]*\bdisabled\b/u',
            $html
        );

        $allRows = HealthRecordsDeath::filteredListingRows(
            Request::create(route('health-records.death.export'), 'GET')
        );
        $this->assertSame(
            ['Adrian Corporal', 'Kristine Reyes', 'Haziel Santos'],
            $this->names($allRows)
        );

        $all = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.export'));
        $all->assertOk();
        $all->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $all->getContent());
        $this->assertStringContainsString('Death Records', $all->getContent());
        $this->assertStringContainsString('Adrian Corporal', $all->getContent());
        $this->assertStringContainsString('Haziel Santos', $all->getContent());
        $this->assertStringContainsString('filename=', (string) $all->headers->get('content-disposition'));
        $this->assertStringContainsString('.pdf', strtolower((string) $all->headers->get('content-disposition')));

        $filteredRows = HealthRecordsDeath::filteredListingRows(
            Request::create(route('health-records.death.export', ['sex' => 'female']), 'GET')
        );
        $this->assertSame(['Haziel Santos'], $this->names($filteredRows));

        $filtered = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.export', ['sex' => 'female']));
        $filtered->assertOk();
        $filtered->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $filtered->getContent());
        $this->assertStringContainsString('Haziel Santos', $filtered->getContent());
        $this->assertStringContainsString('Sex: Female', $filtered->getContent());
        $this->assertStringNotContainsString('Adrian Corporal', $filtered->getContent());

        $reportHtml = view('pages.health-records.death-export-pdf', [
            'rows' => $filteredRows,
            'filters' => ['search' => '', 'zone' => 'all', 'cause' => 'all', 'sex' => 'female', 'year' => 'all'],
            'filterLabels' => HealthRecordsDeath::filterLabels(['sex' => 'female']),
            'generatedAt' => now(),
        ])->render();
        $this->assertStringContainsString('Death Records', $reportHtml);
        $this->assertStringContainsString('Haziel Santos', $reportHtml);
        $this->assertStringContainsString('Stroke', $reportHtml);
        $this->assertStringContainsString('Sex: Female', $reportHtml);
        $this->assertStringNotContainsString('Adrian Corporal', $reportHtml);
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
        $this->assertStringNotContainsString('id="lml-sidebar-collapse-requests"', $html);
    }

    public function test_remains_independent_of_household_profiling_death(): void
    {
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

        $this->assertStringNotContainsString('Pneumonia', $html);
        $this->assertSame(0, DeathRequest::query()->count());
        $this->assertFalse(ResidentVitalStatus::isDeceased('HH-151', 'MB-002'));
    }

    public function test_death_listing_paginates_seven_records_per_page(): void
    {
        $names = [
            'Adrian Corporal',
            'Kristine Reyes',
            'Haziel Santos',
            'Jacob Magistrado',
            'Juan dela Cruz',
            'Maria Santos',
            'Rosa Lim',
            'Carlo Evangelista',
        ];

        foreach ($names as $index => $name) {
            $this->createListingRequest(
                'HH-15'.($index + 1),
                'MB-00'.($index + 1),
                $name,
                $index % 2 === 0 ? 'Male' : 'Female',
                'Cause '.$index,
                'Zone '.(($index % 5) + 1),
                now()->subMinutes($index + 1)
            );
        }

        $page1 = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index'));
        $page1->assertOk();
        $html1 = $page1->getContent();
        $tbody1 = $this->listingTbodyHtml($html1);

        $this->assertSame(7, $this->countListingRows($html1));
        $this->assertStringContainsString('data-hr-death-pagination', $html1);
        $this->assertStringContainsString('Adrian Corporal', $tbody1);
        $this->assertStringContainsString('Rosa Lim', $tbody1);
        $this->assertStringNotContainsString('Carlo Evangelista', $tbody1);
        $this->assertTrue(strpos($tbody1, 'Adrian Corporal') < strpos($tbody1, 'Rosa Lim'));

        $page2 = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index', ['page' => 2]));
        $page2->assertOk();
        $html2 = $page2->getContent();
        $tbody2 = $this->listingTbodyHtml($html2);

        $this->assertSame(1, $this->countListingRows($html2));
        $this->assertStringContainsString('Carlo Evangelista', $tbody2);
        $this->assertStringNotContainsString('Adrian Corporal', $tbody2);
    }

    public function test_death_listing_filters_persist_across_pagination(): void
    {
        for ($index = 0; $index < 8; $index++) {
            $this->createListingRequest(
                'HH-16'.($index + 1),
                'MB-01'.($index + 1),
                'Female Resident '.$index,
                'Female',
                'Stroke',
                'Zone 3',
                now()->subMinutes($index + 1)
            );
        }
        $this->createListingRequest('HH-153', 'MB-005', 'Adrian Corporal', 'Male', 'SILOS', 'Zone 2', now()->subMinutes(20));

        $page1 = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index', ['sex' => 'female']));
        $page1->assertOk();
        $html1 = $page1->getContent();

        $this->assertSame(7, $this->countListingRows($html1));
        $this->assertStringNotContainsString('Adrian Corporal', $this->listingTbodyHtml($html1));
        $this->assertStringContainsString('name="sex"', $html1);
        $this->assertStringContainsString('value="female" selected', $html1);
        $this->assertStringContainsString('sex=female', $html1);

        $page2 = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index', ['sex' => 'female', 'page' => 2]));
        $page2->assertOk();
        $html2 = $page2->getContent();
        $this->assertSame(1, $this->countListingRows($html2));
        $this->assertStringContainsString('Female Resident 7', $this->listingTbodyHtml($html2));
        $this->assertStringNotContainsString('Adrian Corporal', $this->listingTbodyHtml($html2));
        $this->assertStringContainsString('sex=female', $html2);
    }

    public function test_pdf_export_is_not_limited_to_current_pagination_page(): void
    {
        for ($index = 0; $index < 8; $index++) {
            $this->createListingRequest(
                'HH-17'.($index + 1),
                'MB-02'.($index + 1),
                'Export Resident '.$index,
                'Male',
                'Accident',
                'Zone 1',
                now()->subMinutes($index + 1)
            );
        }

        $listing = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index'));
        $this->assertSame(7, $this->countListingRows($listing->getContent()));

        $pdf = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.export'));
        $pdf->assertOk();
        $pdf->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->assertStringContainsString('Export Resident 0', $pdf->getContent());
        $this->assertStringContainsString('Export Resident 7', $pdf->getContent());

        $exported = HealthRecordsDeath::filteredListingRows(
            Request::create(route('health-records.death.export'), 'GET')
        );
        $this->assertCount(8, $exported);
        $this->assertSame('Export Resident 0', $exported[0]['full_name']);
        $this->assertSame('Export Resident 7', $exported[7]['full_name']);
    }

    public function test_summary_counts_remain_global_while_listing_is_paginated(): void
    {
        for ($index = 0; $index < 8; $index++) {
            $this->createApprovedRequest(
                'HH-18'.($index + 1),
                'MB-03'.($index + 1),
                $index < 3 ? 'Female Resident '.$index : 'Male Resident '.$index,
                $index < 3 ? 'Female' : 'Male',
                'Cause '.$index
            );
        }

        $html = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.death.index'))
            ->getContent();

        $this->assertSame(7, $this->countListingRows($html));
        $this->assertMatchesRegularExpression(
            '/data-death-stat="total"[^>]*>\s*8\s*</u',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/data-death-stat="female"[^>]*>\s*3\s*</u',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/data-death-stat="male"[^>]*>\s*5\s*</u',
            $html
        );
    }

    private function countListingRows(string $html): int
    {
        return substr_count($this->listingTbodyHtml($html), 'data-hr-death-row');
    }

    private function listingTbodyHtml(string $html): string
    {
        if (preg_match('/<tbody data-hr-death-tbody>(.*?)<\/tbody>/su', $html, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function createListingRequest(
        string $householdNo,
        string $memberId,
        string $name,
        string $sex,
        string $cause,
        string $zone,
        ?\DateTimeInterface $submittedAt = null
    ): DeathRequest {
        return DeathRequest::query()->create([
            'household_no' => $householdNo,
            'member_id' => $memberId,
            'resident_name' => $name,
            'resident_sex' => $sex,
            'resident_age' => 35,
            'zone' => $zone,
            'household_display_no' => str_replace('-', ' ', $householdNo),
            'address' => 'Layuan St., Brgy. La Medalla',
            'cause_of_death' => $cause,
            'date_of_death' => '2026-07-12',
            'registry_no' => '2026-00123',
            'certificate_no' => 'DC-2026-00451',
            'certificate_disk' => 'death_certificates',
            'certificate_path' => $householdNo.'/'.$memberId.'/1/file.pdf',
            'certificate_original_name' => 'certificate.pdf',
            'certificate_mime' => 'application/pdf',
            'certificate_size' => 1200,
            'certificate_extension' => 'pdf',
            'status' => DeathRequest::STATUS_PENDING,
            'submitted_by_name' => 'Sarah',
            'submitted_by_role' => 'bhw',
            'submitted_at' => $submittedAt ?? now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filterRow(string $name, string $sex, string $zone, string $cause, string $iso): array
    {
        $year = substr($iso, 0, 4);

        return [
            'full_name' => $name,
            'sex' => $sex,
            'sex_filter' => strtolower($sex) === 'female' ? 'female' : 'male',
            'zone' => $zone,
            'cause_of_death' => $cause,
            'year' => $year,
        ];
    }

    private function createApprovedRequest(
        string $householdNo,
        string $memberId,
        string $name,
        string $sex,
        string $cause
    ): DeathRequest {
        $request = DeathRequest::query()->create([
            'household_no' => $householdNo,
            'member_id' => $memberId,
            'resident_name' => $name,
            'resident_sex' => $sex,
            'resident_age' => 35,
            'zone' => 'Zone 2',
            'household_display_no' => 'HH 151',
            'address' => 'Layuan St., Brgy. La Medalla',
            'cause_of_death' => $cause,
            'date_of_death' => '2026-07-12',
            'registry_no' => '2026-00123',
            'certificate_no' => 'DC-2026-00451',
            'certificate_disk' => 'death_certificates',
            'certificate_path' => 'HH-151/MB-002/1/file.pdf',
            'certificate_original_name' => 'certificate.pdf',
            'certificate_mime' => 'application/pdf',
            'certificate_size' => 1200,
            'certificate_extension' => 'pdf',
            'status' => DeathRequest::STATUS_APPROVED,
            'submitted_by_name' => 'Sarah',
            'submitted_by_role' => 'bhw',
            'submitted_at' => now(),
            'reviewed_by_name' => 'Admin User',
            'reviewed_by_role' => 'admin',
            'reviewed_at' => now(),
        ]);

        ResidentVitalStatus::markDeceased($request);

        return $request;
    }
}
