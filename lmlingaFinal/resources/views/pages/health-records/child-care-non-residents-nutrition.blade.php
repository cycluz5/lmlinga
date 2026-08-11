{{-- Health Records → Child Care → Non-Residents nutritional history (UI-phase). --}}
@extends('layouts.dashboard')

@section('title', 'Nutritional Status — Child Care - LMLinga')

@section('content')
    @php
        $listingUrl = route('health-records.child-care.non-residents.index');
        $showUrl = isset($child['key'])
            ? route('health-records.child-care.non-residents.show', ['childKey' => $child['key']])
            : $listingUrl;
        $createUrl = isset($child['key'])
            ? route('health-records.child-care.non-residents.nutrition.create', ['childKey' => $child['key']])
            : $listingUrl;
        $infantRecords = $infantRecords ?? [];
        $childRecords = $childRecords ?? [];
        $hasHistory = $infantRecords !== [] || $childRecords !== [];
    @endphp

    <div class="lml-hr-cc-nr" data-lml-hr-cc-nr data-lml-hr-cc-nr-mode="nutrition">
        <div class="lml-hr-cc-nr__toast" data-hr-cc-nr-toast role="status" aria-live="polite" hidden></div>

        <a href="{{ $showUrl }}" class="lml-hr-cc-nr__page-back lml-focus-ring" aria-label="Back to child record">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
        </a>

        @include('pages.health-records.partials.child-care-non-residents-profile', ['child' => $child])

        @if ($child)
            <section class="lml-hr-cc-nr__history-panel" aria-labelledby="lml-hr-cc-nr-history-title">
                <div class="lml-hr-cc-nr__history-head">
                    <div>
                        <h3 class="lml-hr-cc-nr__dash-title" id="lml-hr-cc-nr-history-title">Nutritional Status</h3>
                        <p class="lml-hr-cc-nr__dash-sub">Track the growth of the child</p>
                    </div>
                    <a href="{{ $createUrl }}" class="lml-hr-cc-nr__save-btn lml-focus-ring" data-hr-cc-nr-add-record>
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        Add Record
                    </a>
                </div>

                @if (! $hasHistory)
                    <div class="lml-hr-cc-nr__age-box" role="status">
                        <div class="lml-hr-cc-nr__empty">
                            <p class="lml-hr-cc-nr__empty-title">No nutritional measurements are recorded for this child.</p>
                            <p class="lml-hr-cc-nr__empty-hint">Add a record to start tracking growth.</p>
                        </div>
                    </div>
                @else
                    @foreach ([
                        ['title' => '0–12 Months Record', 'rows' => $infantRecords, 'key' => 'infant'],
                        ['title' => '1–5 Years Old Record', 'rows' => $childRecords, 'key' => 'child'],
                    ] as $group)
                        <article
                            class="lml-hr-cc-nr__age-box"
                            data-hr-cc-nr-age-group="{{ $group['key'] }}"
                            aria-labelledby="lml-hr-cc-nr-age-{{ $group['key'] }}"
                        >
                            <h4 class="lml-hr-cc-nr__history-group" id="lml-hr-cc-nr-age-{{ $group['key'] }}">{{ $group['title'] }}</h4>
                            @if ($group['rows'] === [])
                                <p class="lml-hr-cc-nr__empty-hint">No records in this age group.</p>
                            @else
                                <div class="lml-hr-cc-nr__table-scroll" tabindex="0">
                                    <table class="lml-hr-cc-nr__table lml-hr-cc-nr__table--history">
                                        <caption class="visually-hidden">{{ $group['title'] }}</caption>
                                        <thead>
                                            <tr>
                                                <th scope="col">Date</th>
                                                <th scope="col">Child Age</th>
                                                <th scope="col">Weight</th>
                                                <th scope="col">Height</th>
                                                <th scope="col">MUAC</th>
                                                <th scope="col">Progress</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($group['rows'] as $row)
                                                <tr>
                                                    <th scope="row">{{ $row['date_label'] }}</th>
                                                    <td>{{ $row['age_label'] }}</td>
                                                    <td>{{ $row['weight_kg'] !== null ? number_format($row['weight_kg'], 1).' kg' : '—' }}</td>
                                                    <td>{{ $row['height_cm'] !== null ? number_format($row['height_cm'], 1).' cm' : '—' }}</td>
                                                    <td>{{ $row['muac_cm'] !== null ? number_format($row['muac_cm'], 1).' cm' : '—' }}</td>
                                                    <td>{{ $row['progress'] }}</td>
                                                    <td>
                                                        @if (filled($row['status']))
                                                            <span class="lml-hr-cc-nr__status-pill @switch($row['status'])
                                                                @case('Needs Monitoring') lml-hr-cc-nr__status-pill--watch @break
                                                                @case('Below Normal') lml-hr-cc-nr__status-pill--below @break
                                                                @case('Above Normal') lml-hr-cc-nr__status-pill--above @break
                                                            @endswitch">{{ $row['status'] }}</span>
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a
                                                            href="{{ route('health-records.child-care.non-residents.nutrition.edit', ['childKey' => $child['key'], 'measurementId' => $row['id']]) }}"
                                                            class="lml-hr-cc-nr__view-btn lml-focus-ring"
                                                        >
                                                            Edit
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </article>
                    @endforeach
                @endif
            </section>
        @endif
    </div>
@endsection
