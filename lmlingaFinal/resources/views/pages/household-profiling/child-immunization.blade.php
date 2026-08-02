{{--
    Household Profiling — Child Immunization destination (UI preview).
    Continuous vertical scroll. Demo data only.

    Persistence: No approved Child Immunization save endpoint or data model
    exists yet. Immunization Edit/Save is preview-safe only. Birth History
    editing is on a dedicated page (preview sessionStorage + toast on return).
--}}
@extends('layouts.dashboard')

@section('title', ($demoMember['name'] ?? 'Member') . ' — Child Immunization - LMLinga')

@section('content')
    @php
        $emptyRecord = 'No record';
        $memberName = (string) ($demoMember['name'] ?? 'Member');
        $memberSex = (string) ($demoMember['sex'] ?? '');
        $memberAge = $demoMember['age'] ?? null;
        $dateBirth = $demoMember
            ? lml_demo_member_display($demoMember, 'birthday')
            : $emptyRecord;
        $motherName = filled(data_get($demoMember, 'mother_name'))
            ? (string) data_get($demoMember, 'mother_name')
            : $emptyRecord;

        $birthHistoryDisplay = static function (mixed $value) use ($emptyRecord): string {
            return filled($value) ? (string) $value : $emptyRecord;
        };

        $birthHistory = [
            'weight' => [
                'label' => 'Birth Weight',
                'value' => $birthHistoryDisplay(data_get($demoMember, 'birth_history.weight')),
            ],
            'length' => [
                'label' => 'Birth Length',
                'value' => $birthHistoryDisplay(data_get($demoMember, 'birth_history.length')),
            ],
            'status' => [
                'label' => 'Status',
                'value' => $birthHistoryDisplay(data_get($demoMember, 'birth_history.status')),
            ],
            'pcab' => [
                'label' => 'PCAB from Neonatal Tetanus',
                'value' => $birthHistoryDisplay(data_get($demoMember, 'birth_history.pcab')),
            ],
        ];

        $sexBadgeClass = strtolower($memberSex) === 'female'
            ? 'lml-child-imm__sex-badge--female'
            : 'lml-child-imm__sex-badge--male';

        $backUrl = route('household-profiling.members.show', [
            'householdNo' => $householdNo,
            'memberId' => $memberId,
        ]);

        $birthHistoryEditUrl = route('household-profiling.members.child-immunization.birth-history.edit', [
            'householdNo' => $householdNo,
            'memberId' => $memberId,
        ]);

        /*
         | Presentation-only dose status icons (Figma demo indicators).
         | Not derived from clinical scheduling or date-input values.
         */
        $vaccineCards = [
            [
                'key' => 'bcg',
                'title' => 'BCG',
                'layout' => 'pair',
                'doses' => [
                    ['label' => '0-28 days', 'status' => 'recorded'],
                    ['label' => '29 days - 1 year old', 'status' => null],
                ],
            ],
            [
                'key' => 'hepa-b',
                'title' => 'Hepa B',
                'layout' => 'pair',
                'doses' => [
                    ['label' => '24 hrs after birth', 'status' => 'recorded'],
                    ['label' => '24 hrs up to 14 days', 'status' => null],
                ],
            ],
            [
                'key' => 'dpt-hib-hepb',
                'title' => 'DPT-HIB-HepB',
                'layout' => 'stack',
                'doses' => [
                    ['label' => '1st Dose (1 1/2 months)', 'status' => null],
                    ['label' => '2nd Dose (2 1/2 months)', 'status' => 'attention'],
                    ['label' => '3rd Dose (3 1/2 months)', 'status' => null],
                ],
            ],
            [
                'key' => 'opv',
                'title' => 'OPV',
                'layout' => 'stack',
                'doses' => [
                    ['label' => '1st Dose (1 1/2 months)', 'status' => 'recorded'],
                    ['label' => '2nd Dose (2 1/2 months)', 'status' => null],
                    ['label' => '3rd Dose (3 1/2 months)', 'status' => null],
                ],
            ],
            [
                'key' => 'pcv',
                'title' => 'PCV',
                'layout' => 'stack',
                'doses' => [
                    ['label' => '1st Dose (1 1/2 months)', 'status' => 'recorded'],
                    ['label' => '2nd Dose (2 1/2 months)', 'status' => 'attention'],
                    ['label' => '3rd Dose (3 1/2 months)', 'status' => 'recorded'],
                ],
            ],
            [
                'key' => 'ipv',
                'title' => 'IPV',
                'layout' => 'stack',
                'doses' => [
                    ['label' => '1st Dose (3 1/2 months)', 'status' => null],
                    ['label' => '2nd Dose (9 months)', 'status' => null],
                ],
            ],
            [
                'key' => 'mmr',
                'title' => 'MMR',
                'layout' => 'stack',
                'doses' => [
                    ['label' => '1st Dose (9 months)', 'status' => null],
                    ['label' => '2nd Dose (12 months)', 'status' => null],
                ],
            ],
        ];

        /*
         | FIC/CIC completion lists (presentation-only):
         | - FIC intentionally requires MMR × 1 dose.
         | - CIC requires MMR × 2 doses.
         | Figma is a visual reference only; its duplicated two-dose FIC sample
         | is not authoritative for this approved requirement.
         */
        $completionCards = [
            [
                'key' => 'fic',
                'title' => 'FIC',
                'range' => '0–12 months',
                'items' => [
                    ['label' => 'BCG', 'doses' => '1 dose'],
                    ['label' => 'OPV', 'doses' => '3 doses'],
                    ['label' => 'DPT-HIB-HepB', 'doses' => '3 doses'],
                    ['label' => 'MMR', 'doses' => '1 dose'],
                ],
            ],
            [
                'key' => 'cic',
                'title' => 'CIC',
                'range' => '13–24 months',
                'items' => [
                    ['label' => 'BCG', 'doses' => '1 dose'],
                    ['label' => 'OPV', 'doses' => '3 doses'],
                    ['label' => 'DPT-HIB-HepB', 'doses' => '3 doses'],
                    ['label' => 'MMR', 'doses' => '2 doses'],
                ],
            ],
        ];

        $vaccineTypes = [
            ['key' => 'bcg', 'label' => 'BCG'],
            ['key' => 'hepa-b', 'label' => 'Hepa B'],
            ['key' => 'dpt-hib-hepb', 'label' => 'DPT-HIB-HepB'],
            ['key' => 'opv', 'label' => 'OPV'],
            ['key' => 'ipv', 'label' => 'IPV'],
            ['key' => 'pcv', 'label' => 'PCV'],
            ['key' => 'mmr', 'label' => 'MMR'],
            ['key' => 'fic', 'label' => 'FIC'],
            ['key' => 'cic', 'label' => 'CIC'],
        ];
    @endphp

    <div
        class="lml-child-imm"
        data-lml-child-imm
        data-demo="true"
        data-household-no="{{ $householdNo }}"
        data-member-id="{{ $memberId }}"
        @if ($demoMember)
            data-member-name="{{ $demoMember['name'] }}"
        @endif
    >
        <div
            class="lml-child-imm__toast"
            data-child-imm-toast
            role="status"
            aria-live="polite"
            hidden
        ></div>

        <a
            href="{{ $backUrl }}"
            class="lml-child-imm__back lml-focus-ring"
            aria-label="Back to Health Summary Records for {{ $memberName }}"
        >
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
        </a>

        @if (! $demoHousehold || ! $demoMember)
            <section class="lml-child-imm__not-found" aria-labelledby="lml-child-imm-nf-title">
                <h2 id="lml-child-imm-nf-title" class="lml-child-imm__not-found-title">
                    Member not found
                </h2>
                <p class="lml-child-imm__not-found-message">
                    @if (! $demoHousehold)
                        No demo household matches <strong>{{ $householdNo }}</strong>.
                    @else
                        No demo member <strong>{{ $memberId }}</strong> belongs to household
                        <strong>{{ $householdNo }}</strong>.
                    @endif
                </p>
                <a
                    href="{{ $demoHousehold ? route('household-profiling.view', ['householdNo' => $householdNo]) : route('household-profiling.index') }}"
                    class="lml-child-imm__not-found-link lml-focus-ring"
                >
                    {{ $demoHousehold ? 'Back to Household' : 'Return to Household List' }}
                </a>
            </section>
        @else
            <article class="lml-child-imm__summary" aria-labelledby="lml-child-imm-member-name">
                    <div class="lml-child-imm__summary-profile">
                        <span class="lml-child-imm__avatar" aria-hidden="true">
                            <i class="bi bi-person-fill"></i>
                        </span>
                        <div class="lml-child-imm__summary-identity">
                            <p id="lml-child-imm-member-name" class="lml-child-imm__member-name">
                                {{ $demoMember['name'] }}
                            </p>
                            @if ($memberSex !== '')
                                <span class="lml-child-imm__sex-badge {{ $sexBadgeClass }}">
                                    {{ $memberSex }}
                                </span>
                            @endif
                            <dl class="lml-child-imm__profile-dl">
                                <div class="lml-child-imm__profile-item">
                                    <dt>Age:</dt>
                                    <dd>{{ $memberAge !== null && $memberAge !== '' ? $memberAge : $emptyRecord }}</dd>
                                </div>
                                <div class="lml-child-imm__profile-item">
                                    <dt>Date Birth:</dt>
                                    <dd>{{ $dateBirth !== '' ? $dateBirth : $emptyRecord }}</dd>
                                </div>
                                <div class="lml-child-imm__profile-item">
                                    <dt>Mother's Name:</dt>
                                    <dd>{{ $motherName }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="lml-child-imm__birth-history" aria-labelledby="lml-child-imm-birth-heading">
                        <div class="lml-child-imm__birth-head">
                            <h2 id="lml-child-imm-birth-heading" class="lml-child-imm__birth-title">
                                <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>
                                <span>Birth History</span>
                            </h2>
                            <a
                                href="{{ $birthHistoryEditUrl }}"
                                class="lml-child-imm__birth-edit-link lml-focus-ring"
                                data-child-imm-birth-edit-link
                                aria-label="Edit birth history"
                            >
                                <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                <span>Edit</span>
                            </a>
                        </div>
                        <dl class="lml-child-imm__birth-dl" data-child-imm-birth-summary>
                            @foreach ($birthHistory as $key => $item)
                                <div class="lml-child-imm__birth-item">
                                    <dt>{{ $item['label'] }}</dt>
                                    <dd data-birth-summary="{{ $key }}">{{ $item['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </article>

                {{-- Inline Immunization edit form: preview-safe only; no server persistence endpoint. --}}
                <form
                    class="lml-child-imm__immunization"
                    data-child-imm-immunization
                    data-editing="false"
                    data-persistence="preview"
                    aria-labelledby="lml-child-imm-heading"
                    novalidate
                >
                    <div class="lml-child-imm__imm-head">
                        <div class="lml-child-imm__imm-intro">
                            <h2 id="lml-child-imm-heading" class="lml-child-imm__imm-title">
                                <i class="bi bi-syringe" aria-hidden="true"></i>
                                <span>Immunization</span>
                            </h2>
                            <p class="lml-child-imm__imm-desc">
                                Vaccination records that support immunity and protection against infectious diseases.
                            </p>
                        </div>
                        <div class="lml-child-imm__imm-actions">
                            <button
                                type="button"
                                class="lml-child-imm__edit lml-focus-ring"
                                data-child-imm-edit="immunization"
                                aria-label="Edit child immunization"
                            >
                                <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                <span>Edit</span>
                            </button>
                            <button
                                type="submit"
                                class="lml-child-imm__save lml-focus-ring"
                                data-child-imm-save
                                aria-label="Save child immunization"
                                hidden
                            >
                                <span>Save</span>
                            </button>
                        </div>
                    </div>

                    <div class="lml-child-imm__body">
                        <div class="lml-child-imm__main">
                            <div class="lml-child-imm__vaccine-grid">
                                @foreach ($vaccineCards as $card)
                                    <section
                                        class="lml-child-imm__vaccine-card lml-child-imm__vaccine-card--{{ $card['key'] }}{{ ($card['layout'] ?? '') === 'pair' ? ' lml-child-imm__vaccine-card--pair' : '' }}"
                                        aria-labelledby="lml-child-imm-vax-{{ $card['key'] }}"
                                    >
                                        <h3
                                            id="lml-child-imm-vax-{{ $card['key'] }}"
                                            class="lml-child-imm__vaccine-title"
                                        >
                                            {{ $card['title'] }}
                                        </h3>
                                        <div class="lml-child-imm__dose-list">
                                            @foreach ($card['doses'] as $index => $dose)
                                                @php
                                                    $fieldId = 'lml-child-imm-'.$card['key'].'-dose-'.$index;
                                                    $status = $dose['status'] ?? null;
                                                @endphp
                                                <div class="lml-child-imm__dose">
                                                    <div class="lml-child-imm__dose-label-row">
                                                        <label
                                                            class="lml-child-imm__dose-label"
                                                            for="{{ $fieldId }}"
                                                        >
                                                            {{ $dose['label'] }}
                                                        </label>
                                                        @if ($status === 'recorded')
                                                            <span class="lml-child-imm__status lml-child-imm__status--recorded">
                                                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                                                <span class="visually-hidden">Demo status: recorded</span>
                                                            </span>
                                                        @elseif ($status === 'attention')
                                                            <span class="lml-child-imm__status lml-child-imm__status--attention">
                                                                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                                                                <span class="visually-hidden">Demo status: needs attention</span>
                                                            </span>
                                                        @elseif ($status === 'pending')
                                                            <span class="lml-child-imm__status lml-child-imm__status--pending">
                                                                <i class="bi bi-circle-fill" aria-hidden="true"></i>
                                                                <span class="visually-hidden">Demo status: pending</span>
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="lml-child-imm__date-wrap">
                                                        <span class="lml-child-imm__date-caption" id="{{ $fieldId }}-caption">
                                                            Date
                                                        </span>
                                                        <input
                                                            type="date"
                                                            id="{{ $fieldId }}"
                                                            name="vaccines[{{ $card['key'] }}][{{ $index }}]"
                                                            class="lml-child-imm__date-input lml-focus-ring"
                                                            data-child-imm-field
                                                            aria-describedby="{{ $fieldId }}-caption"
                                                            autocomplete="off"
                                                            readonly
                                                            disabled
                                                        >
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </section>
                                @endforeach

                                @foreach ($completionCards as $card)
                                    <section
                                        class="lml-child-imm__vaccine-card lml-child-imm__vaccine-card--completion lml-child-imm__vaccine-card--{{ $card['key'] }}"
                                        aria-labelledby="lml-child-imm-vax-{{ $card['key'] }}"
                                    >
                                        <h3
                                            id="lml-child-imm-vax-{{ $card['key'] }}"
                                            class="lml-child-imm__vaccine-title"
                                        >
                                            {{ $card['title'] }}
                                            <span class="lml-child-imm__vaccine-range">({{ $card['range'] }})</span>
                                        </h3>
                                        <p class="visually-hidden">
                                            Presentation-only completion checklist. Not calculated from date inputs.
                                        </p>
                                        <ul class="lml-child-imm__completion-list">
                                            @foreach ($card['items'] as $item)
                                                <li class="lml-child-imm__completion-item">
                                                    <span class="lml-child-imm__completion-mark" aria-hidden="true"></span>
                                                    <span class="lml-child-imm__completion-text">
                                                        <span class="lml-child-imm__completion-label">{{ $item['label'] }}</span>
                                                        <span class="lml-child-imm__completion-doses">{{ $item['doses'] }}</span>
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </section>
                                @endforeach
                            </div>
                        </div>

                        <aside class="lml-child-imm__aside" aria-labelledby="lml-child-imm-types-heading">
                            <div class="lml-child-imm__types-card">
                                <h3 id="lml-child-imm-types-heading" class="lml-child-imm__types-title">
                                    <i class="bi bi-shield-plus" aria-hidden="true"></i>
                                    <span>Vaccines Type</span>
                                </h3>
                                <ul class="lml-child-imm__types-list">
                                    @foreach ($vaccineTypes as $type)
                                        @php
                                            $checkboxId = 'lml-child-imm-type-'.$type['key'];
                                        @endphp
                                        <li>
                                            <label class="lml-child-imm__type-row" for="{{ $checkboxId }}">
                                                <input
                                                    type="checkbox"
                                                    id="{{ $checkboxId }}"
                                                    name="vaccine_types[]"
                                                    value="{{ $type['key'] }}"
                                                    class="lml-child-imm__type-checkbox lml-focus-ring"
                                                    data-child-imm-field
                                                    disabled
                                                >
                                                <span class="lml-child-imm__type-label">{{ $type['label'] }}</span>
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </aside>
                    </div>
                </form>
        @endif
    </div>
@endsection