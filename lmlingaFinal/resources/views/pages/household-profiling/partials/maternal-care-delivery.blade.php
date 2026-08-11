@php
    use App\Support\DemoMaternalCare;

    $updateUrl = route('household-profiling.members.maternal-care.update', $routeParams + [
        'section' => 'delivery',
    ]);
    $delivery = is_array($pregnancy['delivery'] ?? null) ? $pregnancy['delivery'] : [];
    $outcome = (string) ($delivery['outcome'] ?? '');
    $isTerminal = in_array($outcome, ['FD', 'AB'], true);
    $attendant = (string) ($delivery['birth_attendant'] ?? '');
@endphp

<section class="lml-mc__panel" aria-labelledby="lml-mc-delivery-title" data-mc-delivery>
    <header class="lml-mc__panel-head">
        <div class="lml-mc__panel-titles">
            <h2 id="lml-mc-delivery-title" class="lml-mc__panel-title">Pregnancy Delivery &amp; Outcome</h2>
            <p class="lml-mc__panel-subtitle">
                Document delivery and newborn outcomes.
            </p>
        </div>
        <div class="lml-mc__panel-controls">
            <button
                type="button"
                class="lml-mc__btn lml-mc__btn--primary lml-focus-ring"
                data-mc-edit
                data-mc-edit-for="delivery"
            >
                <i class="bi bi-pencil" aria-hidden="true"></i>
                <span>Edit</span>
            </button>
            <button
                type="submit"
                form="lml-mc-delivery-form"
                class="lml-mc__btn lml-mc__btn--primary lml-focus-ring"
                data-mc-save
                data-mc-save-for="delivery"
                hidden
            >
                Save
            </button>
        </div>
    </header>

    <form
        id="lml-mc-delivery-form"
        method="post"
        action="{{ $updateUrl }}"
        class="lml-mc__form"
        data-mc-section-form="delivery"
        data-editing="false"
        novalidate
    >
        @csrf
        @method('PUT')

        <div class="lml-mc__delivery-grid">
            <fieldset class="lml-mc__fieldset" data-mc-outcome-group>
                <legend class="lml-mc__legend">Outcome</legend>
                <div class="lml-mc__radio-list" role="radiogroup" aria-label="Pregnancy outcome">
                    @foreach (DemoMaternalCare::OUTCOMES as $code => $label)
                        <label class="lml-mc__radio{{ $outcome === $code ? ' is-selected' : '' }}">
                            <input
                                type="radio"
                                name="outcome"
                                value="{{ $code }}"
                                class="lml-focus-ring"
                                data-mc-field
                                data-mc-outcome="{{ $code }}"
                                @checked($outcome === $code)
                                disabled
                            >
                            <span>
                                <strong>{{ $code }}</strong> — {{ $label }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <div
                    class="lml-mc__field lml-mc__conditional"
                    data-mc-conditional="fd"
                    @if ($outcome !== 'FD') hidden @endif
                >
                    <label for="lml-mc-fetal-death-date" class="lml-mc__label">Date of Fetal Death</label>
                    <input
                        type="date"
                        id="lml-mc-fetal-death-date"
                        name="fetal_death_date"
                        class="lml-mc__input lml-focus-ring"
                        value="{{ $delivery['fetal_death_date'] ?? '' }}"
                        data-mc-field
                        disabled
                    >
                </div>

                <div
                    class="lml-mc__field lml-mc__conditional"
                    data-mc-conditional="ab"
                    @if ($outcome !== 'AB') hidden @endif
                >
                    <label for="lml-mc-abortion-date" class="lml-mc__label">Date of Abortion</label>
                    <input
                        type="date"
                        id="lml-mc-abortion-date"
                        name="abortion_date"
                        class="lml-mc__input lml-focus-ring"
                        value="{{ $delivery['abortion_date'] ?? '' }}"
                        data-mc-field
                        disabled
                    >
                </div>
            </fieldset>

            <fieldset
                class="lml-mc__fieldset"
                data-mc-delivery-details
                @if ($isTerminal) aria-disabled="true" @endif
            >
                <legend class="lml-mc__legend">Delivery Details</legend>
                <div class="lml-mc__grid lml-mc__grid--1" data-mc-delivery-details-fields>
                    <div class="lml-mc__field">
                        <label for="lml-mc-delivery-type" class="lml-mc__label">Delivery Type</label>
                        <select
                            id="lml-mc-delivery-type"
                            name="delivery_type"
                            class="lml-mc__input lml-focus-ring"
                            data-mc-field
                            disabled
                        >
                            <option value="">Select delivery type</option>
                            @foreach (DemoMaternalCare::DELIVERY_TYPES as $code => $label)
                                <option
                                    value="{{ $code }}"
                                    @selected(($delivery['delivery_type'] ?? '') === $code)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lml-mc__field">
                        <label for="lml-mc-birth-weight" class="lml-mc__label">Birth Weight (kg)</label>
                        <input
                            type="number"
                            id="lml-mc-birth-weight"
                            name="birth_weight"
                            min="0"
                            step="0.01"
                            class="lml-mc__input lml-focus-ring"
                            value="{{ $delivery['birth_weight'] ?? '' }}"
                            data-mc-field
                            disabled
                        >
                    </div>
                    <div class="lml-mc__field">
                        <label for="lml-mc-delivery-status" class="lml-mc__label">Status</label>
                        <input
                            type="text"
                            id="lml-mc-delivery-status"
                            name="status"
                            class="lml-mc__input lml-focus-ring"
                            value="{{ $delivery['status'] ?? '' }}"
                            data-mc-field
                            disabled
                            autocomplete="off"
                        >
                    </div>
                    <div class="lml-mc__field">
                        <label for="lml-mc-delivery-datetime" class="lml-mc__label">Date &amp; Time of Delivery</label>
                        <input
                            type="datetime-local"
                            id="lml-mc-delivery-datetime"
                            name="datetime"
                            class="lml-mc__input lml-focus-ring"
                            value="{{ $delivery['datetime'] ?? '' }}"
                            data-mc-field
                            disabled
                        >
                    </div>
                    <div class="lml-mc__field">
                        <label for="lml-mc-date-terminated" class="lml-mc__label">Date Terminated</label>
                        <input
                            type="date"
                            id="lml-mc-date-terminated"
                            name="date_terminated"
                            class="lml-mc__input lml-focus-ring"
                            value="{{ $delivery['date_terminated'] ?? '' }}"
                            data-mc-field
                            disabled
                        >
                    </div>
                    <div class="lml-mc__field">
                        <label for="lml-mc-birth-attendant" class="lml-mc__label">Birth Attendant</label>
                        <select
                            id="lml-mc-birth-attendant"
                            name="birth_attendant"
                            class="lml-mc__input lml-focus-ring"
                            data-mc-field
                            data-mc-birth-attendant
                            disabled
                        >
                            <option value="">Select birth attendant</option>
                            @foreach (DemoMaternalCare::BIRTH_ATTENDANTS as $code => $label)
                                <option value="{{ $code }}" @selected($attendant === $code)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div
                        class="lml-mc__field lml-mc__conditional"
                        data-mc-conditional="attendant-other"
                        @if ($attendant !== 'Others') hidden @endif
                    >
                        <label for="lml-mc-birth-attendant-other" class="lml-mc__label">
                            Birth Attendant (Others)
                        </label>
                        <input
                            type="text"
                            id="lml-mc-birth-attendant-other"
                            name="birth_attendant_other"
                            class="lml-mc__input lml-focus-ring"
                            value="{{ $delivery['birth_attendant_other'] ?? '' }}"
                            data-mc-field
                            data-mc-birth-attendant-other
                            disabled
                            autocomplete="off"
                        >
                    </div>
                </div>
            </fieldset>

            <fieldset
                class="lml-mc__fieldset"
                data-mc-place-of-delivery
                @if ($isTerminal) aria-disabled="true" @endif
            >
                <legend class="lml-mc__legend">Place of Delivery</legend>
                <div class="lml-mc__radio-list" role="radiogroup" aria-label="Place of delivery" data-mc-place-fields>
                    @foreach (DemoMaternalCare::PLACES_OF_DELIVERY as $code => $label)
                        <label class="lml-mc__radio{{ ($delivery['place'] ?? '') === $code ? ' is-selected' : '' }}">
                            <input
                                type="radio"
                                name="place"
                                value="{{ $code }}"
                                class="lml-focus-ring"
                                data-mc-field
                                @checked(($delivery['place'] ?? '') === $code)
                                disabled
                            >
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="lml-mc__field" data-mc-place-fields>
                    <label for="lml-mc-facility-name" class="lml-mc__label">Facility Name</label>
                    <input
                        type="text"
                        id="lml-mc-facility-name"
                        name="facility_name"
                        class="lml-mc__input lml-focus-ring"
                        value="{{ $delivery['facility_name'] ?? '' }}"
                        data-mc-field
                        disabled
                        autocomplete="organization"
                    >
                </div>

                <fieldset class="lml-mc__nested-fieldset" data-mc-place-fields>
                    <legend class="lml-mc__legend lml-mc__legend--sub">BEmONC / CEmONC Capable?</legend>
                    <div class="lml-mc__radio-list lml-mc__radio-list--inline" role="radiogroup" aria-label="BEmONC or CEmONC capability">
                        @foreach (['Yes', 'No'] as $opt)
                            <label class="lml-mc__radio{{ ($delivery['bemonc_cemonc'] ?? '') === $opt ? ' is-selected' : '' }}">
                                <input
                                    type="radio"
                                    name="bemonc_cemonc"
                                    value="{{ $opt }}"
                                    class="lml-focus-ring"
                                    data-mc-field
                                    @checked(($delivery['bemonc_cemonc'] ?? '') === $opt)
                                    disabled
                                >
                                <span>{{ $opt }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </fieldset>
        </div>
    </form>
</section>
