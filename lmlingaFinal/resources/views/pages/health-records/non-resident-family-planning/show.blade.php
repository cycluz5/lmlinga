{{--
    Health Records → Family Planning → Non-Resident client details (Figma).
--}}
@extends('layouts.dashboard')

@section('title', ($client['full_name'] ?? 'Client') . ' — Non-Residents Family Planning - LMLinga')

@section('content')
    @php
        use App\Support\HealthRecordsNonResidentFamilyPlanning;

        $listingUrl = route('health-records.family-planning.non-residents.index');
        $client = $client ?? null;
        $commoditiesLedger = $commoditiesLedger ?? [];
        $visits = $client !== null && is_array($client['visits'] ?? null) ? $client['visits'] : [];
    @endphp

    <div
        class="lml-hr-fp-nr"
        data-lml-hr-fp-nr
        data-lml-hr-fp-nr-mode="show"
    >
        @if ($client === null)
            <section class="lml-hr-fp-nr__not-found" aria-labelledby="lml-hr-fp-nr-nf-title">
                <h2 id="lml-hr-fp-nr-nf-title">Client not found</h2>
                <p>No demo non-resident client matches this record.</p>
                <a href="{{ $listingUrl }}" class="lml-hr-fp-nr__not-found-link lml-focus-ring">
                    Back to Non-Residents Client listing
                </a>
            </section>
        @else
            @php
                $addVisitUrl = route('health-records.family-planning.non-residents.visits.create', [
                    'clientKey' => $client['key'],
                ]);
            @endphp

            @include('pages.health-records.non-resident-family-planning.partials.workflow-nav', [
                'backUrl' => $listingUrl,
            ])

            @include('pages.health-records.non-resident-family-planning.partials.client-identity-banner', [
                'client' => $client,
                'clientKey' => $client['key'],
                'backUrl' => $listingUrl,
                'hideBannerBack' => true,
                'showAddVisit' => true,
                'addVisitUrl' => $addVisitUrl,
            ])

            <section class="lml-hr-fp-nr__detail-panel" aria-labelledby="lml-hr-fp-nr-detail-title">
                <h3 id="lml-hr-fp-nr-detail-title" class="visually-hidden">Client record details</h3>

                <div class="lml-hr-fp-nr__detail-section">
                    <h4 class="lml-hr-fp-nr__detail-heading">Client Information</h4>
                    <dl class="lml-hr-fp-nr__info-list">
                        <div class="lml-hr-fp-nr__info-item">
                            <dt>Birthday</dt>
                            <dd>{{ HealthRecordsNonResidentFamilyPlanning::formatBirthdayLong($client['birthday']) }}</dd>
                        </div>
                        <div class="lml-hr-fp-nr__info-item">
                            <dt>Civil Status</dt>
                            <dd>{{ $client['civil_status'] !== '' ? $client['civil_status'] : '—' }}</dd>
                        </div>
                        <div class="lml-hr-fp-nr__info-item lml-hr-fp-nr__info-item--full">
                            <dt>Address</dt>
                            <dd>{{ HealthRecordsNonResidentFamilyPlanning::formatAddressLine($client) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="lml-hr-fp-nr__detail-section">
                    <h4 class="lml-hr-fp-nr__detail-heading">Visit History</h4>
                    <div class="lml-hr-fp-nr__detail-table-wrap">
                        <table class="lml-hr-fp-nr__detail-table">
                            <caption class="visually-hidden">Recorded family planning visits for this client</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Visit Date</th>
                                    <th scope="col">Remarks</th>
                                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($visits as $visit)
                                    @php
                                        $editUrl = route('health-records.family-planning.non-residents.visits.edit', [
                                            'clientKey' => $client['key'],
                                            'visitId' => $visit['id'],
                                        ]);
                                    @endphp
                                    <tr>
                                        <td>
                                            <time datetime="{{ $visit['visited_at'] }}">
                                                {{ HealthRecordsNonResidentFamilyPlanning::formatVisitDateShort($visit['visited_at']) }}
                                            </time>
                                        </td>
                                        <td>{{ $visit['remarks'] !== '' ? $visit['remarks'] : '—' }}</td>
                                        <td class="lml-hr-fp-nr__cell-action">
                                            <a
                                                href="{{ $editUrl }}"
                                                class="lml-hr-fp-nr__edit-link lml-focus-ring"
                                                aria-label="Edit visit on {{ HealthRecordsNonResidentFamilyPlanning::formatVisitDateShort($visit['visited_at']) }}"
                                            >
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3">No visits recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="lml-hr-fp-nr__detail-section">
                    <h4 class="lml-hr-fp-nr__detail-heading">Commodities Given</h4>
                    <div class="lml-hr-fp-nr__detail-table-wrap">
                        <table class="lml-hr-fp-nr__detail-table">
                            <caption class="visually-hidden">Commodities dispensed across visits</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Commodity</th>
                                    <th scope="col">Quantity</th>
                                    <th scope="col">Date Given</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($commoditiesLedger as $row)
                                    <tr>
                                        <td>{{ $row['commodity'] }}</td>
                                        <td>{{ $row['quantity'] }}</td>
                                        <td>{{ $row['date_given'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3">No commodities recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif
    </div>
@endsection
