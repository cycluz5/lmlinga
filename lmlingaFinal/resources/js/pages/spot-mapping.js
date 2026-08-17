/**
 * Spot Mapping — Phase 1.1 UI interactions.
 * Demo data only. Browser Geolocation API for field-plotting UX — nothing is persisted.
 */
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { createLaMedallaBaseMap, LA_MEDALLA_CENTER, LA_MEDALLA_ZOOM } from '../maps/la-medalla-base';

/**
 * Spot Mapping scope: Barangay La Medalla, Iriga City, Camarines Sur only.
 * Fixed demo center from PhilAtlas (13.3806, 123.4312).
 *
 * DEMO_SCOPE_RADIUS_METERS is a UI-only safeguard for Phase 1.1 demos.
 * Future phases should replace isWithinDemoScope() with official barangay
 * polygon / GIS validation — do not treat this radius as legal boundaries.
 */
const DEMO_CENTER = LA_MEDALLA_CENTER;
const DEMO_ZOOM = LA_MEDALLA_ZOOM;
const GPS_ZOOM = 17;
const DEMO_SCOPE_RADIUS_METERS = 2000;

const OUTSIDE_SCOPE_MESSAGE =
    'You appear to be outside Barangay La Medalla. This demo only supports household plotting within Barangay La Medalla.';

/**
 * DEMO_HOUSEHOLDS — same canonical demo catalog as Household Profiling
 * (resources/demo/households.php). Keep householdNo values in sync.
 * Not real records. Not persisted. Plot New Household stays session-only.
 *
 * Future: markers load from the households DB; Plot → Water Supply Step 1 →
 * save → then appears in list, map, and View Household.
 */
const DEMO_HOUSEHOLDS = [
    {
        id: 'demo-hh-151',
        householdNo: 'HH-151',
        houseHead: 'Kristine Reyes',
        address: 'Layuan St., Brgy. La Medalla',
        purok: 'Zone 2',
        zone: 2,
        householdType: 'HHTS',
        members: 3,
        lat: 13.3811,
        lng: 123.4306,
        status: 'plotted',
    },
    {
        id: 'demo-hh-152',
        householdNo: 'HH-152',
        houseHead: 'Carlo Evangelista',
        address: 'Dalipay St., Brgy. La Medalla',
        purok: 'Zone 5',
        zone: 5,
        householdType: 'Non-HHTS',
        members: 10,
        lat: 13.3801,
        lng: 123.4320,
        status: 'plotted',
    },
    {
        id: 'demo-hh-153',
        householdNo: 'HH-153',
        houseHead: 'Adrian Corporal',
        address: 'Layuan St., Brgy. La Medalla',
        purok: 'Zone 1',
        zone: 1,
        householdType: 'HHTS',
        members: 10,
        lat: 13.3814,
        lng: 123.4318,
        status: 'plotted',
    },
    {
        id: 'demo-hh-154',
        householdNo: 'HH-154',
        houseHead: 'Maria Santos',
        address: 'Cateel Bay St., Brgy. La Medalla',
        purok: 'Zone 4',
        zone: 4,
        householdType: 'HHTS',
        members: 10,
        lat: 13.3798,
        lng: 123.4308,
        status: 'pending',
    },
    {
        id: 'demo-hh-155',
        householdNo: 'HH-155',
        houseHead: 'Juan dela Cruz',
        address: 'Layuan St., Brgy. La Medalla',
        purok: 'Zone 2',
        zone: 2,
        householdType: 'Non-HHTS',
        members: 10,
        lat: 13.3809,
        lng: 123.4324,
        status: 'pending',
    },
    {
        id: 'demo-hh-156',
        householdNo: 'HH-156',
        houseHead: 'Rosa Lim',
        address: 'Cateel Bay St., Brgy. La Medalla',
        purok: 'Zone 3',
        zone: 3,
        householdType: 'HHTS',
        members: 10,
        lat: 13.3805,
        lng: 123.4310,
        status: 'plotted',
    },
];

const STATUS_LABELS = {
    plotted: 'Completed / Plotted',
    pending: 'Pending',
    new: 'Pending',
};

const MARKER_ICONS = {
    plotted: 'bi-house-door-fill',
    pending: 'bi-house-door-fill',
    new: 'bi-plus-lg',
};

const ZONE_LABELS = {
    1: 'Zone 1',
    2: 'Zone 2',
    3: 'Zone 3',
    4: 'Zone 4',
    5: 'Zone 5',
};

const GEO_OPTIONS = {
    enableHighAccuracy: true,
    timeout: 15000,
    maximumAge: 0,
};

function escapeAttr(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function normalizeZone(zone) {
    const value = typeof zone === 'string'
        ? Number.parseInt(zone.replace(/\D/g, ''), 10)
        : Number(zone);

    if (value >= 1 && value <= 5) {
        return value;
    }

    return null;
}

/**
 * Unique client-side ID for each temporary plot in the current session.
 * Prevents markerById collisions when multiple households are plotted
 * without a page refresh.
 */
function createTempHouseholdId() {
    if (
        typeof crypto !== 'undefined'
        && typeof crypto.randomUUID === 'function'
    ) {
        return `demo-temp-${crypto.randomUUID()}`;
    }

    return `demo-temp-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
}

function zoneLabel(zone) {
    const normalized = normalizeZone(zone);
    return normalized ? ZONE_LABELS[normalized] : '—';
}

function markerAccessibleName(data) {
    const statusLabel = STATUS_LABELS[data.status] || 'Household';
    const zone = zoneLabel(data.zone);
    const zonePart = zone !== '—' ? `, ${zone}` : '';
    return `${statusLabel}: ${data.householdNo}${zonePart}`;
}

function createMarkerIcon(data, selected = false, accessibleName = '') {
    const safeStatus = STATUS_LABELS[data.status] ? data.status : 'plotted';
    const iconClass = MARKER_ICONS[safeStatus];
    const selectedClass = selected ? ' is-selected' : '';
    const zone = normalizeZone(data.zone);
    const zoneClass = zone ? `lml-spot-map__marker--zone-${zone}` : '';
    const statusClass = `lml-spot-map__marker--status-${safeStatus}`;
    const label = accessibleName || markerAccessibleName(data);

    return L.divIcon({
        className: 'lml-spot-map__marker-wrap',
        html: `<span class="lml-spot-map__marker ${zoneClass} ${statusClass}${selectedClass}" aria-label="${escapeAttr(label)}"><i class="bi ${iconClass}" aria-hidden="true"></i></span>`,
        iconSize: [32, 32],
        iconAnchor: [16, 16],
        popupAnchor: [0, -16],
    });
}

function applyMarkerAccessibleName(marker, accessibleName) {
    const el = marker.getElement();
    if (!el) {
        return;
    }

    el.setAttribute('role', 'button');
    el.setAttribute('aria-label', accessibleName);
}

function formatCoord(value) {
    if (typeof value !== 'number' || Number.isNaN(value)) {
        return '—';
    }

    return value.toFixed(6);
}

function formatAccuracy(meters) {
    if (typeof meters !== 'number' || Number.isNaN(meters)) {
        return '—';
    }

    return `±${Math.round(meters)} m`;
}

function geolocationErrorMessage(error) {
    const code = error?.code;

    if (code === 1) {
        return 'Location permission was denied. Please enable location/GPS for this site, then tap Plot New Household again — or tap the map to place a location manually.';
    }

    if (code === 2) {
        return 'Location is unavailable. Please turn on device location/GPS, then try again — or tap the map to place a location manually.';
    }

    if (code === 3) {
        return 'Location request timed out. Please check GPS signal and try again — or tap the map to place a location manually.';
    }

    return 'Location access is unavailable. Please enable GPS or manually select a location on the map.';
}

/**
 * Haversine distance in meters between two WGS84 coordinates.
 */
function distanceMeters(lat1, lng1, lat2, lng2) {
    const toRad = (degrees) => (degrees * Math.PI) / 180;
    const earthRadiusMeters = 6371000;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a = Math.sin(dLat / 2) ** 2
        + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;

    return 2 * earthRadiusMeters * Math.asin(Math.min(1, Math.sqrt(a)));
}

/**
 * UI-only demo scope check against DEMO_CENTER + DEMO_SCOPE_RADIUS_METERS.
 * Swap this helper later for polygon / GIS / backend geofencing without
 * changing the GPS success / manual fallback workflow.
 */
function isWithinDemoScope(lat, lng) {
    const [centerLat, centerLng] = DEMO_CENTER;
    return distanceMeters(centerLat, centerLng, lat, lng) <= DEMO_SCOPE_RADIUS_METERS;
}

function initSpotMapping() {
    const root = document.querySelector('[data-lml-spot-map]');
    if (!root) {
        return;
    }

    const mapEl = root.querySelector('#lml-spot-map-canvas');
    const plotBtn = root.querySelector('[data-spot-map-plot]');
    const overlay = root.querySelector('[data-spot-map-overlay]');
    const overlayIcon = root.querySelector('[data-spot-map-overlay-icon]');
    const overlayText = root.querySelector('[data-spot-map-overlay-text]');
    const panel = root.querySelector('[data-spot-map-panel]');
    const noteEl = root.querySelector('[data-spot-map-note]');
    const closeBtn = root.querySelector('[data-spot-map-close]');
    const cancelBtn = root.querySelector('[data-spot-map-cancel]');
    const confirmBtn = root.querySelector('[data-spot-map-confirm]');
    const viewHhLink = root.querySelector('[data-spot-map-view-hh]');
    const zoneBadge = root.querySelector('[data-zone-badge]');
    const zoneDot = root.querySelector('[data-zone-dot]');
    const zoneText = root.querySelector('[data-zone-text]');
    const zoneSelect = root.querySelector('[data-spot-map-zone-select]');
    const hhTypeSelect = root.querySelector('[data-spot-map-hh-type-select]');
    const hhTypeText = root.querySelector('[data-hh-type-text]');
    const householdNumberInput = root.querySelector('[data-spot-map-household-number]');
    const householdHeadInput = root.querySelector('[data-spot-map-household-head]');
    const householdNumberText = root.querySelector('[data-household-number-text]');
    const householdHeadText = root.querySelector('[data-household-head-text]');
    const consentWrap = root.querySelector('[data-spot-map-consent]');
    const consentInput = root.querySelector('[data-spot-map-consent-input]');
    const VIEW_HH_BASE = '/household-profiling/';

    const FIELD_ERROR_MESSAGES = {
        householdNumber: 'Household number is required.',
        householdType: 'Please select a household type.',
        householdHead: 'Household head name is required.',
        zone: 'Please select a zone.',
        consent: 'Consent from the head of household is required before plotting.',
    };

    const fieldControls = {
        householdNumber: householdNumberInput,
        householdType: hhTypeSelect,
        householdHead: householdHeadInput,
        zone: zoneSelect,
        consent: consentInput,
    };

    if (!mapEl || !plotBtn || !overlay || !panel) {
        return;
    }

    let placing = false;
    let locating = false;
    let locateRequestId = 0;
    let selectedId = null;
    let tempMarker = null;
    let panelReturnFocusEl = null;
    const markerById = new Map();

    function isEditingActiveTempPlot() {
        return Boolean(
            tempMarker
            && tempMarker.__lmlData?.isTemp
            && selectedId
            && selectedId === tempMarker.__lmlData.id,
        );
    }

    const map = createLaMedallaBaseMap(mapEl, { zoom: DEMO_ZOOM });

    function showOverlay(message, { mode = 'info', icon = 'bi-geo-alt-fill' } = {}) {
        overlay.hidden = false;
        overlay.dataset.mode = mode;
        if (overlayIcon) {
            overlayIcon.className = `bi ${icon}`;
        }
        if (overlayText) {
            overlayText.textContent = message;
        }
    }

    function hideOverlay() {
        overlay.hidden = true;
        overlay.dataset.mode = 'info';
    }

    function focusMapCanvas() {
        if (typeof mapEl.focus === 'function') {
            mapEl.focus({ preventScroll: true });
        }
    }

    function setLocating(next) {
        locating = next;
        root.classList.toggle('is-locating', locating);
        plotBtn.setAttribute('aria-busy', locating ? 'true' : 'false');

        if (next) {
            // Move focus before disabling Plot so keyboard focus does not fall to <body>.
            focusMapCanvas();
        }

        plotBtn.disabled = locating;
    }

    function clearFieldError(fieldKey) {
        const control = fieldControls[fieldKey];
        const errorEl = panel.querySelector(`[data-error-for="${fieldKey}"]`);

        if (control) {
            control.classList.remove('is-invalid');
            control.removeAttribute('aria-invalid');
        }

        if (errorEl) {
            errorEl.hidden = true;
            errorEl.textContent = '';
        }
    }

    function clearAllFieldErrors() {
        Object.keys(FIELD_ERROR_MESSAGES).forEach((key) => clearFieldError(key));
    }

    function setFieldError(fieldKey, message) {
        const control = fieldControls[fieldKey];
        const errorEl = panel.querySelector(`[data-error-for="${fieldKey}"]`);

        if (control) {
            control.classList.add('is-invalid');
            control.setAttribute('aria-invalid', 'true');
        }

        if (errorEl) {
            errorEl.hidden = false;
            errorEl.textContent = message;
        }
    }

    function validatePlotForm() {
        clearAllFieldErrors();

        const householdNumber = (householdNumberInput?.value || '').trim();
        const householdHead = (householdHeadInput?.value || '').trim();
        const householdType = hhTypeSelect?.value || '';
        const zone = normalizeZone(zoneSelect?.value);
        const hasConsent = Boolean(consentInput?.checked);

        const invalid = [];

        if (!householdNumber) {
            setFieldError('householdNumber', FIELD_ERROR_MESSAGES.householdNumber);
            invalid.push(householdNumberInput);
        }

        if (householdType !== 'HHTS' && householdType !== 'Non-HHTS') {
            setFieldError('householdType', FIELD_ERROR_MESSAGES.householdType);
            invalid.push(hhTypeSelect);
        }

        if (!householdHead) {
            setFieldError('householdHead', FIELD_ERROR_MESSAGES.householdHead);
            invalid.push(householdHeadInput);
        }

        if (!zone) {
            setFieldError('zone', FIELD_ERROR_MESSAGES.zone);
            invalid.push(zoneSelect);
        }

        if (!hasConsent) {
            setFieldError('consent', FIELD_ERROR_MESSAGES.consent);
            invalid.push(consentInput);
        }

        return {
            valid: invalid.length === 0,
            firstInvalid: invalid.find(Boolean) || null,
            values: {
                householdNumber,
                householdHead,
                householdType,
                zone,
            },
        };
    }

    function setPlacing(next, message = null) {
        placing = next;
        root.classList.toggle('is-placing', placing);
        plotBtn.setAttribute('aria-pressed', placing ? 'true' : 'false');

        if (placing) {
            closePanel();
            showOverlay(
                message
                    || 'Click on the map to plot a location.',
                { mode: message ? 'error' : 'info', icon: message ? 'bi-exclamation-triangle-fill' : 'bi-geo-alt-fill' },
            );
        } else if (!locating) {
            hideOverlay();
        }
    }

    function fillPanel(data) {
        const fields = {
            purok: data.purok ?? '—',
            members: data.members != null ? String(data.members) : '—',
            statusLabel: STATUS_LABELS[data.status] ?? data.status ?? '—',
            lat: formatCoord(data.lat),
            lng: formatCoord(data.lng),
        };

        Object.entries(fields).forEach(([key, value]) => {
            const el = panel.querySelector(`[data-field="${key}"]`);
            if (el) {
                el.textContent = value;
            }
        });

        const zone = normalizeZone(data.zone);
        const canAssignZone = Boolean(data.isTemp);

        // Editable inputs only for a NEW temporary plot; existing households are read-only text.
        const numberValue = data.householdNo === 'HH-NEW'
            ? ''
            : (data.householdNo ?? '');
        const headValue = data.houseHead === '—'
            ? ''
            : (data.houseHead ?? '');

        if (householdNumberInput) {
            householdNumberInput.value = numberValue;
            householdNumberInput.hidden = !canAssignZone;
        }
        if (householdNumberText) {
            householdNumberText.hidden = canAssignZone;
            householdNumberText.textContent = numberValue || '—';
        }

        if (householdHeadInput) {
            householdHeadInput.value = headValue;
            householdHeadInput.hidden = !canAssignZone;
        }
        if (householdHeadText) {
            householdHeadText.hidden = canAssignZone;
            householdHeadText.textContent = headValue || '—';
        }
        const householdType = data.householdType === 'HHTS' || data.householdType === 'Non-HHTS'
            ? data.householdType
            : '';

        if (zoneSelect) {
            zoneSelect.hidden = !canAssignZone;
            if (zone) {
                zoneSelect.value = String(zone);
            } else {
                zoneSelect.selectedIndex = 0;
            }
        }

        if (zoneBadge && zoneDot && zoneText) {
            zoneBadge.hidden = canAssignZone;
            zoneText.textContent = zoneLabel(zone);
            zoneDot.className = 'lml-spot-map__zone-dot'
                + (zone ? ` lml-spot-map__zone-dot--${zone}` : '');
        }

        if (hhTypeSelect && hhTypeText) {
            hhTypeSelect.hidden = !canAssignZone;
            hhTypeText.hidden = canAssignZone;
            if (householdType) {
                hhTypeSelect.value = householdType;
            } else {
                hhTypeSelect.selectedIndex = 0;
            }
            hhTypeText.textContent = householdType || '—';
        }

        if (consentWrap && consentInput) {
            consentWrap.hidden = !canAssignZone;
            if (!canAssignZone) {
                consentInput.checked = false;
            }
        }

        if (noteEl) {
            if (data.isTemp && data.source === 'gps') {
                noteEl.textContent = 'Location from device GPS (demo). Select a Barangay Zone, then confirm. Confirming keeps a temporary map marker only — it does not create a household.';
            } else if (data.isTemp) {
                noteEl.textContent = 'Select a Barangay Zone for this household location (demo). Plotting does not create a completed household.';
            } else if (data.householdNo === 'HH-NEW') {
                noteEl.textContent = 'Temporary demo marker only. This household is not yet saved to Profiling, so View Household is unavailable. Closing hides this panel only — nothing is saved.';
            } else {
                noteEl.textContent = 'Demo household details for preview. Use View Household to open the shared Profiling record. Closing hides this panel only — nothing is saved.';
            }
        }

        if (viewHhLink) {
            const canView = !data.isTemp && data.householdNo && data.householdNo !== 'HH-NEW';
            viewHhLink.hidden = !canView;
            if (canView) {
                viewHhLink.href = `${VIEW_HH_BASE}${encodeURIComponent(data.householdNo)}`;
                viewHhLink.setAttribute('aria-label', `View household ${data.householdNo}`);
            } else {
                viewHhLink.removeAttribute('aria-label');
            }
        }

        if (confirmBtn) {
            confirmBtn.hidden = !data.isTemp;
        }
    }

    function refreshMarkerSelection() {
        markerById.forEach((marker, id) => {
            const data = marker.__lmlData;
            const name = markerAccessibleName(data);
            marker.setIcon(createMarkerIcon(data, id === selectedId, name));
            applyMarkerAccessibleName(marker, name);
        });

        if (tempMarker) {
            const data = tempMarker.__lmlData;
            const name = markerAccessibleName(data);
            tempMarker.setIcon(createMarkerIcon(data, data.id === selectedId, name));
            applyMarkerAccessibleName(tempMarker, name);
        }
    }

    function openPanel(data, triggerEl = null) {
        panelReturnFocusEl = triggerEl
            || (document.activeElement instanceof HTMLElement ? document.activeElement : null);
        selectedId = data.id;
        fillPanel(data);
        clearAllFieldErrors();
        panel.hidden = false;
        refreshMarkerSelection();

        if (closeBtn) {
            closeBtn.focus();
        }

        requestAnimationFrame(() => map.invalidateSize());
    }

    function closePanel({ removeTemp = false } = {}) {
        selectedId = null;
        panel.hidden = true;
        refreshMarkerSelection();

        if (removeTemp && tempMarker) {
            map.removeLayer(tempMarker);
            tempMarker = null;
        }

        const returnEl = panelReturnFocusEl;
        panelReturnFocusEl = null;
        if (returnEl && typeof returnEl.focus === 'function' && document.contains(returnEl)) {
            returnEl.focus();
        }

        requestAnimationFrame(() => map.invalidateSize());
    }

    function attachMarker(data, { temporary = false } = {}) {
        const accessibleName = markerAccessibleName(data);
        const marker = L.marker([data.lat, data.lng], {
            icon: createMarkerIcon(data, false, accessibleName),
            keyboard: true,
            title: accessibleName,
            alt: accessibleName,
        });

        marker.__lmlData = data;
        marker.on('add', () => {
            applyMarkerAccessibleName(marker, accessibleName);
        });
        marker.on('click', (event) => {
            L.DomEvent.stopPropagation(event);
            if (placing && !temporary) {
                setPlacing(false);
            }
            openPanel(marker.__lmlData, marker.getElement());
        });

        marker.addTo(map);
        applyMarkerAccessibleName(marker, accessibleName);

        if (temporary) {
            tempMarker = marker;
        } else {
            markerById.set(data.id, marker);
        }

        return marker;
    }

    function placeTemporaryMarker(lat, lng, { accuracy = null, source = 'manual' } = {}) {
        if (tempMarker) {
            map.removeLayer(tempMarker);
            tempMarker = null;
        }

        const tempData = {
            id: createTempHouseholdId(),
            householdNo: 'HH-NEW',
            houseHead: '—',
            address: 'Brgy. La Medalla, Iriga City (demo)',
            purok: '—',
            zone: null,
            householdType: '',
            members: '—',
            lat,
            lng,
            accuracy,
            status: 'new',
            isTemp: true,
            source,
        };

        attachMarker(tempData, { temporary: true });
        return tempData;
    }

    function enterManualFallback(message) {
        setLocating(false);
        setPlacing(true, message);
        focusMapCanvas();
    }

    /**
     * Outside-scope GPS: announce the restriction via setPlacing, then swap to the
     * standard placement cue after 2.8s so demos can continue without re-tapping Plot.
     */
    function enterOutsideScopeFallback(requestId) {
        setLocating(false);
        setPlacing(true, OUTSIDE_SCOPE_MESSAGE);
        focusMapCanvas();

        window.setTimeout(() => {
            if (requestId !== locateRequestId || !placing || locating) {
                return;
            }
            showOverlay('Click on the map to plot a location.', {
                mode: 'info',
                icon: 'bi-geo-alt-fill',
            });
        }, 2800);
    }

    function handleGpsSuccess(position, requestId) {
        if (requestId !== locateRequestId) {
            return;
        }

        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const accuracy = typeof position.coords.accuracy === 'number'
            ? position.coords.accuracy
            : null;

        // UI-only radius guard — no temp marker / flyTo / panel when outside scope.
        if (!isWithinDemoScope(lat, lng)) {
            enterOutsideScopeFallback(requestId);
            return;
        }

        setLocating(false);
        placing = false;
        root.classList.remove('is-placing');
        plotBtn.setAttribute('aria-pressed', 'false');

        showOverlay('Location found. Review the household details.', {
            mode: 'success',
            icon: 'bi-check-circle-fill',
        });

        const tempData = placeTemporaryMarker(lat, lng, { accuracy, source: 'gps' });
        map.flyTo([lat, lng], GPS_ZOOM, { animate: true, duration: 0.85 });

        window.setTimeout(() => {
            if (requestId !== locateRequestId) {
                return;
            }
            hideOverlay();
            openPanel(tempData, plotBtn);
            map.invalidateSize();
        }, 700);
    }

    function handleGpsError(error, requestId) {
        if (requestId !== locateRequestId) {
            return;
        }

        enterManualFallback(geolocationErrorMessage(error));
    }

    function startGpsPlot() {
        closePanel();
        locateRequestId += 1;
        const requestId = locateRequestId;

        setPlacing(false);
        setLocating(true);
        showOverlay('Getting your current location…', {
            mode: 'loading',
            icon: 'bi-geo-alt-fill',
        });

        if (!navigator.geolocation) {
            enterManualFallback(
                'Location access is unavailable. Please enable GPS or manually select a location on the map.',
            );
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => handleGpsSuccess(position, requestId),
            (error) => handleGpsError(error, requestId),
            GEO_OPTIONS,
        );
    }

    DEMO_HOUSEHOLDS.forEach((household) => {
        attachMarker({ ...household });
    });

    plotBtn.addEventListener('click', () => {
        if (locating) {
            return;
        }

        if (placing) {
            setPlacing(false);
            return;
        }

        startGpsPlot();
    });

    map.on('click', (event) => {
        if (!placing || locating) {
            return;
        }

        const { lat, lng } = event.latlng;
        const tempData = placeTemporaryMarker(lat, lng, { source: 'manual' });
        setPlacing(false);
        openPanel(tempData, plotBtn);
    });

    function handleDismiss() {
        locateRequestId += 1;
        setLocating(false);
        setPlacing(false);
        hideOverlay();
        clearAllFieldErrors();
        if (consentInput) {
            consentInput.checked = false;
        }
        // Cancel / Close / Escape must never leave an orphan temporary marker.
        closePanel({ removeTemp: true });
    }

    closeBtn?.addEventListener('click', handleDismiss);
    cancelBtn?.addEventListener('click', handleDismiss);

    zoneSelect?.addEventListener('change', () => {
        if (normalizeZone(zoneSelect.value)) {
            clearFieldError('zone');
        }

        if (!isEditingActiveTempPlot()) {
            return;
        }

        const zone = normalizeZone(zoneSelect.value);
        const data = {
            ...tempMarker.__lmlData,
            zone,
            purok: zone ? ZONE_LABELS[zone] : '—',
        };
        tempMarker.__lmlData = data;
        refreshMarkerSelection();
        fillPanel(data);
    });

    householdNumberInput?.addEventListener('input', () => {
        if ((householdNumberInput.value || '').trim()) {
            clearFieldError('householdNumber');
        }

        if (!isEditingActiveTempPlot()) {
            return;
        }

        tempMarker.__lmlData = {
            ...tempMarker.__lmlData,
            householdNo: householdNumberInput.value,
        };
    });

    householdHeadInput?.addEventListener('input', () => {
        if ((householdHeadInput.value || '').trim()) {
            clearFieldError('householdHead');
        }

        if (!isEditingActiveTempPlot()) {
            return;
        }

        tempMarker.__lmlData = {
            ...tempMarker.__lmlData,
            houseHead: householdHeadInput.value,
        };
    });

    hhTypeSelect?.addEventListener('change', () => {
        if (hhTypeSelect.value === 'HHTS' || hhTypeSelect.value === 'Non-HHTS') {
            clearFieldError('householdType');
        }

        if (!isEditingActiveTempPlot()) {
            return;
        }

        const householdType = hhTypeSelect.value === 'HHTS' || hhTypeSelect.value === 'Non-HHTS'
            ? hhTypeSelect.value
            : '';
        const data = {
            ...tempMarker.__lmlData,
            householdType,
        };
        tempMarker.__lmlData = data;
        fillPanel(data);
    });

    consentInput?.addEventListener('change', () => {
        if (consentInput.checked) {
            clearFieldError('consent');
        }
    });

    confirmBtn?.addEventListener('click', async () => {
        /*
         | After validation, request a server-minted short-lived handoff token,
         | then continue into Household Water Supply Step 1.
         */
        if (!isEditingActiveTempPlot()) {
            return;
        }

        if (confirmBtn.disabled || confirmBtn.getAttribute('aria-busy') === 'true') {
            return;
        }

        const validation = validatePlotForm();
        if (!validation.valid) {
            const firstInvalid = validation.firstInvalid;
            if (firstInvalid) {
                firstInvalid.focus?.();
                firstInvalid.scrollIntoView?.({ block: 'nearest', behavior: 'smooth' });
            }
            return;
        }

        const selectedZone = validation.values.zone;
        const selectedType = validation.values.householdType;
        const confirmedMarker = tempMarker;
        const plotPayload = {
            household_no: validation.values.householdNumber,
            house_head: validation.values.householdHead,
            household_type: selectedType,
            zone: selectedZone,
            lat: confirmedMarker.__lmlData?.lat,
            lng: confirmedMarker.__lmlData?.lng,
            consent: true,
            // UI marker reference only — server assigns the trusted plot_id.
            client_marker_id: String(confirmedMarker.__lmlData?.id || ''),
        };

        const handoffUrl = root.getAttribute('data-plot-handoff-url') || '/spot-mapping/plot-handoff';
        const defaultHandoffError = 'Unable to continue because the household plot session is invalid or expired. Please plot the household again.';
        const panelNote = root.querySelector('[data-spot-map-note]');

        confirmBtn.disabled = true;
        confirmBtn.setAttribute('aria-busy', 'true');
        if (panelNote) {
            panelNote.textContent = 'Securing household plot session…';
            panelNote.hidden = false;
        }

        let handoffToken = '';

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch(handoffUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(plotPayload),
            });

            let body = null;
            try {
                body = await response.json();
            } catch {
                body = null;
            }

            if (!response.ok) {
                const message = typeof body?.message === 'string' && body.message.trim()
                    ? body.message.trim()
                    : defaultHandoffError;
                throw new Error(message);
            }

            handoffToken = typeof body?.handoff_token === 'string' ? body.handoff_token.trim() : '';
            if (!handoffToken) {
                throw new Error(defaultHandoffError);
            }

            const serverPlotId = typeof body?.plot_id === 'string' ? body.plot_id.trim() : '';
            if (serverPlotId) {
                confirmedMarker.__lmlData = {
                    ...confirmedMarker.__lmlData,
                    serverPlotId,
                };
            }
        } catch (error) {
            const message = error instanceof Error && error.message
                ? error.message
                : defaultHandoffError;

            if (panelNote) {
                panelNote.textContent = message;
                panelNote.hidden = false;
            }

            showOverlay(message, {
                mode: 'error',
                icon: 'bi-exclamation-triangle-fill',
            });

            confirmBtn.disabled = false;
            confirmBtn.removeAttribute('aria-busy');
            return;
        }

        const data = {
            ...confirmedMarker.__lmlData,
            householdNo: validation.values.householdNumber,
            houseHead: validation.values.householdHead,
            zone: selectedZone,
            purok: selectedZone ? ZONE_LABELS[selectedZone] : confirmedMarker.__lmlData.purok,
            householdType: selectedType,
            status: 'pending',
            isTemp: false,
        };
        confirmedMarker.__lmlData = data;
        markerById.set(data.id, confirmedMarker);
        tempMarker = null;

        clearAllFieldErrors();
        if (consentInput) {
            consentInput.checked = false;
        }
        locateRequestId += 1;
        setLocating(false);
        setPlacing(false);
        hideOverlay();
        closePanel();

        try {
            sessionStorage.setItem(
                'lml_pending_water_supply_household',
                JSON.stringify({
                    householdNo: data.householdNo,
                    plotId: data.serverPlotId || data.id,
                    clientMarkerId: data.id,
                    zone: data.zone || '',
                    householdType: data.householdType || '',
                    houseHead: data.houseHead || '',
                    lat: data.lat ?? null,
                    lng: data.lng ?? null,
                    plottedAt: new Date().toISOString(),
                })
            );
        } catch {
            // Private browsing may block storage; handoff token carries authority.
        }

        const waterSupplyUrl = new URL(
            '/environmental-health/household-water-supply',
            window.location.origin
        );
        waterSupplyUrl.searchParams.set('handoff', handoffToken);
        window.location.assign(waterSupplyUrl.toString());
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        if (!panel.hidden || placing || locating) {
            handleDismiss();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSpotMapping);
} else {
    initSpotMapping();
}
