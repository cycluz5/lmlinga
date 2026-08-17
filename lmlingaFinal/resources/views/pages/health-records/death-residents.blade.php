{{--
    Health Records — Death resident selection.
    Dedicated page opened from + Record death on the Death listing.
--}}
@extends('layouts.dashboard')

@section('title', 'Select a resident - Death - LMLinga')

@section('content')
    @php
        $residents = $residents ?? [];
    @endphp

    <div
        class="lml-hr-death"
        data-lml-hr-death
        data-death-data-mode="persisted"
    >
        <section
            class="lml-hr-death__panel"
            id="lml-hr-death-residents"
            aria-labelledby="lml-hr-death-residents-heading"
            data-hr-death-residents
        >
            <header class="lml-hr-death__residents-head">
                <div>
                    <h2 class="lml-hr-death__title" id="lml-hr-death-residents-heading">
                        Select a resident
                    </h2>
                    <p class="lml-hr-death__residents-hint">
                        A death submission must identify a specific resident. Open a resident to begin.
                    </p>
                    <a
                        href="{{ route('health-records.death.index') }}"
                        class="lml-hr-death__residents-back lml-focus-ring"
                    >
                        Back to Death records
                    </a>
                </div>
                <div class="lml-hr-death__search lml-hr-death__search--residents">
                    <label class="visually-hidden" for="lml-hr-death-resident-search">Search residents</label>
                    <i class="bi bi-search lml-hr-death__search-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        id="lml-hr-death-resident-search"
                        class="lml-hr-death__search-input lml-focus-ring"
                        data-hr-death-resident-search
                        placeholder="Search resident name"
                        autocomplete="off"
                    >
                </div>
            </header>

            <div class="lml-hr-death__table-scroll" tabindex="0">
                <table class="lml-hr-death__table">
                    <caption class="visually-hidden">
                        Residents available for death record submission.
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">Resident</th>
                            <th scope="col">Household</th>
                            <th scope="col">Zone</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody data-hr-death-resident-tbody>
                        @foreach ($residents as $resident)
                            @php
                                $identityParts = array_values(array_filter([
                                    (string) ($resident['sex'] ?? ''),
                                    (string) ($resident['age'] ?? ''),
                                    (string) ($resident['relationship'] ?? ''),
                                ], static fn (string $part): bool => $part !== '' && $part !== '—'));
                                $idParts = array_values(array_filter([
                                    (string) ($resident['member_id'] ?? ''),
                                    filled($resident['birthday_display'] ?? null)
                                        ? 'Born '.$resident['birthday_display']
                                        : '',
                                ]));
                                $actionVerb = $resident['can_submit'] ? 'Record death for' : 'Open death record for';
                                $ariaIdentity = implode(', ', array_filter([
                                    (string) $resident['full_name'],
                                    (string) ($resident['sex'] ?? ''),
                                    (string) ($resident['relationship'] ?? ''),
                                    (string) ($resident['member_id'] ?? ''),
                                ]));
                            @endphp
                            <tr
                                data-hr-death-resident-row
                                data-name="{{ strtolower($resident['full_name']) }}"
                                data-search="{{ $resident['identity_search'] ?? strtolower($resident['full_name']) }}"
                            >
                                <th scope="row" class="lml-hr-death__cell">
                                    {{ $resident['full_name'] }}
                                    @if ($identityParts !== [])
                                        <span class="lml-hr-death__resident-meta">
                                            {{ implode(' · ', $identityParts) }}
                                        </span>
                                    @endif
                                    @if ($idParts !== [])
                                        <span class="lml-hr-death__resident-meta">
                                            {{ implode(' · ', $idParts) }}
                                        </span>
                                    @endif
                                </th>
                                <td class="lml-hr-death__cell">{{ $resident['household_display'] }}</td>
                                <td class="lml-hr-death__cell">{{ $resident['zone'] }}</td>
                                <td class="lml-hr-death__cell">
                                    <span class="lml-hr-death__status lml-hr-death__status--{{ $resident['status'] }}">
                                        {{ $resident['vital_label'] }}
                                    </span>
                                </td>
                                <td class="lml-hr-death__cell">
                                    <a
                                        href="{{ $resident['open_url'] }}"
                                        class="lml-hr-death__open-btn lml-focus-ring"
                                        aria-label="{{ $actionVerb }} {{ $ariaIdentity }}"
                                    >
                                        {{ $resident['can_submit'] ? 'Record death' : 'Open' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
