@php
    $showActions = $showActions ?? true;
    $showStoreInfo = $showStoreInfo ?? true;
    $actionMode = $actionMode ?? 'default';
    $showDeletedMeta = $actionMode === 'recycle-bin';
    $showBulkSelect = $actionMode === 'recycle-bin';
@endphp
@once
    <script>
        (function () {
            if (!window.__materialSkipPage || window.__materialSkipSortHandled) return;
            window.__materialSkipSortHandled = true;
            const url = new URL(window.location.href);
            const sortBy = url.searchParams.get('sort_by');
            const sortDirection = url.searchParams.get('sort_direction');
            if (sortBy !== 'brand' || sortDirection !== 'asc') {
                url.searchParams.set('sort_by', 'brand');
                url.searchParams.set('sort_direction', 'asc');
                url.hash = '#skip-page';
                window.location.replace(url.toString());
            }
        })();
    </script>
    <style>
        .material-chunk-sentinel {
            position: relative;
        }

        .material-chunk-loading-indicator {
            display: none;
            align-items: center;
            gap: 10px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .material-chunk-loading-indicator.is-visible {
            display: inline-flex;
        }

        .material-chunk-loading-loop {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            border: 3px solid rgba(148, 163, 184, 0.25);
            border-top-color: #f59e0b;
            border-right-color: #0ea5e9;
            animation: materialChunkLoopSpin 0.9s linear infinite;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.9);
        }

        @keyframes materialChunkLoopSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
@endonce
@if(isset($material['is_loaded']) && !$material['is_loaded'])
    @php
        $lazyTabParams = [
            'type' => $material['type'],
            'search' => request('search'),
            'sort_by' => request('sort_by'),
            'sort_direction' => request('sort_direction'),
        ];
        $lazyTabParams = array_filter($lazyTabParams, function ($value) {
            return $value !== null && $value !== '';
        });
    @endphp
    <div class="material-tab-loading" data-url="{{ route('materials.tab', $lazyTabParams) }}" style="position: relative; overflow: hidden; background: transparent; padding: 0;">
        {{-- Skeleton Loader CSS --}}
        <style>
            .material-skeleton-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
            }
            .material-skeleton-header {
                background: #ffffff;
                border-bottom: 1px solid #e2e8f0;
                height: 40px;
            }
            .material-skeleton-header th {
                border: 1px solid #cbd5e1 !important;
                background: #ffffff;
                vertical-align: top !important;
                box-sizing: border-box;
            }
            /* Single Header Height */
            .material-skeleton-header.single-header tr th {
                height: 40px !important;
                padding: 8px 12px !important;
                line-height: 1.1;
            }
            /* Double Header Group Row Height */
            .material-skeleton-header.has-dim-sub tr.dim-group-row th {
                height: 26px !important;
                padding: 6px 12px !important;
                line-height: 1.1;
            }
            /* Double Header Sub Row Height */
            .material-skeleton-header.has-dim-sub tr.dim-sub-row th {
                height: 14px !important;
                padding: 1px 2px !important;
                font-size: 11px !important;
            }
            
            .material-skeleton-row {
                height: 35px !important; /* Match real row height */
            }
            .material-skeleton-cell {
                border: 1px solid #f1f5f9;
                background-color: #ffffff;
                padding: 2px 8px !important;
                vertical-align: middle;
                height: 35px !important;
            }
            .skeleton-box {
                height: 16px;
                background: #f1f5f9;
                border-radius: 4px;
                width: 100%;
                position: relative;
                overflow: hidden;
            }
            .skeleton-box::after {
                content: "";
                position: absolute;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;
                transform: translateX(-100%);
                background-image: linear-gradient(
                    90deg,
                    rgba(255, 255, 255, 0) 0,
                    rgba(255, 255, 255, 0.5) 20%,
                    rgba(255, 255, 255, 0.8) 60%,
                    rgba(255, 255, 255, 0)
                );
                animation: shimmer 2s infinite;
            }
            .skeleton-w-10 { width: 10%; }
            .skeleton-w-20 { width: 20%; }
            .skeleton-w-30 { width: 30%; }
            .skeleton-w-40 { width: 40%; }
            .skeleton-w-50 { width: 50%; }
            .skeleton-w-60 { width: 60%; }
            .skeleton-w-70 { width: 70%; }
            
            @keyframes shimmer {
                100% { transform: translateX(100%); }
            }
            
            .loader-overlay {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(255, 255, 255, 0.95);
                padding: 24px 40px;
                border-radius: 16px;
                box-shadow: none;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 16px;
                border: none;
                z-index: 10;
            }
            
            .construction-spinner {
                width: 48px;
                height: 48px;
                position: relative;
                animation: spin 3s linear infinite;
            }
            .construction-spinner::before, .construction-spinner::after {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
                right: 0;
                border: 4px solid transparent;
                border-top-color: #f59e0b; /* Amber-500 */
                border-radius: 50%;
                animation: spin-reverse 1.5s linear infinite;
            }
            .construction-spinner::after {
                border-top-color: transparent;
                border-bottom-color: #0ea5e9; /* Sky-500 */
                animation: spin 2s linear infinite;
            }
            
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            @keyframes spin-reverse { 0% { transform: rotate(360deg); } 100% { transform: rotate(0deg); } }
        </style>

        {{-- Interactive Construction Spinner --}}
        <div class="loader-overlay">
            <div class="construction-spinner"></div>
            <div style="font-weight: 600; color: #334155; font-size: 15px; letter-spacing: 0.01em;">Memuat Data Material...</div>
        </div>

        {{-- Table Skeleton Background --}}
        <div class="table-container text-nowrap" style="opacity: 1; pointer-events: none;">
            <table class="material-skeleton-table">
                @switch($material['type'])
                    @case('brick')
                        <thead class="material-skeleton-header has-dim-sub">
                            <tr class="dim-group-row">
                                <th rowspan="2" style="width: 40px; min-width: 40px; text-align: center;"><div class="skeleton-box skeleton-w-60" style="margin: 0 auto;"></div></th>
                                <th rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-30"></div></th>
                                <th colspan="3" style="text-align: center; width: 120px; min-width: 120px;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th colspan="2" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-60"></div></th>
                                <th colspan="3" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                @if($showStoreInfo)
                                <th rowspan="2" style="text-align: left; width: 150px; min-width: 150px;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th rowspan="2" style="text-align: left; width: 200px; min-width: 200px;"><div class="skeleton-box skeleton-w-70"></div></th>
                                @endif
                                <th colspan="3" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th colspan="3" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="width: 90px; min-width: 90px;"><div class="skeleton-box skeleton-w-20"></div></th>
                            </tr>
                            <tr class="dim-sub-row">
                                <th style="text-align: center; width: 50px; padding: 0 2px;"></th>
                                <th style="text-align: center; width: 50px; padding: 0 2px;"></th>
                                <th style="text-align: center; width: 50px; padding: 0 2px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 12; $i++)
                            <tr class="material-skeleton-row">
                                <td class="material-skeleton-cell" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50" style="margin: 0 auto;"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-60"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center;"><div class="skeleton-box skeleton-w-30"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right; width: 80px;"><div class="skeleton-box skeleton-w-70"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="text-align: left;"><div class="skeleton-box skeleton-w-40"></div></td>
                                @if($showStoreInfo)
                                <td class="material-skeleton-cell" style="width: 150px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="width: 200px;"><div class="skeleton-box skeleton-w-60"></div></td>
                                @endif
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 60px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="width: 60px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 90px;"><div class="skeleton-box skeleton-w-30" style="margin: 0 auto;"></div></td>
                            </tr>
                            @endfor
                        </tbody>
                        @break

                    @case('sand')
                        <thead class="material-skeleton-header has-dim-sub">
                            <tr class="dim-group-row">
                                <th rowspan="2" style="width: 40px; min-width: 40px; text-align: center;"><div class="skeleton-box skeleton-w-60" style="margin: 0 auto;"></div></th>
                                <th rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-30"></div></th>
                                <th colspan="3" style="text-align: center; width: 120px; min-width: 120px;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th colspan="2" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-60"></div></th>
                                @if($showStoreInfo)
                                <th rowspan="2" style="text-align: left; width: 150px; min-width: 150px;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th rowspan="2" style="text-align: left; width: 200px; min-width: 200px;"><div class="skeleton-box skeleton-w-70"></div></th>
                                @endif
                                <th colspan="3" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th colspan="3" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="width: 90px; min-width: 90px;"><div class="skeleton-box skeleton-w-20"></div></th>
                            </tr>
                            <tr class="dim-sub-row">
                                <th style="text-align: center; width: 50px; padding: 0 2px;"></th>
                                <th style="text-align: center; width: 50px; padding: 0 2px;"></th>
                                <th style="text-align: center; width: 50px; padding: 0 2px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 12; $i++)
                            <tr class="material-skeleton-row">
                                <td class="material-skeleton-cell" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50" style="margin: 0 auto;"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-60"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center;"><div class="skeleton-box skeleton-w-30"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right; width: 60px;"><div class="skeleton-box skeleton-w-70"></div></td>
                                <td class="material-skeleton-cell" style="width: 30px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 150px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="width: 200px;"><div class="skeleton-box skeleton-w-60"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="width: 80px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 90px;"><div class="skeleton-box skeleton-w-30" style="margin: 0 auto;"></div></td>
                            </tr>
                            @endfor
                        </tbody>
                        @break

                    @case('cat')
                        <thead class="material-skeleton-header single-header">
                            <tr>
                                <th class="cat-sticky-col col-no" style="width: 40px; min-width: 40px; text-align: center;"><div class="skeleton-box skeleton-w-60" style="margin: 0 auto;"></div></th>
                                <th class="cat-sticky-col col-type" style="text-align: left;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th class="cat-sticky-col col-brand cat-sticky-edge" style="text-align: center;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th style="text-align: start;"><div class="skeleton-box skeleton-w-30"></div></th>
                                <th style="text-align: right;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th style="text-align: left;"><div class="skeleton-box skeleton-w-60"></div></th>
                                <th colspan="3" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></th>
                                <th colspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></th>
                                <th colspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></th>
                                @if($showStoreInfo)
                                <th style="text-align: left; width: 150px; min-width: 150px;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th style="text-align: left; width: 200px; min-width: 200px;"><div class="skeleton-box skeleton-w-70"></div></th>
                                @endif
                                <th colspan="3" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th colspan="3" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th style="width: 90px; min-width: 90px;"><div class="skeleton-box skeleton-w-20"></div></th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 12; $i++)
                            <tr class="material-skeleton-row">
                                <td class="material-skeleton-cell cat-sticky-col col-no" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50" style="margin: 0 auto;"></div></td>
                                <td class="material-skeleton-cell cat-sticky-col col-type" style="text-align: left;"><div class="skeleton-box skeleton-w-60"></div></td>
                                <td class="material-skeleton-cell cat-sticky-col col-brand cat-sticky-edge" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="text-align: start;"><div class="skeleton-box skeleton-w-30"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: left;"><div class="skeleton-box skeleton-w-70"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center; width: 50px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right; width: 50px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: left; width: 50px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right; width: 60px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: left; width: 30px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right; width: 60px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: left; width: 30px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                @if($showStoreInfo)
                                <td class="material-skeleton-cell" style="text-align: left; width: 150px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: left; width: 200px;"><div class="skeleton-box skeleton-w-60"></div></td>
                                @endif
                                <td class="material-skeleton-cell" style="text-align: right; width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right; width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="text-align: left; width: 80px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right; width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right; width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="text-align: left; width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 90px;"><div class="skeleton-box skeleton-w-30" style="margin: 0 auto;"></div></td>
                            </tr>
                            @endfor
                        </tbody>
                        @break

                    @case('cement')
                    @case('nat')
                        <thead class="material-skeleton-header single-header">
                            <tr>
                                <th class="cement-sticky-col" rowspan="2" style="width: 40px; min-width: 40px; text-align: center;"><div class="skeleton-box skeleton-w-60" style="margin: 0 auto;"></div></th>
                                <th class="cement-sticky-col" rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th class="cement-sticky-col cement-sticky-edge" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-30"></div></th>
                                <th rowspan="2" style="text-align: right;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-60"></div></th>
                                <th rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></th>
                                <th colspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></th>
                                @if($showStoreInfo)
                                <th rowspan="2" style="text-align: left; width: 150px; min-width: 150px;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th rowspan="2" style="text-align: left; width: 200px; min-width: 200px;"><div class="skeleton-box skeleton-w-70"></div></th>
                                @endif
                                <th colspan="3" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th colspan="3" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="width: 90px; min-width: 90px;"><div class="skeleton-box skeleton-w-20"></div></th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 12; $i++)
                            <tr class="material-skeleton-row">
                                <td class="material-skeleton-cell cement-sticky-col" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50" style="margin: 0 auto;"></div></td>
                                <td class="material-skeleton-cell cement-sticky-col"><div class="skeleton-box skeleton-w-60"></div></td>
                                <td class="material-skeleton-cell cement-sticky-col cement-sticky-edge" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-30"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-70"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                @if($showStoreInfo)
                                <td class="material-skeleton-cell" style="width: 150px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="width: 200px;"><div class="skeleton-box skeleton-w-60"></div></td>
                                @endif
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 90px;"><div class="skeleton-box skeleton-w-30" style="margin: 0 auto;"></div></td>
                            </tr>
                            @endfor
                        </tbody>
                        @break

                    @case('ceramic')
                        <thead class="material-skeleton-header has-dim-sub">
                            <tr class="dim-group-row">
                                <th class="ceramic-sticky-col col-no" rowspan="2" style="width: 40px; min-width: 40px; text-align: center;"><div class="skeleton-box skeleton-w-60" style="margin: 0 auto;"></div></th>
                                <th class="ceramic-sticky-col col-type" rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th class="ceramic-sticky-col col-dim-group" colspan="3" style="text-align: center; width: 120px; min-width: 120px;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th class="ceramic-sticky-col col-brand ceramic-sticky-edge" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-30"></div></th>
                                <th rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="text-align: right;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="text-align: left;"><div class="skeleton-box skeleton-w-60"></div></th>
                                <th rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></th>
                                <th colspan="3" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></th>
                                <th colspan="2" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></th>
                                @if($showStoreInfo)
                                <th rowspan="2" style="text-align: left; width: 150px; min-width: 150px;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th rowspan="2" style="text-align: left; width: 200px; min-width: 200px;"><div class="skeleton-box skeleton-w-70"></div></th>
                                @endif
                                <th colspan="3" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th colspan="3" rowspan="2" style="text-align: center;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th rowspan="2" style="width: 90px; min-width: 90px;"><div class="skeleton-box skeleton-w-20"></div></th>
                            </tr>
                            <tr class="dim-sub-row">
                                <th class="ceramic-sticky-col col-dim-p" style="text-align: center; width: 50px; padding: 0 2px;"></th>
                                <th class="ceramic-sticky-col col-dim-l" style="text-align: center; width: 50px; padding: 0 2px;"></th>
                                <th class="ceramic-sticky-col col-dim-t" style="text-align: center; width: 50px; padding: 0 2px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 12; $i++)
                            <tr class="material-skeleton-row">
                                <td class="material-skeleton-cell ceramic-sticky-col col-no" style="text-align: center; width: 40px;"><div class="skeleton-box skeleton-w-50" style="margin: 0 auto;"></div></td>
                                <td class="material-skeleton-cell ceramic-sticky-col col-type" style="text-align: left;"><div class="skeleton-box skeleton-w-60"></div></td>
                                <td class="material-skeleton-cell ceramic-sticky-col col-dim-p" style="text-align: center; width: 50px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell ceramic-sticky-col col-dim-l" style="text-align: center; width: 50px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell ceramic-sticky-col col-dim-t" style="text-align: center; width: 50px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell ceramic-sticky-col col-brand ceramic-sticky-edge" style="text-align: center;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-30"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="text-align: right;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-70"></div></td>
                                <td class="material-skeleton-cell" style="text-align: center;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 60px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 30px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 150px;"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell" style="width: 200px;"><div class="skeleton-box skeleton-w-60"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 80px;"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell" style="width: 40px;"><div class="skeleton-box skeleton-w-20"></div></td>
                                <td class="material-skeleton-cell" style="width: 90px;"><div class="skeleton-box skeleton-w-30" style="margin: 0 auto;"></div></td>
                            </tr>
                            @endfor
                        </tbody>
                        @break

                    @default
                        {{-- Fallback generic skeleton --}}
                        <thead class="material-skeleton-header">
                            <tr>
                                <th style="width: 50px; text-align: center;"><div class="skeleton-box skeleton-w-60" style="margin: 0 auto;"></div></th>
                                <th style="width: 150px;"><div class="skeleton-box skeleton-w-40"></div></th>
                                <th style="width: 120px;"><div class="skeleton-box skeleton-w-50"></div></th>
                                <th style="width: 100px;"><div class="skeleton-box skeleton-w-30"></div></th>
                                <th><div class="skeleton-box skeleton-w-40"></div></th>
                                <th><div class="skeleton-box skeleton-w-60"></div></th>
                                <th style="width: 80px;"><div class="skeleton-box skeleton-w-20"></div></th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 12; $i++)
                            <tr class="material-skeleton-row">
                                <td class="material-skeleton-cell" style="text-align: center;"><div class="skeleton-box skeleton-w-50" style="margin: 0 auto;"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-60"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-40"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-30"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-50"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-70"></div></td>
                                <td class="material-skeleton-cell"><div class="skeleton-box skeleton-w-40"></div></td>
                            </tr>
                            @endfor
                        </tbody>
                @endswitch
            </table>
        </div>
    </div>
@else
    <style>
        .material-table-loaded th {
            font-weight: 700 !important;
        }

        .material-table-loaded thead th span {
            font-weight: 700 !important;
        }

        .material-map-cell {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
        }

        .material-map-warning-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 8px;
            border-radius: 999px;
            border: 1px solid #fee2e2;
            background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%);
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            box-shadow: 0 10px 24px rgba(185, 28, 28, 0.24);
            animation: material-map-warning-pulse 1.6s ease-in-out infinite;
        }

        .material-map-warning-note {
            display: inline-block;
            padding: 6px 8px;
            border-radius: 10px;
            background: #fff1f2;
            border: 1px solid #fda4af;
            color: #9f1239;
            font-size: 11px;
            line-height: 1.35;
            white-space: normal;
        }

        .material-map-warning-link {
            color: #9f1239 !important;
            font-weight: 800;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .material-map-warning-link:hover {
            color: #881337 !important;
            text-decoration: underline;
        }

        @keyframes material-map-warning-pulse {
            0%, 100% { transform: translateY(0); box-shadow: 0 14px 32px rgba(220, 38, 38, 0.28); }
            50% { transform: translateY(-1px); box-shadow: 0 18px 36px rgba(220, 38, 38, 0.36); }
        }
    </style>
    @php
        $inlineFormId = 'material-inline-form-' . $material['type'];
    @endphp
    <div class="material-table-frame" data-inline-panel="{{ $material['type'] }}">
        @if($showActions)
            <button type="button"
                class="material-inline-create-handle open-inline-create"
                data-inline-type="{{ $material['type'] }}"
                data-inline-url="{{ route($material['type'] . 's.create') }}"
                data-inline-store-url="{{ route($material['type'] . 's.store') }}"
                data-inline-label="{{ $material['label'] }}"
                title="Tambah {{ $material['label'] }}">
                <i class="bi bi-plus-lg"></i>
            </button>
        @endif
        <div class="table-container text-nowrap material-table-loaded">
        <table>
            <thead class="{{ in_array($material['type'], ['brick','sand','ceramic','steel','kasa_gypsum','paku','paku_tembak']) ? 'has-dim-sub' : 'single-header' }}">
                @php
                  if (!function_exists('getMaterialSortUrl')) {
                        function getMaterialSortUrl($column, $currentSortBy, $currentDirection, $isStoreLocation = false, $store = null, $location = null) {
                            $params = array_merge(request()->query(), []);
                            $tabParam = request('tab');
                            if (!$tabParam) {
                                $routeType = request()->route('type');
                                if (is_string($routeType) && $routeType !== '') {
                                    $tabParam = $routeType;
                                }
                            }
                            if ($tabParam) {
                                $params['tab'] = $tabParam;
                            }
                            unset($params['sort_by'], $params['sort_direction']);
                            if ($currentSortBy === $column) {
                                if ($currentDirection === 'asc') {
                                    $params['sort_by'] = $column;
                                    $params['sort_direction'] = 'desc';
                                } elseif ($currentDirection === 'desc') {
                                    unset($params['sort_by'], $params['sort_direction']);
                                } else {
                                    $params['sort_by'] = $column;
                                    $params['sort_direction'] = 'asc';
                                }
                            } else {
                                $params['sort_by'] = $column;
                                $params['sort_direction'] = 'asc';
                            }

                            // Use appropriate route based on context
                            if ($isStoreLocation && $store && $location) {
                                return route('store-locations.materials', array_merge(['store' => $store->id, 'location' => $location->id], $params));
                            }

                            if (request()->routeIs('materials.recycle-bin')) {
                                return route('materials.recycle-bin', $params);
                            }

                            return route('materials.index', $params);
                        }
                    }

                    // Set context variables for sort URLs
                    $sortIsStoreLocation = isset($isStoreLocation) && $isStoreLocation;
                    $sortStore = $sortIsStoreLocation && isset($store) ? $store : null;
                    $sortLocation = $sortIsStoreLocation && isset($location) ? $location : null;

                    $brickSortable = [
                        'type' => 'Jenis',
                        'brand' => 'Merek',
                        'form' => 'Bentuk',
                        'dimension_length' => 'Dimensi ( cm )',
                        'package_volume' => 'Volume',
                        'store' => 'Toko',
                        'address' => 'Alamat',
                        'package_type' => 'Kemasan',
                        'price_per_piece' => 'Harga Beli',
                        'comparison_price_per_m3' => 'Harga <br> Komparasi ( / M3 )',
                    ];
                    $sandSortable = [
                        'type' => 'Jenis',
                        'brand' => 'Merek',
                        'package_unit' => 'Kemasan',
                        'dimension_length' => 'Dimensi Kemasan ( M )',
                        'package_volume' => 'Volume',
                        'store' => 'Toko',
                        'address' => 'Alamat',
                        'package_price' => 'Harga Beli',
                        'comparison_price_per_m3' => 'Harga <br> Komparasi ( / M3 )',
                    ];
                    $catSortable = [
                        'type' => 'Jenis',
                        'brand' => 'Merek',
                        'sub_brand' => 'Sub Merek',
                        'color_code' => 'Kode',
                        'color_name' => 'Warna',
                        'package_unit' => 'Kemasan',
                        'volume' => 'Volume',
                        'package_weight_net' => 'Berat Bersih',
                        'store' => 'Toko',
                        'address' => 'Alamat',
                        'purchase_price' => 'Harga Beli',
                        'comparison_price_per_kg' => 'Harga <br> Komparasi ( / Kg )',
                    ];
                    $cementSortable = [
                        'type' => 'Jenis',
                        'brand' => 'Merek',
                        'sub_brand' => 'Sub Merek',
                        'code' => 'Kode',
                        'color' => 'Warna',
                        'package_unit' => 'Kemasan',
                        'dimension_length' => 'Dimensi ( cm )',
                        'package_weight_net' => 'Berat Bersih',
                        'store' => 'Toko',
                        'address' => 'Alamat',
                        'package_price' => 'Harga Beli',
                        'comparison_price_per_kg' => 'Harga <br> Komparasi ( / Kg )',
                    ];
                    $ceramicSortable = [
                        'type' => 'Jenis',
                        'brand' => 'Merek',
                        'sub_brand' => 'Sub Merek',
                        'code' => 'Kode',
                        'color' => 'Warna',
                        'form' => 'Bentuk',
                        'surface' => 'Permukaan',
                        'packaging' => 'Kemasan',
                        'pieces_per_package' => 'Volume',
                        'coverage_per_package' => 'Luas ( M2 / Dus )',
                        'dimension_length' => 'Dimensi ( cm )',
                        'store' => 'Toko',
                        'address' => 'Alamat',
                        'price_per_package' => 'Harga / Kemasan',
                        'comparison_price_per_m2' => 'Harga Komparasi <br> ( / M2 )',
                    ];
                    $steelSortable = [
                        'type' => 'Jenis',
                        'brand' => 'Merek',
                        'quality' => 'Kualitas',
                        'term' => 'Istilah',
                        'package_unit' => 'Kemasan',
                        'dimension_length' => 'Dimensi',
                        'package_volume' => 'Volume',
                        'store' => 'Toko',
                        'address' => 'Alamat',
                        'package_price' => 'Harga Beli',
                        'comparison_price_per_m3' => 'Harga Komparasi',
                    ];
                    @endphp
                        @if($material['type'] == 'brick')
                            <tr class="dim-group-row">
                                @if($showBulkSelect)
                                    <th class="brick-sticky-col recycle-select-all-th" rowspan="2" style="text-align: center; width: 34px; min-width: 34px;">
                                        <input type="checkbox"
                                            class="recycle-select-all-checkbox recycle-row-checkbox"
                                            data-material-tab="{{ $material['type'] }}"
                                            aria-label="Pilih semua">
                                    </th>
                                @endif
                                <th class="brick-sticky-col col-no" rowspan="2" style="text-align: center; width: 40px; min-width: 40px;">No</th>
                                <th class="sortable brick-sticky-col col-type" rowspan="2" style="text-align: left;">
                                    <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                        style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $brickSortable['type'] }}</span>
                                        @if(request('sort_by') == 'type')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable brick-sticky-col col-brand brick-sticky-edge" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                        style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $brickSortable['brand'] }}</span>
                                        @if(request('sort_by') == 'brand')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('form', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                        style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $brickSortable['form'] }}</span>
                                        @if(request('sort_by') == 'form')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="3" style="text-align: center; font-size: 13px; width: 120px; min-width: 120px;">
                                    <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                        style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Dimensi ( cm )</span>
                                        @if(in_array(request('sort_by'), ['dimension_length','dimension_width','dimension_height']))
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="2" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_volume', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                        style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Volume</span>
                                        @if(request('sort_by') == 'package_volume')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_type', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                        style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $brickSortable['package_type'] }}</span>
                                        @if(request('sort_by') == 'package_type')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showStoreInfo)
                                <th class="sortable" rowspan="2" style="text-align: left; width: 150px; min-width: 150px;">
                                    <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                        style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $brickSortable['store'] }}</span>
                                        @if(request('sort_by') == 'store')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: left; width: 200px; min-width: 200px;">
                                    <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                        style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $brickSortable['address'] }}</span>
                                        @if(request('sort_by') == 'address')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @endif
                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('price_per_piece', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                        style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Harga Beli</span>
                                        @if(request('sort_by') == 'price_per_piece')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('comparison_price_per_m3', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                        style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Harga Komparasi</span>
                                        @if(request('sort_by') == 'comparison_price_per_m3')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showActions)
                                @if($showDeletedMeta)
                                <th rowspan="2" class="deleted-meta-col" style="text-align: left;">Dihapus Oleh</th>
                                <th rowspan="2" class="deleted-meta-col" style="text-align: left;">Dihapus Pada</th>
                                @endif
                                <th rowspan="2" class="action-cell">Aksi</th>
                                @endif
                            </tr>
                            <tr class="dim-sub-row">
                                <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">P</th>
                                <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">L</th>
                                <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">T</th>
                            </tr>

                        @elseif($material['type'] == 'sand')
                            <tr class="dim-group-row">
                                @if($showBulkSelect)
                                    <th class="recycle-select-all-th" rowspan="2" style="text-align: center; width: 34px; min-width: 34px;">
                                        <input type="checkbox"
                                            class="recycle-select-all-checkbox recycle-row-checkbox"
                                            data-material-tab="{{ $material['type'] }}"
                                            aria-label="Pilih semua">
                                    </th>
                                @endif
                                <th rowspan="2" style="text-align: center; width: 40px; min-width: 40px;">No</th>
                                <th class="sortable" rowspan="2" style="text-align: left;">
                                    <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $sandSortable['type'] }}</span>
                                        @if(request('sort_by') == 'type')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $sandSortable['brand'] }}</span>
                                        @if(request('sort_by') == 'brand')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $sandSortable['package_unit'] }}</span>
                                        @if(request('sort_by') == 'package_unit')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="3" style="text-align: center; font-size: 13px; width: 120px; min-width: 120px;">
                                    <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Dimensi ( M )</span>
                                        @if(in_array(request('sort_by'), ['dimension_length','dimension_width','dimension_height']))
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="2" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_volume', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Volume</span>
                                        @if(request('sort_by') == 'package_volume')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showStoreInfo)
                                <th class="sortable" rowspan="2" style="text-align: left; width: 150px; min-width: 150px;">
                                    <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $sandSortable['store'] }}</span>
                                        @if(request('sort_by') == 'store')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: left; width: 200px; min-width: 200px;">
                                    <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $sandSortable['address'] }}</span>
                                        @if(request('sort_by') == 'address')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @endif
                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_price', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Harga Beli</span>
                                        @if(request('sort_by') == 'package_price')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('comparison_price_per_m3', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Harga Komparasi</span>
                                        @if(request('sort_by') == 'comparison_price_per_m3')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showActions)
                                @if($showDeletedMeta)
                                <th rowspan="2" class="deleted-meta-col" style="text-align: left;">Dihapus Oleh</th>
                                <th rowspan="2" class="deleted-meta-col" style="text-align: left;">Dihapus Pada</th>
                                @endif
                                <th rowspan="2" class="action-cell">Aksi</th>
                                @endif
                            </tr>
                            <tr class="dim-sub-row">
                                <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">P</th>
                                <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">L</th>
                                <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">T</th>
                            </tr>

                        @elseif($material['type'] == 'cat')
                            <tr class="dim-group-row">
                                @if($showBulkSelect)
                                    <th class="cat-sticky-col recycle-select-all-th" style="text-align: center; width: 34px; min-width: 34px;">
                                        <input type="checkbox"
                                            class="recycle-select-all-checkbox recycle-row-checkbox"
                                            data-material-tab="{{ $material['type'] }}"
                                            aria-label="Pilih semua">
                                    </th>
                                @endif
                                <th class="cat-sticky-col col-no" style="text-align: center; width: 40px; min-width: 40px;">No</th>
                                <th class="sortable cat-sticky-col col-type" style="text-align: left;">
                                    <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $catSortable['type'] }}</span>
                                        @if(request('sort_by') == 'type')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable cat-sticky-col col-brand cat-sticky-edge" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $catSortable['brand'] }}</span>
                                        @if(request('sort_by') == 'brand')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" style="text-align: start;">
                                    <a href="{{ getMaterialSortUrl('sub_brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $catSortable['sub_brand'] }}</span>
                                        @if(request('sort_by') == 'sub_brand')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" style="text-align: right;">
                                    <a href="{{ getMaterialSortUrl('color_code', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $catSortable['color_code'] }}</span>
                                        @if(request('sort_by') == 'color_code')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" style="text-align: left;">
                                    <a href="{{ getMaterialSortUrl('color_name', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $catSortable['color_name'] }}</span>
                                        @if(request('sort_by') == 'color_name')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="3" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $catSortable['package_unit'] }}</span>
                                        @if(request('sort_by') == 'package_unit')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('volume', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $catSortable['volume'] }}</span>
                                        @if(request('sort_by') == 'volume')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_weight_net', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Berat<br>Bersih</span>
                                        @if(request('sort_by') == 'package_weight_net')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showStoreInfo)
                                <th class="sortable" style="text-align: left; width: 150px; min-width: 150px;">
                                    <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $catSortable['store'] }}</span>
                                        @if(request('sort_by') == 'store')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" style="text-align: left; width: 200px; min-width: 200px;">
                                    <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $catSortable['address'] }}</span>
                                        @if(request('sort_by') == 'address')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @endif
                                <th class="sortable" colspan="3" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('purchase_price', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Harga Beli</span>
                                        @if(request('sort_by') == 'purchase_price')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="3" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('comparison_price_per_kg', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Harga Komparasi</span>
                                        @if(request('sort_by') == 'comparison_price_per_kg')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showActions)
                                @if($showDeletedMeta)
                                <th class="deleted-meta-col" style="text-align: left;">Dihapus Oleh</th>
                                <th class="deleted-meta-col" style="text-align: left;">Dihapus Pada</th>
                                @endif
                                <th class="action-cell">Aksi</th>
                                @endif
                            </tr>
                            
                        @elseif(in_array($material['type'], ['cement', 'nat']))
                            <tr class="dim-group-row">
                                @if($showBulkSelect)
                                    <th class="cement-sticky-col recycle-select-all-th" rowspan="2" style="text-align: center; width: 34px; min-width: 34px;">
                                        <input type="checkbox"
                                            class="recycle-select-all-checkbox recycle-row-checkbox"
                                            data-material-tab="{{ $material['type'] }}"
                                            aria-label="Pilih semua">
                                    </th>
                                @endif
                                <th class="cement-sticky-col" rowspan="2" style="text-align: center; width: 40px; min-width: 40px;">No</th>
                                <th class="sortable cement-sticky-col" rowspan="2" style="text-align: left;">
                                    <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $cementSortable['type'] }}</span>
                                        @if(request('sort_by') == 'type')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable cement-sticky-col cement-sticky-edge" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $cementSortable['brand'] }}</span>
                                        @if(request('sort_by') == 'brand')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: left;">
                                    <a href="{{ getMaterialSortUrl('sub_brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $cementSortable['sub_brand'] }}</span>
                                        @if(request('sort_by') == 'sub_brand')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: right;">
                                    <a href="{{ getMaterialSortUrl('code', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $cementSortable['code'] }}</span>
                                        @if(request('sort_by') == 'code')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: left;">
                                    <a href="{{ getMaterialSortUrl('color', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $cementSortable['color'] }}</span>
                                        @if(request('sort_by') == 'color')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $cementSortable['package_unit'] }}</span>
                                        @if(request('sort_by') == 'package_unit')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_weight_net', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Berat<br>Bersih</span>
                                        @if(request('sort_by') == 'package_weight_net')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showStoreInfo)
                                <th class="sortable" rowspan="2" style="text-align: left; width: 150px; min-width: 15s0px;">
                                    <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $cementSortable['store'] }}</span>
                                        @if(request('sort_by') == 'store')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: left; width: 200px; min-width: 200px;">
                                    <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $cementSortable['address'] }}</span>
                                        @if(request('sort_by') == 'address')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @endif
                                <th class="sortable" colspan="3" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_price', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Harga Beli</span>
                                        @if(request('sort_by') == 'package_price')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="3" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('comparison_price_per_kg', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Harga Komparasi</span>
                                        @if(request('sort_by') == 'comparison_price_per_kg')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showActions)
                                @if($showDeletedMeta)
                                <th rowspan="2" class="deleted-meta-col" style="text-align: left;">Dihapus Oleh</th>
                                <th rowspan="2" class="deleted-meta-col" style="text-align: left;">Dihapus Pada</th>
                                @endif
                                <th rowspan="2" class="action-cell">Aksi</th>
                                @endif
                            </tr>

                        @elseif($material['type'] == 'steel')
                            <tr class="dim-group-row">
                                @if($showBulkSelect)
                                    <th class="recycle-select-all-th" rowspan="2" style="text-align: center; width: 34px; min-width: 34px;">
                                        <input type="checkbox"
                                            class="recycle-select-all-checkbox recycle-row-checkbox"
                                            data-material-tab="{{ $material['type'] }}"
                                            aria-label="Pilih semua">
                                    </th>
                                @endif
                                <th class="steel-sticky-col col-no" rowspan="2" style="text-align: center; width: 40px; min-width: 40px;">No</th>
                                <th class="sortable steel-sticky-col col-type" rowspan="2" style="text-align: left;">
                                    <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $steelSortable['type'] }}</span>
                                        @if(request('sort_by') == 'type')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable steel-sticky-col col-brand steel-sticky-edge" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $steelSortable['brand'] }}</span>
                                        @if(request('sort_by') == 'brand')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('quality', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $steelSortable['quality'] }}</span>
                                        @if(request('sort_by') == 'quality')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('term', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $steelSortable['term'] }}</span>
                                        @if(request('sort_by') == 'term')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $steelSortable['package_unit'] }}</span>
                                        @if(request('sort_by') == 'package_unit')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="8" style="text-align: center; font-size: 13px;">
                                    <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                        <span>Dimensi</span>
                                        @if(in_array(request('sort_by'), ['dimension_length','dimension_width','dimension_height','dimension_thickness']))
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="2" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_volume', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $steelSortable['package_volume'] }}</span>
                                        @if(request('sort_by') == 'package_volume')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showStoreInfo)
                                <th class="sortable" rowspan="2" style="text-align: left; width: 150px; min-width: 150px;">
                                    <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $steelSortable['store'] }}</span>
                                        @if(request('sort_by') == 'store')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: left; width: 200px; min-width: 200px;">
                                    <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $steelSortable['address'] }}</span>
                                        @if(request('sort_by') == 'address')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @endif
                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_price', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $steelSortable['package_price'] }}</span>
                                        @if(request('sort_by') == 'package_price')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('comparison_price_per_m3', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                       style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>{{ $steelSortable['comparison_price_per_m3'] }}</span>
                                        @if(request('sort_by') == 'comparison_price_per_m3')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showActions)
                                @if($showDeletedMeta)
                                <th rowspan="2" class="deleted-meta-col" style="text-align: left;">Dihapus Oleh</th>
                                <th rowspan="2" class="deleted-meta-col" style="text-align: left;">Dihapus Pada</th>
                                @endif
                                <th rowspan="2" class="action-cell">Aksi</th>
                                @endif
                            </tr>
                            <tr class="dim-sub-row">
                                <th colspan="2" style="text-align: center; font-size: 12px !important; padding: 0 2px; min-width: 74px;">Panjang</th>
                                <th colspan="2" style="text-align: center; font-size: 12px !important; padding: 0 2px; min-width: 74px;">Lebar</th>
                                <th colspan="2" style="text-align: center; font-size: 12px !important; padding: 0 2px; min-width: 74px;">Tinggi</th>
                                <th colspan="2" style="text-align: center; font-size: 12px !important; padding: 0 2px; min-width: 74px;">Tebal</th>
                            </tr>

                        @elseif($material['type'] == 'kasa_gypsum')
                            <tr class="dim-group-row">
                                @if($showBulkSelect)
                                    <th class="recycle-select-all-th" rowspan="2" style="text-align: center; width: 34px; min-width: 34px;">
                                        <input type="checkbox"
                                            class="recycle-select-all-checkbox recycle-row-checkbox"
                                            data-material-tab="{{ $material['type'] }}"
                                            aria-label="Pilih semua">
                                    </th>
                                @endif
                                <th rowspan="2" style="text-align: center; width: 40px; min-width: 40px;">No</th>
                                <th class="sortable" rowspan="2" style="text-align: left;">
                                    <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Jenis</span>
                                        @if(request('sort_by') == 'type')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Merek</span>
                                        @if(request('sort_by') == 'brand')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Kemasan</span>
                                        @if(request('sort_by') == 'package_unit')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="4" style="text-align: center; font-size: 13px;">
                                    <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Ukuran</span>
                                        @if(in_array(request('sort_by'), ['dimension_length','dimension_width']))
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showStoreInfo)
                                <th class="sortable" rowspan="2" style="text-align: left; width: 150px; min-width: 150px;">
                                    <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Toko</span>
                                        @if(request('sort_by') == 'store')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: left; width: 200px; min-width: 200px;">
                                    <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Alamat</span>
                                        @if(request('sort_by') == 'address')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @endif
                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_price', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Harga Beli</span>
                                        @if(request('sort_by') == 'package_price')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('comparison_price_per_m', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Harga Komparasi</span>
                                        @if(request('sort_by') == 'comparison_price_per_m')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showActions)
                                @if($showDeletedMeta)
                                <th rowspan="2" class="deleted-meta-col" style="text-align: left;">Dihapus Oleh</th>
                                <th rowspan="2" class="deleted-meta-col" style="text-align: left;">Dihapus Pada</th>
                                @endif
                                <th rowspan="2" class="action-cell">Aksi</th>
                                @endif
                            </tr>
                            <tr class="dim-sub-row kasa-dim-sub-row">
                                <th colspan="2" style="text-align: center; font-size: 12px !important; padding: 0 2px; min-width: 80px;">Lebar</th>
                                <th colspan="2" style="text-align: center; font-size: 12px !important; padding: 0 2px; min-width: 80px;">Panjang</th>
                            </tr>

                        @elseif($material['type'] == 'paku_tembak')
                            <tr class="dim-group-row">
                                @if($showBulkSelect)
                                    <th class="recycle-select-all-th" rowspan="2" style="text-align: center; width: 34px; min-width: 34px;">
                                        <input type="checkbox"
                                            class="recycle-select-all-checkbox recycle-row-checkbox"
                                            data-material-tab="{{ $material['type'] }}"
                                            aria-label="Pilih semua">
                                    </th>
                                @endif
                                <th class="paku-tembak-sticky-col col-no" rowspan="2" style="text-align: center; width: 40px; min-width: 40px;">No</th>
                                <th class="sortable paku-tembak-sticky-col col-type" rowspan="2" style="text-align: left;">
                                    <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Jenis</span>
                                        @if(request('sort_by') == 'type')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable paku-tembak-sticky-col col-brand paku-tembak-sticky-edge" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Merek</span>
                                        @if(request('sort_by') == 'brand')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Kemasan</span>
                                        @if(request('sort_by') == 'package_unit')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="5" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('mesiu_code', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Mesiu</span>
                                        @if(in_array(request('sort_by'), ['mesiu_code', 'mesiu_size', 'mesiu_content']))
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="5" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('paku_code', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Paku</span>
                                        @if(in_array(request('sort_by'), ['paku_code', 'paku_size', 'paku_content']))
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showStoreInfo)
                                <th class="sortable" rowspan="2" style="text-align: left; width: 150px; min-width: 150px;">
                                    <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Toko</span>
                                        @if(request('sort_by') == 'store')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: left; width: 200px; min-width: 200px;">
                                    <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Alamat</span>
                                        @if(request('sort_by') == 'address')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @endif
                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('package_price', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Harga Beli</span>
                                        @if(request('sort_by') == 'package_price')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('comparison_price', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Harga Komparasi</span>
                                        @if(request('sort_by') == 'comparison_price')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showActions)
                                @if($showDeletedMeta)
                                <th rowspan="2" class="deleted-meta-col" style="text-align: left;">Dihapus Oleh</th>
                                <th rowspan="2" class="deleted-meta-col" style="text-align: left;">Dihapus Pada</th>
                                @endif
                                <th rowspan="2" class="action-cell">Aksi</th>
                                @endif
                            </tr>
                            <tr class="dim-sub-row paku-tembak-mesiu-sub-row">
                                <th style="text-align: center; font-size: 12px; padding: 0 2px;">Kode</th>
                                <th colspan="2" style="text-align: center; font-size: 12px; padding: 0 2px;">Ukuran</th>
                                <th colspan="2" style="text-align: center; font-size: 12px; padding: 0 2px;">Isi</th>
                                <th style="text-align: center; font-size: 12px; padding: 0 2px;">Kode</th>
                                <th colspan="2" style="text-align: center; font-size: 12px; padding: 0 2px;">Ukuran</th>
                                <th colspan="2" style="text-align: center; font-size: 12px; padding: 0 2px;">Isi</th>
                            </tr>

                        @elseif($material['type'] == 'paku')
                            <tr class="dim-group-row">
                                @if($showBulkSelect)
                                    <th class="recycle-select-all-th" rowspan="2" style="text-align: right; width: 34px; min-width: 34px;">
                                        <input type="checkbox"
                                            class="recycle-select-all-checkbox recycle-row-checkbox"
                                            data-material-tab="{{ $material['type'] }}"
                                            aria-label="Pilih semua">
                                    </th>
                                @endif
                                <th class="paku-sticky-col col-no" rowspan="2" style="text-align: right; width: 40px; min-width: 40px;">No</th>
                                <th class="sortable paku-sticky-col col-type" rowspan="2" style="text-align: right;">
                                    <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: flex-end; gap: 6px;">
                                        <span>Jenis</span>
                                        @if(request('sort_by') == 'type')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable paku-sticky-col col-brand" rowspan="2" style="text-align: right;">
                                    <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: flex-end; gap: 6px;">
                                        <span>Merek</span>
                                        @if(request('sort_by') == 'brand')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable paku-sticky-col paku-sticky-edge" colspan="8" style="text-align: center;">
                                    <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                        <span>Dimensi</span>
                                        @if(in_array(request('sort_by'), ['dimension_length','dimension_length_mm','dimension_body_diameter','dimension_head_diameter']))
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: right;">
                                    <a href="{{ getMaterialSortUrl('color', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: flex-end; gap: 6px;">
                                        <span>Warna</span>
                                        @if(request('sort_by') == 'color')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: right;">
                                    <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: flex-end; gap: 6px;">
                                        <span>Kemasan</span>
                                        @if(request('sort_by') == 'package_unit')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="2" rowspan="2" style="text-align: right;">
                                    <a href="{{ getMaterialSortUrl('package_weight', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: flex-end; gap: 6px;">
                                        <span>Berat Isi</span>
                                        @if(request('sort_by') == 'package_weight')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="2" rowspan="2" style="text-align: right;">
                                    <a href="{{ getMaterialSortUrl('package_content', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: flex-end; gap: 6px;">
                                        <span>Jumlah Isi</span>
                                        @if(request('sort_by') == 'package_content')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showStoreInfo)
                                <th class="sortable" rowspan="2" style="text-align: right; width: 150px; min-width: 150px;">
                                    <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: flex-end; gap: 6px;">
                                        <span>Toko</span>
                                        @if(request('sort_by') == 'store')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" rowspan="2" style="text-align: right; width: 200px; min-width: 200px;">
                                    <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: flex-end; gap: 6px;">
                                        <span>Alamat</span>
                                        @if(request('sort_by') == 'address')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @endif
                                <th class="sortable" colspan="3" rowspan="2" style="text-align: right;">
                                    <a href="{{ getMaterialSortUrl('package_price', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: flex-end; gap: 6px;">
                                        <span>Harga Beli</span>
                                        @if(request('sort_by') == 'package_price')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" colspan="3" rowspan="2" style="text-align: right;">
                                    <a href="{{ getMaterialSortUrl('comparison_price', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: flex-end; gap: 6px;">
                                        <span>Harga Komparasi</span>
                                        @if(request('sort_by') == 'comparison_price')
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                        @endif
                                    </a>
                                </th>
                                @if($showActions)
                                @if($showDeletedMeta)
                                <th class="deleted-meta-col" rowspan="2" style="text-align: right;">Dihapus Oleh</th>
                                <th class="deleted-meta-col" rowspan="2" style="text-align: right;">Dihapus Pada</th>
                                @endif
                                <th class="action-cell" rowspan="2">Aksi</th>
                                @endif
                            </tr>
                            <tr class="dim-sub-row paku-dim-sub-row">
                                <th class="paku-sticky-col" colspan="4" style="text-align: center; font-size: 12px; padding: 0 2px; min-width: 90px;">
                                    <span>Panjang</span>
                                </th>
                                <th class="paku-sticky-col" colspan="2" style="text-align: center; font-size: 12px; padding: 0 2px; min-width: 100px;">
                                    <span>Diameter Badan</span>
                                </th>
                                <th class="paku-sticky-col paku-sticky-edge" colspan="2" style="text-align: center; font-size: 12px; padding: 0 2px; min-width: 100px;">
                                    <span>Diameter Kepala</span>
                                </th>
                            </tr>

                        @elseif($material['type'] == 'ceramic')
                        <tr class="dim-group-row">
                            @if($showBulkSelect)
                                <th class="ceramic-sticky-col recycle-select-all-th" rowspan="2" style="text-align: center; width: 34px; min-width: 34px;">
                                    <input type="checkbox"
                                        class="recycle-select-all-checkbox recycle-row-checkbox"
                                        data-material-tab="{{ $material['type'] }}"
                                        aria-label="Pilih semua">
                                </th>
                            @endif
                            <th class="ceramic-sticky-col col-no" rowspan="2" style="text-align: center;">No</th>
                            <th class="sortable ceramic-sticky-col col-type" rowspan="2" style="text-align: left;">
                                <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                    <span>{{ $ceramicSortable['type'] }}</span>
                                    @if(request('sort_by') == 'type')
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="sortable ceramic-sticky-col col-dim-group" colspan="3" style="text-align: center; font-size: 13px;">
                                <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                    <span>Dimensi ( cm )</span>
                                    @if(in_array(request('sort_by'), ['dimension_length','dimension_width','dimension_thickness']))
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="sortable ceramic-sticky-col col-brand ceramic-sticky-edge" rowspan="2" style="text-align: center;">
                                <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                    <span>{{ $ceramicSortable['brand'] }}</span>
                                    @if(request('sort_by') == 'brand')
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="sortable" rowspan="2" style="text-align: left;">
                                <a href="{{ getMaterialSortUrl('sub_brand', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                    <span>{{ $ceramicSortable['sub_brand'] }}</span>
                                    @if(request('sort_by') == 'sub_brand')
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="sortable" rowspan="2" style="text-align: left;">
                                <a href="{{ getMaterialSortUrl('surface', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                    <span>{{ $ceramicSortable['surface'] }}</span>
                                    @if(request('sort_by') == 'surface')
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 12px; font-size: 12px; opacity: 0.8 !important;"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="sortable" rowspan="2" style="text-align: right;">
                                <a href="{{ getMaterialSortUrl('code', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                    <span>Nomor Seri<br>( Kode Pembakaran )</span>
                                    @if(request('sort_by') == 'code')
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="sortable" rowspan="2" style="text-align: left;">
                                <a href="{{ getMaterialSortUrl('color', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                    <span>Corak ( {{ $ceramicSortable['color'] }} )</span>
                                    @if(request('sort_by') == 'color')
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="sortable" rowspan="2" style="text-align: center;">
                                <a href="{{ getMaterialSortUrl('form', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                    <span>{{ $ceramicSortable['form'] }}</span>
                                    @if(request('sort_by') == 'form')
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                <a href="{{ getMaterialSortUrl('packaging', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                    <span>{{ $ceramicSortable['packaging'] }}</span>
                                    @if(request('sort_by') == 'packaging')
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="sortable" colspan="2" rowspan="2" style="text-align: center;">
                                <a href="{{ getMaterialSortUrl('coverage_per_package', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                    <span>Luas<br>( / Dus )</span>
                                    @if(request('sort_by') == 'coverage_per_package')
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                    @endif
                                </a>
                            </th>
                            @if($showStoreInfo)
                            <th class="sortable" rowspan="2" style="text-align: left; width: 150px; min-width: 150px;">
                                <a href="{{ getMaterialSortUrl('store', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                    <span>{{ $ceramicSortable['store'] }}</span>
                                    @if(request('sort_by') == 'store')
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="sortable" rowspan="2" style="text-align: left; width: 200px; min-width: 200px;">
                                <a href="{{ getMaterialSortUrl('address', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                    <span>{{ $ceramicSortable['address'] }}</span>
                                    @if(request('sort_by') == 'address')
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.8 !important;"></i>
                                    @endif
                                </a>
                            </th>
                            @endif
                            <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                <a href="{{ getMaterialSortUrl('price_per_package', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                   style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                    <span>Harga Beli</span>
                                    @if(request('sort_by') == 'price_per_package')
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                <a href="{{ getMaterialSortUrl('comparison_price_per_m2', request('sort_by'), request('sort_direction'), $sortIsStoreLocation, $sortStore, $sortLocation) }}"
                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                    <span>Harga Komparasi</span>
                                    @if(request('sort_by') == 'comparison_price_per_m2')
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.8 !important;"></i>
                                    @endif
                                </a>
                            </th>
                            @if($showActions)
                            @if($showDeletedMeta)
                            <th rowspan="2" class="deleted-meta-col" style="text-align: left;">Dihapus Oleh</th>
                            <th rowspan="2" class="deleted-meta-col" style="text-align: left;">Dihapus Pada</th>
                            @endif
                            <th rowspan="2" class="action-cell">Aksi</th>
                            @endif
                        </tr>
                        <tr class="dim-sub-row">
                            <th class="ceramic-sticky-col col-dim-p" style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">P</th>
                            <th class="ceramic-sticky-col col-dim-l" style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">L</th>
                            <th class="ceramic-sticky-col col-dim-t" style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">T</th>
                        </tr>
                        @endif
                    </thead>
                        @php
                            $letterGroups = $material['data']->groupBy(function ($item) use ($material) {
                                $groupValue = $item->brand ?? '';
                                $groupValue = trim((string) $groupValue);
                                return $groupValue !== '' ? strtoupper(substr($groupValue, 0, 1)) : '#';
                            });
                            $orderedGroups = collect();
                            $isSorting = request()->filled('sort_by');
                            $defaultSort = false;
                                                    
                            if ($isSorting) {
                                $orderedGroups['*'] = $material['data'];
                            } else {
                                // Modified: Sort by Type (Jenis) alphabetically as default, instead of grouping by Brand
                                $orderedGroups['*'] = $material['data']->sortBy('type');
                            }
                            $pagination = (array) ($material['pagination'] ?? []);
                            $currentPage = max(1, (int) ($pagination['current_page'] ?? 1));
                            $perPage = max(1, (int) ($pagination['per_page'] ?? count($material['data'])));
                            $rowNumber = (($currentPage - 1) * $perPage) + 1;
                            $seenAnchors = [];
                        @endphp
                    <tbody>
                        <tr class="material-inline-editor-row" data-inline-row hidden>
                            @include('materials.partials.inline-editor-cells', [
                                'material' => $material,
                                'showStoreInfo' => $showStoreInfo,
                                'inlineFormId' => $inlineFormId,
                                'inlinePackageUnits' => $inlinePackageUnits ?? [],
                            ])
                        </tr>
                        @if(collect($material['data'] ?? [])->isEmpty())
                            <tr class="material-empty-search-row" data-non-material-row="true">
                                <td colspan="99" style="padding: 18px 20px; text-align: center; color: #64748b; font-weight: 600; background: #fffdf7;">
                                    {{ request('search') ? 'Tidak ada hasil pencarian di tab ini.' : 'Belum ada data material di tab ini.' }}
                                </td>
                            </tr>
                        @endif
                        @foreach($orderedGroups as $letter => $items)
                            @foreach($items as $item)
                            @php
                                $brandFirst = $item->brand ?? '';
                                $brandFirst = trim((string) $brandFirst);
                                $rowLetter = $brandFirst !== '' ? strtoupper(substr($brandFirst, 0, 1)) : '#';
                                if (!ctype_alpha($rowLetter)) {
                                    $rowLetter = '#';
                                }
                                                    
                                $rowAnchorId = null;
                                if (!$defaultSort && !isset($seenAnchors[$rowLetter])) {
                                    $anchorSuffix = $rowLetter === '#' ? 'other' : $rowLetter;
                                    $rowAnchorId = $material['type'] . '-letter-' . $anchorSuffix;
                                    $seenAnchors[$rowLetter] = true;
                                }
                                $searchParts = array_filter([
                                    $item->type ?? null,
                                    $item->material_name ?? null,
                                    $item->cat_name ?? null,
                                    $item->cement_name ?? null,
                                    $item->nat_name ?? null,
                                    $item->sand_name ?? null,
                                    $item->brand ?? null,
                                    $item->quality ?? null,
                                    $item->term ?? null,
                                    $item->sub_brand ?? null,
                                    $item->code ?? null,
                                    $item->color ?? null,
                                    $item->color_name ?? null,
                                    $material['type'] !== 'steel' ? ($item->form ?? null) : null,
                                    $item->package_type ?? null,
                                    $item->surface ?? null,
                                ], function ($value) {
                                    return !is_null($value) && trim((string) $value) !== '';
                                });
                                $searchValue = strtolower(trim(preg_replace('/\s+/', ' ', implode(' ', $searchParts))));
                                
                                $stickyClass = '';
                                if($material['type'] == 'ceramic') $stickyClass = 'ceramic-sticky-col col-no';
                                elseif($material['type'] == 'brick') $stickyClass = 'brick-sticky-col col-no';
                                elseif($material['type'] == 'cat') $stickyClass = 'cat-sticky-col col-no';
                                elseif(in_array($material['type'], ['cement', 'nat'])) $stickyClass = 'cement-sticky-col';
                                elseif($material['type'] == 'steel') $stickyClass = 'steel-sticky-col col-no';
                                elseif($material['type'] == 'paku_tembak') $stickyClass = 'paku-tembak-sticky-col col-no';
                                elseif($material['type'] == 'paku') $stickyClass = 'paku-sticky-col col-no';
                                $rowMaterialType = $item->row_material_type ?? (($item->material_kind ?? null) === 'nat' ? 'nat' : $material['type']);

                                $inlinePricePerPiece = $item->price_per_piece ?? '';
                                if ($material['type'] === 'brick' && strtolower((string) ($item->package_type ?? '')) === 'kubik') {
                                    $inlinePricePerPiece = $item->comparison_price_per_m3 ?? $item->price_per_piece ?? '';
                                }
                            @endphp
                    <tr data-material-tab="{{ $material['type'] }}"
                        data-material-id="{{ $item->id }}"
                        data-material-kind="{{ $item->type ?? $item->nat_name ?? '' }}"
                        data-material-search="{{ $searchValue }}"
                        data-material-brand-letter="{{ $rowLetter }}"
                        data-inline-update-url="{{ route($rowMaterialType . 's.update', $item->id) }}"
                        data-inline-material-type="{{ $rowMaterialType }}"
                        data-inline-photo-path="{{ $item->photo ?? '' }}"
                        data-inline-field-type="{{ $item->type ?? '' }}"
                        data-inline-field-nat-name="{{ $item->nat_name ?? '' }}"
                        data-inline-field-brand="{{ $item->brand ?? '' }}"
                        data-inline-field-quality="{{ $item->quality ?? '' }}"
                        data-inline-field-term="{{ $item->term ?? '' }}"
                        data-inline-field-sub-brand="{{ $item->sub_brand ?? '' }}"
                        data-inline-field-form="{{ $material['type'] === 'steel' ? '' : ($item->form ?? '') }}"
                        data-inline-field-code="{{ $item->code ?? '' }}"
                        data-inline-field-color="{{ $item->color ?? '' }}"
                        data-inline-field-color-code="{{ $item->color_code ?? '' }}"
                        data-inline-field-color-name="{{ $item->color_name ?? '' }}"
                        data-inline-field-surface="{{ $item->surface ?? '' }}"
                        data-inline-field-package-type="{{ $item->package_type ?? '' }}"
                        data-inline-field-package-unit="{{ $item->package_unit ?? '' }}"
                        data-inline-field-packaging="{{ $item->packaging ?? '' }}"
                        data-inline-field-dimension-length="{{ $item->dimension_length ?? '' }}"
                        data-inline-field-dimension-length-mm="{{ $item->dimension_length_mm ?? '' }}"
                        data-inline-field-dimension-width="{{ $item->dimension_width ?? '' }}"
                        data-inline-field-dimension-height="{{ $item->dimension_height ?? '' }}"
                        data-inline-field-dimension-body-diameter="{{ $item->dimension_body_diameter ?? '' }}"
                        data-inline-field-dimension-head-diameter="{{ $item->dimension_head_diameter ?? '' }}"
                        data-inline-field-dimension-thickness="{{ $item->dimension_thickness ?? '' }}"
                        data-inline-field-package-volume="{{ $item->package_volume ?? '' }}"
                        data-inline-field-package-weight="{{ $item->package_weight ?? '' }}"
                        data-inline-field-package-weight-gross="{{ $item->package_weight_gross ?? '' }}"
                        data-inline-field-package-weight-net="{{ $item->package_weight_net ?? '' }}"
                        data-inline-field-package-content="{{ $item->package_content ?? '' }}"
                        data-inline-field-mesiu-code="{{ $item->mesiu_code ?? '' }}"
                        data-inline-field-mesiu-size="{{ $item->mesiu_size ?? '' }}"
                        data-inline-field-mesiu-content="{{ $item->mesiu_content ?? '' }}"
                        data-inline-field-paku-code="{{ $item->paku_code ?? '' }}"
                        data-inline-field-paku-size="{{ $item->paku_size ?? '' }}"
                        data-inline-field-paku-content="{{ $item->paku_content ?? '' }}"
                        data-inline-field-volume="{{ $item->volume ?? '' }}"
                        data-inline-field-volume-unit="{{ $item->volume_unit ?? '' }}"
                        data-inline-field-pieces-per-package="{{ $item->pieces_per_package ?? '' }}"
                        data-inline-field-coverage-per-package="{{ $item->coverage_per_package ?? '' }}"
                        data-inline-field-store="{{ $item->store ?? '' }}"
                        data-inline-field-address="{{ $item->address ?? '' }}"
                        data-inline-field-store-location-id="{{ $item->store_location_id ?? '' }}"
                        data-inline-field-price-per-piece="{{ $inlinePricePerPiece }}"
                        data-inline-field-package-price="{{ $item->package_price ?? '' }}"
                        data-inline-field-purchase-price="{{ $item->purchase_price ?? '' }}"
                        data-inline-field-price-per-package="{{ $item->price_per_package ?? '' }}"
                        data-inline-field-comparison-price-per-m3="{{ $item->comparison_price_per_m3 ?? '' }}"
                        data-inline-field-comparison-price-per-kg="{{ $item->comparison_price_per_kg ?? '' }}"
                        data-inline-field-comparison-price-per-m="{{ $item->comparison_price_per_m ?? '' }}"
                        data-inline-field-comparison-price-per-m2="{{ $item->comparison_price_per_m2 ?? '' }}"
                        data-inline-field-comparison-price="{{ $item->comparison_price ?? '' }}">
                        @include('materials.partials.row-content', [
                            'material' => $material,
                            'item' => $item,
                            'rowNumber' => $rowNumber,
                            'stickyClass' => $stickyClass,
                            'rowAnchorId' => $rowAnchorId,
                            'rowMaterialType' => $rowMaterialType,
                            'actionMode' => $actionMode,
                        ])
                        
                        @if($showActions)
                        @if($showDeletedMeta)
                        <td class="deleted-meta-col text-left whitespace-nowrap">{{ $item->deleted_by_name ?? ($item->deletedBy?->name ?? '-') }}</td>
                        <td class="deleted-meta-col text-left whitespace-nowrap">{{ $item->deleted_at_formatted ?? ($item->deleted_at ? $item->deleted_at->format('d-m-Y H:i:s') : '-') }}</td>
                        @endif
                        <td class="text-center action-cell">
                            @if($actionMode === 'recycle-bin')
                                @include('materials.recycle-bin.partials.actions', ['item' => $item, 'rowMaterialType' => $rowMaterialType])
                            @else
                            <div class="btn-group-compact">
                                <a href="{{ route($rowMaterialType . 's.show', $item->id) }}" class="btn btn-primary btn-action open-modal" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route($rowMaterialType . 's.edit', $item->id) }}"
                                    class="btn btn-warning btn-action open-inline-edit"
                                    data-inline-type="{{ $material['type'] }}"
                                    title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <button type="button"
                                    class="btn btn-danger btn-action"
                                    title="Hapus"
                                    onclick="deleteMaterial('{{ $rowMaterialType }}', {{ $item->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @php $rowNumber++; @endphp
                        @endforeach
                    @endforeach
                </tbody>
            </table>
            @php
                $chunkParams = array_filter([
                    'type' => $material['type'],
                    'search' => request('search'),
                    'sort_by' => request('sort_by'),
                    'sort_direction' => request('sort_direction'),
                    'letter' => request('letter'),
                ], fn ($value) => $value !== null && $value !== '');
                $chunkBaseUrl = route('materials.tab', $chunkParams);
                $pagination = (array) ($material['pagination'] ?? []);
                $currentPage = max(1, (int) ($pagination['current_page'] ?? 1));
                $lastPage = max(1, (int) ($pagination['last_page'] ?? 1));
                $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;
            @endphp
            <div class="material-chunk-state"
                data-base-url="{{ $chunkBaseUrl }}"
                data-current-page="{{ $currentPage }}"
                data-last-page="{{ $lastPage }}"
                data-next-page="{{ $nextPage ?? '' }}"
                data-material-tab="{{ $material['type'] }}"
                hidden></div>
            <div class="material-chunk-sentinel"
                data-material-tab="{{ $material['type'] }}"
                style="height: {{ $nextPage ? '48px' : '1px' }}; display: {{ $nextPage ? 'flex' : 'none' }}; align-items: center; justify-content: center; color: #64748b; font-size: 12px; background: transparent;">
                <span class="material-chunk-loading-indicator" aria-live="polite" aria-label="Memuat data berikutnya">
                    <span class="material-chunk-loading-loop" aria-hidden="true"></span>
                </span>
            </div>
        </div>
        <form id="{{ $inlineFormId }}" data-inline-form method="POST" action="{{ route($material['type'] . 's.store') }}" enctype="multipart/form-data" style="display: none;">
            @csrf
            <input type="hidden" name="_method" value="PUT" data-inline-method>
            <input type="hidden" name="_redirect_url" value="{{ request()->fullUrl() }}">
            <input type="hidden" name="_redirect_to_materials" value="1">
        </form>
    </div>

        <div class="material-footer-sticky">

            @if(!(isset($isStoreLocation) && $isStoreLocation))
            <!-- Left Area: Pagination & Kanggo Logo (Only for materials/index) -->
            <div class="material-footer-left">
            <!-- Hexagon Stats (moved to the left of Kanggo logo) 
                <div class="material-footer-right" style="justify-content: flex-start; gap: 4px;">
                    
                    <div class="material-footer-hex-block" style="display: flex; flex-direction: column; align-items: center; justify-content: flex-start;"
                        title="Total {{ $material['label'] }}">

                        <div class="material-footer-hex" style="position: relative; display: flex; align-items: center; justify-content: center;">
                            <img src="{{ asset('assets/hex4.png') }}"
                                alt="Hexagon {{ $material['label'] }}"
                                style="width: 50px; height: 50px;">

                            <div class="material-footer-hex-inner" style="position: absolute; display: flex; align-items: center; justify-content: center; width: 64px;">
                                <span class="material-footer-count" style="line-height: 1; transform: translateY(-0.6px);">
                                    @format($material['db_count'])
                                </span>
                            </div>
                        </div>

                        <span class="material-footer-label">
                            {{ $material['label'] }}
                        </span>
                    </div>

                    <div class="material-footer-hex-block" style="display: flex; flex-direction: column; align-items: center; justify-content: flex-start;"
                        title="Total Semua Material">

                        <div class="material-footer-hex" style="position: relative; display: flex; align-items: center; justify-content: center;">
                            <img src="{{ asset('assets/hex4.png') }}"
                                alt="Hexagon Total"
                                style="width: 50px; height: 50px;">

                            <div class="material-footer-hex-inner" style="position: absolute; display: flex; align-items: center; justify-content: center; width: 64px;">
                                <span class="material-footer-count" style="line-height: 1; transform: translateY(-0.6px);">
                                    @format($grandTotal)
                                </span>
                            </div>
                        </div>

                        <span class="material-footer-label">
                            Total
                        </span>
                    </div>

                    @php
                        $grandTotalPadded = sprintf('%05d', $grandTotal);
                        $grandTotalDigits = strlen($grandTotalPadded);
                        $grandTotalFontSize = match (true) {
                            $grandTotalDigits >= 9 => 10,
                            $grandTotalDigits >= 8 => 11,
                            $grandTotalDigits >= 7 => 12,
                            $grandTotalDigits >= 6 => 13,
                            $grandTotalDigits >= 5 => 14,
                            default => 16,
                        };
                    @endphp

                    <div class="material-footer-hex-block" style="display: flex; flex-direction: column; align-items: center; justify-content: flex-start;"
                        title="Total">
                        <div class="material-footer-hex"
                            style="
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            min-height: 50px;
                            height: 50px;
                            min-width: 50px;
                            width: auto !important;
                            padding: 0 16px;
                            position: relative;
                            ">
                            <img src="{{ asset('assets/hex5.png') }}"
                                style="
                                position: absolute;
                                inset: 0;
                                width: 100% !important;
                                height: 100% !important;
                                object-fit: fill;
                                z-index: 1;
                                ">
                            <span class="material-footer-count"
                                style="
                                    position: relative;
                                    z-index: 2;
                                    white-space: nowrap;
                                    line-height: 1;
                                    font-weight: bold;
                                ">
                                {{ $grandTotalPadded }}
                            </span>
                        </div>
                        <span class="material-footer-label">
                            Total
                        </span>
                    </div>

                    <div class="material-footer-hex-block" style="display: flex; flex-direction: column; align-items: center; justify-content: flex-start;"
                        title="Total">
                        <div class="material-footer-hex"
                            style="
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            width: 50px !important;
                            height: 50px !important;
                            box-sizing: border-box;
                            position: relative;
                            ">
                            <img src="{{ asset('assets/hex5.png') }}"
                                style="
                                position: absolute;
                                inset: 0;
                                width: 100% !important;
                                height: 100% !important;
                                object-fit: fill;
                                z-index: 1;
                                ">
                            <span class="material-footer-count"
                                style="
                                    position: relative;
                                    z-index: 2;
                                    white-space: nowrap;
                                    line-height: 1;
                                    font-weight: bold;
                                    font-size: {{ $grandTotalFontSize }}px !important;
                                ">
                                {{ $grandTotalPadded }}
                            </span>
                        </div>
                        <span class="material-footer-label">
                            Total
                        </span>
                    </div>
                </div>
            -->

                <!-- Kanggo A-Z Pagination (Logo & Letters) -->
                @if(!request('search'))
                <div class="kanggo-container" style="padding-top: 0;">
                    <div class="kanggo-logo">
                        <img src="{{ asset('/Pagination/kangg.png') }}" alt="Kanggo" style="height: 36px; width: auto;">
                    </div>
                    <div class="kanggo-letters" style="justify-content: center; margin-top: 3px; height: 80px;">
                        @php
                            $activeLetters = $material['active_letters'];
                            if ($activeLetters instanceof \Illuminate\Support\Collection) {
                                $activeLetters = $activeLetters->toArray();
                            }
                        @endphp

                        @foreach(range('A', 'Z') as $index => $char)
                            @php
                                $isActive = in_array($char, $activeLetters);
                                $imgIndex = $index + 1;
                            @endphp

                            @if($isActive)
                                <a href="#{{ $material['type'] }}-letter-{{ $char }}" class="kanggo-img-link">
                                    <img src="/Pagination/{{ $imgIndex }}.png" alt="{{ $char }}" class="kanggo-img">
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            
            <!-- Right Area: Recycle Bin Button (Only for materials/index) -->
            @if(!(isset($isStoreLocation) && $isStoreLocation))
            <div class="material-footer-right" style="justify-content: flex-end;">
                @if(auth()->check() && app(\App\Support\Auth\SupplyPermissionGate::class)->allows(auth()->user(), 'materials.recycle-bin.view'))
                <a href="{{ route('materials.recycle-bin') }}" class="inline-flex items-center px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded-lg transition" title="Lihat recycle bin material">
                    <i class="bi bi-trash"></i>
                </a>
                @endif
            </div>
            @endif
            @endif

            <!-- Hexagon Stats or Navigation -->
            @if(isset($isStoreLocation) && $isStoreLocation && isset($allMaterials))
                <!-- HEXAGON NAVIGATION FOR STORE LOCATION (Left Aligned) -->
                <div class="material-footer-right" style="width: 100%; justify-content: flex-start; margin-top: 8px;">
                    <!-- Total Hexagon (First - leftmost) -->
                    <div class="material-nav-hex-block material-footer-hex-block" data-tab="total" style="display: flex; flex-direction: column; align-items: center; cursor: pointer; transition: all 0.2s ease;"
                        title="Total Semua Material">
                        <div class="material-footer-hex" style="position: relative; display: flex; align-items: center; justify-content: center;">
                            <img src="{{ asset('assets/hex2.png') }}" alt="Total" style="width: 50px; height: 50px;">
                            <div class="material-footer-hex-inner" style="position: absolute; display: flex; align-items: center; justify-content: center; width: 64px;">
                                <span class="material-footer-count" style="line-height: 1; transform: translateY(-0.6px);">
                                    @format($grandTotal)
                                </span>
                            </div>
                        </div>
                        <span class="material-footer-label">Total</span>
                    </div>

                    <!-- Material Type Hexagons -->
                    @foreach($allMaterials as $mat)
                    <div class="material-nav-hex-block material-footer-hex-block" data-tab="{{ $mat['type'] }}" style="display: flex; flex-direction: column; align-items: center; cursor: pointer; transition: all 0.2s ease;"
                        title="Material {{ $mat['label'] }}">
                        <div class="material-footer-hex" style="position: relative; display: flex; align-items: center; justify-content: center;">
                            <img src="{{ asset('assets/hex3.png') }}" alt="{{ $mat['label'] }}" style="width: 50px; height: 50px;">
                            <div class="material-footer-hex-inner" style="position: absolute; display: flex; align-items: center; justify-content: center; width: 64px;">
                                <span class="material-footer-count" style="line-height: 1; transform: translateY(-0.6px);">
                                    @format($mat['count'])
                                </span>
                            </div>
                        </div>
                        <span class="material-footer-label">{{ $mat['label'] }}</span>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
@endif
