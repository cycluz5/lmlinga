{{--
    Spot Mapping — UI preview (Phase 1.1).
    Demo data only. Browser geolocation is used for field-plotting UX demo — nothing is saved.
--}}
@extends('layouts.dashboard')

@section('title', 'Spot Mapping - LMLinga')

@section('content')
    <div
        class="lml-spot-map"
        data-lml-spot-map
        data-demo="true"
        data-plot-handoff-url="{{ route('spot-mapping.plot-handoff') }}"
    >
        @if ($errors->any())
            <div class="lml-spot-map__server-alert" role="alert">
                <p class="lml-spot-map__server-alert-text">{{ $errors->first() }}</p>
            </div>
        @endif

        <div class="lml-spot-map__toolbar">
            <div class="lml-spot-map__stats" role="group" aria-label="Household mapping summary (demo values)">
                <article class="lml-spot-map__card">
                    <p class="lml-spot-map__card-label">Total HH</p>
                    <p class="lml-spot-map__card-value" data-stat="total">60</p>
                </article>
                <article class="lml-spot-map__card">
                    <p class="lml-spot-map__card-label">Plotted</p>
                    <p class="lml-spot-map__card-value" data-stat="plotted">53</p>
                </article>
                <article class="lml-spot-map__card">
                    <p class="lml-spot-map__card-label">Pending</p>
                    <p class="lml-spot-map__card-value" data-stat="pending">7</p>
                </article>
            </div>

            <button
                type="button"
                class="lml-spot-map__plot-btn lml-focus-ring"
                data-spot-map-plot
                aria-pressed="false"
                aria-busy="false"
            >
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Plot New Household</span>
            </button>
        </div>

        <div class="lml-spot-map__workspace">
            <div class="lml-spot-map__map-wrap">
                <div
                    id="lml-spot-map-canvas"
                    class="lml-spot-map__map"
                    role="region"
                    aria-label="Barangay La Medalla household spot map (demo markers only)"
                    tabindex="0"
                ></div>

                <div
                    class="lml-spot-map__overlay"
                    data-spot-map-overlay
                    data-mode="info"
                    hidden
                    role="status"
                    aria-live="polite"
                >
                    <i class="bi bi-geo-alt-fill" data-spot-map-overlay-icon aria-hidden="true"></i>
                    <span data-spot-map-overlay-text>Getting your current location…</span>
                </div>

                <div class="lml-spot-map__legend" aria-label="Map legend">
                    <p class="lml-spot-map__legend-title">Map Legend</p>

                    <div class="lml-spot-map__legend-section">
                        <p class="lml-spot-map__legend-heading">Household Status</p>
                        <ul class="lml-spot-map__legend-list list-unstyled mb-0">
                            <li class="lml-spot-map__legend-item">
                                <span class="lml-spot-map__legend-swatch lml-spot-map__legend-swatch--plotted" aria-hidden="true"></span>
                                <span>Completed / Plotted</span>
                            </li>
                            <li class="lml-spot-map__legend-item">
                                <span class="lml-spot-map__legend-swatch lml-spot-map__legend-swatch--pending" aria-hidden="true"></span>
                                <span>Pending</span>
                            </li>
                        </ul>
                    </div>

                    <div class="lml-spot-map__legend-section">
                        <p class="lml-spot-map__legend-heading">Barangay Zones</p>
                        <ul class="lml-spot-map__legend-list list-unstyled mb-0">
                            <li class="lml-spot-map__legend-item">
                                <span class="lml-spot-map__legend-swatch lml-spot-map__legend-swatch--zone-1" aria-hidden="true"></span>
                                <span>Zone 1</span>
                            </li>
                            <li class="lml-spot-map__legend-item">
                                <span class="lml-spot-map__legend-swatch lml-spot-map__legend-swatch--zone-2" aria-hidden="true"></span>
                                <span>Zone 2</span>
                            </li>
                            <li class="lml-spot-map__legend-item">
                                <span class="lml-spot-map__legend-swatch lml-spot-map__legend-swatch--zone-3" aria-hidden="true"></span>
                                <span>Zone 3</span>
                            </li>
                            <li class="lml-spot-map__legend-item">
                                <span class="lml-spot-map__legend-swatch lml-spot-map__legend-swatch--zone-4" aria-hidden="true"></span>
                                <span>Zone 4</span>
                            </li>
                            <li class="lml-spot-map__legend-item">
                                <span class="lml-spot-map__legend-swatch lml-spot-map__legend-swatch--zone-5" aria-hidden="true"></span>
                                <span>Zone 5</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <aside
                class="lml-spot-map__panel"
                data-spot-map-panel
                hidden
                aria-labelledby="lml-spot-map-panel-title"
            >
                <div class="lml-spot-map__panel-header">
                    <h2 id="lml-spot-map-panel-title" class="lml-spot-map__panel-title">Household Details</h2>
                    <button
                        type="button"
                        class="lml-spot-map__panel-close lml-focus-ring"
                        data-spot-map-close
                        aria-label="Close household details panel"
                    >
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <p class="lml-spot-map__panel-note" data-spot-map-note>
                    Demo preview only. No data is saved.
                </p>

                <dl class="lml-spot-map__fields">
                    <div class="lml-spot-map__field">
                        <dt>
                            <label for="lml-spot-map-household-number">
                                Household Number <span class="lml-spot-map__req" aria-hidden="true">*</span>
                            </label>
                        </dt>
                        <dd>
                            <span data-household-number-text hidden>—</span>
                            <input
                                id="lml-spot-map-household-number"
                                type="text"
                                class="form-control lml-form-control lml-spot-map__control lml-focus-ring"
                                data-spot-map-household-number
                                placeholder="Enter household number"
                                autocomplete="off"
                                required
                                aria-required="true"
                                aria-describedby="lml-spot-map-household-number-error"
                            >
                            <p
                                id="lml-spot-map-household-number-error"
                                class="lml-spot-map__field-error"
                                data-error-for="householdNumber"
                                role="alert"
                                hidden
                            ></p>
                        </dd>
                    </div>
                    <div class="lml-spot-map__field">
                        <dt>
                            <label id="lml-spot-map-hh-type-label" for="lml-spot-map-hh-type">
                                Household Type <span class="lml-spot-map__req" aria-hidden="true">*</span>
                            </label>
                        </dt>
                        <dd data-field="householdTypeDisplay">
                            <span data-hh-type-text>—</span>
                            <select
                                id="lml-spot-map-hh-type"
                                class="lml-spot-map__zone-select lml-spot-map__control lml-focus-ring"
                                data-spot-map-hh-type-select
                                aria-labelledby="lml-spot-map-hh-type-label"
                                required
                                aria-required="true"
                                aria-describedby="lml-spot-map-hh-type-error"
                                hidden
                            >
                                <option value="" selected disabled>Select household type</option>
                                <option value="HHTS">HHTS</option>
                                <option value="Non-HHTS">Non-HHTS</option>
                            </select>
                            <p
                                id="lml-spot-map-hh-type-error"
                                class="lml-spot-map__field-error"
                                data-error-for="householdType"
                                role="alert"
                                hidden
                            ></p>
                        </dd>
                    </div>
                    <div class="lml-spot-map__field">
                        <dt>
                            <label for="lml-spot-map-household-head">
                                Household Head <span class="lml-spot-map__req" aria-hidden="true">*</span>
                            </label>
                        </dt>
                        <dd>
                            <span data-household-head-text hidden>—</span>
                            <input
                                id="lml-spot-map-household-head"
                                type="text"
                                class="form-control lml-form-control lml-spot-map__control lml-focus-ring"
                                data-spot-map-household-head
                                placeholder="Enter household head name"
                                autocomplete="name"
                                required
                                aria-required="true"
                                aria-describedby="lml-spot-map-household-head-error"
                            >
                            <p
                                id="lml-spot-map-household-head-error"
                                class="lml-spot-map__field-error"
                                data-error-for="householdHead"
                                role="alert"
                                hidden
                            ></p>
                        </dd>
                    </div>
                    <div class="lml-spot-map__field">
                        <dt>Purok</dt>
                        <dd data-field="purok">—</dd>
                    </div>
                    <div class="lml-spot-map__field">
                        <dt>
                            <label id="lml-spot-map-zone-label" for="lml-spot-map-zone">
                                Zone <span class="lml-spot-map__req" aria-hidden="true">*</span>
                            </label>
                        </dt>
                        <dd data-field="zoneDisplay">
                            <span class="lml-spot-map__zone-badge" data-zone-badge hidden>
                                <span class="lml-spot-map__zone-dot" data-zone-dot aria-hidden="true"></span>
                                <span data-zone-text>—</span>
                            </span>
                            <select
                                id="lml-spot-map-zone"
                                class="lml-spot-map__zone-select lml-spot-map__control lml-focus-ring"
                                data-spot-map-zone-select
                                aria-labelledby="lml-spot-map-zone-label"
                                required
                                aria-required="true"
                                aria-describedby="lml-spot-map-zone-error"
                                hidden
                            >
                                <option value="" selected disabled>Select zone</option>
                                <option value="1">Zone 1</option>
                                <option value="2">Zone 2</option>
                                <option value="3">Zone 3</option>
                                <option value="4">Zone 4</option>
                                <option value="5">Zone 5</option>
                            </select>
                            <p
                                id="lml-spot-map-zone-error"
                                class="lml-spot-map__field-error"
                                data-error-for="zone"
                                role="alert"
                                hidden
                            ></p>
                        </dd>
                    </div>
                    <div class="lml-spot-map__field">
                        <dt>Members</dt>
                        <dd data-field="members">—</dd>
                    </div>
                    <div class="lml-spot-map__field">
                        <dt>Status</dt>
                        <dd data-field="statusLabel">—</dd>
                    </div>
                    <div class="lml-spot-map__field">
                        <dt>Latitude</dt>
                        <dd data-field="lat">—</dd>
                    </div>
                    <div class="lml-spot-map__field">
                        <dt>Longitude</dt>
                        <dd data-field="lng">—</dd>
                    </div>
                </dl>

                <div class="lml-spot-map__consent-block" data-spot-map-consent>
                    <p class="lml-spot-map__consent-heading" id="lml-spot-map-consent-label">
                        Consent <span class="lml-spot-map__req" aria-hidden="true">*</span>
                    </p>
                    <label class="lml-spot-map__consent" for="lml-spot-map-consent-input">
                        <input
                            id="lml-spot-map-consent-input"
                            type="checkbox"
                            class="lml-spot-map__consent-input lml-focus-ring"
                            data-spot-map-consent-input
                            required
                            aria-required="true"
                            aria-describedby="lml-spot-map-consent-error"
                        >
                        <span class="lml-spot-map__consent-text">
                            Head of Household agreed to collect household coordinates for household profiling purposes.
                        </span>
                    </label>
                    <p
                        id="lml-spot-map-consent-error"
                        class="lml-spot-map__field-error"
                        data-error-for="consent"
                        role="alert"
                        hidden
                    ></p>
                </div>

                <div class="lml-spot-map__panel-actions">
                    <a
                        href="{{ url('/household-profiling/HH-151') }}"
                        class="lml-spot-map__view-hh lml-focus-ring"
                        data-spot-map-view-hh
                        hidden
                    >
                        <i class="bi bi-eye-fill" aria-hidden="true"></i>
                        <span>View Household</span>
                    </a>
                    <button
                        type="button"
                        class="lml-spot-map__btn lml-spot-map__btn--secondary lml-focus-ring"
                        data-spot-map-cancel
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="lml-spot-map__btn lml-spot-map__btn--primary lml-focus-ring"
                        data-spot-map-confirm
                    >
                        Plot
                    </button>
                </div>
            </aside>
        </div>
    </div>
@endsection
