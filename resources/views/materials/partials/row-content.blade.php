@php
    $showStoreInfo = $showStoreInfo ?? true;
    $actionMode = $actionMode ?? 'default';
    $isRecycleBinMode = $actionMode === 'recycle-bin';
    $rowMaterialType = $rowMaterialType ?? ($item->row_material_type ?? (($item->material_kind ?? null) === 'nat' ? 'nat' : $material['type']));
    $hasMapWarning = (bool) ($item->has_missing_map_coordinates ?? false);
    $mapWarningLabel = trim((string) ($item->map_warning_label ?? 'WAJIB SET MAP'));
    $mapWarningReason = trim((string) ($item->map_warning_reason ?? 'Koordinat Google Maps toko ini belum diisi.'));
    $mapWarningActionUrl = trim((string) ($item->map_warning_action_url ?? ''));
    $mapWarningActionMode = trim((string) ($item->map_warning_action_mode ?? ''));
@endphp
@if($material['type'] == 'brick')
    @php
        $brickPackageType = strtolower((string) ($item->package_type ?? '')) === 'kubik' ? 'kubik' : 'eceran';
        $brickPurchasePrice = $brickPackageType === 'kubik'
            ? ($item->comparison_price_per_m3 ?? $item->price_per_piece)
            : ($item->price_per_piece ?? $item->comparison_price_per_m3);
        $brickPurchaseUnit = $brickPackageType === 'kubik' ? '/ M3' : '/ Bh';
        $brickPackageCount = null;
        if ($brickPackageType === 'eceran') {
            $brickPackageCount = 1;
        } elseif (!is_null($item->package_volume) && (float) $item->package_volume > 0) {
            $brickPackageCount = (int) floor(1 / (float) $item->package_volume);
        }
        $brickPackageLabel = $brickPackageType === 'kubik' ? 'Kubik' : 'Eceran';
        $brickPackageUnitLabel = $brickPackageType === 'kubik' ? 'Bh )' : 'Bh )';
    @endphp
    @if($isRecycleBinMode)
        <td class="{{ $stickyClass }} recycle-select-cell-td" style="text-align: center; width: 34px; min-width: 34px;">
            <span class="recycle-select-cell">
                <input type="checkbox"
                    id="recycle-select-{{ $material['type'] }}-{{ $item->id }}"
                    class="recycle-bulk-checkbox recycle-row-checkbox"
                    value="{{ $item->id }}"
                    data-material-id="{{ $item->id }}"
                    data-material-type="{{ $rowMaterialType }}">
            </span>
        </td>
    @endif
    <td class="{{ $stickyClass }} recycle-no-cell-td" @if($rowAnchorId) id="{{ $rowAnchorId }}" @endif style="text-align: center; width: 40px; min-width: 40px;">
        {{ $rowNumber }}
    </td>
    <td class="brick-sticky-col col-type" style="text-align: left;">{{ $item->type ?? '-' }}</td>
    <td class="material-brand-cell brick-sticky-col col-brand brick-sticky-edge" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
    <td style="text-align: center;">{{ $item->form ?? '-' }}</td>
    <td class="dim-cell border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
        @if(!is_null($item->dimension_length))
            @format($item->dimension_length)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="dim-cell border-left-none border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
        @if(!is_null($item->dimension_width))
            @format($item->dimension_width)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="dim-cell border-left-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
        @if(!is_null($item->dimension_height))
            @format($item->dimension_height)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-right-none brick-scroll-td" style="text-align: right; width: 80px; min-width: 80px; font-size: 12px;">
        @if(!is_null($item->package_volume))
            <div class="brick-scroll-cell" style="max-width: 80px; width: 100%; white-space: nowrap;">
                {{ \App\Helpers\NumberHelper::formatPlain($item->package_volume) }}
            </div>
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">M3</td>
    <td class="border-right-none" style="text-align: left;">{{ $brickPackageLabel }}</td>
    <td class="border-left-none border-right-none" style="text-align: right;">
        @if(!is_null($brickPackageCount))
            ( {{ \App\Helpers\NumberHelper::formatPlain($brickPackageCount) }}
        @else
            <span>( -</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left;">{{ $brickPackageUnitLabel }}</td>
    @if($showStoreInfo)
    <td class="brick-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
        <div class="material-map-cell">
            <div class="brick-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
        </div>
    </td>
    <td class="brick-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
        <div class="material-map-cell">
            <div class="brick-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
            @if($hasMapWarning)
                <div class="material-map-warning-note">
                    <span>
                        {{ $mapWarningReason }}
                        @if($mapWarningActionUrl !== '')
                            <a href="{{ $mapWarningActionUrl }}"
                                class="material-map-warning-link {{ $mapWarningActionMode === 'modal' ? 'global-open-modal' : '' }}">
                                Klik di sini
                            </a>
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </td>
    @endif
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 60px; min-width: 60px;">
        @if($brickPurchasePrice)
            @price($brickPurchasePrice)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 60px; min-width: 60px;">{{ $brickPurchaseUnit }}</td>
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
        @if($item->comparison_price_per_m3)
            @price($item->comparison_price_per_m3)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ M3</td>

@elseif($material['type'] == 'cat')
    @if($isRecycleBinMode)
        <td class="{{ $stickyClass }} recycle-select-cell-td" style="text-align: center; width: 34px; min-width: 34px;">
            <span class="recycle-select-cell">
                <input type="checkbox"
                    id="recycle-select-{{ $material['type'] }}-{{ $item->id }}"
                    class="recycle-bulk-checkbox recycle-row-checkbox"
                    value="{{ $item->id }}"
                    data-material-id="{{ $item->id }}"
                    data-material-type="{{ $rowMaterialType }}">
            </span>
        </td>
    @endif
    <td class="{{ $stickyClass }} recycle-no-cell-td" @if($rowAnchorId) id="{{ $rowAnchorId }}" @endif style="text-align: center; width: 40px; min-width: 40px;">
        {{ $rowNumber }}
    </td>
    <td class="cat-sticky-col col-type" style="text-align: left;">{{ $item->type ?? '-' }}</td>
    <td class="material-brand-cell cat-sticky-col col-brand cat-sticky-edge" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
    <td style="text-align: start;">{{ $item->sub_brand ?? '-' }}</td>
    <td style="text-align: right; font-size: 12px;">{{ $item->color_code ?? '-' }}</td>
    <td style="text-align: left;">{{ $item->color_name ?? '-' }}</td>
    <td class="border-right-none" style="text-align: right; width: 50px; min-width: 50px;">
        @if($item->package_unit)
            {{ $item->packageUnit?->name ?? $item->package_unit }}
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 50px; min-width: 50px;">
        @if($item->package_weight_gross)
            (  @format($item->package_weight_gross )
        @else
            <span>(  -</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 50px; min-width: 50px;">Kg  )</td>
    <td class="border-right-none cat-scroll-td" style="text-align: right; width: 60px; min-width: 60px;">
        @if($item->volume)
            <div class="cat-scroll-cell" style="max-width: 60px; width: 100%; white-space: nowrap;">@format($item->volume)</div>
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 30px; min-width: 30px;">L</td>
    <td class="border-right-none" style="text-align: right; width: 60px; min-width: 60px;">
        @if($item->package_weight_net && $item->package_weight_net > 0)
            @format($item->package_weight_net)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 30px; min-width: 30px;">Kg</td>
    @if($showStoreInfo)
    <td class="cat-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
        <div class="material-map-cell">
            <div class="cat-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
        </div>
    </td>
    <td class="cat-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
        <div class="material-map-cell">
            <div class="cat-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
            @if($hasMapWarning)
                <div class="material-map-warning-note">
                    <span>
                        {{ $mapWarningReason }}
                        @if($mapWarningActionUrl !== '')
                            <a href="{{ $mapWarningActionUrl }}"
                                class="material-map-warning-link {{ $mapWarningActionMode === 'modal' ? 'global-open-modal' : '' }}">
                                Klik di sini
                            </a>
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </td>
    @endif
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
        @if($item->purchase_price)
            @price($item->purchase_price)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 80px; min-width: 80px;">/ {{ $item->packageUnit?->code ?? $item->package_unit ?? '-' }}</td>
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
        @if($item->comparison_price_per_kg)
            @price($item->comparison_price_per_kg)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ Kg</td>

@elseif(in_array($material['type'], ['cement', 'nat']))
    @php
        $rowMaterialType = $item->row_material_type ?? (($item->material_kind ?? null) === 'nat' ? 'nat' : $material['type']);
    @endphp
    @if($isRecycleBinMode)
        <td class="{{ $stickyClass }} recycle-select-cell-td" style="text-align: center; width: 34px; min-width: 34px;">
            <span class="recycle-select-cell">
                <input type="checkbox"
                    id="recycle-select-{{ $material['type'] }}-{{ $item->id }}"
                    class="recycle-bulk-checkbox recycle-row-checkbox"
                    value="{{ $item->id }}"
                    data-material-id="{{ $item->id }}"
                    data-material-type="{{ $rowMaterialType }}">
            </span>
        </td>
    @endif
    <td class="{{ $stickyClass }} recycle-no-cell-td" @if($rowAnchorId) id="{{ $rowAnchorId }}" @endif style="text-align: center; width: 40px; min-width: 40px;">
        {{ $rowNumber }}
    </td>
    <td class="cement-sticky-col" style="text-align: left;">
        {{ $item->type ?? $item->nat_name ?? '-' }}
    </td>
    <td class="material-brand-cell cement-sticky-col cement-sticky-edge" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
    <td style="text-align: left;">{{ $item->sub_brand ?? '-' }}</td>
    <td style="text-align: right; font-size: 12px;">{{ $item->code ?? '-' }}</td>
    <td style="text-align: left;">{{ $item->color ?? '-' }}</td>
    <td style="text-align: center; font-size: 13px;">
        @if($item->package_unit)
            {{ $item->packageUnit?->name ?? $item->package_unit }}
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px; font-size: 12px;">
        @if($item->package_weight_net && $item->package_weight_net > 0)
            @format($item->package_weight_net)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">Kg</td>
    @if($showStoreInfo)
    <td class="cement-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
        <div class="material-map-cell">
            <div class="cement-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
        </div>
    </td>
    <td class="cement-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
        <div class="material-map-cell">
            <div class="cement-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
            @if($hasMapWarning)
                <div class="material-map-warning-note">
                    <span>
                        {{ $mapWarningReason }}
                        @if($mapWarningActionUrl !== '')
                            <a href="{{ $mapWarningActionUrl }}"
                                class="material-map-warning-link {{ $mapWarningActionMode === 'modal' ? 'global-open-modal' : '' }}">
                                Klik di sini
                            </a>
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </td>
    @endif
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
        @if($item->package_price)
            @price($item->package_price)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ {{ $item->packageUnit?->code ?? $item->package_unit ?? '-' }}</td>
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
        @if($item->comparison_price_per_kg)
            @price($item->comparison_price_per_kg)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ Kg</td>

@elseif($material['type'] == 'sand')
    @if($isRecycleBinMode)
        <td class="{{ $stickyClass }} recycle-select-cell-td" style="text-align: center; width: 34px; min-width: 34px;">
            <span class="recycle-select-cell">
                <input type="checkbox"
                    id="recycle-select-{{ $material['type'] }}-{{ $item->id }}"
                    class="recycle-bulk-checkbox recycle-row-checkbox"
                    value="{{ $item->id }}"
                    data-material-id="{{ $item->id }}"
                    data-material-type="{{ $rowMaterialType }}">
            </span>
        </td>
    @endif
    <td class="{{ $stickyClass }} recycle-no-cell-td" @if($rowAnchorId) id="{{ $rowAnchorId }}" @endif style="text-align: center; width: 40px; min-width: 40px;">
        {{ $rowNumber }}
    </td>
    <td style="text-align: left;">{{ $item->type ?? '-' }}</td>
    <td class="material-brand-cell" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
    <td style="text-align: center; font-size: 13px;">
        @if($item->package_unit)
            {{ $item->packageUnit?->name ?? $item->package_unit }}
        @else
            <span>-</span>
        @endif
    </td>
    <td class="dim-cell border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
        @if(!is_null($item->dimension_length))
            @format($item->dimension_length)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="dim-cell border-left-none border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
        @if(!is_null($item->dimension_width))
            @format($item->dimension_width)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="dim-cell border-left-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
        @if(!is_null($item->dimension_height))
            @format($item->dimension_height)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-right-none sand-scroll-td" style="text-align: right; width: 60px; min-width: 60px; font-size: 12px;">
        @if($item->package_volume)
            <div class="sand-scroll-cell" style="max-width: 60px; width: 100%; white-space: nowrap;">
                {{ \App\Helpers\NumberHelper::formatPlain($item->package_volume) }}
            </div>
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 30px; min-width: 30px;">M3</td>
    @if($showStoreInfo)
    <td class="sand-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
        <div class="material-map-cell">
            <div class="sand-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
        </div>
    </td>
    <td class="sand-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
        <div class="material-map-cell">
            <div class="sand-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
            @if($hasMapWarning)
                <div class="material-map-warning-note">
                    <span>
                        {{ $mapWarningReason }}
                        @if($mapWarningActionUrl !== '')
                            <a href="{{ $mapWarningActionUrl }}"
                                class="material-map-warning-link {{ $mapWarningActionMode === 'modal' ? 'global-open-modal' : '' }}">
                                Klik di sini
                            </a>
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </td>
    @endif
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
        @if($item->package_price)
            @price($item->package_price)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 80px; min-width: 80px;">/ {{ $item->packageUnit?->code ?? $item->package_unit ?? '-' }}</td>
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
        @if($item->comparison_price_per_m3)
            @price($item->comparison_price_per_m3)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ M3</td>

@elseif($material['type'] == 'steel')
    @if($isRecycleBinMode)
        <td class="{{ $stickyClass }} recycle-select-cell-td" style="text-align: center; width: 34px; min-width: 34px;">
            <span class="recycle-select-cell">
                <input type="checkbox"
                    id="recycle-select-{{ $material['type'] }}-{{ $item->id }}"
                    class="recycle-bulk-checkbox recycle-row-checkbox"
                    value="{{ $item->id }}"
                    data-material-id="{{ $item->id }}"
                    data-material-type="{{ $rowMaterialType }}">
            </span>
        </td>
    @endif
    <td class="{{ $stickyClass }} recycle-no-cell-td" @if($rowAnchorId) id="{{ $rowAnchorId }}" @endif style="text-align: center; width: 40px; min-width: 40px;">
        {{ $rowNumber }}
    </td>
    <td class="steel-sticky-col col-type" style="text-align: left;">{{ $item->type ?? '-' }}</td>
    <td class="material-brand-cell steel-sticky-col col-brand steel-sticky-edge" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
    <td style="text-align: center;">{{ $item->quality ?? '-' }}</td>
    <td style="text-align: center;">{{ $item->term ?? '-' }}</td>
    <td style="text-align: center; font-size: 13px;">
        @if($item->package_unit)
            {{ $item->packageUnit?->name ?? $item->package_unit }}
        @else
            <span>-</span>
        @endif
    </td>
    <td class="dim-cell border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
        @if(!is_null($item->dimension_length))
            @format($item->dimension_length)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; font-size: 12px; width: 30px; min-width: 30px;">M</td>
    <td class="dim-cell border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
        @if(!is_null($item->dimension_width))
            @format($item->dimension_width)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; font-size: 12px; width: 32px; min-width: 32px;">mm</td>
    <td class="dim-cell border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
        @if(!is_null($item->dimension_height))
            @format($item->dimension_height)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; font-size: 12px; width: 32px; min-width: 32px;">mm</td>
    <td class="dim-cell border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
        @if(!is_null($item->dimension_thickness))
            @format($item->dimension_thickness)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; font-size: 12px; width: 32px; min-width: 32px;">mm</td>
    <td class="border-right-none volume-cell sand-scroll-td" style="text-align: right; width: 80px; min-width: 80px; font-size: 12px;">
        @if($item->package_volume)
            <div class="sand-scroll-cell" style="max-width: 80px; width: 100%; white-space: nowrap;">
                {{ \App\Helpers\NumberHelper::formatPlain($item->package_volume) }}
            </div>
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">M3</td>
    @if($showStoreInfo)
    <td class="sand-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
        <div class="material-map-cell">
            <div class="sand-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
        </div>
    </td>
    <td class="sand-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
        <div class="material-map-cell">
            <div class="sand-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
            @if($hasMapWarning)
                <div class="material-map-warning-note">
                    <span>
                        {{ $mapWarningReason }}
                        @if($mapWarningActionUrl !== '')
                            <a href="{{ $mapWarningActionUrl }}"
                                class="material-map-warning-link {{ $mapWarningActionMode === 'modal' ? 'global-open-modal' : '' }}">
                                Klik di sini
                            </a>
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </td>
    @endif
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
        @if($item->package_price)
            @price($item->package_price)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; min-width: 40px;">/ {{ $item->packageUnit?->code ?? $item->package_unit ?? '-' }}</td>
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
        @if($item->comparison_price_per_m3)
            @price($item->comparison_price_per_m3)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ M3</td>

@elseif($material['type'] == 'kasa_gypsum')
    @if($isRecycleBinMode)
        <td class="{{ $stickyClass }} recycle-select-cell-td" style="text-align: center; width: 34px; min-width: 34px;">
            <span class="recycle-select-cell">
                <input type="checkbox"
                    id="recycle-select-{{ $material['type'] }}-{{ $item->id }}"
                    class="recycle-bulk-checkbox recycle-row-checkbox"
                    value="{{ $item->id }}"
                    data-material-id="{{ $item->id }}"
                    data-material-type="{{ $rowMaterialType }}">
            </span>
        </td>
    @endif
    <td class="{{ $stickyClass }} recycle-no-cell-td" @if($rowAnchorId) id="{{ $rowAnchorId }}" @endif style="text-align: center; width: 40px; min-width: 40px;">
        {{ $rowNumber }}
    </td>
    <td style="text-align: left;">{{ $item->type ?? '-' }}</td>
    <td class="material-brand-cell" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
    <td style="text-align: center; font-size: 13px;">
        @if($item->package_unit)
            {{ $item->packageUnit?->name ?? $item->package_unit }}
        @else
            <span>-</span>
        @endif
    </td>
    <td class="dim-cell border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
        @if(!is_null($item->dimension_width))
            @format($item->dimension_width)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; font-size: 12px; width: 30px; min-width: 30px; padding: 0 2px;">cm</td>
    <td class="dim-cell border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
        @if(!is_null($item->dimension_length))
            @format($item->dimension_length)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; font-size: 12px; width: 24px; min-width: 24px; padding: 0 2px;">M</td>
    @if($showStoreInfo)
    <td class="sand-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
        <div class="material-map-cell">
            <div class="sand-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
        </div>
    </td>
    <td class="sand-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
        <div class="material-map-cell">
            <div class="sand-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
            @if($hasMapWarning)
                <div class="material-map-warning-note">
                    <span>
                        {{ $mapWarningReason }}
                        @if($mapWarningActionUrl !== '')
                            <a href="{{ $mapWarningActionUrl }}"
                                class="material-map-warning-link {{ $mapWarningActionMode === 'modal' ? 'global-open-modal' : '' }}">
                                Klik di sini
                            </a>
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </td>
    @endif
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
        @if($item->package_price)
            @price($item->package_price)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 80px; min-width: 80px;">/ {{ $item->packageUnit?->code ?? $item->package_unit ?? '-' }}</td>
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
        @if($item->comparison_price_per_m)
            @price($item->comparison_price_per_m)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ M</td>

@elseif($material['type'] == 'paku_tembak')
    @if($isRecycleBinMode)
        <td class="{{ $stickyClass }} recycle-select-cell-td" style="text-align: center; width: 34px; min-width: 34px;">
            <span class="recycle-select-cell">
                <input type="checkbox"
                    id="recycle-select-{{ $material['type'] }}-{{ $item->id }}"
                    class="recycle-bulk-checkbox recycle-row-checkbox"
                    value="{{ $item->id }}"
                    data-material-id="{{ $item->id }}"
                    data-material-type="{{ $rowMaterialType }}">
            </span>
        </td>
    @endif
    <td class="{{ $stickyClass }} recycle-no-cell-td" @if($rowAnchorId) id="{{ $rowAnchorId }}" @endif style="text-align: center; width: 40px; min-width: 40px;">
        {{ $rowNumber }}
    </td>
    <td class="paku-tembak-sticky-col col-type" style="text-align: left;">{{ $item->type ?? '-' }}</td>
    <td class="material-brand-cell paku-tembak-sticky-col col-brand paku-tembak-sticky-edge" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
    <td style="text-align: center; font-size: 13px;">
        @if($item->package_unit)
            {{ $item->packageUnit?->name ?? $item->package_unit }}
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-right-none" style="text-align: center; min-width: 40px;">
        {{ $item->mesiu_code ?? '-' }}
    </td>
    <td class="border-left-none border-right-none" style="text-align: right; min-width: 40px;">
        {{ $item->mesiu_size ?? '-' }}
    </td>
    <td class="border-left-none border-right-none" style="text-align: left; width: 30px; min-width: 30px;">cm</td>
    <td class="border-left-none border-right-none" style="text-align: right; min-width: 40px;">
        {{ !is_null($item->mesiu_content) ? \App\Helpers\NumberHelper::formatPlain((float) $item->mesiu_content) : '-' }}
    </td>
    <td class="border-left-none" style="text-align: left; width: 30px; min-width: 30px;">Pcs</td>
    <td class="border-right-none" style="text-align: center; min-width: 40px;">
        {{ $item->paku_code ?? '-' }}
    </td>
    <td class="border-left-none border-right-none" style="text-align: right; min-width: 40px;">
        {{ $item->paku_size ?? '-' }}
    </td>
    <td class="border-left-none border-right-none" style="text-align: left; width: 30px; min-width: 30px;">cm</td>
    <td class="border-left-none border-right-none" style="text-align: right; min-width: 40px;">
        {{ !is_null($item->paku_content) ? \App\Helpers\NumberHelper::formatPlain((float) $item->paku_content) : '-' }}
    </td>
    <td class="border-left-none" style="text-align: left; width: 30px; min-width: 30px;">Pcs</td>
    @if($showStoreInfo)
    <td class="sand-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
        <div class="material-map-cell">
            <div class="sand-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">
                {{ $item->store ?? '-' }}
            </div>
        </div>
    </td>
    <td class="sand-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
        <div class="material-map-cell">
            <div class="sand-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">
                {{ $item->address ?? '-' }}
            </div>
            @if($hasMapWarning)
                <div class="material-map-warning-note">
                    <span>
                        {{ $mapWarningReason }}
                        @if($mapWarningActionUrl !== '')
                            <a href="{{ $mapWarningActionUrl }}"
                                class="material-map-warning-link {{ $mapWarningActionMode === 'modal' ? 'global-open-modal' : '' }}">
                                Klik di sini
                            </a>
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </td>
    @endif
    <td class="border-right-none" style="text-align: right; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 60px; min-width: 60px;">
        @if($item->package_price)
            @price($item->package_price)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; min-width: 40px;">/ {{ $item->packageUnit?->code ?? $item->package_unit ?? '-' }}</td>
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 60px; min-width: 60px;">
        @if($item->comparison_price)
            @price($item->comparison_price)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ Pcs</td>

@elseif($material['type'] == 'paku')
    @if($isRecycleBinMode)
        <td class="{{ $stickyClass }} recycle-select-cell-td" style="text-align: center; width: 34px; min-width: 34px;">
            <span class="recycle-select-cell">
                <input type="checkbox"
                    id="recycle-select-{{ $material['type'] }}-{{ $item->id }}"
                    class="recycle-bulk-checkbox recycle-row-checkbox"
                    value="{{ $item->id }}"
                    data-material-id="{{ $item->id }}"
                    data-material-type="{{ $rowMaterialType }}">
            </span>
        </td>
    @endif
    <td class="{{ $stickyClass }} recycle-no-cell-td" @if($rowAnchorId) id="{{ $rowAnchorId }}" @endif style="text-align: center; width: 40px; min-width: 40px;">
        {{ $rowNumber }}
    </td>
    <td class="paku-sticky-col col-type" style="text-align: left;">{{ $item->type ?? '-' }}</td>
    <td class="material-brand-cell paku-sticky-col col-brand" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
    <td class="dim-cell paku-sticky-col border-right-none" style="text-align: right; font-size: 12px; width: 40px; padding: 0 2px;">
        @if(!is_null($item->dimension_length))
            @format($item->dimension_length)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="paku-sticky-col border-left-none border-right-none" style="text-align: left; font-size: 12px; width: 24px; min-width: 24px; padding: 0 2px;">"</td>
    <td class="dim-cell paku-sticky-col border-left-none border-right-none" style="text-align: right; font-size: 12px; width: 50px; padding: 0 !important;">
        @if(!is_null($item->dimension_length_mm))
            ( @format($item->dimension_length_mm)
        @else
            <span>( -</span>
        @endif
    </td>
    <td class="paku-sticky-col border-left-none border-right-none" style="text-align: left; font-size: 12px; width: 30px; min-width: 30px; padding: 0 2px;">) mm</td>
    <td class="dim-cell paku-sticky-col border-left-none border-right-none" style="text-align: right; font-size: 12px; min-width: 60px; padding: 0 2px;">
        @if(!is_null($item->dimension_body_diameter))
            @format($item->dimension_body_diameter)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="paku-sticky-col border-left-none border-right-none" style="text-align: left; font-size: 12px; width: 30px; min-width: 30px; padding: 0 2px;">cm</td>
    <td class="dim-cell paku-sticky-col border-left-none" style="text-align: right; font-size: 12px; min-width: 60px; padding: 0 2px;">
        @if(!is_null($item->dimension_head_diameter))
            @format($item->dimension_head_diameter)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="paku-sticky-col paku-sticky-edge border-left-none border-right-none" style="text-align: left; font-size: 12px; width: 30px; min-width: 30px; padding: 0 2px;">mm</td>
    <td style="text-align: left;">{{ $item->color ?? '-' }}</td>
    <td style="text-align: left; font-size: 13px;">
        @if($item->package_unit)
            {{ $item->packageUnit?->name ?? $item->package_unit }}
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-right-none" style="text-align: right;">{{ !is_null($item->package_weight) ? \App\Helpers\NumberHelper::formatPlain((float) $item->package_weight) : '-' }}</td>
    <td class="border-left-none" style="text-align: left; width: 30px; min-width: 30px;">kg</td>
    <td class="border-right-none" style="text-align: right;">{{ !is_null($item->package_content) ? \App\Helpers\NumberHelper::formatPlain((float) $item->package_content) : '-' }}</td>
    <td class="border-left-none" style="text-align: left; width: 36px; min-width: 36px;">Pcs</td>
    @if($showStoreInfo)
    <td class="sand-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
        <div class="material-map-cell">
            <div class="sand-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
        </div>
    </td>
    <td class="sand-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
        <div class="material-map-cell">
            <div class="sand-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
            @if($hasMapWarning)
                <div class="material-map-warning-note">
                    <span>
                        {{ $mapWarningReason }}
                        @if($mapWarningActionUrl !== '')
                            <a href="{{ $mapWarningActionUrl }}"
                                class="material-map-warning-link {{ $mapWarningActionMode === 'modal' ? 'global-open-modal' : '' }}">
                                Klik di sini
                            </a>
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </td>
    @endif
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 60px; min-width: 60px;">
        @if($item->package_price)
            @price($item->package_price)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ {{ $item->packageUnit?->code ?? $item->package_unit ?? '-' }}</td>
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 60px; min-width: 60px;">
        @if($item->comparison_price)
            @price($item->comparison_price)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ Pcs</td>

@elseif($material['type'] == 'ceramic')
    @if($isRecycleBinMode)
        <td class="{{ $stickyClass }} recycle-select-cell-td" style="text-align: center; width: 34px; min-width: 34px;">
            <span class="recycle-select-cell">
                <input type="checkbox"
                    id="recycle-select-{{ $material['type'] }}-{{ $item->id }}"
                    class="recycle-bulk-checkbox recycle-row-checkbox"
                    value="{{ $item->id }}"
                    data-material-id="{{ $item->id }}"
                    data-material-type="{{ $rowMaterialType }}">
            </span>
        </td>
    @endif
    <td class="{{ $stickyClass }} recycle-no-cell-td" @if($rowAnchorId) id="{{ $rowAnchorId }}" @endif style="text-align: center;">
        {{ $rowNumber }}
    </td>
    <td class="ceramic-sticky-col col-type" style="text-align: left;">{{ $item->type ?? '-' }}</td>
    <td class="dim-cell ceramic-sticky-col col-dim-p border-right-none" style="text-align: center; font-size: 12px; padding: 0 2px;">
        @if(!is_null($item->dimension_length))
            @format($item->dimension_length)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="dim-cell ceramic-sticky-col col-dim-l border-left-none border-right-none" style="text-align: center; font-size: 12px; padding: 0 2px;">
        @if(!is_null($item->dimension_width))
            @format($item->dimension_width)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="dim-cell ceramic-sticky-col col-dim-t border-left-none" style="text-align: center; font-size: 12px; padding: 0 2px;">
        @if(!is_null($item->dimension_thickness))
            @format($item->dimension_thickness)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="material-brand-cell ceramic-sticky-col col-brand ceramic-sticky-edge" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
    <td style="text-align: left;">{{ $item->sub_brand ?? '-' }}</td>
    <td style="text-align: left;">{{ $item->surface ?? '-' }}</td>
    <td style="text-align: right; font-size: 12px;">{{ $item->code ?? '-' }}</td>
    <td style="text-align: left;">{{ $item->color ?? '-' }}</td>
    <td style="text-align: center;">{{ $item->form ?? '-' }}</td>
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px; font-size: 13px;">{{ $item->packaging ?? '-' }}</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 40px; min-width: 40px; font-size: 13px;">
        @if($item->pieces_per_package)
            (  @format($item->pieces_per_package)
        @else
            <span>(  -</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">Lbr  )</td>
    <td class="border-right-none" style="text-align: right; width: 60px; min-width: 60px; font-size: 12px;">
        @if($item->coverage_per_package)
            @format($item->coverage_per_package)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 30px; min-width: 30px;">M2</td>
    @if($showStoreInfo)
    <td class="ceramic-scroll-td" style="text-align: left; width: 150px; min-width: 150px; max-width: 150px;">
        <div class="material-map-cell">
            <div class="ceramic-scroll-cell" style="max-width: 150px; width: 100%; white-space: nowrap;">{{ $item->store ?? '-' }}</div>
        </div>
    </td>
    <td class="ceramic-scroll-td" style="text-align: left; width: 200px; min-width: 200px; max-width: 200px;">
        <div class="material-map-cell">
            <div class="ceramic-scroll-cell" style="max-width: 200px; width: 100%; white-space: nowrap;">{{ $item->address ?? '-' }}</div>
            @if($hasMapWarning)
                <div class="material-map-warning-note">
                    <span>
                        {{ $mapWarningReason }}
                        @if($mapWarningActionUrl !== '')
                            <a href="{{ $mapWarningActionUrl }}"
                                class="material-map-warning-link {{ $mapWarningActionMode === 'modal' ? 'global-open-modal' : '' }}">
                                Klik di sini
                            </a>
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </td>
    @endif
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
        @if($item->price_per_package)
            @price($item->price_per_package)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ {{ $item->packaging ?? '-' }}</td>
    <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
    <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
        @if($item->comparison_price_per_m2)
            @price($item->comparison_price_per_m2)
        @else
            <span>-</span>
        @endif
    </td>
    <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ M2</td>
@endif
