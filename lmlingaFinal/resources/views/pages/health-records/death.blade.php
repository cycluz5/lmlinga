{{--
    Health Records — Death barangay-wide listing (Figma-aligned).

    Data: App\Support\HealthRecordsDeath — UI-phase fixture rows only.
    Independent of Household Profiling DemoDeath. Filters are client-side.
    Export has no destination in this UI phase.
--}}
@extends('layouts.dashboard')

@section('title', 'Death - LMLinga')

@section('content')
    @php
        $rows = $rows ?? [];
        $zones = $zones ?? [];
        $causes = $causes ?? [];
        $years = $years ?? [];
        $summary = $summary ?? ['total' => 0, 'female' => 0, 'male' => 0];
        $totalUnfiltered = $totalUnfiltered ?? count($rows);
        $pageDescription = 'Record and management of death details for monitoring and tracking mortality status.';
        $hasRows = $totalUnfiltered > 0;
    @endphp

    <div
        class="lml-hr-death"
        data-lml-hr-death
        data-death-data-mode="ui-phase-fixture"
        data-total="{{ $totalUnfiltered }}"
    >
        <div class="lml-hr-death__panel">
            <header class="lml-hr-death__top">
                <div class="lml-hr-death__title-block">
                    <h2 class="lml-hr-death__title" id="lml-hr-death-heading">Death</h2>
                    <p class="lml-hr-death__description" id="lml-hr-death-desc">
                        {{ $pageDescription }}
                    </p>
                </div>

                <div class="lml-hr-death__actions">
                    <button
                        type="button"
                        class="lml-hr-death__export-btn"
                        data-hr-death-export
                        disabled
                        aria-disabled="true"
                        aria-label="Export Death data"
                        aria-describedby="lml-hr-death-export-note"
                    >
                        <i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i>
                        <span>Export Data</span>
                    </button>
                    <p id="lml-hr-death-export-note" class="visually-hidden">
                        Export is not available. No export destination is implemented for Death records.
                    </p>
                </div>
            </header>

            <div
                class="lml-hr-death__stats"
                role="group"
                aria-label="Death barangay summary"
            >
                <article class="lml-hr-death__card lml-hr-death__card--total">
                    <div class="lml-hr-death__card-body">
                        <p class="lml-hr-death__card-label">Total Deaths</p>
                        <p class="lml-hr-death__card-value" data-death-stat="total">{{ $summary['total'] }}</p>
                    </div>
                    <span class="lml-hr-death__card-icon" aria-hidden="true">
                        <i class="bi bi-archive"></i>
                    </span>
                </article>

                <article class="lml-hr-death__card lml-hr-death__card--female">
                    <div class="lml-hr-death__card-body">
                        <p class="lml-hr-death__card-label">Female</p>
                        <p class="lml-hr-death__card-value" data-death-stat="female">{{ $summary['female'] }}</p>
                    </div>
                    <span class="lml-hr-death__card-icon" aria-hidden="true">
                        <i class="bi bi-gender-female"></i>
                    </span>
                </article>

                <article class="lml-hr-death__card lml-hr-death__card--male">
                    <div class="lml-hr-death__card-body">
                        <p class="lml-hr-death__card-label">Male</p>
                        <p class="lml-hr-death__card-value" data-death-stat="male">{{ $summary['male'] }}</p>
                    </div>
                    <span class="lml-hr-death__card-icon" aria-hidden="true">
                        <i class="bi bi-gender-male"></i>
                    </span>
                </article>
            </div>

            <div
                class="lml-hr-death__filters"
                role="search"
                aria-label="Death search and filters"
            >
                <div class="lml-hr-death__search">
                    <label class="visually-hidden" for="lml-hr-death-search">Search Name</label>
                    <i class="bi bi-search lml-hr-death__search-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        id="lml-hr-death-search"
                        class="lml-hr-death__search-input lml-focus-ring"
                        data-hr-death-search
                        placeholder="Search Name"
                        autocomplete="off"
                    >
                </div>

                <div class="lml-hr-death__select-wrap">
                    <label class="visually-hidden" for="lml-hr-death-zone">Filter by zone</label>
                    <select
                        id="lml-hr-death-zone"
                        class="lml-hr-death__select lml-focus-ring"
                        data-hr-death-zone
                    >
                        <option value="all">All Zones</option>
                        @foreach ($zones as $zone)
                            <option value="{{ $zone }}">{{ $zone }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down lml-hr-death__select-icon" aria-hidden="true"></i>
                </div>

                <div class="lml-hr-death__select-wrap">
                    <label class="visually-hidden" for="lml-hr-death-cause">Filter by cause of death</label>
                    <select
                        id="lml-hr-death-cause"
                        class="lml-hr-death__select lml-focus-ring"
                        data-hr-death-cause
                    >
                        <option value="all">Cause of Death</option>
                        @foreach ($causes as $cause)
                            <option value="{{ $cause }}">{{ $cause }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down lml-hr-death__select-icon" aria-hidden="true"></i>
                </div>

                <div class="lml-hr-death__select-wrap">
                    <label class="visually-hidden" for="lml-hr-death-sex">Filter by sex</label>
                    <select
                        id="lml-hr-death-sex"
                        class="lml-hr-death__select lml-focus-ring"
                        data-hr-death-sex
                    >
                        <option value="all">Sex</option>
                        <option value="female">Female</option>
                        <option value="male">Male</option>
                    </select>
                    <i class="bi bi-chevron-down lml-hr-death__select-icon" aria-hidden="true"></i>
                </div>

                <div class="lml-hr-death__select-wrap">
                    <label class="visually-hidden" for="lml-hr-death-year">Filter by year</label>
                    <select
                        id="lml-hr-death-year"
                        class="lml-hr-death__select lml-focus-ring"
                        data-hr-death-year
                    >
                        <option value="all">Year</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down lml-hr-death__select-icon" aria-hidden="true"></i>
                </div>
            </div>

            <p class="lml-hr-death__results visually-hidden" data-hr-death-results aria-live="polite">
                Showing {{ $totalUnfiltered }} of {{ $totalUnfiltered }} death records
            </p>

            <div class="lml-hr-death__table-card">
                <div
                    class="lml-hr-death__table-scroll"
                    tabindex="0"
                    aria-labelledby="lml-hr-death-heading"
                    aria-describedby="lml-hr-death-desc"
                    @if (! $hasRows) hidden @endif
                >
                    <table class="lml-hr-death__table">
                        <caption class="visually-hidden">
                            Death records by full name, age, cause of death, and date of death.
                            Rows are UI-phase preview/demo values.
                        </caption>
                        <colgroup>
                            <col class="lml-hr-death__col lml-hr-death__col--name">
                            <col class="lml-hr-death__col lml-hr-death__col--age">
                            <col class="lml-hr-death__col lml-hr-death__col--cause">
                            <col class="lml-hr-death__col lml-hr-death__col--date">
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">Full Name</th>
                                <th scope="col">Age</th>
                                <th scope="col">Cause of Death</th>
                                <th scope="col">Date of Death</th>
                            </tr>
                        </thead>
                        <tbody data-hr-death-tbody>
                            @foreach ($rows as $row)
                                <tr
                                    data-hr-death-row
                                    data-name="{{ strtolower($row['full_name']) }}"
                                    data-zone="{{ $row['zone'] }}"
                                    data-cause="{{ $row['cause_of_death'] }}"
                                    data-sex="{{ $row['sex_filter'] }}"
                                    data-year="{{ $row['year'] }}"
                                    data-row-key="{{ $row['key'] }}"
                                >
                                    <th scope="row" class="lml-hr-death__cell lml-hr-death__cell--name">
                                        {{ $row['full_name'] }}
                                    </th>
                                    <td class="lml-hr-death__cell lml-hr-death__cell--age">
                                        {{ $row['age'] }}
                                    </td>
                                    <td class="lml-hr-death__cell lml-hr-death__cell--cause">
                                        {{ $row['cause_of_death'] }}
                                    </td>
                                    <td class="lml-hr-death__cell lml-hr-death__cell--date">
                                        {{ $row['date_of_death'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div
                    class="lml-hr-death__empty"
                    data-hr-death-empty
                    role="status"
                    @if ($hasRows) hidden @endif
                >
                    <div class="lml-hr-death__empty-icon" aria-hidden="true">
                        <i class="bi bi-journal-x"></i>
                    </div>
                    <p class="lml-hr-death__empty-title" data-hr-death-empty-title>
                        @if ($hasRows)
                            No death records match the selected filters.
                        @else
                            No death records have been recorded yet.
                        @endif
                    </p>
                    <p class="lml-hr-death__empty-hint" data-hr-death-empty-hint>
                        @if ($hasRows)
                            Try adjusting search, zone, cause, sex, or year.
                        @else
                            Death Information is recorded through Household Profiling for a selected member.
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
