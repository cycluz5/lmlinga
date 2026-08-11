{{--
    Household Profiling — Maternal Care Phase 1 (UI preview).
    Session-backed demo state only. No database persistence.
--}}
@extends('layouts.dashboard')

@section('title', ($demoMember['name'] ?? 'Member') . ' — Maternal Care - LMLinga')

@section('content')
    @php
        use App\Support\DemoMaternalCare;

        $mcMode = $mcMode ?? 'landing';
        $pregnancy = $pregnancy ?? null;
        $history = $history ?? [];
        $routeParams = [
            'householdNo' => $householdNo,
            'memberId' => $memberId,
        ];
        $statusMessage = session('status');
    @endphp

    <div
        class="lml-mc"
        data-lml-mc
        data-lml-mc-mode="{{ $mcMode }}"
        data-demo="true"
        data-persistence="session-preview"
        data-household-no="{{ $householdNo }}"
        data-member-id="{{ $memberId }}"
        @if ($demoMember)
            data-member-name="{{ $demoMember['name'] }}"
        @endif
    >
        @if ($statusMessage)
            <p class="lml-mc__toast" role="status" data-mc-toast>
                {{ $statusMessage }}
            </p>
        @endif

        @if (! $demoHousehold || ! $demoMember)
            <section class="lml-mc__not-found" aria-labelledby="lml-mc-nf-title">
                <h2 id="lml-mc-nf-title" class="lml-mc__not-found-title">
                    Member not found
                </h2>
                <p class="lml-mc__not-found-message">
                    @if (! $demoHousehold)
                        No demo household matches <strong>{{ $householdNo }}</strong>.
                    @else
                        No demo member <strong>{{ $memberId }}</strong> belongs to household
                        <strong>{{ $householdNo }}</strong>.
                    @endif
                </p>
                <a
                    href="{{ $demoHousehold ? route('household-profiling.view', ['householdNo' => $householdNo]) : route('household-profiling.index') }}"
                    class="lml-mc__not-found-link lml-focus-ring"
                >
                    {{ $demoHousehold ? 'Back to Household' : 'Return to Household List' }}
                </a>
            </section>
        @else
            @include('pages.household-profiling.partials.maternal-care-member-card', [
                'demoMember' => $demoMember,
                'householdNo' => $householdNo,
                'memberId' => $memberId,
                'pregnancy' => $pregnancy,
                'showActivePregnancy' => is_array($pregnancy) && ($pregnancy['status'] ?? '') === 'active',
                'backUrl' => in_array($mcMode, ['landing', 'overview', 'register', 'history'], true)
                    ? route('household-profiling.members.show', $routeParams)
                    : route('household-profiling.members.maternal-care.index', $routeParams),
                'backLabel' => in_array($mcMode, ['landing', 'overview', 'register', 'history'], true)
                    ? 'Back to Health Summary Records for '.$demoMember['name']
                    : 'Back to Maternal Care overview for '.$demoMember['name'],
            ])

            @if ($mcMode === 'landing')
                @include('pages.household-profiling.partials.maternal-care-landing')
            @elseif ($mcMode === 'register')
                @include('pages.household-profiling.partials.maternal-care-register', [
                    'routeParams' => $routeParams,
                ])
            @elseif ($mcMode === 'overview')
                @include('pages.household-profiling.partials.maternal-care-overview', [
                    'pregnancy' => $pregnancy,
                    'routeParams' => $routeParams,
                    'history' => $history,
                ])
            @elseif ($mcMode === 'history')
                @include('pages.household-profiling.partials.maternal-care-history', [
                    'history' => $history,
                    'pregnancy' => $pregnancy,
                    'routeParams' => $routeParams,
                ])
            @elseif ($mcMode === 'trans-out')
                @include('pages.household-profiling.partials.maternal-care-trans-out', [
                    'pregnancy' => $pregnancy,
                    'routeParams' => $routeParams,
                ])
            @elseif ($mcMode === 'prenatal')
                @include('pages.household-profiling.partials.maternal-care-prenatal', [
                    'pregnancy' => $pregnancy,
                    'routeParams' => $routeParams,
                ])
            @elseif ($mcMode === 'immunizations')
                @include('pages.household-profiling.partials.maternal-care-immunizations', [
                    'pregnancy' => $pregnancy,
                    'routeParams' => $routeParams,
                ])
            @elseif ($mcMode === 'supplementations')
                @include('pages.household-profiling.partials.maternal-care-supplementations', [
                    'pregnancy' => $pregnancy,
                    'routeParams' => $routeParams,
                ])
            @elseif ($mcMode === 'laboratory')
                @include('pages.household-profiling.partials.maternal-care-laboratory', [
                    'pregnancy' => $pregnancy,
                    'routeParams' => $routeParams,
                ])
            @elseif ($mcMode === 'delivery')
                @include('pages.household-profiling.partials.maternal-care-delivery', [
                    'pregnancy' => $pregnancy,
                    'routeParams' => $routeParams,
                ])
            @elseif ($mcMode === 'postnatal')
                @include('pages.household-profiling.partials.maternal-care-postnatal', [
                    'pregnancy' => $pregnancy,
                    'routeParams' => $routeParams,
                ])
            @endif
        @endif
    </div>
@endsection
