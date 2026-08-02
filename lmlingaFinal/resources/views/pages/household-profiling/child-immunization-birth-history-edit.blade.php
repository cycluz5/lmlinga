{{--
    Household Profiling — Birth History dedicated edit page (UI preview).
    Continuous vertical scroll. Demo data only.

    Persistence: No approved Birth History save endpoint or data model exists.
    Save is preview-safe (sessionStorage + return toast). Not server-persisted.
--}}
@extends('layouts.dashboard')

@section('title', ($demoMember['name'] ?? 'Member') . ' — Birth History - LMLinga')

@section('content')
    @php
        $emptyRecord = 'No record';
        $memberName = (string) ($demoMember['name'] ?? 'Member');
        $memberSex = (string) ($demoMember['sex'] ?? '');
        $dateBirth = $demoMember
            ? lml_demo_member_display($demoMember, 'birthday')
            : '';

        $memberLastName = (string) ($demoMember['last_name'] ?? '');
        $memberFirstName = (string) ($demoMember['first_name'] ?? '');
        $memberMiddleName = (string) ($demoMember['middle_name'] ?? '');
        $memberDobDisplay = $dateBirth !== '' ? $dateBirth : '';

        $birthWeightForm = filled(data_get($demoMember, 'birth_history.weight'))
            ? (string) data_get($demoMember, 'birth_history.weight')
            : '';
        $birthLengthForm = filled(data_get($demoMember, 'birth_history.length'))
            ? (string) data_get($demoMember, 'birth_history.length')
            : '';
        $birthPcabForm = filled(data_get($demoMember, 'birth_history.pcab'))
            ? (string) data_get($demoMember, 'birth_history.pcab')
            : '';
        $birthBfDateForm = filled(data_get($demoMember, 'birth_history.breastfeeding_date'))
            ? (string) data_get($demoMember, 'birth_history.breastfeeding_date')
            : '';

        $pcabOptions = [
            'at_least_2_doses_1_month_prior' => 'At least 2 doses received at least 1 month prior to delivery',
            'tt3_td3_to_tt5_td5_prior' => 'TT3/TD3 – TT5/TD5 given to the mother anytime prior to delivery',
        ];

        $backUrl = route('household-profiling.members.child-immunization', [
            'householdNo' => $householdNo,
            'memberId' => $memberId,
        ]);
    @endphp

    <div
        class="lml-bh-edit"
        data-lml-bh-edit
        data-demo="true"
        data-persistence="preview"
        data-household-no="{{ $householdNo }}"
        data-member-id="{{ $memberId }}"
        data-return-url="{{ $backUrl }}"
    >
        <div
            class="lml-bh-edit__toast"
            data-bh-edit-toast
            role="status"
            aria-live="polite"
            hidden
        ></div>

        <a
            href="{{ $backUrl }}"
            class="lml-bh-edit__back lml-focus-ring"
            aria-label="Back to Child Immunization for {{ $memberName }}"
        >
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
        </a>

        @if (! $demoHousehold || ! $demoMember)
            <section class="lml-bh-edit__not-found" aria-labelledby="lml-bh-edit-nf-title">
                <h2 id="lml-bh-edit-nf-title" class="lml-bh-edit__not-found-title">
                    Member not found
                </h2>
                <p class="lml-bh-edit__not-found-message">
                    @if (! $demoHousehold)
                        No demo household matches <strong>{{ $householdNo }}</strong>.
                    @else
                        No demo member <strong>{{ $memberId }}</strong> belongs to household
                        <strong>{{ $householdNo }}</strong>.
                    @endif
                </p>
                <a
                    href="{{ $demoHousehold ? route('household-profiling.view', ['householdNo' => $householdNo]) : route('household-profiling.index') }}"
                    class="lml-bh-edit__not-found-link lml-focus-ring"
                >
                    {{ $demoHousehold ? 'Back to Household' : 'Return to Household List' }}
                </a>
            </section>
        @else
            <section
                id="lml-child-imm-birth-editor"
                class="lml-child-imm__birth-editor"
                data-child-imm-birth-editor
                data-persistence="preview"
                aria-labelledby="lml-child-imm-birth-editor-heading"
            >
                <form
                    class="lml-child-imm__birth-editor-form"
                    data-child-imm-birth-form
                    data-persistence="preview"
                    novalidate
                >
                    <header class="lml-child-imm__birth-editor-head">
                        <h2
                            id="lml-child-imm-birth-editor-heading"
                            class="lml-child-imm__birth-editor-title"
                            tabindex="-1"
                        >
                            <span class="lml-child-imm__birth-editor-icon" aria-hidden="true">
                                <i class="bi bi-person"></i>
                            </span>
                            <span>Birth History</span>
                        </h2>
                    </header>

                    <div class="lml-child-imm__birth-editor-body">
                        <div class="lml-child-imm__birth-editor-grid">
                            <div class="lml-child-imm__birth-field lml-child-imm__birth-field--third">
                                <label class="lml-child-imm__birth-label" for="lml-child-imm-bh-last-name">
                                    Last Name
                                </label>
                                <input
                                    type="text"
                                    id="lml-child-imm-bh-last-name"
                                    class="lml-child-imm__birth-input lml-child-imm__birth-input--readonly lml-focus-ring"
                                    value="{{ $memberLastName }}"
                                    readonly
                                    autocomplete="off"
                                    aria-readonly="true"
                                >
                            </div>
                            <div class="lml-child-imm__birth-field lml-child-imm__birth-field--third">
                                <label class="lml-child-imm__birth-label" for="lml-child-imm-bh-first-name">
                                    First Name
                                </label>
                                <input
                                    type="text"
                                    id="lml-child-imm-bh-first-name"
                                    class="lml-child-imm__birth-input lml-child-imm__birth-input--readonly lml-focus-ring"
                                    value="{{ $memberFirstName }}"
                                    readonly
                                    autocomplete="off"
                                    aria-readonly="true"
                                >
                            </div>
                            <div class="lml-child-imm__birth-field lml-child-imm__birth-field--third">
                                <label class="lml-child-imm__birth-label" for="lml-child-imm-bh-middle-name">
                                    Middle Name
                                </label>
                                <input
                                    type="text"
                                    id="lml-child-imm-bh-middle-name"
                                    class="lml-child-imm__birth-input lml-child-imm__birth-input--readonly lml-focus-ring"
                                    value="{{ $memberMiddleName }}"
                                    readonly
                                    autocomplete="off"
                                    aria-readonly="true"
                                >
                            </div>

                            <div class="lml-child-imm__birth-field lml-child-imm__birth-field--half">
                                <label class="lml-child-imm__birth-label" for="lml-child-imm-bh-dob">
                                    Date of Birth
                                </label>
                                <input
                                    type="text"
                                    id="lml-child-imm-bh-dob"
                                    class="lml-child-imm__birth-input lml-child-imm__birth-input--readonly lml-focus-ring"
                                    value="{{ $memberDobDisplay }}"
                                    readonly
                                    autocomplete="off"
                                    aria-readonly="true"
                                >
                            </div>
                            <div class="lml-child-imm__birth-field lml-child-imm__birth-field--half">
                                <label class="lml-child-imm__birth-label" for="lml-child-imm-bh-sex">
                                    Sex
                                </label>
                                <input
                                    type="text"
                                    id="lml-child-imm-bh-sex"
                                    class="lml-child-imm__birth-input lml-child-imm__birth-input--readonly lml-focus-ring"
                                    value="{{ $memberSex }}"
                                    readonly
                                    autocomplete="off"
                                    aria-readonly="true"
                                >
                            </div>

                            <div class="lml-child-imm__birth-field lml-child-imm__birth-field--half">
                                <label class="lml-child-imm__birth-label" for="lml-child-imm-bh-weight">
                                    Birth Weight
                                </label>
                                <input
                                    type="number"
                                    id="lml-child-imm-bh-weight"
                                    name="birth_weight"
                                    class="lml-child-imm__birth-input lml-focus-ring"
                                    data-child-imm-birth-field="weight"
                                    value="{{ $birthWeightForm }}"
                                    placeholder="Enter Weight in kg"
                                    inputmode="decimal"
                                    min="0"
                                    step="0.01"
                                    autocomplete="off"
                                >
                            </div>
                            <div class="lml-child-imm__birth-field lml-child-imm__birth-field--half">
                                <label class="lml-child-imm__birth-label" for="lml-child-imm-bh-length">
                                    Birth Length
                                </label>
                                <input
                                    type="number"
                                    id="lml-child-imm-bh-length"
                                    name="birth_length"
                                    class="lml-child-imm__birth-input lml-focus-ring"
                                    data-child-imm-birth-field="length"
                                    value="{{ $birthLengthForm }}"
                                    placeholder="Enter length in cm"
                                    inputmode="decimal"
                                    min="0"
                                    step="0.01"
                                    autocomplete="off"
                                >
                            </div>

                            <div class="lml-child-imm__birth-field lml-child-imm__birth-field--half">
                                <label class="lml-child-imm__birth-label" for="lml-child-imm-bh-pcab">
                                    PCAB from Neonatal Tetanus
                                </label>
                                <select
                                    id="lml-child-imm-bh-pcab"
                                    name="pcab"
                                    class="lml-child-imm__birth-select lml-focus-ring"
                                    data-child-imm-birth-field="pcab"
                                >
                                    <option value="" @selected($birthPcabForm === '')>Select</option>
                                    @foreach ($pcabOptions as $pcabValue => $pcabLabel)
                                        <option
                                            value="{{ $pcabValue }}"
                                            @selected($birthPcabForm === $pcabValue)
                                        >
                                            {{ $pcabLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="lml-child-imm__birth-field lml-child-imm__birth-field--half">
                                <label class="lml-child-imm__birth-label" for="lml-child-imm-bh-breastfeeding">
                                    Initiated Breast Feeding Date
                                </label>
                                <input
                                    type="date"
                                    id="lml-child-imm-bh-breastfeeding"
                                    name="breastfeeding_date"
                                    class="lml-child-imm__birth-input lml-focus-ring"
                                    data-child-imm-birth-field="breastfeeding_date"
                                    value="{{ $birthBfDateForm }}"
                                    autocomplete="off"
                                >
                            </div>
                        </div>

                        <div class="lml-child-imm__birth-editor-actions">
                            <a
                                href="{{ $backUrl }}"
                                class="lml-child-imm__birth-btn lml-child-imm__birth-btn--close lml-focus-ring"
                                data-child-imm-birth-close
                                aria-label="Close birth history editor"
                            >
                                Close
                            </a>
                            <button
                                type="submit"
                                class="lml-child-imm__birth-btn lml-child-imm__birth-btn--save lml-focus-ring"
                                data-child-imm-birth-save
                                aria-label="Save birth history"
                            >
                                Save
                            </button>
                        </div>
                    </div>
                </form>
            </section>
        @endif
    </div>
@endsection
