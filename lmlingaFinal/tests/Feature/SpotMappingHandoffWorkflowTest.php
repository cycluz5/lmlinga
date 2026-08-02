<?php

namespace Tests\Feature;

use App\Support\DemoHouseholdWaterSupply;
use App\Support\DemoSpotMappingHandoff;
use App\Support\DemoSpotMappingPlot;
use App\Support\UiRole;
use Tests\TestCase;

class SpotMappingHandoffWorkflowTest extends TestCase
{
    /**
     * @dataProvider shellRolesProvider
     */
    public function test_legitimate_plot_handoff_workflow_per_role(string $role): void
    {
        $this->withSession([
            UiRole::SESSION_KEY => $role,
        ]);

        $payload = [
            'household_no' => 'HH-501',
            'house_head' => 'Maria Santos',
            'household_type' => 'HHTS',
            'zone' => '2',
            'lat' => 13.3811,
            'lng' => 123.4306,
            'consent' => true,
            'client_marker_id' => 'client-marker-demo',
        ];

        $issue = $this->postJson(route('spot-mapping.plot-handoff'), $payload);
        $issue->assertOk();
        $issue->assertJsonStructure(['handoff_token', 'plot_id', 'expires_in_seconds']);

        $token = (string) $issue->json('handoff_token');
        $plotId = (string) $issue->json('plot_id');

        $this->assertSame(64, strlen($token));
        $this->assertStringStartsWith('SMP-', $plotId);

        $step1 = $this->get(route('environmental-health.household-water-supply', [
            'handoff' => $token,
        ]));

        $step1->assertRedirect(route('environmental-health.household-water-supply', [
            'household' => 'HH-501',
        ]));

        $follow = $this->followRedirects($step1);
        $follow->assertOk();
        $follow->assertSee('Household Water Supply Information', false);
        $follow->assertSee('value="HH-501"', false);

        $plot = DemoSpotMappingPlot::find($plotId);
        $this->assertSame(DemoSpotMappingPlot::STATUS_WATER_SUPPLY_LINKED, $plot['status'] ?? null);
        $this->assertTrue(DemoHouseholdWaterSupply::isLinkedForActor('HH-501'));
    }

    public function test_fabricated_plot_id_cannot_issue_token(): void
    {
        $this->assertNull(DemoSpotMappingHandoff::issueForPlotId('fabricated-plot-id'));
    }

    public function test_direct_household_query_without_linkage_is_rejected(): void
    {
        $response = $this->get(route('environmental-health.household-water-supply', [
            'household' => 'HH-999',
        ]));

        $response->assertRedirect(route('spot-mapping.index'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function shellRolesProvider(): array
    {
        return [
            'bns' => ['bns'],
            'bhw' => ['bhw'],
            'bspo' => ['bspo'],
            'admin' => ['admin'],
        ];
    }
}
