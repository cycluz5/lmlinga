<?php

namespace Tests\Feature;

use App\Support\DemoDeath;
use App\Support\UiRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Feature coverage for Household Profiling → member → Death Information Phase 1.
 */
class HouseholdProfilingDeathTest extends TestCase
{
    /**
     * @return array{householdNo: string, memberId: string}
     */
    private function memberParams(): array
    {
        return [
            'householdNo' => 'HH-151',
            'memberId' => 'MB-002',
        ];
    }

    public function test_named_routes_resolve_under_household_profiling_member(): void
    {
        $params = $this->memberParams();

        $this->assertSame(
            url('/household-profiling/HH-151/members/MB-002/death'),
            route('household-profiling.members.death.index', $params)
        );
        $this->assertSame(
            url('/household-profiling/HH-151/members/MB-002/death/create'),
            route('household-profiling.members.death.create', $params)
        );
        $this->assertSame(
            url('/household-profiling/HH-151/members/MB-002/death/edit'),
            route('household-profiling.members.death.edit', $params)
        );
    }

    public function test_routes_are_protected_by_ui_role_middleware(): void
    {
        foreach ([
            'household-profiling.members.death.index',
            'household-profiling.members.death.create',
            'household-profiling.members.death.store',
            'household-profiling.members.death.edit',
            'household-profiling.members.death.update',
        ] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains('ui.role', $route->gatherMiddleware());
        }
    }

    public function test_member_view_death_uses_named_route_link(): void
    {
        $params = $this->memberParams();
        $response = $this->get(route('household-profiling.members.show', $params));

        $response->assertOk();
        $response->assertSee('data-hh-member-death', false);
        $response->assertDontSee('data-hh-member-view-record="Death"', false);
        $response->assertSee(
            'href="'.e(route('household-profiling.members.death.index', $params)).'"',
            false
        );
    }

    public function test_health_summary_death_view_targets_index_not_create_or_edit(): void
    {
        $params = $this->memberParams();
        $response = $this->get(route('household-profiling.members.show', $params));
        $response->assertOk();
        $html = $response->getContent();

        $this->assertSame(
            1,
            preg_match(
                '/<a\b(?=[^>]*\bdata-hh-member-death\b)(?=[^>]*\bhref="([^"]+)")[^>]*>/i',
                $html,
                $matches
            )
        );

        $href = html_entity_decode($matches[1], ENT_QUOTES);
        $indexUrl = route('household-profiling.members.death.index', $params);
        $createUrl = route('household-profiling.members.death.create', $params);
        $editUrl = route('household-profiling.members.death.edit', $params);

        $this->assertSame($indexUrl, $href);
        $this->assertStringNotContainsString('/death/create', $href);
        $this->assertStringNotContainsString('/death/edit', $href);
        $this->assertNotSame($createUrl, $href);
        $this->assertNotSame($editUrl, $href);
        $this->assertStringContainsString('data-death-entry="index"', $html);
    }

    public function test_death_index_no_record_shows_alive_and_record_cta_to_create_only(): void
    {
        $params = $this->memberParams();
        $response = $this->get(route('household-profiling.members.death.index', $params));
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-lml-death-mode="empty"', $html);
        $this->assertStringContainsString('Person is still ALIVE', $html);
        $this->assertStringContainsString('Record death information', $html);
        $this->assertStringNotContainsString('data-lml-death-mode="create"', $html);
        $this->assertStringNotContainsString('data-lml-death-mode="edit"', $html);
        $this->assertStringNotContainsString('data-death-form', $html);
        $this->assertStringNotContainsString('name="cause_of_death"', $html);
        $this->assertStringNotContainsString('name="death_certificate"', $html);
        $this->assertStringNotContainsString('data-death-save', $html);

        $this->assertSame(
            1,
            preg_match(
                '/<a\b(?=[^>]*\bdata-death-record-cta\b)(?=[^>]*\bhref="([^"]+)")[^>]*>/i',
                $html,
                $matches
            )
        );
        $ctaHref = html_entity_decode($matches[1], ENT_QUOTES);
        $this->assertSame(
            route('household-profiling.members.death.create', $params),
            $ctaHref
        );
    }

    public function test_death_index_with_existing_record_is_read_only_until_edit(): void
    {
        $params = $this->memberParams();
        $this->post(route('household-profiling.members.death.store', $params), [
            'cause_of_death' => 'Indexed cause',
            'date_of_death' => '2026-04-01',
        ])->assertRedirect(route('household-profiling.members.death.index', $params));

        $index = $this->get(route('household-profiling.members.death.index', $params));
        $index->assertOk();
        $html = $index->getContent();

        $this->assertStringContainsString('data-lml-death-mode="view"', $html);
        $this->assertStringContainsString('Indexed cause', $html);
        $this->assertStringContainsString('data-death-edit', $html);
        $this->assertStringNotContainsString('data-lml-death-mode="edit"', $html);
        $this->assertStringNotContainsString('data-lml-death-mode="create"', $html);
        $this->assertStringNotContainsString('data-death-save', $html);
        $this->assertStringNotContainsString('data-death-form', $html);

        $this->assertSame(
            1,
            preg_match(
                '/<a\b(?=[^>]*\bdata-death-edit\b)(?=[^>]*\bhref="([^"]+)")[^>]*>/i',
                $html,
                $matches
            )
        );
        $editHref = html_entity_decode($matches[1], ENT_QUOTES);
        $this->assertSame(
            route('household-profiling.members.death.edit', $params),
            $editHref
        );

        $edit = $this->get($editHref);
        $edit->assertOk();
        $editHtml = $edit->getContent();
        $this->assertStringContainsString('data-lml-death-mode="edit"', $editHtml);
        $this->assertStringContainsString('data-death-save', $editHtml);
        $this->assertStringContainsString('name="cause_of_death"', $editHtml);
    }

    public function test_no_record_state_renders_alive_empty_surface(): void
    {
        $params = $this->memberParams();
        $response = $this->get(route('household-profiling.members.death.index', $params));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-lml-death-mode="empty"', $html);
        $this->assertStringContainsString('DEATH INFORMATION', $html);
        $this->assertStringContainsString('Track and monitor mortality of individual', $html);
        $this->assertStringContainsString('Person is still ALIVE', $html);
        $this->assertStringContainsString('Record death information', $html);
        $this->assertStringContainsString('data-death-record-cta', $html);
        $this->assertStringContainsString('data-household-no="HH-151"', $html);
        $this->assertStringContainsString('data-member-id="MB-002"', $html);
        $this->assertStringContainsString('Kristine Reyes', $html);
        $this->assertStringContainsString('lml-sidebar__link--active', $html);
        $this->assertStringContainsString('Household Profiling', $html);
    }

    public function test_create_surface_renders_required_fields_and_save(): void
    {
        $params = $this->memberParams();
        $response = $this->get(route('household-profiling.members.death.create', $params));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-lml-death-mode="create"', $html);
        $this->assertStringContainsString('Cause of Death', $html);
        $this->assertStringContainsString('Date of Death', $html);
        $this->assertStringContainsString('Death Certificate', $html);
        $this->assertStringContainsString('Choose File', $html);
        $this->assertStringContainsString('PNG, JPG, PDF', $html);
        $this->assertStringContainsString('SAVE', $html);
        $this->assertStringContainsString('name="cause_of_death"', $html);
        $this->assertStringContainsString('type="date"', $html);
        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringContainsString('name="death_certificate"', $html);
        $this->assertStringContainsString('accept="', $html);
    }

    public function test_store_shows_recorded_view_with_edit_and_does_not_duplicate(): void
    {
        $params = $this->memberParams();

        $store = $this->post(route('household-profiling.members.death.store', $params), [
            'cause_of_death' => 'Pneumonia',
            'date_of_death' => '2026-03-15',
        ]);
        $store->assertRedirect(route('household-profiling.members.death.index', $params));

        $view = $this->get(route('household-profiling.members.death.index', $params));
        $view->assertOk();
        $html = $view->getContent();

        $this->assertStringContainsString('data-lml-death-mode="view"', $html);
        $this->assertStringContainsString('Pneumonia', $html);
        $this->assertStringContainsString('March 15, 2026', $html);
        $this->assertStringContainsString('data-death-edit', $html);
        $this->assertStringContainsString('EDIT', $html);
        $this->assertSame(1, substr_count($html, 'data-death-recorded'));
        $this->assertSame(1, substr_count($html, 'data-death-record-fields'));

        $again = $this->post(route('household-profiling.members.death.store', $params), [
            'cause_of_death' => 'Pneumonia (updated)',
            'date_of_death' => '2026-03-16',
        ]);
        $again->assertRedirect(route('household-profiling.members.death.index', $params));

        $view2 = $this->get(route('household-profiling.members.death.index', $params));
        $html2 = $view2->getContent();
        $this->assertStringContainsString('Pneumonia (updated)', $html2);
        $this->assertSame(1, substr_count($html2, 'data-death-recorded'));
        $this->assertSame(1, substr_count($html2, 'data-death-record-fields'));
        $this->assertSame(1, substr_count($html2, 'data-death-view-cause'));
    }

    public function test_edit_mode_returns_editable_fields_and_save_returns_to_read_mode(): void
    {
        $params = $this->memberParams();
        $this->post(route('household-profiling.members.death.store', $params), [
            'cause_of_death' => 'Cardiac arrest',
            'date_of_death' => '2025-12-01',
        ]);

        $edit = $this->get(route('household-profiling.members.death.edit', $params));
        $edit->assertOk();
        $editHtml = $edit->getContent();
        $this->assertStringContainsString('data-lml-death-mode="edit"', $editHtml);
        $this->assertStringContainsString('name="cause_of_death"', $editHtml);
        $this->assertStringContainsString('value="Cardiac arrest"', $editHtml);
        $this->assertStringContainsString('value="2025-12-01"', $editHtml);
        $this->assertStringContainsString('SAVE', $editHtml);
        $this->assertStringContainsString('data-death-save', $editHtml);

        $update = $this->put(route('household-profiling.members.death.update', $params), [
            'cause_of_death' => 'Cardiac arrest — confirmed',
            'date_of_death' => '2025-12-02',
        ]);
        $update->assertRedirect(route('household-profiling.members.death.index', $params));

        $view = $this->get(route('household-profiling.members.death.index', $params));
        $html = $view->getContent();
        $this->assertStringContainsString('data-lml-death-mode="view"', $html);
        $this->assertStringContainsString('Cardiac arrest — confirmed', $html);
        $this->assertStringContainsString('December 2, 2025', $html);
        $this->assertStringContainsString('EDIT', $html);
    }

    public function test_certificate_selection_stores_safe_filename_only_without_local_path(): void
    {
        $params = $this->memberParams();
        $file = UploadedFile::fake()->create('report.pdf', 120, 'application/pdf');

        $store = $this->post(route('household-profiling.members.death.store', $params), [
            'cause_of_death' => 'Trauma',
            'date_of_death' => '2026-01-10',
            'death_certificate' => $file,
        ]);
        $store->assertRedirect(route('household-profiling.members.death.index', $params));

        $record = DemoDeath::record($params['householdNo'], $params['memberId']);
        $this->assertNotNull($record);
        $this->assertIsArray($record['certificate'] ?? null);
        $this->assertSame('report.pdf', $record['certificate']['original_name']);
        $this->assertArrayNotHasKey('path', $record['certificate']);
        $this->assertArrayNotHasKey('realpath', $record['certificate']);
        $this->assertArrayNotHasKey('tmp_name', $record['certificate']);

        $view = $this->get(route('household-profiling.members.death.index', $params));
        $html = $view->getContent();
        $this->assertStringContainsString('report.pdf', $html);
        $this->assertStringNotContainsString('C:\\', $html);
        $this->assertStringNotContainsString('/tmp/', $html);
        $this->assertStringContainsString('session preview', strtolower($html));
    }

    public function test_records_are_scoped_per_member_and_do_not_leak(): void
    {
        $a = ['householdNo' => 'HH-151', 'memberId' => 'MB-001'];
        $b = ['householdNo' => 'HH-151', 'memberId' => 'MB-002'];

        $this->post(route('household-profiling.members.death.store', $a), [
            'cause_of_death' => 'Member A cause',
            'date_of_death' => '2026-02-01',
        ])->assertRedirect();

        $viewB = $this->get(route('household-profiling.members.death.index', $b));
        $viewB->assertOk();
        $htmlB = $viewB->getContent();
        $this->assertStringContainsString('data-lml-death-mode="empty"', $htmlB);
        $this->assertStringNotContainsString('Member A cause', $htmlB);
        $this->assertStringContainsString('Person is still ALIVE', $htmlB);

        $viewA = $this->get(route('household-profiling.members.death.index', $a));
        $this->assertStringContainsString('Member A cause', $viewA->getContent());
    }

    public function test_unrelated_member_destinations_remain_intact(): void
    {
        $params = $this->memberParams();

        $this->get(route('household-profiling.members.show', $params))->assertOk();
        $this->get(route('household-profiling.members.risk-assessment', $params))->assertOk();
        $this->get(route('household-profiling.members.family-planning.index', $params))->assertOk();
        $this->get(route('household-profiling.members.maternal-care.index', $params))->assertOk();
    }

    public function test_household_profiling_remains_sidebar_active(): void
    {
        $this->withSession([UiRole::SESSION_KEY => 'bhw'])
            ->get(route(
                'household-profiling.members.death.index',
                $this->memberParams()
            ))
            ->assertOk();

        $this->assertSame(
            'household-profiling',
            UiRole::sidebarActiveKey('household-profiling')
        );
    }
}
