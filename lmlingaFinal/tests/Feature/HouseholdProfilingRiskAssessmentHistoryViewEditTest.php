<?php

namespace Tests\Feature;

use App\Support\DemoRiskAssessment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * HV-01..HV-16 — Risk Assessment History View + Edit.
 */
class HouseholdProfilingRiskAssessmentHistoryViewEditTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @return array{householdNo: string, memberId: string}
     */
    private function memberA(): array
    {
        return [
            'householdNo' => 'HH-151',
            'memberId' => 'MB-001',
        ];
    }

    /**
     * @return array{householdNo: string, memberId: string}
     */
    private function memberB(): array
    {
        return [
            'householdNo' => 'HH-151',
            'memberId' => 'MB-002',
        ];
    }

    public function test_hv01_correct_historical_assessment_is_opened(): void
    {
        $response = $this->get(route(
            'household-profiling.members.risk-assessment.show',
            $this->memberA() + ['assessmentId' => 'RA-002']
        ));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-assessment-id="RA-002"', $html);
        $this->assertStringContainsString('data-household-no="HH-151"', $html);
        $this->assertStringContainsString('data-member-id="MB-001"', $html);
        $this->assertStringContainsString('May 1, 2026', $html);
        $this->assertStringNotContainsString('data-assessment-id="RA-001"', $html);
    }

    public function test_hv02_historical_values_are_displayed_from_stored_data(): void
    {
        $response = $this->get(route(
            'household-profiling.members.risk-assessment.section',
            $this->memberA() + [
                'assessmentId' => 'RA-002',
                'section' => 'red-flags',
            ]
        ));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-lml-risk-assess-mode="history-section"', $html);
        $this->assertStringContainsString('data-history-editing="false"', $html);
        $this->assertMatchesRegularExpression(
            '/name="red_flags\[\]"[^>]*value="chest_pain"[^>]*checked/u',
            $html
        );
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('data-risk-assess-history-edit', $html);
        $this->assertStringNotContainsString('data-risk-assess-history-save', $html);
    }

    public function test_hv03_edit_mode_preloads_existing_stored_values(): void
    {
        $response = $this->get(route(
            'household-profiling.members.risk-assessment.section.edit',
            $this->memberA() + [
                'assessmentId' => 'RA-001',
                'section' => 'family-history',
            ]
        ));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-history-editing="true"', $html);
        $this->assertStringContainsString('data-risk-assess-history-save', $html);
        $this->assertMatchesRegularExpression(
            '/name="family_history\[\]"[^>]*value="hypertension"[^>]*checked/u',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/name="family_history\[\]"[^>]*value="none"[^>]*checked/u',
            $html
        );
    }

    public function test_hv04_saving_updates_the_same_assessment(): void
    {
        $params = $this->memberA() + [
            'assessmentId' => 'RA-001',
            'section' => 'red-flags',
        ];

        $response = $this->from(route(
            'household-profiling.members.risk-assessment.section.edit',
            $params
        ))->put(
            route('household-profiling.members.risk-assessment.section.update', $params),
            ['red_flags' => ['chest_pain']]
        );

        $response->assertRedirect(route(
            'household-profiling.members.risk-assessment.section',
            $params
        ));

        $updated = DemoRiskAssessment::find('HH-151', 'MB-001', 'RA-001');
        $this->assertNotNull($updated);
        $this->assertSame(['chest_pain'], $updated['red_flags']);
        $this->assertSame('RA-001', $updated['id']);
        $this->assertSame('2026-06-08', $updated['conducted_at']);
    }

    public function test_hv05_save_does_not_create_an_additional_assessment(): void
    {
        $beforeCount = count(DemoRiskAssessment::forMember('HH-151', 'MB-001'));

        $params = $this->memberA() + [
            'assessmentId' => 'RA-001',
            'section' => 'past-medical',
        ];

        $this->put(
            route('household-profiling.members.risk-assessment.section.update', $params),
            ['past_medical' => ['asthma']]
        )->assertRedirect();

        $after = DemoRiskAssessment::forMember('HH-151', 'MB-001');
        $this->assertCount($beforeCount, $after);
        $this->assertSame(
            ['RA-001', 'RA-002', 'RA-003'],
            array_column($after, 'id')
        );
    }

    public function test_hv06_editing_one_assessment_does_not_modify_another(): void
    {
        $beforeOther = DemoRiskAssessment::find('HH-151', 'MB-001', 'RA-002');

        $this->put(
            route(
                'household-profiling.members.risk-assessment.section.update',
                $this->memberA() + [
                    'assessmentId' => 'RA-001',
                    'section' => 'lifestyle',
                ]
            ),
            [
                'tobacco' => 'current',
                'alcohol' => 'excessive',
                'dietary' => ['high_salt'],
                'physical_activity' => 'below',
            ]
        )->assertRedirect();

        $updated = DemoRiskAssessment::find('HH-151', 'MB-001', 'RA-001');
        $other = DemoRiskAssessment::find('HH-151', 'MB-001', 'RA-002');

        $this->assertSame('current', $updated['tobacco']);
        $this->assertSame($beforeOther, $other);
    }

    public function test_hv07_editing_one_members_assessment_does_not_modify_another_member(): void
    {
        $this->assertSame([], DemoRiskAssessment::forMember('HH-151', 'MB-002'));

        $this->put(
            route(
                'household-profiling.members.risk-assessment.section.update',
                $this->memberB() + [
                    'assessmentId' => 'RA-001',
                    'section' => 'red-flags',
                ]
            ),
            ['red_flags' => ['chest_pain']]
        )->assertForbidden();

        $this->assertSame([], DemoRiskAssessment::forMember('HH-151', 'MB-002'));
        $this->assertSame(['none'], DemoRiskAssessment::find('HH-151', 'MB-001', 'RA-001')['red_flags']);
    }

    public function test_hv08_tampered_assessment_identity_cannot_update_another_member_record(): void
    {
        $response = $this->put(
            route(
                'household-profiling.members.risk-assessment.section.update',
                [
                    'householdNo' => 'HH-151',
                    'memberId' => 'MB-002',
                    'assessmentId' => 'RA-002',
                    'section' => 'red-flags',
                ]
            ),
            ['red_flags' => ['seizure']]
        );

        $response->assertForbidden();
        $this->assertSame(
            ['chest_pain'],
            DemoRiskAssessment::find('HH-151', 'MB-001', 'RA-002')['red_flags']
        );
        $this->assertNull(DemoRiskAssessment::find('HH-151', 'MB-002', 'RA-002'));
    }

    public function test_hv09_red_flag_history_edit_persists(): void
    {
        $params = $this->memberA() + [
            'assessmentId' => 'RA-003',
            'section' => 'red-flags',
        ];

        $this->put(
            route('household-profiling.members.risk-assessment.section.update', $params),
            ['red_flags' => ['slurred_speech', 'facial_asymmetry']]
        )->assertRedirect(route(
            'household-profiling.members.risk-assessment.section',
            $params
        ));

        $row = DemoRiskAssessment::find('HH-151', 'MB-001', 'RA-003');
        $this->assertSame(['slurred_speech', 'facial_asymmetry'], $row['red_flags']);

        $view = $this->get(route(
            'household-profiling.members.risk-assessment.section',
            $params
        ));
        $view->assertOk();
        $html = $view->getContent();
        $this->assertMatchesRegularExpression(
            '/name="red_flags\[\]"[^>]*value="slurred_speech"[^>]*checked/u',
            $html
        );
        $this->assertStringContainsString('data-history-editing="false"', $html);
    }

    public function test_hv10_past_medical_history_edit_persists(): void
    {
        $params = $this->memberA() + [
            'assessmentId' => 'RA-001',
            'section' => 'past-medical',
        ];

        $this->put(
            route('household-profiling.members.risk-assessment.section.update', $params),
            ['past_medical' => ['diabetes', 'allergies']]
        )->assertRedirect();

        $this->assertSame(
            ['diabetes', 'allergies'],
            DemoRiskAssessment::find('HH-151', 'MB-001', 'RA-001')['past_medical']
        );
    }

    public function test_hv11_family_history_edit_persists(): void
    {
        $params = $this->memberA() + [
            'assessmentId' => 'RA-001',
            'section' => 'family-history',
        ];

        $this->put(
            route('household-profiling.members.risk-assessment.section.update', $params),
            ['family_history' => ['stroke', 'cancer']]
        )->assertRedirect();

        $this->assertSame(
            ['stroke', 'cancer'],
            DemoRiskAssessment::find('HH-151', 'MB-001', 'RA-001')['family_history']
        );
    }

    public function test_hv12_lifestyle_and_risk_factor_edit_persists(): void
    {
        $params = $this->memberA() + [
            'assessmentId' => 'RA-001',
            'section' => 'lifestyle',
        ];

        $this->put(
            route('household-profiling.members.risk-assessment.section.update', $params),
            [
                'tobacco' => 'stopped_lt_1y',
                'alcohol' => 'light',
                'dietary' => ['balanced', 'low_fruits'],
                'physical_activity' => 'below',
            ]
        )->assertRedirect();

        $row = DemoRiskAssessment::find('HH-151', 'MB-001', 'RA-001');
        $this->assertSame('stopped_lt_1y', $row['tobacco']);
        $this->assertSame('light', $row['alcohol']);
        $this->assertSame(['balanced', 'low_fruits'], $row['dietary']);
        $this->assertSame('below', $row['physical_activity']);
    }

    public function test_hv13_physical_measurements_clinical_screening_edit_persists(): void
    {
        $params = $this->memberA() + [
            'assessmentId' => 'RA-001',
            'section' => 'physical',
        ];

        $this->put(
            route('household-profiling.members.risk-assessment.section.update', $params),
            [
                'height_cm' => '170',
                'weight_kg' => '65',
                'bmi' => '22.5',
                'waist_cm' => '78',
                'systolic' => '118',
                'diastolic' => '76',
                'bp_status' => 'Normal',
                'visual_no_screening' => '1',
                'visual_blurred' => '0',
                'visual_blurred_note' => '',
            ]
        )->assertRedirect();

        $row = DemoRiskAssessment::find('HH-151', 'MB-001', 'RA-001');
        $this->assertSame('170', $row['height_cm']);
        $this->assertSame('65', $row['weight_kg']);
        $this->assertSame('22.5', $row['bmi']);
        $this->assertSame('78', $row['waist_cm']);
        $this->assertSame('118', $row['systolic']);
        $this->assertSame('76', $row['diastolic']);
        $this->assertSame('Normal', $row['bp_status']);
        $this->assertTrue($row['visual_no_screening']);
        $this->assertFalse($row['visual_blurred']);
        $this->assertSame('118/76', $row['bp_reading']);
    }

    public function test_hv14_none_exclusive_rules_remain_valid(): void
    {
        $params = $this->memberA() + [
            'assessmentId' => 'RA-002',
            'section' => 'red-flags',
        ];

        $this->put(
            route('household-profiling.members.risk-assessment.section.update', $params),
            ['red_flags' => ['chest_pain', 'none']]
        )->assertRedirect();

        $this->assertSame(
            ['none'],
            DemoRiskAssessment::find('HH-151', 'MB-001', 'RA-002')['red_flags']
        );

        $this->assertSame(
            ['none'],
            DemoRiskAssessment::applyNoneExclusive(['asthma', 'none', 'copd'])
        );
    }

    public function test_hv15_history_date_filter_behavior_remains_unchanged(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15'));

        $response = $this->get(route(
            'household-profiling.members.risk-assessment',
            $this->memberA() + ['date' => 'this_month']
        ));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, 'data-risk-assess-row'));
        $this->assertStringContainsString('data-conducted-at="2026-06-08"', $html);
        $this->assertStringContainsString('Filter risk assessments by date conducted', $html);
        $this->assertStringContainsString('>Custom range</option>', $html);
        $this->assertStringNotContainsString('lml-risk-assess__date-clear', $html);

        $rows = DemoRiskAssessment::forMember('HH-151', 'MB-001');
        $this->assertCount(1, DemoRiskAssessment::filterByDate($rows, 'this_month'));
    }

    public function test_hv16_history_section_routes_resolve_and_named_routes_exist(): void
    {
        $params = $this->memberA() + [
            'assessmentId' => 'RA-001',
            'section' => 'physical',
        ];

        $this->assertSame(
            url('/household-profiling/HH-151/members/MB-001/risk-assessment/RA-001/physical'),
            route('household-profiling.members.risk-assessment.section', $params)
        );
        $this->assertSame(
            url('/household-profiling/HH-151/members/MB-001/risk-assessment/RA-001/physical/edit'),
            route('household-profiling.members.risk-assessment.section.edit', $params)
        );

        foreach ([
            'household-profiling.members.risk-assessment.section',
            'household-profiling.members.risk-assessment.section.edit',
            'household-profiling.members.risk-assessment.section.update',
        ] as $name) {
            $this->assertNotNull(Route::getRoutes()->getByName($name));
        }

        $this->get(route(
            'household-profiling.members.risk-assessment.section',
            $params
        ))->assertOk();
    }

    public function test_unknown_assessment_update_does_not_create_record(): void
    {
        $before = DemoRiskAssessment::forMember('HH-151', 'MB-001');

        $this->put(
            route(
                'household-profiling.members.risk-assessment.section.update',
                $this->memberA() + [
                    'assessmentId' => 'RA-999',
                    'section' => 'red-flags',
                ]
            ),
            ['red_flags' => ['chest_pain']]
        )->assertForbidden();

        $this->assertNull(DemoRiskAssessment::find('HH-151', 'MB-001', 'RA-999'));
        $this->assertCount(count($before), DemoRiskAssessment::forMember('HH-151', 'MB-001'));
    }
}
