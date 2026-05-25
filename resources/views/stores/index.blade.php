@extends('layouts.app')

@section('title', 'Database Toko')

@section('content')
<div class="page-content stores-page">
    <div class="container-fluid pt-1 pb-4">
        <!-- Single Row Search & Action Bar -->
        <form action="{{ route('stores.index') }}" method="GET" class="w-100 mb-3 mt-0" data-search-manual="true">
            <div class="d-flex align-items-center gap-2 w-100 flex-wrap flex-md-nowrap">
                <!-- Search Input -->
                <div class="position-relative flex-grow-1 w-100 w-md-auto">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted small" style="z-index: 10;"></i>
                    <input type="text" name="search" 
                        class="form-control py-2 ps-5 fs-6" 
                        placeholder="Cari nama toko, alamat, atau kota..." 
                        value="{{ request('search') }}">
                </div>

                <!-- Search Button -->
                <button type="submit" class="btn btn-primary py-2 px-4 rounded-2 btn-sm text-nowrap">
                    <i class="bi bi-search me-1"></i>Cari
                </button>

                @if(request()->filled('search'))
                    <a href="{{ route('stores.index') }}" class="btn btn-secondary py-2 px-4 rounded-2 btn-sm text-nowrap">
                        <i class="bi bi-x-lg me-1"></i> Reset
                    </a>
                @endif

                <!-- Add Store Button -->
                <a href="{{ route('stores.create') }}" 
                class="btn btn-success py-2 px-4 rounded-2 btn-sm text-nowrap global-open-modal">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Toko
                </a>
            </div>
        </form>

        <!-- Table Layout -->
        <div class="stores-map-card card border-0 shadow-sm mb-3">
            <div class="card-body py-2 px-2">
                <button type="button"
                    class="stores-map-toggle"
                    id="storesMapAccordionToggle"
                    aria-expanded="false"
                    aria-controls="storesMapAccordionBody">
                    <span class="stores-map-toggle-copy">
                        <span class="stores-map-toggle-title">Preview Peta Semua Toko</span>
                    </span>
                    <span class="stores-map-toggle-icon" aria-hidden="true">
                        <i class="bi bi-chevron-down"></i>
                    </span>
                </button>

                <div class="stores-map-collapse" id="storesMapAccordionBody" hidden>
                    <div id="storesIndexLocationsMap"
                        class="stores-index-map"
                        data-google-maps-api-key="{{ config('services.google.maps_api_key') }}"
                        data-store-label-min-zoom="12"
                        data-store-marker-icon="{{ asset('images/store-marker.svg') }}"
                        hidden></div>
                    <div id="storesIndexMapEmptyState" class="alert alert-light border mb-0 py-2 px-3 small text-muted" hidden>
                        Belum ada lokasi toko yang memiliki koordinat.
                    </div>
                </div>
            </div>
        </div>

        <div class="stores-table-wrapper">
            <div class="table-container text-nowrap">
                <table>
                    <thead class="single-header">
                            <tr>
                                <th class="store-col-no">No</th>
                                <th class="store-col-name">Nama Toko</th>
                                <th class="store-col-address">Alamat</th>
                                <th class="store-col-city">Kota</th>
                                <th class="store-col-province">Provinsi</th>
                                <th class="store-col-phone">No Telp</th>
                                <th class="store-col-pic">Nama PIC</th>
                                <th class="store-col-count text-center">Material</th>
                                <th class="store-col-count text-center">Cabang</th>
                                <th class="store-col-action text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @include('stores.partials.rows', ['stores' => $stores, 'pagination' => $pagination])
                        </tbody>
                    </table>
                    @php
                        $chunkBaseParams = array_filter([
                            'search' => request('search'),
                            'sort_by' => request('sort_by'),
                            'sort_direction' => request('sort_direction'),
                        ], fn ($value) => $value !== null && $value !== '');
                    @endphp
                    <div class="stores-chunk-state"
                        data-base-url="{{ route('stores.chunk', $chunkBaseParams) }}"
                        data-current-page="{{ (int) ($pagination['current_page'] ?? 1) }}"
                        data-last-page="{{ (int) ($pagination['last_page'] ?? 1) }}"
                        data-next-page="{{ $pagination['next_page'] ?? '' }}"
                        hidden></div>
                    <div class="stores-chunk-sentinel" style="height: {{ !empty($pagination['next_page']) ? '48px' : '1px' }}; display: {{ !empty($pagination['next_page']) ? 'flex' : 'none' }}; align-items: center; justify-content: center;">
                        <span class="stores-chunk-loading-indicator" aria-live="polite" aria-label="Memuat data toko berikutnya">
                            <span class="stores-chunk-loading-loop" aria-hidden="true"></span>
                        </span>
                    </div>
                </div>
        </div>
    </div>
</div>

<style>
    html, body {
        overflow-y: hidden !important;
    }

    .stores-page,
    .stores-page .container-fluid {
        height: calc(100vh - 70px);
        display: flex;
        flex-direction: column;
    }

    .stores-table-wrapper {
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .stores-map-card {
        flex: 0 0 auto;
        border-radius: 10px;
    }

    .stores-map-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        width: 100%;
        border: none;
        background: transparent;
        text-align: left;
        color: #0f172a;
    }

    .stores-map-toggle-copy {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }

    .stores-map-toggle-title {
        font-size: 14px;
        font-weight: 700;
        line-height: 1.3;
    }

    .stores-map-toggle-subtitle {
        font-size: 12px;
        line-height: 1.45;
        color: #64748b;
    }

    .stores-map-toggle-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        border-radius: 999px;
        background: #eff6ff;
        color: #2563eb;
        flex: 0 0 auto;
        transition: transform 0.2s ease, background-color 0.2s ease;
    }

    .stores-map-toggle.is-open .stores-map-toggle-icon {
        transform: rotate(180deg);
        background: #dbeafe;
    }

    .stores-map-collapse {
        padding-top: 8px;
    }

    .stores-map-collapse[hidden] {
        display: none !important;
    }

    .stores-index-map {
        width: 100%;
        height: 260px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #f8fafc;
    }

    .store-map-cell {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 6px;
        width: 100%;
        min-width: 0;
    }

    .store-map-warning-note {
        display: inline-block;
        padding: 6px 8px;
        border-radius: 10px;
        background: #fff1f2;
        border: 1px solid #fda4af;
        color: #9f1239;
        font-size: 11px;
        line-height: 1.35;
        white-space: normal;
        align-self: flex-start;
    }

    .stores-map-legend {
        font-size: 12px;
        color: #64748b;
        display: inline-flex;
        align-items: center;
    }

    .stores-table-wrapper .card {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .stores-table-wrapper .table-container {
        overflow-y: auto;
        overflow-x: hidden;
        flex-grow: 1;
        min-height: 0;
        height: 100%;
        box-shadow: none !important;
        margin-top: 0 !important;
        padding-bottom: 12px;
    }

    /* ========== TABLE STYLING (IDENTICAL TO MATERIALS) ========== */
    .table-container {
        position: relative;
    }

    .table-container table {
        border-collapse: separate !important;
        border-spacing: 0 !important;
        width: 100%;
        table-layout: fixed !important;
    }

    /* Single-header styling - COMPACT 40px */
    .table-container thead.single-header th {
        height: 46px !important;
        padding: 10px 12px !important;
        box-sizing: border-box;
    }

    .table-container thead {
        height: 46px !important;
    }

    .table-container thead th {
        position: sticky;
        top: 0;
        background-color: #f8fafc;
        font-weight: 600;
        letter-spacing: 0.05em;
        color: #64748b;
        font-size: 12px;
        border: 1px solid #cbd5e1 !important;
        vertical-align: top !important;
        z-index: 30;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .table-container tbody td {
        border: 1px solid #f1f5f9 !important;
        vertical-align: middle !important;
        color: #1e293b !important;
        text-shadow: none !important;
        -webkit-text-stroke: 0 !important;
        min-height: 46px !important;
        padding: 8px 10px !important;
        font-size: 12.5px !important;
        line-height: 1.45 !important;
        overflow: hidden;
    }

    .table-container .store-col-no {
        width: 42px !important;
        min-width: 42px !important;
        max-width: 42px !important;
        text-align: center !important;
    }

    .table-container .store-col-name {
        width: clamp(128px, 13vw, 190px);
    }

    .table-container .store-col-address {
        width: clamp(190px, 24vw, 330px);
    }

    .table-container .store-col-city {
        width: clamp(88px, 8vw, 125px);
    }

    .table-container .store-col-province {
        width: clamp(96px, 9vw, 140px);
    }

    .table-container .store-col-phone {
        width: clamp(104px, 9vw, 140px);
    }

    .table-container .store-col-pic {
        width: clamp(110px, 10vw, 155px);
    }

    .table-container .store-col-count {
        width: 72px;
    }

    .table-container .store-col-action {
        width: 96px !important;
        min-width: 96px !important;
        max-width: 96px !important;
        text-align: center !important;
    }

    .table-container tbody tr:hover {
        background-color: #fcfcfc;
    }

    .table-container tbody td.store-name-td {
        height: auto !important;
    }

    .table-container thead th.store-col-no,
    .table-container tbody td.store-col-no {
        position: sticky;
        left: 0;
        z-index: 24;
        background: #ffffff;
    }

    .table-container thead th.store-col-no {
        background: #f8fafc;
        top: 0;
        z-index: 45;
    }

    .table-container thead th.store-col-action,
    .table-container tbody td.action-cell {
        position: sticky;
        right: 0;
        z-index: 24;
        background: #ffffff;
        box-shadow: -1px 0 0 #f1f5f9;
    }

    .table-container thead th.store-col-action {
        background: #f8fafc;
        top: 0;
        z-index: 45;
    }

    /* Store scroll cells (for long store name, address, city, province, phone, and PIC values) */
    .store-scroll-td {
        position: relative;
        overflow: hidden;
        min-width: 0;
        max-width: 100%;
        white-space: nowrap;
    }

    .store-address-td {
        width: clamp(190px, 24vw, 330px);
    }

    .store-city-td {
        width: clamp(88px, 8vw, 125px);
    }

    .store-province-td {
        width: clamp(96px, 9vw, 140px);
    }

    .store-phone-td {
        width: clamp(104px, 9vw, 140px);
    }

    .store-pic-td {
        width: clamp(110px, 10vw, 155px);
    }

    .store-missing-location-td {
        white-space: normal;
    }
    .store-scroll-td.is-scrollable::after {
        content: '...';
        position: absolute;
        right: 6px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 12px;
        font-weight: 600;
        color: rgba(15, 23, 42, 0.85);
        background: linear-gradient(90deg, rgba(248, 250, 252, 0) 0%, rgba(248, 250, 252, 0.95) 40%, rgba(248, 250, 252, 1) 100%);
        padding-left: 8px;
        pointer-events: none;
    }
    .store-scroll-td.is-scrolled-end::after {
        opacity: 0;
    }
    .store-scroll-cell {
        display: block;
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: none;
        scrollbar-color: transparent transparent;
        white-space: nowrap;
        cursor: ew-resize;
    }
    .store-scroll-cell::-webkit-scrollbar {
        height: 0;
    }

    .stores-chunk-loading-indicator {
        display: none;
        align-items: center;
        justify-content: center;
    }

    .stores-chunk-loading-indicator.is-visible {
        display: inline-flex;
    }

    .stores-chunk-loading-loop {
        width: 28px;
        height: 28px;
        border-radius: 999px;
        border: 3px solid rgba(148, 163, 184, 0.25);
        border-top-color: #0ea5e9;
        border-right-color: #22c55e;
        animation: storesChunkLoopSpin 0.9s linear infinite;
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.9);
    }

    @keyframes storesChunkLoopSpin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* ========== ACTION BUTTONS (IDENTICAL TO MATERIALS) ========== */
    .btn-group-compact {
        display: inline-flex;
        align-items: center;
        border-radius: 0;
        overflow: visible;
        box-shadow: none;
        background: transparent;
    }
    .btn-group-compact .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 22px;
        width: 26px;
        padding: 0;
        margin: 0;
        border-radius: 0 !important;
        font-size: 12px;
        line-height: 1;
        font-weight: normal !important;
        -webkit-text-stroke: 0 !important;
        text-shadow: none !important;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }
    .btn-group-compact .btn-action:hover {
        background: transparent !important;
        box-shadow: none !important;
    }
    .btn-group-compact .btn-action {
        color: #0f172a !important;
    }
    .btn-group-compact .btn-action.btn btn-primary {
        color: #0f172a !important;
    }
    .btn-group-compact .btn-action.btn-warning {
        color: #b45309 !important;
    }
    .btn-group-compact .btn-action.btn-danger {
        color: #b91c1c !important;
    }
    .btn-group-compact .btn-action i::before {
        -webkit-text-stroke: 0 !important;
    }
    .btn-group-compact .btn-action:first-child {
        border-top-left-radius: 999px !important;
        border-bottom-left-radius: 999px !important;
    }
    .btn-group-compact .btn-action:last-child {
        border-top-right-radius: 999px !important;
        border-bottom-right-radius: 999px !important;
    }
    .btn-group-compact .btn-action + .btn-action {
        border-left: 1px solid rgba(255, 255, 255, 0.35);
    }

    /* ========== MISC ========== */
    /* Keep table rows as native table layout so tbody aligns with thead */
    .table-container tbody tr,
    .table-container .store-row {
        display: table-row !important;
    }

    .badge {
        font-size: 11px !important;
        padding: 0.25em 0.6em;
        font-weight: 500;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    .btn-light, .btn-white {
        color: #1e293b !important;
    }
</style>

<script>
// Scroll indicator for address cells
(function() {
    function updateStoreScrollIndicators() {
        const cells = document.querySelectorAll('.store-scroll-td');
        cells.forEach(td => {
            const scroller = td.querySelector('.store-scroll-cell');
            if (!scroller) return;
            const isScrollable = scroller.scrollWidth > scroller.clientWidth + 1;
            td.classList.toggle('is-scrollable', isScrollable);
            const atEnd = scroller.scrollLeft + scroller.clientWidth >= scroller.scrollWidth - 1;
            td.classList.toggle('is-scrolled-end', isScrollable && atEnd);
        });
    }

    function bindStoreScrollHandlers() {
        const cells = document.querySelectorAll('.store-scroll-td');
        cells.forEach(td => {
            const scroller = td.querySelector('.store-scroll-cell');
            if (!scroller || scroller.__storeScrollBound) return;
            scroller.__storeScrollBound = true;
            scroller.addEventListener('scroll', updateStoreScrollIndicators, { passive: true });
            // Allow normal mouse wheel to pan horizontally inside the address cell.
            scroller.addEventListener('wheel', function(e) {
                const delta = Math.abs(e.deltaX) > 0 ? e.deltaX : e.deltaY;
                if (!delta) return;
                scroller.scrollLeft += delta;
                e.preventDefault();
            }, { passive: false });
        });
    }

    function initializeStoresPageEnhancements() {
        updateStoreScrollIndicators();
        bindStoreScrollHandlers();
        initializeStoresChunkLoader();
        requestAnimationFrame(updateStoreScrollIndicators);
        setTimeout(updateStoreScrollIndicators, 60);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeStoresPageEnhancements);
    } else {
        initializeStoresPageEnhancements();
    }
    window.addEventListener('resize', function() {
        updateStoreScrollIndicators();
        bindStoreScrollHandlers();
        initializeStoresChunkLoader();
    });
    window.addEventListener('load', function() {
        updateStoreScrollIndicators();
        initializeStoresChunkLoader();
    });

    function updateStoreRowNumbers() {
        const rows = document.querySelectorAll('.stores-table-wrapper tbody .store-row');
        rows.forEach((row, index) => {
            const cell = row.querySelector('.store-col-no');
            if (cell) {
                cell.textContent = index + 1;
            }
        });
    }

    async function loadNextStoreChunk() {
        const state = document.querySelector('.stores-chunk-state');
        const sentinel = document.querySelector('.stores-chunk-sentinel');
        const tbody = document.querySelector('.stores-table-wrapper tbody');

        if (!state || !sentinel || !tbody) return false;
        if (state.dataset.loading === 'true') return false;

        const nextPage = Number.parseInt(state.dataset.nextPage || '', 10);
        if (!Number.isFinite(nextPage) || nextPage <= 0) {
            sentinel.style.display = 'none';
            return false;
        }

        state.dataset.loading = 'true';
        const loadingIndicator = sentinel.querySelector('.stores-chunk-loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.classList.add('is-visible');
        }

        try {
            const url = new URL(state.dataset.baseUrl, window.location.origin);
            url.searchParams.set('page', String(nextPage));

            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html, */*;q=0.8'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const html = await response.text();
            const parser = new DOMParser();
            const parsed = parser.parseFromString(html, 'text/html');
            const incomingTbody = parsed.querySelector('tbody');
            const nextState = parsed.querySelector('.stores-chunk-state');

            if (!incomingTbody || !nextState) {
                console.warn('Unexpected stores chunk response shape.', {
                    hasTbody: !!incomingTbody,
                    hasChunkState: !!nextState,
                    responseLength: html.length,
                    responsePreview: html.slice(0, 200),
                });
                if (typeof window.showToast === 'function') {
                    window.showToast('Respons lazy load toko tidak valid. Coba refresh halaman.', 'error');
                }
                return false;
            }

            const incomingRows = Array.from(incomingTbody.querySelectorAll('tr'));
            if (!incomingRows.length) {
                state.dataset.nextPage = '';
                sentinel.style.display = 'none';
                return false;
            }

            const fragment = document.createDocumentFragment();
            incomingRows.forEach(row => fragment.appendChild(row));
            tbody.appendChild(fragment);

            state.dataset.currentPage = nextState.dataset.currentPage || state.dataset.currentPage;
            state.dataset.lastPage = nextState.dataset.lastPage || state.dataset.lastPage;
            state.dataset.nextPage = nextState.dataset.nextPage || '';

            const hasMore = state.dataset.nextPage !== '';
            sentinel.style.display = hasMore ? 'flex' : 'none';
            sentinel.style.height = hasMore ? '48px' : '1px';

            bindStoreScrollHandlers();
            updateStoreScrollIndicators();
            updateStoreRowNumbers();
            document.dispatchEvent(new CustomEvent('stores:rows-appended'));

            return true;
        } catch (error) {
            console.error('Failed to load store chunk:', error);
            if (typeof window.showToast === 'function') {
                window.showToast('Gagal memuat data toko berikutnya.', 'error');
            }
            return false;
        } finally {
            state.dataset.loading = 'false';
            if (loadingIndicator) {
                loadingIndicator.classList.remove('is-visible');
            }
        }
    }

    function initializeStoresChunkLoader() {
        const container = document.querySelector('.stores-table-wrapper .table-container');
        const sentinel = document.querySelector('.stores-chunk-sentinel');
        const state = document.querySelector('.stores-chunk-state');

        if (!container || !sentinel || !state) return;

        if (sentinel.__storesChunkObserver) {
            sentinel.__storesChunkObserver.disconnect();
        }

        if (!state.dataset.nextPage) {
            sentinel.style.display = 'none';
            return;
        }

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    loadNextStoreChunk();
                }
            });
        }, {
            root: container,
            rootMargin: '250px 0px',
            threshold: 0.01
        });

        observer.observe(sentinel);
        sentinel.__storesChunkObserver = observer;

        if (!container.__storesChunkScrollBound) {
            container.__storesChunkScrollBound = true;
            container.addEventListener('scroll', function() {
                const nextPage = Number.parseInt(state.dataset.nextPage || '', 10);
                if (!Number.isFinite(nextPage) || nextPage <= 0) {
                    return;
                }

                const remaining = container.scrollHeight - container.scrollTop - container.clientHeight;
                if (remaining <= 220) {
                    loadNextStoreChunk();
                }
            }, { passive: true });
        }
    }
})();

const initStoresMapAccordion = function() {
    const mapToggleEl = document.getElementById('storesMapAccordionToggle');
    const mapCollapseEl = document.getElementById('storesMapAccordionBody');
    const mapEl = document.getElementById('storesIndexLocationsMap');
    const mapEmptyEl = document.getElementById('storesIndexMapEmptyState');

    if (!mapToggleEl || !mapCollapseEl || mapToggleEl.dataset.accordionReady === 'true') {
        return;
    }

    mapToggleEl.dataset.accordionReady = 'true';

    const apiKey = mapEl?.dataset.googleMapsApiKey || '';
    let storesIndexMap = null;
    let storesIndexBounds = null;
    let mapInitializationPromise = null;
    let storesIndexMarkers = [];
    let markerNameOverlays = [];

    const collectStoresIndexPointsFromRows = function() {
        return Array.from(document.querySelectorAll('.stores-table-wrapper tbody .store-row'))
            .map(function(row) {
                const lat = Number(row.dataset.storeMapLat);
                const lng = Number(row.dataset.storeMapLng);

                return {
                    store_name: row.dataset.storeMapName || '',
                    address: row.dataset.storeMapAddress || '',
                    city: row.dataset.storeMapCity || '',
                    province: row.dataset.storeMapProvince || '',
                    latitude: Number.isFinite(lat) ? lat : null,
                    longitude: Number.isFinite(lng) ? lng : null,
                };
            })
            .filter(function(point) {
                return Number.isFinite(point.latitude) && Number.isFinite(point.longitude);
            });
    };

    const syncMapAccordionState = function(expanded) {
        mapToggleEl.classList.toggle('is-open', expanded);
        mapToggleEl.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        mapCollapseEl.hidden = !expanded;
    };

    const refreshStoresIndexMapViewport = function() {
        if (!storesIndexMap || !storesIndexBounds || !window.google?.maps) {
            return;
        }

        google.maps.event.trigger(storesIndexMap, 'resize');

        const points = collectStoresIndexPointsFromRows();
        if (points.length === 1) {
            storesIndexMap.setCenter(storesIndexBounds.getCenter());
            storesIndexMap.setZoom(14);
            return;
        }

        storesIndexMap.fitBounds(storesIndexBounds, 70);
        google.maps.event.addListenerOnce(storesIndexMap, 'bounds_changed', function() {
            if (storesIndexMap.getZoom() > 14) {
                storesIndexMap.setZoom(14);
            }
        });
    };

    const ensureStoreMarkerLabelStyle = function() {
        if (document.getElementById('stores-index-marker-label-style')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'stores-index-marker-label-style';
        style.textContent = `
            .stores-index-marker-label-overlay {
                position: absolute;
                transform: translate3d(18px, -31px, 0);
                pointer-events: none;
                will-change: transform, left, top;
            }
            .stores-index-marker-label {
                display: inline-block;
                color: #0f172a;
                font-size: 12px;
                font-weight: 600;
                line-height: 1.15;
                white-space: nowrap;
                letter-spacing: 0.05px;
                text-shadow:
                    -1px -1px 0 #ffffff,
                    1px -1px 0 #ffffff,
                    -1px 1px 0 #ffffff,
                    1px 1px 0 #ffffff,
                    0 0 2px rgba(255, 255, 255, 0.95),
                    0 1px 2px rgba(15, 23, 42, 0.2);
            }
        `;
        document.head.appendChild(style);
    };

    const buildStoreMarkerLabelText = function(name) {
        const text = String(name || '').trim();
        if (!text) return 'Toko';
        return text.length <= 26 ? text : `${text.slice(0, 25)}...`;
    };

    const syncStoresIndexMapVisibility = function(points) {
        const hasPoints = Array.isArray(points) && points.length > 0;
        if (mapEl) {
            mapEl.hidden = !hasPoints;
        }
        if (mapEmptyEl) {
            mapEmptyEl.hidden = hasPoints;
        }
    };

    const createStoreIcon = function() {
        const iconUrl = mapEl?.dataset.storeMarkerIcon || '/images/store-marker.svg';
        return {
            url: iconUrl,
            scaledSize: new google.maps.Size(30, 30),
            anchor: new google.maps.Point(15, 30),
        };
    };

    const escapeHtml = function(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    const buildStoreInfoContent = function(point) {
        const addressText = point.address ? escapeHtml(point.address) : '-';
        const cityProvinceText = [point.city, point.province].filter(Boolean).map(escapeHtml).join(', ');
        return `
            <div style="min-width:220px;line-height:1.45;">
                <div style="font-weight:700;color:#0f172a;margin-bottom:4px;">${escapeHtml(point.store_name || '-')}</div>
                <div style="font-size:12px;color:#64748b;">${addressText}</div>
                ${cityProvinceText ? `<div style="font-size:12px;color:#64748b;">${cityProvinceText}</div>` : ''}
            </div>
        `;
    };

    const createStoreNameOverlay = function(map, position, storeName, minZoom) {
        if (!window.google?.maps || typeof window.google.maps.OverlayView !== 'function') {
            return null;
        }

        const latLng = new google.maps.LatLng(position.lat, position.lng);
        const labelText = buildStoreMarkerLabelText(storeName);

        class StoreNameOverlay extends google.maps.OverlayView {
            constructor() {
                super();
                this.containerEl = null;
            }

            onAdd() {
                const container = document.createElement('div');
                container.className = 'stores-index-marker-label-overlay';

                const label = document.createElement('span');
                label.className = 'stores-index-marker-label';
                label.textContent = labelText;
                container.appendChild(label);

                this.containerEl = container;
                const panes = this.getPanes();
                if (panes?.overlayLayer) {
                    panes.overlayLayer.appendChild(container);
                }
            }

            draw() {
                if (!this.containerEl) return;

                const currentZoom = typeof map.getZoom === 'function' ? Number(map.getZoom()) : NaN;
                const hiddenByZoom = Number.isFinite(currentZoom) && currentZoom < minZoom;
                this.containerEl.style.display = hiddenByZoom ? 'none' : 'block';
                if (hiddenByZoom) return;

                const projection = this.getProjection();
                if (!projection) return;

                const pixel = projection.fromLatLngToDivPixel(latLng);
                if (!pixel) return;

                this.containerEl.style.left = `${Math.round(pixel.x)}px`;
                this.containerEl.style.top = `${Math.round(pixel.y)}px`;
            }

            onRemove() {
                if (this.containerEl?.parentNode) {
                    this.containerEl.parentNode.removeChild(this.containerEl);
                }
                this.containerEl = null;
            }
        }

        const overlay = new StoreNameOverlay();
        overlay.setMap(map);
        return overlay;
    };

    const clearStoresIndexMapArtifacts = function() {
        storesIndexMarkers.forEach(function(marker) {
            if (marker && typeof marker.setMap === 'function') {
                marker.setMap(null);
            }
        });
        markerNameOverlays.forEach(function(overlay) {
            if (overlay && typeof overlay.setMap === 'function') {
                overlay.setMap(null);
            }
        });
        storesIndexMarkers = [];
        markerNameOverlays = [];
    };

    const renderStoresIndexMapPoints = function() {
        const points = collectStoresIndexPointsFromRows();
        syncStoresIndexMapVisibility(points);

        if (!storesIndexMap || !window.google?.maps || !points.length) {
            return;
        }

        clearStoresIndexMapArtifacts();
        storesIndexBounds = new google.maps.LatLngBounds();
        const infoWindow = new google.maps.InfoWindow();
        const icon = createStoreIcon();
        const parsedLabelMinZoom = Number(mapEl.dataset.storeLabelMinZoom);
        const storeLabelMinZoom = Number.isFinite(parsedLabelMinZoom) ? parsedLabelMinZoom : 12;

        points.forEach(function(point) {
            const position = { lat: Number(point.latitude), lng: Number(point.longitude) };
            storesIndexBounds.extend(position);

            const marker = new google.maps.Marker({
                map: storesIndexMap,
                position,
                title: point.store_name || 'Toko',
                icon,
                zIndex: 10,
            });
            storesIndexMarkers.push(marker);

            const nameOverlay = createStoreNameOverlay(storesIndexMap, position, point.store_name, storeLabelMinZoom);
            if (nameOverlay) {
                markerNameOverlays.push(nameOverlay);
            }

            marker.addListener('click', function() {
                infoWindow.setContent(buildStoreInfoContent(point));
                infoWindow.open(storesIndexMap, marker);
            });
        });

        storesIndexMap.addListener('click', function() {
            infoWindow.close();
        });

        refreshStoresIndexMapViewport();
    };

    const ensureStoresIndexMap = function() {
        const points = collectStoresIndexPointsFromRows();
        syncStoresIndexMapVisibility(points);

        if (!points.length || !mapEl) {
            return Promise.resolve(null);
        }

        if (storesIndexMap) {
            renderStoresIndexMapPoints();
            refreshStoresIndexMapViewport();
            return Promise.resolve(storesIndexMap);
        }

        if (mapInitializationPromise) {
            return mapInitializationPromise;
        }

        if (!window.GoogleMapsPicker || typeof window.GoogleMapsPicker.loadApi !== 'function') {
            console.warn('GoogleMapsPicker helper is not available for stores index map.');
            return Promise.resolve(null);
        }

        mapInitializationPromise = Promise.resolve(window.GoogleMapsPicker.loadApi(apiKey))
            .then(function() {
                if (!window.google?.maps) {
                    return null;
                }

                ensureStoreMarkerLabelStyle();

                const map = new google.maps.Map(mapEl, {
                    center: { lat: Number(points[0].latitude), lng: Number(points[0].longitude) },
                    zoom: 11,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false,
                    gestureHandling: 'greedy',
                    scrollwheel: true,
                });

                storesIndexMap = map;
                renderStoresIndexMapPoints();

                return map;
            })
            .catch(function(error) {
                console.error('Failed to initialize stores index map:', error);
                return null;
            });

        return mapInitializationPromise;
    };

    mapToggleEl.addEventListener('click', function() {
        const nextExpanded = mapToggleEl.getAttribute('aria-expanded') !== 'true';
        syncMapAccordionState(nextExpanded);

        if (!nextExpanded) {
            return;
        }

        requestAnimationFrame(function() {
            window.dispatchEvent(new Event('resize'));
        });

        ensureStoresIndexMap().then(function(mapInstance) {
            if (!mapInstance) {
                return;
            }

            window.setTimeout(refreshStoresIndexMapViewport, 80);
        });
    });

    syncMapAccordionState(false);

    document.addEventListener('stores:rows-appended', function() {
        if (mapToggleEl.getAttribute('aria-expanded') !== 'true') {
            return;
        }

        if (!storesIndexMap) {
            return;
        }

        renderStoresIndexMapPoints();
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStoresMapAccordion);
} else {
    initStoresMapAccordion();
}
</script>

@endsection
