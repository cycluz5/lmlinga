{{--
    Health Records → Family Planning → Non-Residents Client listing (Figma).
    UI-phase fixture; filters are client-side. Add / Export are preview toasts.
--}}
@extends('layouts.dashboard')

@section('title', 'Family Planning — Non-Residents Client - LMLinga')

@section('content')
    @php
        $clients = $clients ?? [];
        $barangays = $barangays ?? [];
        $years = $years ?? [];
        $totalUnfiltered = $totalUnfiltered ?? count($clients);
        $summaryUrl = route('health-records.family-planning.index');
        $createUrl = route('health-records.family-planning.non-residents.create');
    @endphp

    <div
        class="lml-hr-fp-nr"
        data-lml-hr-fp-nr
        data-lml-hr-fp-nr-mode="listing"
        data-total="{{ $totalUnfiltered }}"
    >
        <div class="lml-hr-fp-nr__panel">
            <header class="lml-hr-fp-nr__top">
                <div class="lml-hr-fp-nr__title-block">
                    <div class="lml-hr-fp-nr__title-row">
                        <h2 class="lml-hr-fp-nr__title" id="lml-hr-fp-nr-heading">Family Planning</h2>
                        <span
                            class="lml-hr-fp-nr__badge"
                            role="status"
                            aria-label="Non-residents client context"
                        >
                            Non - Residents Client
                        </span>
                    </div>
                    <p class="lml-hr-fp-nr__description" id="lml-hr-fp-nr-desc">
                        Family planning clients from outside the barangay.
                    </p>
                </div>

                <div class="lml-hr-fp-nr__actions" role="group" aria-label="Non-resident client actions">
                    <a
                        href="{{ $createUrl }}"
                        class="lml-hr-fp-nr__add-btn lml-focus-ring"
                        data-hr-fp-nr-add
                    >
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        <span>Add</span>
                    </a>
                    <button
                        type="button"
                        class="lml-hr-fp-nr__export-btn lml-focus-ring"
                        data-hr-fp-nr-export
                        aria-label="Export non-resident family planning data"
                    >
                        <i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i>
                        <span>Export Data</span>
                    </button>
                </div>
            </header>

            <p class="lml-hr-fp-nr__breadcrumb">
                <a href="{{ $summaryUrl }}" class="lml-hr-fp-nr__breadcrumb-link lml-focus-ring">
                    Family Planning Summary
                </a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Non-Residents Client</span>
            </p>

            <div
                class="lml-hr-fp-nr__toast"
                data-hr-fp-nr-toast
                role="status"
                aria-live="polite"
                hidden
            ></div>

            <div
                class="lml-hr-fp-nr__filters"
                role="toolbar"
                aria-label="Non-resident client search and filters"
            >
                <div class="lml-hr-fp-nr__search">
                    <label class="visually-hidden" for="lml-hr-fp-nr-search">Search Name</label>
                    <i class="bi bi-search lml-hr-fp-nr__search-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        id="lml-hr-fp-nr-search"
                        class="lml-hr-fp-nr__search-input lml-focus-ring"
                        data-hr-fp-nr-search
                        placeholder="Search Name"
                        autocomplete="off"
                    >
                </div>

                <div class="lml-hr-fp-nr__select-wrap">
                    <label class="visually-hidden" for="lml-hr-fp-nr-barangay">Filter by barangay</label>
                    <select
                        id="lml-hr-fp-nr-barangay"
                        class="lml-hr-fp-nr__select lml-focus-ring"
                        data-hr-fp-nr-barangay
                    >
                        <option value="all">Barangay</option>
                        @foreach ($barangays as $barangay)
                            <option value="{{ $barangay }}">{{ $barangay }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down lml-hr-fp-nr__select-icon" aria-hidden="true"></i>
                </div>

                <div class="lml-hr-fp-nr__select-wrap lml-hr-fp-nr__select-wrap--year">
                    <label class="visually-hidden" for="lml-hr-fp-nr-year">Filter by year</label>
                    <select
                        id="lml-hr-fp-nr-year"
                        class="lml-hr-fp-nr__select lml-focus-ring"
                        data-hr-fp-nr-year
                    >
                        <option value="all">Year</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down lml-hr-fp-nr__select-icon" aria-hidden="true"></i>
                </div>
            </div>

            <p class="lml-hr-fp-nr__results visually-hidden" data-hr-fp-nr-results aria-live="polite">
                Showing {{ $totalUnfiltered }} of {{ $totalUnfiltered }} non-resident clients
            </p>

            <div class="lml-hr-fp-nr__table-card">
                <div
                    class="lml-hr-fp-nr__table-scroll"
                    tabindex="0"
                    aria-labelledby="lml-hr-fp-nr-heading"
                    aria-describedby="lml-hr-fp-nr-desc"
                >
                    <table class="lml-hr-fp-nr__table">
                        <caption class="visually-hidden">
                            Non-resident family planning clients by full name, age, method, start date, and last visit.
                            UI-phase preview data only.
                        </caption>
                        <colgroup>
                            <col class="lml-hr-fp-nr__col lml-hr-fp-nr__col--name">
                            <col class="lml-hr-fp-nr__col lml-hr-fp-nr__col--age">
                            <col class="lml-hr-fp-nr__col lml-hr-fp-nr__col--method">
                            <col class="lml-hr-fp-nr__col lml-hr-fp-nr__col--date">
                            <col class="lml-hr-fp-nr__col lml-hr-fp-nr__col--date">
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">Full Name</th>
                                <th scope="col">Age</th>
                                <th scope="col">Method</th>
                                <th scope="col">Start Date</th>
                                <th scope="col">Last Visit</th>
                            </tr>
                        </thead>
                        <tbody data-hr-fp-nr-tbody>
                            @foreach ($clients as $client)
                                @php
                                    $showUrl = route('health-records.family-planning.non-residents.show', [
                                        'clientKey' => $client['key'],
                                    ]);
                                @endphp
                                <tr
                                    data-hr-fp-nr-row
                                    data-name="{{ strtolower($client['full_name']) }}"
                                    data-barangay="{{ $client['barangay'] }}"
                                    data-year="{{ $client['year'] }}"
                                >
                                    <th scope="row" class="lml-hr-fp-nr__cell lml-hr-fp-nr__cell--name">
                                        <a
                                            href="{{ $showUrl }}"
                                            class="lml-hr-fp-nr__row-link lml-focus-ring"
                                        >
                                            {{ $client['full_name'] }}
                                        </a>
                                    </th>
                                    <td class="lml-hr-fp-nr__cell lml-hr-fp-nr__cell--age">{{ $client['age'] ?? '—' }}</td>
                                    <td class="lml-hr-fp-nr__cell lml-hr-fp-nr__cell--method">{{ $client['method'] }}</td>
                                    <td class="lml-hr-fp-nr__cell lml-hr-fp-nr__cell--date">{{ $client['start_date'] }}</td>
                                    <td class="lml-hr-fp-nr__cell lml-hr-fp-nr__cell--date">{{ $client['last_visit'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div
                    class="lml-hr-fp-nr__empty"
                    data-hr-fp-nr-empty
                    role="status"
                    hidden
                >
                    <div class="lml-hr-fp-nr__empty-icon" aria-hidden="true">
                        <i class="bi bi-search"></i>
                    </div>
                    <p class="lml-hr-fp-nr__empty-title">
                        No non-resident clients match the selected filters.
                    </p>
                    <p class="lml-hr-fp-nr__empty-hint">Try adjusting search, barangay, or year.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
