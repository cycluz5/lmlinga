{{--
    Household Profiling — Risk Assessment History (UI preview).
    Resident-specific. Assessment is optional — empty history is valid.
    Demo catalog only; no persistence.
--}}
@extends('layouts.dashboard')

@section('title', ($demoMember['name'] ?? 'Member') . ' — Risk Assessment History - LMLinga')

@section('content')
    @php
        use App\Support\DemoRiskAssessment;

        $allowedFilters = ['all', 'this_month', 'last_3_months', 'this_year', 'custom'];
        $rawFilter = is_string(request('date')) ? trim((string) request('date')) : '';
        $filterDate = in_array($rawFilter, $allowedFilters, true) ? $rawFilter : '';
        $filterFrom = is_string(request('from')) ? trim((string) request('from')) : '';
        $filterTo = is_string(request('to')) ? trim((string) request('to')) : '';
        if ($filterFrom !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterFrom)) {
            $filterFrom = '';
        }
        if ($filterTo !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterTo)) {
            $filterTo = '';
        }

        $allAssessments = $assessments ?? [];
        $filteredAssessments = DemoRiskAssessment::filterByDate(
            $allAssessments,
            $filterDate !== '' ? $filterDate : null,
            $filterFrom !== '' ? $filterFrom : null,
            $filterTo !== '' ? $filterTo : null
        );
        $showCustomRange = $filterDate === 'custom';

        $createUrl = route('household-profiling.members.risk-assessment.create', [
            'householdNo' => $householdNo,
            'memberId' => $memberId,
        ]);
        $historyUrl = route('household-profiling.members.risk-assessment', [
            'householdNo' => $householdNo,
            'memberId' => $memberId,
        ]);
    @endphp

    <div
        class="lml-risk-assess"
        data-lml-risk-assess
        data-lml-risk-assess-mode="history"
        data-demo="true"
        data-household-no="{{ $householdNo }}"
        data-member-id="{{ $memberId }}"
        @if ($demoMember)
            data-member-name="{{ $demoMember['name'] }}"
        @endif
    >
        <div
            class="lml-risk-assess__toast"
            data-risk-assess-toast
            role="status"
            aria-live="polite"
            hidden
        ></div>

        @if (! $demoHousehold || ! $demoMember)
            <section class="lml-risk-assess__not-found" aria-labelledby="lml-risk-assess-nf-title">
                <h2 id="lml-risk-assess-nf-title" class="lml-risk-assess__not-found-title">
                    Member not found
                </h2>
                <p class="lml-risk-assess__not-found-message">
                    @if (! $demoHousehold)
                        No demo household matches <strong>{{ $householdNo }}</strong>.
                    @else
                        No demo member <strong>{{ $memberId }}</strong> belongs to household
                        <strong>{{ $householdNo }}</strong>.
                    @endif
                </p>
                <a
                    href="{{ $demoHousehold ? route('household-profiling.view', ['householdNo' => $householdNo]) : route('household-profiling.index') }}"
                    class="lml-risk-assess__not-found-link lml-focus-ring"
                >
                    {{ $demoHousehold ? 'Back to Household' : 'Return to Household List' }}
                </a>
            </section>
        @else
            @include('pages.household-profiling.partials.risk-assessment-member-card', [
                'demoMember' => $demoMember,
                'householdNo' => $householdNo,
                'memberId' => $memberId,
            ])

            <section
                class="lml-risk-assess__panel"
                aria-labelledby="lml-risk-assess-history-title"
                data-risk-assess-history
            >
                <header class="lml-risk-assess__panel-head">
                    <div class="lml-risk-assess__panel-titles">
                        <h2 id="lml-risk-assess-history-title" class="lml-risk-assess__panel-title">
                            <i
                                class="bi bi-clipboard2-pulse lml-risk-assess__panel-title-icon"
                                aria-hidden="true"
                                data-risk-assess-history-icon
                            ></i>
                            <span>RISK ASSESSMENT HISTORY</span>
                        </h2>
                        <p class="lml-risk-assess__panel-subtitle">
                            View previous risk assessments conducted for this individual
                        </p>
                    </div>

                    <div class="lml-risk-assess__panel-controls">
                        <form
                            id="lml-risk-assess-date-filter-form"
                            class="lml-risk-assess__date-filter"
                            method="get"
                            action="{{ $historyUrl }}"
                            data-risk-assess-date-filter
                        >
                            <div class="lml-risk-assess__date-control">
                                <i
                                    class="bi bi-funnel lml-risk-assess__date-control-icon"
                                    data-risk-assess-date-icon-funnel
                                    @if ($showCustomRange) hidden @endif
                                    aria-hidden="true"
                                ></i>
                                <i
                                    class="bi bi-calendar3 lml-risk-assess__date-control-icon"
                                    data-risk-assess-date-icon-calendar
                                    @unless ($showCustomRange) hidden @endunless
                                    aria-hidden="true"
                                ></i>
                                <select
                                    id="lml-risk-assess-date"
                                    name="date"
                                    class="lml-risk-assess__date-select lml-focus-ring"
                                    data-risk-assess-date-select
                                    aria-label="Filter risk assessments by date conducted"
                                    aria-controls="lml-risk-assess-custom-range"
                                >
                                    <option value="" @selected($filterDate === '')>Date</option>
                                    <option value="all" @selected($filterDate === 'all')>All Dates</option>
                                    <option value="this_month" @selected($filterDate === 'this_month')>This Month</option>
                                    <option value="last_3_months" @selected($filterDate === 'last_3_months')>Last 3 Months</option>
                                    <option value="this_year" @selected($filterDate === 'this_year')>This Year</option>
                                    <option value="custom" @selected($filterDate === 'custom')>Custom range</option>
                                </select>
                                <i
                                    class="bi bi-chevron-down lml-risk-assess__date-control-chevron"
                                    aria-hidden="true"
                                ></i>
                            </div>
                        </form>

                        <a
                            href="{{ $createUrl }}"
                            class="lml-risk-assess__add-btn lml-focus-ring"
                            data-risk-assess-add
                        >
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                            <span>ADD</span>
                        </a>
                    </div>
                </header>

                <div
                    id="lml-risk-assess-custom-range"
                    class="lml-risk-assess__custom-range"
                    data-risk-assess-custom-range
                    @unless ($showCustomRange) hidden @endunless
                >
                    <div class="lml-risk-assess__custom-range-fields">
                        <div class="lml-risk-assess__custom-range-field">
                            <label
                                class="lml-risk-assess__custom-range-label"
                                for="lml-risk-assess-date-from"
                            >
                                From
                            </label>
                            <input
                                id="lml-risk-assess-date-from"
                                type="date"
                                name="from"
                                form="lml-risk-assess-date-filter-form"
                                value="{{ $filterFrom }}"
                                class="lml-risk-assess__custom-range-input lml-focus-ring"
                                data-risk-assess-date-from
                                @unless ($showCustomRange) disabled @endunless
                                autocomplete="off"
                            >
                        </div>
                        <div class="lml-risk-assess__custom-range-field">
                            <label
                                class="lml-risk-assess__custom-range-label"
                                for="lml-risk-assess-date-to"
                            >
                                To
                            </label>
                            <input
                                id="lml-risk-assess-date-to"
                                type="date"
                                name="to"
                                form="lml-risk-assess-date-filter-form"
                                value="{{ $filterTo }}"
                                class="lml-risk-assess__custom-range-input lml-focus-ring"
                                data-risk-assess-date-to
                                @unless ($showCustomRange) disabled @endunless
                                autocomplete="off"
                            >
                        </div>
                    </div>
                </div>

                @if (count($allAssessments) === 0)
                    <div
                        class="lml-risk-assess__empty"
                        data-risk-assess-empty
                        role="status"
                    >
                        <p class="lml-risk-assess__empty-title">
                            No risk assessments recorded for this resident.
                        </p>
                        <p class="lml-risk-assess__empty-hint">
                            Risk Assessment is optional. Use ADD when a health worker initiates an assessment.
                        </p>
                    </div>
                @elseif (count($filteredAssessments) === 0)
                    <div
                        class="lml-risk-assess__empty"
                        data-risk-assess-empty-filtered
                        role="status"
                    >
                        <p class="lml-risk-assess__empty-title">
                            No risk assessments match the selected date.
                        </p>
                        <p class="lml-risk-assess__empty-hint">
                            Select All Dates to see all assessments for this resident.
                        </p>
                    </div>
                @else
                    <div class="lml-risk-assess__table-scroll" tabindex="0">
                        <table class="lml-risk-assess__table">
                            <caption class="visually-hidden">
                                Risk assessment history for {{ $demoMember['name'] }}
                            </caption>
                            <thead>
                                <tr>
                                    <th scope="col">Date Conducted</th>
                                    <th scope="col">BP Reading</th>
                                    <th scope="col">BMI</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($filteredAssessments as $row)
                                    @php
                                        $viewUrl = route('household-profiling.members.risk-assessment.show', [
                                            'householdNo' => $householdNo,
                                            'memberId' => $memberId,
                                            'assessmentId' => $row['id'],
                                        ]);
                                    @endphp
                                    <tr
                                        data-risk-assess-row
                                        data-conducted-at="{{ $row['conducted_at'] }}"
                                    >
                                        <td>
                                            <span class="lml-risk-assess__date-cell">
                                                <i class="bi bi-calendar3" aria-hidden="true"></i>
                                                <time datetime="{{ $row['conducted_at'] }}">
                                                    {{ DemoRiskAssessment::formatConductedDate((string) $row['conducted_at']) }}
                                                </time>
                                            </span>
                                        </td>
                                        <td>{{ $row['bp_reading'] }}</td>
                                        <td>{{ $row['bmi_label'] }}</td>
                                        <td>
                                            <a
                                                href="{{ $viewUrl }}"
                                                class="lml-risk-assess__view-link lml-focus-ring"
                                                data-risk-assess-view
                                            >
                                                View
                                                <span aria-hidden="true">→</span>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif
    </div>
@endsection
