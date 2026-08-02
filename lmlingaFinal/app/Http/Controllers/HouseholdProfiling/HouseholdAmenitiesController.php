<?php

namespace App\Http\Controllers\HouseholdProfiling;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateHouseholdAmenitiesRequest;
use App\Support\DemoCatalog;
use App\Support\DemoHouseholdWaterSupply;
use App\Support\DemoSpotMappingHandoff;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HouseholdAmenitiesController extends Controller
{
    public function show(string $householdNo): View
    {
        $key = DemoCatalog::normalizeHouseholdNo($householdNo);
        $household = DemoCatalog::findHousehold($key);
        $record = DemoHouseholdWaterSupply::findForActor($key);
        $linked = DemoHouseholdWaterSupply::findLinkedForActor($key);

        return view('pages.household-profiling.amenities-show', [
            'active' => 'household-profiling',
            'pageTitle' => 'Household Profiling',
            'pageSubtitle' => $household
                ? 'Review household amenities details.'
                : 'Demo household was not found.',
            'householdNo' => $key,
            'demoHousehold' => $household,
            'amenitiesRecord' => $record,
            'linkedContext' => $linked,
            'socioeconomicStatus' => DemoHouseholdWaterSupply::socioeconomicStatusLabel($record, $household),
            'validationTestingStatus' => DemoHouseholdWaterSupply::validationTestingStatus($record),
            'completeSanitationStatus' => DemoHouseholdWaterSupply::deriveCompleteSanitationFacilityStatus($record),
        ]);
    }

    public function edit(string $householdNo): View|RedirectResponse
    {
        $key = DemoCatalog::normalizeHouseholdNo($householdNo);
        $household = DemoCatalog::findHousehold($key);

        if (! is_array($household)) {
            return redirect()
                ->route('household-profiling.amenities.show', ['householdNo' => $key])
                ->withErrors(['household_no' => DemoHouseholdWaterSupply::NOT_FOUND_MESSAGE]);
        }

        $stored = DemoHouseholdWaterSupply::find($key);
        if (is_array($stored) && (string) ($stored['actor_id'] ?? '') !== DemoSpotMappingHandoff::actorKey()) {
            return redirect()
                ->route('household-profiling.amenities.show', ['householdNo' => $key])
                ->withErrors(['household_no' => DemoSpotMappingHandoff::INVALID_MESSAGE]);
        }

        $record = DemoHouseholdWaterSupply::findForActor($key);
        $linked = DemoHouseholdWaterSupply::findLinkedForActor($key);

        return view('pages.household-profiling.amenities-edit', [
            'active' => 'household-profiling',
            'pageTitle' => 'Household Profiling',
            'pageSubtitle' => 'Edit household amenities details.',
            'householdNo' => $key,
            'demoHousehold' => $household,
            'amenitiesRecord' => $record,
            'linkedContext' => $linked,
            'socioeconomicStatus' => DemoHouseholdWaterSupply::socioeconomicStatusLabel(
                is_array($record) ? $record : $linked,
                $household
            ),
        ]);
    }

    public function update(UpdateHouseholdAmenitiesRequest $request, string $householdNo): RedirectResponse
    {
        $validated = $request->validated();
        $key = DemoCatalog::normalizeHouseholdNo($householdNo);

        DemoHouseholdWaterSupply::saveStep1($key, $validated);
        DemoHouseholdWaterSupply::saveStep2($key, $validated);
        DemoHouseholdWaterSupply::saveStep3($key, $validated);
        DemoHouseholdWaterSupply::saveStep4($key, $validated);

        return redirect()
            ->route('household-profiling.amenities.show', ['householdNo' => $key])
            ->with('status', 'Household amenities details saved successfully.');
    }
}
