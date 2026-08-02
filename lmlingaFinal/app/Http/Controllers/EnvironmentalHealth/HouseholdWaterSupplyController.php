<?php

namespace App\Http\Controllers\EnvironmentalHealth;

use App\Http\Controllers\Controller;
use App\Http\Requests\IssueSpotMappingHandoffRequest;
use App\Http\Requests\StoreHouseholdWaterSupplyRequest;
use App\Http\Requests\StoreHouseholdWaterSupplyStep2Request;
use App\Http\Requests\StoreHouseholdWaterSupplyStep3Request;
use App\Http\Requests\StoreHouseholdWaterSupplyStep4Request;
use App\Support\DemoHouseholdWaterSupply;
use App\Support\DemoSpotMappingHandoff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HouseholdWaterSupplyController extends Controller
{
    /**
     * Step 1 — Household Water Supply Information.
     * Authority comes from a consumed Spot Mapping handoff token, never from household query alone.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $handoffToken = trim((string) $request->query('handoff', ''));

        if ($handoffToken !== '') {
            $record = DemoSpotMappingHandoff::consume($handoffToken);

            if ($record === null) {
                return redirect()
                    ->route('spot-mapping.index')
                    ->withErrors([
                        'handoff' => DemoSpotMappingHandoff::INVALID_MESSAGE,
                    ]);
            }

            $householdNo = DemoHouseholdWaterSupply::linkFromHandoff($record);

            if ($householdNo === null) {
                return redirect()
                    ->route('spot-mapping.index')
                    ->withErrors([
                        'handoff' => DemoSpotMappingHandoff::INVALID_MESSAGE,
                    ]);
            }

            // PRG: drop token from the URL after single-use consume.
            return redirect()->route('environmental-health.household-water-supply', [
                'household' => $householdNo,
            ]);
        }

        $rawHouseholdNo = trim((string) $request->query('household', ''));

        if ($rawHouseholdNo === '') {
            return $this->step1View('');
        }

        if (! DemoHouseholdWaterSupply::isValidHouseholdNo($rawHouseholdNo)
            || ! DemoHouseholdWaterSupply::isLinkedForActor($rawHouseholdNo)) {
            return redirect()
                ->route('spot-mapping.index')
                ->withErrors([
                    'handoff' => DemoSpotMappingHandoff::INVALID_MESSAGE,
                ]);
        }

        return $this->step1View(
            DemoHouseholdWaterSupply::normalizeHouseholdNo($rawHouseholdNo)
        );
    }

    /**
     * Create a trusted Spot Mapping plot, then issue a short-lived handoff token.
     */
    public function issueHandoff(IssueSpotMappingHandoffRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = DemoSpotMappingHandoff::createPlotAndIssue($validated);

        if ($result === null) {
            return response()->json([
                'message' => DemoSpotMappingHandoff::INVALID_MESSAGE,
            ], 422);
        }

        return response()->json([
            'handoff_token' => $result['handoff_token'],
            'plot_id' => $result['plot_id'],
            'expires_in_seconds' => DemoSpotMappingHandoff::TTL_MINUTES * 60,
        ]);
    }

    /**
     * Persist Step 1 (demo session store) and continue to Step 2.
     */
    public function store(StoreHouseholdWaterSupplyRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $householdNo = (string) $validated['household_no'];

        DemoHouseholdWaterSupply::saveStep1($householdNo, $validated);

        return redirect()->route('environmental-health.household-water-supply.step2', [
            'householdNo' => $householdNo,
        ]);
    }

    /**
     * Step 2 — Validation / Random Sampling / Testing (optional).
     * Requires a completed Step 1 record for the household.
     */
    public function showStep2(string $householdNo): View|RedirectResponse
    {
        if (! DemoHouseholdWaterSupply::isValidHouseholdNo($householdNo)) {
            return redirect()
                ->route('spot-mapping.index')
                ->withErrors([
                    'handoff' => DemoSpotMappingHandoff::INVALID_MESSAGE,
                ]);
        }

        $normalized = DemoHouseholdWaterSupply::normalizeHouseholdNo($householdNo);

        if (! DemoHouseholdWaterSupply::hasCompletedStep1($normalized)) {
            $params = DemoHouseholdWaterSupply::isLinkedForActor($normalized)
                ? ['household' => $normalized]
                : [];

            return redirect()
                ->route(
                    $params === []
                        ? 'spot-mapping.index'
                        : 'environmental-health.household-water-supply',
                    $params
                )
                ->withErrors([
                    'household_no' => 'Please complete Household Water Supply Information (Step 1) before continuing to Step 2.',
                ]);
        }

        if (! DemoHouseholdWaterSupply::isRecognized($normalized)) {
            return redirect()
                ->route('spot-mapping.index')
                ->withErrors([
                    'handoff' => DemoSpotMappingHandoff::INVALID_MESSAGE,
                ]);
        }

        $savedRecord = DemoHouseholdWaterSupply::find($normalized);

        return view('pages.environmental-health.household-water-supply-step2', [
            'active' => 'spot-mapping',
            'pageTitle' => 'Spot Mapping',
            'pageSubtitle' => 'Complete the household environmental information after plotting the household location.',
            'householdNo' => $normalized,
            'savedRecord' => $savedRecord,
            'validationTestingStatus' => DemoHouseholdWaterSupply::validationTestingStatus($savedRecord),
        ]);
    }

    /**
     * Persist optional Step 2 (Part 1.2) test sections and continue to Part 2.
     * The {householdNo} route parameter is the authoritative household identity.
     */
    public function storeStep2(
        StoreHouseholdWaterSupplyStep2Request $request,
        string $householdNo
    ): RedirectResponse {
        $validated = $request->validated();
        $normalized = DemoHouseholdWaterSupply::normalizeHouseholdNo($householdNo);

        DemoHouseholdWaterSupply::saveStep2($normalized, $validated);

        return redirect()->route('environmental-health.household-water-supply.step3', [
            'householdNo' => $normalized,
        ]);
    }

    /**
     * Step 3 — Basic Sanitation Facility (Part 2, required).
     * Requires a completed Part 1.2 pass (optional test fields may be blank).
     */
    public function showStep3(string $householdNo): View|RedirectResponse
    {
        if (! DemoHouseholdWaterSupply::isValidHouseholdNo($householdNo)) {
            return redirect()
                ->route('spot-mapping.index')
                ->withErrors([
                    'handoff' => DemoSpotMappingHandoff::INVALID_MESSAGE,
                ]);
        }

        $normalized = DemoHouseholdWaterSupply::normalizeHouseholdNo($householdNo);

        if (! DemoHouseholdWaterSupply::hasCompletedStep2($normalized)) {
            if (DemoHouseholdWaterSupply::hasCompletedStep1($normalized)) {
                return redirect()
                    ->route('environmental-health.household-water-supply.step2', [
                        'householdNo' => $normalized,
                    ])
                    ->withErrors([
                        'household_no' => 'Please complete Validation / Random Sampling / Testing (Step 1.2) before continuing to Basic Sanitation Facility.',
                    ]);
            }

            $params = DemoHouseholdWaterSupply::isLinkedForActor($normalized)
                ? ['household' => $normalized]
                : [];

            return redirect()
                ->route(
                    $params === []
                        ? 'spot-mapping.index'
                        : 'environmental-health.household-water-supply',
                    $params
                )
                ->withErrors([
                    'household_no' => 'Please complete Household Water Supply Information (Step 1) before continuing.',
                ]);
        }

        if (! DemoHouseholdWaterSupply::isRecognized($normalized)) {
            return redirect()
                ->route('spot-mapping.index')
                ->withErrors([
                    'handoff' => DemoSpotMappingHandoff::INVALID_MESSAGE,
                ]);
        }

        return view('pages.environmental-health.household-water-supply-step3', [
            'active' => 'spot-mapping',
            'pageTitle' => 'Spot Mapping',
            'pageSubtitle' => 'Complete the household environmental information after plotting the household location.',
            'householdNo' => $normalized,
            'savedRecord' => DemoHouseholdWaterSupply::find($normalized),
        ]);
    }

    /**
     * Persist required Part 2 Basic Sanitation Facility and continue to Step 3 (Part 3).
     */
    public function storeStep3(StoreHouseholdWaterSupplyStep3Request $request): RedirectResponse
    {
        $validated = $request->validated();
        $householdNo = (string) $validated['household_no'];

        DemoHouseholdWaterSupply::saveStep3($householdNo, $validated);

        return redirect()->route('environmental-health.household-water-supply.step4', [
            'householdNo' => $householdNo,
        ]);
    }

    /**
     * Step 4 — Part 3 Solid Waste Management. Requires completed Part 2 (Basic Sanitation).
     */
    public function showStep4(string $householdNo): View|RedirectResponse
    {
        if (! DemoHouseholdWaterSupply::isValidHouseholdNo($householdNo)) {
            return redirect()
                ->route('spot-mapping.index')
                ->withErrors([
                    'handoff' => DemoSpotMappingHandoff::INVALID_MESSAGE,
                ]);
        }

        $normalized = DemoHouseholdWaterSupply::normalizeHouseholdNo($householdNo);

        if (! DemoHouseholdWaterSupply::hasCompletedStep3($normalized)) {
            if (DemoHouseholdWaterSupply::hasCompletedStep2($normalized)) {
                return redirect()
                    ->route('environmental-health.household-water-supply.step3', [
                        'householdNo' => $normalized,
                    ])
                    ->withErrors([
                        'household_no' => 'Please complete Basic Sanitation Facility before continuing to Step 3.',
                    ]);
            }

            if (DemoHouseholdWaterSupply::hasCompletedStep1($normalized)) {
                return redirect()
                    ->route('environmental-health.household-water-supply.step2', [
                        'householdNo' => $normalized,
                    ])
                    ->withErrors([
                        'household_no' => 'Please complete Validation / Random Sampling / Testing (Step 1.2) before continuing.',
                    ]);
            }

            $params = DemoHouseholdWaterSupply::isLinkedForActor($normalized)
                ? ['household' => $normalized]
                : [];

            return redirect()
                ->route(
                    $params === []
                        ? 'spot-mapping.index'
                        : 'environmental-health.household-water-supply',
                    $params
                )
                ->withErrors([
                    'household_no' => 'Please complete Household Water Supply Information (Step 1) before continuing.',
                ]);
        }

        if (! DemoHouseholdWaterSupply::isRecognized($normalized)) {
            return redirect()
                ->route('spot-mapping.index')
                ->withErrors([
                    'handoff' => DemoSpotMappingHandoff::INVALID_MESSAGE,
                ]);
        }

        return view('pages.environmental-health.household-water-supply-step4', [
            'active' => 'spot-mapping',
            'pageTitle' => 'Spot Mapping',
            'pageSubtitle' => 'Complete the household environmental information after plotting the household location.',
            'householdNo' => $normalized,
            'savedRecord' => DemoHouseholdWaterSupply::find($normalized),
        ]);
    }

    /**
     * Persist Part 3 Solid Waste Management and return to Spot Mapping.
     */
    public function storeStep4(StoreHouseholdWaterSupplyStep4Request $request): RedirectResponse
    {
        $validated = $request->validated();
        $householdNo = (string) $validated['household_no'];

        DemoHouseholdWaterSupply::saveStep4($householdNo, $validated);

        return redirect()
            ->route('spot-mapping.index')
            ->with('status', 'Solid Waste Management was saved.');
    }

    private function step1View(string $householdNo): View
    {
        return view('pages.environmental-health.household-water-supply', [
            'active' => 'spot-mapping',
            'pageTitle' => 'Spot Mapping',
            'pageSubtitle' => 'Complete the household environmental information after plotting the household location.',
            'householdNo' => $householdNo,
        ]);
    }
}
