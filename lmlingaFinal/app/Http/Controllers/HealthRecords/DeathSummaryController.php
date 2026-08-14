<?php

namespace App\Http\Controllers\HealthRecords;

use App\Http\Controllers\Controller;
use App\Support\HealthRecordsDeath;
use Illuminate\View\View;

class DeathSummaryController extends Controller
{
    public function index(): View
    {
        $rows = HealthRecordsDeath::rows();
        $summary = HealthRecordsDeath::summaryCounts($rows);

        return view('pages.health-records.death', [
            'active' => 'death',
            'pageTitle' => 'Death',
            'pageSubtitle' => 'Record and management of death details for monitoring and tracking mortality status.',
            'rows' => $rows,
            'summary' => $summary,
            'zones' => HealthRecordsDeath::zones(),
            'causes' => HealthRecordsDeath::causes($rows),
            'years' => HealthRecordsDeath::years($rows),
            'totalUnfiltered' => count($rows),
        ]);
    }
}
