<?php

namespace App\Http\Controllers\HouseholdProfiling;

use App\Http\Controllers\Controller;
use App\Support\DemoMaternalCare;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaternalCareController extends Controller
{
    public function index(string $householdNo, string $memberId): View
    {
        $ctx = DemoMaternalCare::resolveMember($householdNo, $memberId);
        $active = $ctx['member']
            ? DemoMaternalCare::activePregnancy($ctx['householdNo'], $ctx['memberId'])
            : null;
        $history = $ctx['member']
            ? DemoMaternalCare::history($ctx['householdNo'], $ctx['memberId'])
            : [];

        $mode = $active ? 'overview' : 'landing';

        return view('pages.household-profiling.maternal-care', [
            'active' => 'household-profiling',
            'pageTitle' => 'Maternal Care',
            'pageSubtitle' => $ctx['member']
                ? 'Maternal care for '.$ctx['member']['name'].' in '.$ctx['householdNo'].'.'
                : 'Demo member was not found.',
            'householdNo' => $ctx['householdNo'],
            'memberId' => $ctx['memberId'],
            'demoHousehold' => $ctx['household'],
            'demoMember' => $ctx['member'],
            'mcMode' => $mode,
            'pregnancy' => $active,
            'history' => $history,
            'section' => null,
        ]);
    }

    public function register(string $householdNo, string $memberId): View|RedirectResponse
    {
        $ctx = DemoMaternalCare::resolveMember($householdNo, $memberId);
        if ($ctx['member'] && DemoMaternalCare::activePregnancy($ctx['householdNo'], $ctx['memberId'])) {
            return redirect()->route('household-profiling.members.maternal-care.index', [
                'householdNo' => $ctx['householdNo'],
                'memberId' => $ctx['memberId'],
            ]);
        }

        return view('pages.household-profiling.maternal-care', [
            'active' => 'household-profiling',
            'pageTitle' => 'Maternal Care',
            'pageSubtitle' => $ctx['member']
                ? 'Register maternal record for '.$ctx['member']['name'].'.'
                : 'Demo member was not found.',
            'householdNo' => $ctx['householdNo'],
            'memberId' => $ctx['memberId'],
            'demoHousehold' => $ctx['household'],
            'demoMember' => $ctx['member'],
            'mcMode' => 'register',
            'pregnancy' => null,
            'history' => $ctx['member']
                ? DemoMaternalCare::history($ctx['householdNo'], $ctx['memberId'])
                : [],
            'section' => null,
        ]);
    }

    public function store(Request $request, string $householdNo, string $memberId): RedirectResponse
    {
        $ctx = DemoMaternalCare::resolveMember($householdNo, $memberId);
        if (! $ctx['member']) {
            return redirect()->route('household-profiling.members.maternal-care.index', [
                'householdNo' => $ctx['householdNo'],
                'memberId' => $ctx['memberId'],
            ]);
        }

        if (DemoMaternalCare::activePregnancy($ctx['householdNo'], $ctx['memberId'])) {
            return redirect()->route('household-profiling.members.maternal-care.index', [
                'householdNo' => $ctx['householdNo'],
                'memberId' => $ctx['memberId'],
            ]);
        }

        DemoMaternalCare::register($ctx['householdNo'], $ctx['memberId'], $request->all());

        return redirect()
            ->route('household-profiling.members.maternal-care.index', [
                'householdNo' => $ctx['householdNo'],
                'memberId' => $ctx['memberId'],
            ])
            ->with('status', 'Preview only: Maternal record was kept in this browser session and was not permanently saved.');
    }

    public function history(string $householdNo, string $memberId): View
    {
        return $this->sectionView($householdNo, $memberId, 'history');
    }

    public function transOut(string $householdNo, string $memberId): View|RedirectResponse
    {
        return $this->requireActiveSection($householdNo, $memberId, 'trans-out');
    }

    public function prenatal(string $householdNo, string $memberId): View|RedirectResponse
    {
        return $this->requireActiveSection($householdNo, $memberId, 'prenatal');
    }

    public function immunizations(string $householdNo, string $memberId): View|RedirectResponse
    {
        return $this->requireActiveSection($householdNo, $memberId, 'immunizations');
    }

    public function supplementations(string $householdNo, string $memberId): View|RedirectResponse
    {
        return $this->requireActiveSection($householdNo, $memberId, 'supplementations');
    }

    public function laboratory(string $householdNo, string $memberId): View|RedirectResponse
    {
        return $this->requireActiveSection($householdNo, $memberId, 'laboratory');
    }

    public function delivery(string $householdNo, string $memberId): View|RedirectResponse
    {
        return $this->requireActiveSection($householdNo, $memberId, 'delivery');
    }

    public function postnatal(string $householdNo, string $memberId): View|RedirectResponse
    {
        return $this->requireActiveSection($householdNo, $memberId, 'postnatal');
    }

    public function updateSection(
        Request $request,
        string $householdNo,
        string $memberId,
        string $section
    ): RedirectResponse {
        $ctx = DemoMaternalCare::resolveMember($householdNo, $memberId);
        $allowed = [
            'prenatal',
            'immunizations',
            'supplementations',
            'laboratory',
            'delivery',
            'postnatal',
            'trans-out',
        ];
        $sectionKey = strtolower(trim($section));
        if (! in_array($sectionKey, $allowed, true) || ! $ctx['member']) {
            return redirect()->route('household-profiling.members.maternal-care.index', [
                'householdNo' => $ctx['householdNo'],
                'memberId' => $ctx['memberId'],
            ]);
        }

        $updated = DemoMaternalCare::updateSection(
            $ctx['householdNo'],
            $ctx['memberId'],
            $sectionKey,
            $request->all()
        );

        if ($updated === null) {
            return redirect()->route('household-profiling.members.maternal-care.index', [
                'householdNo' => $ctx['householdNo'],
                'memberId' => $ctx['memberId'],
            ]);
        }

        if ($sectionKey === 'trans-out') {
            return redirect()
                ->route('household-profiling.members.maternal-care.history', [
                    'householdNo' => $ctx['householdNo'],
                    'memberId' => $ctx['memberId'],
                ])
                ->with('status', 'Preview only: Trans-Out was kept in this browser session and was not permanently saved.');
        }

        $routeMap = [
            'prenatal' => 'household-profiling.members.maternal-care.prenatal',
            'immunizations' => 'household-profiling.members.maternal-care.immunizations',
            'supplementations' => 'household-profiling.members.maternal-care.supplementations',
            'laboratory' => 'household-profiling.members.maternal-care.laboratory',
            'delivery' => 'household-profiling.members.maternal-care.delivery',
            'postnatal' => 'household-profiling.members.maternal-care.postnatal',
        ];

        return redirect()
            ->route($routeMap[$sectionKey], [
                'householdNo' => $ctx['householdNo'],
                'memberId' => $ctx['memberId'],
            ])
            ->with('status', 'Preview only: Maternal Care section was kept in this browser session and was not permanently saved.');
    }

    private function requireActiveSection(
        string $householdNo,
        string $memberId,
        string $section
    ): View|RedirectResponse {
        $ctx = DemoMaternalCare::resolveMember($householdNo, $memberId);
        $active = $ctx['member']
            ? DemoMaternalCare::activePregnancy($ctx['householdNo'], $ctx['memberId'])
            : null;

        if (! $active) {
            return redirect()->route('household-profiling.members.maternal-care.index', [
                'householdNo' => $ctx['householdNo'],
                'memberId' => $ctx['memberId'],
            ]);
        }

        return $this->sectionView($householdNo, $memberId, $section, $active);
    }

    private function sectionView(
        string $householdNo,
        string $memberId,
        string $section,
        ?array $active = null
    ): View {
        $ctx = DemoMaternalCare::resolveMember($householdNo, $memberId);
        $pregnancy = $active;
        if ($pregnancy === null && $ctx['member']) {
            $pregnancy = DemoMaternalCare::activePregnancy($ctx['householdNo'], $ctx['memberId']);
        }

        return view('pages.household-profiling.maternal-care', [
            'active' => 'household-profiling',
            'pageTitle' => 'Maternal Care',
            'pageSubtitle' => $ctx['member']
                ? 'Maternal care for '.$ctx['member']['name'].' in '.$ctx['householdNo'].'.'
                : 'Demo member was not found.',
            'householdNo' => $ctx['householdNo'],
            'memberId' => $ctx['memberId'],
            'demoHousehold' => $ctx['household'],
            'demoMember' => $ctx['member'],
            'mcMode' => $section,
            'pregnancy' => $pregnancy,
            'history' => $ctx['member']
                ? DemoMaternalCare::history($ctx['householdNo'], $ctx['memberId'])
                : [],
            'section' => $section,
        ]);
    }
}
