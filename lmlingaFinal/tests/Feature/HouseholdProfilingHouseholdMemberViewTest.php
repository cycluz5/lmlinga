<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Feature coverage for Household Profiling — View Member Information,
 * focused on Health Summary Records / Child Care accordion.
 */
class HouseholdProfilingHouseholdMemberViewTest extends TestCase
{
    public function test_member_page_renders_child_care_accordion_defaults(): void
    {
        $response = $this->get(route('household-profiling.members.show', [
            'householdNo' => 'HH-151',
            'memberId' => 'MB-001',
        ]));

        $response->assertOk();
        $response->assertSee('data-hh-member-child-care-toggle', false);
        $response->assertSee('>Child Care</span>', false);
        $response->assertSee('aria-expanded="false"', false);
        $response->assertSee('aria-controls="lml-hh-mv-child-care-panel"', false);
        $this->assertMatchesRegularExpression(
            '/id="lml-hh-mv-child-care-panel"[^>]*\bhidden\b/u',
            $response->getContent()
        );
        $this->assertSame(1, substr_count($response->getContent(), 'id="lml-hh-mv-child-care-panel"'));
        $this->assertSame(1, substr_count($response->getContent(), 'id="lml-hh-mv-child-care-toggle"'));
    }

    public function test_child_care_panel_contains_three_module_links_once_each(): void
    {
        $response = $this->get(route('household-profiling.members.show', [
            'householdNo' => 'HH-151',
            'memberId' => 'MB-001',
        ]));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, 'Child Immunization'));
        $this->assertSame(1, substr_count($html, 'School-Based Immunization'));
        $this->assertSame(1, substr_count($html, 'Child Nutrition'));
        $this->assertSame(1, substr_count($html, '>Child Care</span>'));
    }

    public function test_child_care_links_use_named_routes(): void
    {
        $response = $this->get(route('household-profiling.members.show', [
            'householdNo' => 'HH-151',
            'memberId' => 'MB-001',
        ]));

        $response->assertOk();

        $params = [
            'householdNo' => 'HH-151',
            'memberId' => 'MB-001',
        ];

        $response->assertSee(
            'href="'.e(route('household-profiling.members.child-immunization', $params)).'"',
            false
        );
        $response->assertSee(
            'href="'.e(route('household-profiling.members.school-based-immunization', $params)).'"',
            false
        );
        $response->assertSee(
            'href="'.e(route('household-profiling.members.child-nutrition', $params)).'"',
            false
        );
    }

    public function test_remaining_health_summary_records_remain_visible(): void
    {
        $response = $this->get(route('household-profiling.members.show', [
            'householdNo' => 'HH-151',
            'memberId' => 'MB-001',
        ]));

        $response->assertOk();
        $response->assertSee('Risk Assessment', false);
        $response->assertSee('Family Planning', false);
        $response->assertSee('Maternal', false);
        $response->assertSee('Death', false);
        $response->assertSee('data-hh-member-view-record="Risk Assessment"', false);
        $response->assertSee('data-hh-member-view-record="Family Planning"', false);
        $response->assertSee('data-hh-member-view-record="Maternal"', false);
        $response->assertSee('data-hh-member-view-record="Death"', false);
    }

    public function test_member_edit_and_delete_controls_remain_present(): void
    {
        $response = $this->get(route('household-profiling.members.show', [
            'householdNo' => 'HH-151',
            'memberId' => 'MB-001',
        ]));

        $response->assertOk();

        $editUrl = route('household-profiling.members.edit', [
            'householdNo' => 'HH-151',
            'memberId' => 'MB-001',
        ]);

        $response->assertSee('href="'.e($editUrl).'"', false);
        $response->assertSee('data-hh-member-view-delete', false);
        $response->assertSee('>Edit</span>', false);
        $response->assertSee('Delete', false);
    }

    public function test_child_care_named_routes_remain_resolvable(): void
    {
        $this->assertSame(
            url('/household-profiling/HH-151/members/MB-001/child-immunization'),
            route('household-profiling.members.child-immunization', [
                'householdNo' => 'HH-151',
                'memberId' => 'MB-001',
            ])
        );
        $this->assertSame(
            url('/household-profiling/HH-151/members/MB-001/school-based-immunization'),
            route('household-profiling.members.school-based-immunization', [
                'householdNo' => 'HH-151',
                'memberId' => 'MB-001',
            ])
        );
        $this->assertSame(
            url('/household-profiling/HH-151/members/MB-001/child-nutrition'),
            route('household-profiling.members.child-nutrition', [
                'householdNo' => 'HH-151',
                'memberId' => 'MB-001',
            ])
        );
    }

    #[DataProvider('pendingChildCareModulesProvider')]
    public function test_pending_child_care_stub_redirects_with_flash_and_member_markup(
        string $routeName,
        string $moduleLabel
    ): void {
        $params = [
            'householdNo' => 'HH-151',
            'memberId' => 'MB-001',
        ];
        $showUrl = route('household-profiling.members.show', $params);

        $response = $this->get(route($routeName, $params));

        $response->assertRedirect($showUrl);
        $response->assertSessionHas('lml_pending_health_module', $moduleLabel);

        $followed = $this->followRedirects($response);
        $followed->assertOk();
        $followed->assertSee('data-pending-health-module="'.$moduleLabel.'"', false);
        $followed->assertSee('data-household-no="HH-151"', false);
        $followed->assertSee('data-member-id="MB-001"', false);
        $followed->assertSee('>Child Care</span>', false);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function pendingChildCareModulesProvider(): array
    {
        return [
            'school-based immunization' => [
                'household-profiling.members.school-based-immunization',
                'School-Based Immunization',
            ],
            'child nutrition' => [
                'household-profiling.members.child-nutrition',
                'Child Nutrition',
            ],
        ];
    }
}
