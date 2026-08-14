<?php

namespace Tests\Feature;

use App\Support\DemoCatalog;
use App\Support\HealthRecordsNonResidentChildCare;
use App\Support\HealthRecordsNonResidentFamilyPlanning;
use App\Support\HealthRecordsRiskAssessment;
use App\Support\UiRole;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Health Records → Risk Assessment barangay-wide summary.
 */
class HealthRecordsRiskAssessmentTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_risk_assessment_route_resolves(): void
    {
        $this->assertTrue(Route::has('health-records.risk-assessment.index'));

        $route = Route::getRoutes()->getByName('health-records.risk-assessment.index');
        $this->assertNotNull($route);
        $this->assertSame('health-records/risk-assessment', $route->uri());
    }

    public function test_risk_assessment_page_renders_successfully(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bns'])
            ->get(route('health-records.risk-assessment.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-lml-hr-risk', $html);
        $this->assertMatchesRegularExpression(
            '/id="lml-hr-risk-heading"[^>]*>\s*Risk Assessment\s*</u',
            $html
        );
        $this->assertStringContainsString(
            'Record and management of risk assessment details for monitoring and tracking health risks.',
            $html
        );
    }

    public function test_summary_cards_render_from_fixture(): void
    {
        $summary = HealthRecordsRiskAssessment::summaryCounts();

        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.risk-assessment.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('Total Assessed Clients', $html);
        $this->assertMatchesRegularExpression(
            '/data-ra-stat="total"[^>]*>\s*'.preg_quote((string) $summary['total'], '/').'\s*</u',
            $html
        );

        foreach (HealthRecordsRiskAssessment::zones() as $zone) {
            $this->assertStringContainsString('>'.$zone.'</p>', $html);
            $count = (int) ($summary['zones'][$zone] ?? 0);
            $slug = \Illuminate\Support\Str::slug($zone);
            $this->assertMatchesRegularExpression(
                '/data-ra-stat="'.preg_quote($slug, '/').'"[^>]*>[\s\S]*?>\s*'.$count.'\s*</u',
                $html
            );
        }
    }

    public function test_filter_controls_render(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.risk-assessment.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-hr-ra-search', $html);
        $this->assertStringContainsString('placeholder="Search Name"', $html);
        $this->assertStringContainsString('for="lml-hr-ra-search"', $html);
        $this->assertStringContainsString('data-hr-ra-zone', $html);
        $this->assertStringContainsString('>All Zones</option>', $html);
        $this->assertStringContainsString('for="lml-hr-ra-zone"', $html);
        $this->assertStringContainsString('data-hr-ra-year', $html);
        $this->assertStringContainsString('>All Years</option>', $html);
        $this->assertStringContainsString('for="lml-hr-ra-year"', $html);

        foreach (HealthRecordsRiskAssessment::years() as $year) {
            $this->assertStringContainsString('>'.$year.'</option>', $html);
        }
    }

    public function test_table_headers_and_fixture_rows_render(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.risk-assessment.index'));

        $response->assertOk();
        $html = $response->getContent();

        preg_match_all(
            '/<th scope="col">\s*<span class="lml-hr-risk__th-main">([^<]+)<\/span>/u',
            $html,
            $headerMatches
        );
        $this->assertSame([
            'Full Name',
            'BMI Status',
            'BP Status',
            'Smoking Status',
            'Alcohol Status',
            'Physical Activity Risk',
            'Family History Risk',
            'Chronic Disease',
        ], $headerMatches[1]);
        $this->assertStringContainsString('lml-hr-risk__col--name', $html);
        $this->assertStringContainsString('lml-hr-risk__col--bmi', $html);
        $this->assertStringContainsString('lml-hr-risk__col--bp', $html);

        $this->assertStringContainsString('(Underweight/', $html);
        $this->assertStringContainsString('Normal/', $html);
        $this->assertStringContainsString('Overweight/', $html);
        $this->assertStringContainsString('Obese)', $html);
        $this->assertStringContainsString('(Never/', $html);
        $this->assertStringContainsString('Current/', $html);
        $this->assertStringContainsString('Quit)', $html);

        foreach (HealthRecordsRiskAssessment::rows() as $row) {
            $this->assertStringContainsString($row['full_name'], $html);
            $this->assertStringContainsString($row['bmi_status'], $html);
        }

        $this->assertStringContainsString('<caption class="visually-hidden">', $html);
        $this->assertStringContainsString('data-hr-ra-empty', $html);
        $this->assertStringContainsString(
            'No risk assessment records match the selected filters.',
            $html
        );
    }

    public function test_status_headers_have_title_description_separator_structure(): void
    {
        $html = $this->riskAssessmentHtml();

        $this->assertMatchesRegularExpression(
            '/<th scope="col">\s*<span class="lml-hr-risk__th-main">Full Name<\/span>\s*<\/th>/u',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<span class="lml-hr-risk__th-main">Full Name<\/span>\s*<span class="lml-hr-risk__th-sub">/u',
            $html
        );

        preg_match_all(
            '/<th scope="col">\s*<span class="lml-hr-risk__th-main">([^<]+)<\/span>\s*<span class="lml-hr-risk__th-sub">/u',
            $html,
            $statusHeaders
        );

        $this->assertSame([
            'BMI Status',
            'BP Status',
            'Smoking Status',
            'Alcohol Status',
            'Physical Activity Risk',
            'Family History Risk',
            'Chronic Disease',
        ], $statusHeaders[1]);

        preg_match_all('/class="lml-hr-risk__th-sub"/u', $html, $subMatches);
        $this->assertCount(7, $subMatches[0]);
    }

    public function test_export_control_exists_and_add_button_is_absent(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.risk-assessment.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringNotContainsString('data-hr-ra-add', $html);
        $this->assertStringNotContainsString('aria-label="Add risk assessment record"', $html);
        $this->assertStringNotContainsString('lml-hr-risk__add-btn', $html);
        $this->assertDoesNotMatchRegularExpression('/>\s*Add\s*<\/span>/u', $html);
        $this->assertStringContainsString('data-hr-ra-export', $html);
        $this->assertStringContainsString('aria-label="Export Risk Assessment data"', $html);
        $this->assertMatchesRegularExpression('/>\s*Export Data\s*<\/span>/u', $html);
        $this->assertStringContainsString('data-hr-ra-toast', $html);
        $this->assertStringNotContainsString('href="#"', $html);
    }

    public function test_sidebar_risk_assessment_is_real_link_and_active(): void
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.risk-assessment.index'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertSame('risk-assessment', UiRole::sidebarActiveKey());
        $this->assertMatchesRegularExpression(
            '/id="lml-sidebar-collapse-health-records"[^>]*\bis-open\b/u',
            $html
        );
        $this->assertStringContainsString(
            'href="'.e(route('health-records.risk-assessment.index')).'"',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/lml-sidebar__sublink--active[^>]*aria-current="page"[^>]*>[\s\S]*>Risk Assessment</u',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/lml-sidebar__sublink--active[^>]*>[\s\S]*>Child Care</u',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<span[^>]*lml-sidebar__sublink--unavailable[^>]*>[\s\S]*?<span>\s*Risk Assessment\s*<\/span>/u',
            $html
        );
    }

    public function test_summary_counts_match_eligible_resident_rows(): void
    {
        $rows = HealthRecordsRiskAssessment::rows();
        $summary = HealthRecordsRiskAssessment::summaryCounts($rows);

        $this->assertSame(count($rows), $summary['total']);
        $this->assertSame(count(HealthRecordsRiskAssessment::eligibleResidents()), $summary['total']);
        $this->assertSame(8, $summary['total']);

        $this->assertSame(1, $summary['zones']['Zone 1']);
        $this->assertSame(3, $summary['zones']['Zone 2']);
        $this->assertSame(1, $summary['zones']['Zone 3']);
        $this->assertSame(1, $summary['zones']['Zone 4']);
        $this->assertSame(2, $summary['zones']['Zone 5']);

        $this->assertSame(['2026', '2025', '2024'], HealthRecordsRiskAssessment::years($rows));
    }

    public function test_eligibility_uses_birthday_not_stored_age_field(): void
    {
        $asOf = Carbon::parse('2026-08-11')->startOfDay();

        $this->assertTrue(HealthRecordsRiskAssessment::isEligibleResident([
            'id' => 'SYN-19',
            'birthday' => '2007-08-11',
            'age' => 10,
        ], $asOf));
        $this->assertSame(
            19,
            HealthRecordsRiskAssessment::ageInYears([
                'birthday' => '2007-08-11',
                'age' => 10,
            ], $asOf)
        );

        $this->assertFalse(HealthRecordsRiskAssessment::isEligibleResident([
            'id' => 'SYN-NO-DOB',
            'age' => 40,
        ], $asOf));
        $this->assertNull(HealthRecordsRiskAssessment::ageInYears(['age' => 40], $asOf));
    }

    public function test_dob_boundary_includes_nineteenth_birthday_and_excludes_day_before(): void
    {
        $asOf = Carbon::parse('2026-08-11')->startOfDay();

        $this->assertSame(
            19,
            HealthRecordsRiskAssessment::ageInYears(['birthday' => '2007-08-11'], $asOf)
        );
        $this->assertTrue(HealthRecordsRiskAssessment::isEligibleResident([
            'birthday' => '2007-08-11',
        ], $asOf));

        $this->assertSame(
            18,
            HealthRecordsRiskAssessment::ageInYears(['birthday' => '2007-08-12'], $asOf)
        );
        $this->assertFalse(HealthRecordsRiskAssessment::isEligibleResident([
            'birthday' => '2007-08-12',
        ], $asOf));
    }

    public function test_age_eighteen_is_not_eligible_and_older_than_nineteen_is(): void
    {
        $asOf = Carbon::parse('2026-08-11')->startOfDay();

        $this->assertSame(
            18,
            HealthRecordsRiskAssessment::ageInYears(['birthday' => '2008-08-11'], $asOf)
        );
        $this->assertFalse(HealthRecordsRiskAssessment::isEligibleResident([
            'birthday' => '2008-08-11',
        ], $asOf));

        $this->assertSame(
            20,
            HealthRecordsRiskAssessment::ageInYears(['birthday' => '2006-08-11'], $asOf)
        );
        $this->assertTrue(HealthRecordsRiskAssessment::isEligibleResident([
            'birthday' => '2006-08-11',
        ], $asOf));

        $this->assertSame(
            5,
            HealthRecordsRiskAssessment::ageInYears(['birthday' => '2020-11-03'], $asOf)
        );
        $this->assertFalse(HealthRecordsRiskAssessment::isEligibleResident([
            'birthday' => '2020-11-03',
        ], $asOf));
    }

    public function test_nineteen_year_old_catalog_resident_is_listed_and_selectable(): void
    {
        $row = collect(HealthRecordsRiskAssessment::rows())
            ->firstWhere('member_id', 'MB-012');

        $this->assertIsArray($row);
        $this->assertSame('Liza M. Evangelista', $row['full_name']);
        $this->assertSame(19, HealthRecordsRiskAssessment::ageInYears([
            'birthday' => $row['birthday'],
        ]));

        $html = $this->riskAssessmentHtml();
        $this->assertStringContainsString('data-member-id="MB-012"', $html);
        $this->assertStringContainsString('Liza M. Evangelista', $html);
        $this->assertStringContainsString('data-birthday="'.$row['birthday'].'"', $html);
    }

    public function test_resident_older_than_nineteen_is_listed_and_selectable(): void
    {
        $row = collect(HealthRecordsRiskAssessment::rows())
            ->firstWhere('member_id', 'MB-001');

        $this->assertIsArray($row);
        $this->assertSame('Kristine Reyes', $row['full_name']);
        $this->assertGreaterThan(
            19,
            (int) HealthRecordsRiskAssessment::ageInYears(['birthday' => $row['birthday']])
        );

        $html = $this->riskAssessmentHtml();
        $this->assertStringContainsString('data-member-id="MB-001"', $html);
        $this->assertStringContainsString('Kristine Reyes', $html);
    }

    public function test_eighteen_year_old_catalog_resident_is_not_listed_or_selectable(): void
    {
        $catalogIds = collect(HealthRecordsRiskAssessment::catalogResidents())
            ->map(static fn (array $item): string => strtoupper((string) ($item['member']['id'] ?? '')));
        $this->assertTrue($catalogIds->contains('MB-013'));

        $eighteen = collect(HealthRecordsRiskAssessment::catalogResidents())
            ->first(static fn (array $item): bool => strtoupper((string) ($item['member']['id'] ?? '')) === 'MB-013');
        $this->assertIsArray($eighteen);
        $this->assertSame(
            18,
            HealthRecordsRiskAssessment::ageInYears($eighteen['member'])
        );
        $this->assertFalse(HealthRecordsRiskAssessment::isEligibleResident($eighteen['member']));

        $listedIds = array_column(HealthRecordsRiskAssessment::rows(), 'member_id');
        $this->assertNotContains('MB-013', $listedIds);

        $html = $this->riskAssessmentHtml();
        $this->assertStringNotContainsString('data-member-id="MB-013"', $html);
        $this->assertStringNotContainsString('Marco M. Evangelista', $html);
    }

    public function test_residents_younger_than_eighteen_are_not_listed(): void
    {
        $underageIds = ['MB-003', 'MB-009', 'MB-010', 'MB-011'];
        $listedIds = array_column(HealthRecordsRiskAssessment::rows(), 'member_id');

        foreach ($underageIds as $memberId) {
            $this->assertNotContains($memberId, $listedIds);
        }

        $html = $this->riskAssessmentHtml();
        $this->assertStringNotContainsString('Angelo David Reyes', $html);
        $this->assertStringNotContainsString('Kristine B. Reyes', $html);
        $this->assertStringNotContainsString('Jacob A. Magistrado', $html);
        $this->assertStringNotContainsString('Haziel H. Santos', $html);
        $this->assertStringNotContainsString('data-member-id="MB-003"', $html);
        $this->assertStringNotContainsString('data-member-id="MB-009"', $html);
        $this->assertStringNotContainsString('data-member-id="MB-010"', $html);
        $this->assertStringNotContainsString('data-member-id="MB-011"', $html);
    }

    public function test_rows_are_catalog_residents_only_and_exclude_non_residents(): void
    {
        $catalogIds = [];
        foreach (DemoCatalog::households() as $household) {
            foreach ($household['memberList'] ?? [] as $member) {
                $id = strtoupper(trim((string) ($member['id'] ?? '')));
                if ($id !== '') {
                    $catalogIds[$id] = true;
                }
            }
        }

        foreach (HealthRecordsRiskAssessment::rows() as $row) {
            $this->assertArrayHasKey($row['member_id'], $catalogIds);
            $this->assertTrue(HealthRecordsRiskAssessment::isEligibleResident([
                'birthday' => $row['birthday'],
            ]));
        }

        $html = $this->riskAssessmentHtml();
        $nonResidentNames = array_unique(array_merge(
            array_column(HealthRecordsNonResidentChildCare::rows(), 'full_name'),
            array_map(
                static fn (array $client): string => trim(implode(' ', array_filter([
                    $client['first_name'] ?? '',
                    $client['middle_name'] ?? '',
                    $client['last_name'] ?? '',
                ]))),
                HealthRecordsNonResidentFamilyPlanning::clients()
            ),
            [
                'Andrei B. Malaya',
                'Crisley F. Fernando',
                'Gabriel Allan S. Chua',
                'Maria L. Domingo',
                'Paolo R. Santos',
                'Roselyn A. Mendoza',
            ]
        ));

        foreach ($nonResidentNames as $name) {
            if ($name === '') {
                continue;
            }
            $this->assertStringNotContainsString($name, $html);
        }

        $this->assertFalse(Route::has('health-records.risk-assessment.non-residents'));
        $this->assertFalse(Route::has('health-records.non-resident-risk-assessment.index'));
    }

    public function test_search_dataset_contains_only_eligible_resident_rows(): void
    {
        $html = $this->riskAssessmentHtml();

        preg_match_all('/data-hr-ra-row\b[^>]*>/u', $html, $matches);
        $this->assertNotSame([], $matches[0]);
        $this->assertSame(count(HealthRecordsRiskAssessment::rows()), count($matches[0]));

        foreach ($matches[0] as $tag) {
            $this->assertMatchesRegularExpression('/data-member-id="MB-\d+"/', $tag);
            $this->assertMatchesRegularExpression('/data-birthday="\d{4}-\d{2}-\d{2}"/', $tag);
        }
    }

    private function riskAssessmentHtml(): string
    {
        $response = $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route('health-records.risk-assessment.index'));

        $response->assertOk();

        return $response->getContent();
    }
}
