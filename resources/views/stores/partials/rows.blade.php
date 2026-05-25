@php
    $pagination = $pagination ?? [
        'current_page' => 1,
        'per_page' => max(1, count($stores ?? [])),
    ];
    $rowNumber = (max(1, (int) ($pagination['current_page'] ?? 1)) - 1) * max(1, (int) ($pagination['per_page'] ?? 1)) + 1;
@endphp

@forelse($stores as $store)
    @php
        $mainLoc = $store->primary_location;
    @endphp
    <tr class="store-row"
        data-store-map-name="{{ $store->name }}"
        data-store-map-address="{{ $mainLoc?->resolved_address ?? $mainLoc?->address ?? '' }}"
        data-store-map-city="{{ $mainLoc?->city ?? '' }}"
        data-store-map-province="{{ $mainLoc?->province ?? '' }}"
        data-store-map-lat="{{ is_numeric($mainLoc?->latitude) ? (float) $mainLoc->latitude : '' }}"
        data-store-map-lng="{{ is_numeric($mainLoc?->longitude) ? (float) $mainLoc->longitude : '' }}">
        <td class="store-col-no">{{ $rowNumber }}</td>
        <td class="store-scroll-td store-name-td">
            <span class="store-scroll-cell store-name-cell fw-semibold text-dark" title="{{ $store->name }}">{{ $store->name }}</span>
        </td>
        @if($mainLoc)
            <td class="store-scroll-td store-address-td">
                <div class="store-map-cell">
                    <span class="store-scroll-cell" title="{{ $mainLoc->resolved_address ?? $mainLoc->address ?? '-' }}">{{ $mainLoc->resolved_address ?? $mainLoc->address ?? '-' }}</span>
                    @if($mainLoc->has_missing_map_coordinates ?? false)
                        <div class="store-map-warning-note">Koordinat cabang utama belum diisi.</div>
                    @endif
                </div>
            </td>
            <td class="store-scroll-td store-city-td">
                <span class="store-scroll-cell" title="{{ $mainLoc->city ?? '-' }}">{{ $mainLoc->city ?? '-' }}</span>
            </td>
            <td class="store-scroll-td store-province-td">
                <span class="store-scroll-cell" title="{{ $mainLoc->province ?? '-' }}">{{ $mainLoc->province ?? '-' }}</span>
            </td>
            <td class="store-scroll-td store-phone-td">
                <span class="store-scroll-cell" title="{{ $mainLoc->contact_phone ?? '-' }}">{{ $mainLoc->contact_phone ?? '-' }}</span>
            </td>
            <td class="store-scroll-td store-pic-td">
                <span class="store-scroll-cell" title="{{ $mainLoc->contact_name ?? '-' }}">{{ $mainLoc->contact_name ?? '-' }}</span>
            </td>
        @else
            <td colspan="5" class="store-missing-location-td">
                <div class="store-map-cell">
                    <span class="text-muted fst-italic">Belum ada lokasi</span>
                    <div class="store-map-warning-note">Tambahkan lokasi dan titik Google Maps sekarang.</div>
                </div>
            </td>
        @endif
        <td class="text-center">
            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-2 fw-medium">
                {{ $store->resolved_material_count ?? 0 }}
            </span>
        </td>
        <td class="text-center">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2">
                {{ $store->resolved_branch_count ?? 0 }}
            </span>
        </td>
        <td class="store-col-action text-center action-cell">
            <div class="btn-group-compact">
                <a href="{{ route('stores.show', $store) }}" class="btn btn-primary btn-action" data-bs-toggle="tooltip" title="Detail">
                    <i class="bi bi-eye"></i>
                </a>
                <a href="{{ route('stores.edit', $store) }}" class="btn btn-warning btn-action global-open-modal" data-bs-toggle="tooltip" title="Edit">
                    <i class="bi bi-pencil-square"></i>
                </a>
                <form action="{{ route('stores.destroy', $store) }}" method="POST" class="d-inline"
                    data-confirm="Apakah Anda yakin ingin menghapus toko {{ $store->name }}? Data yang dihapus tidak dapat dikembalikan."
                    data-confirm-title="Hapus Toko"
                    data-confirm-type="danger"
                    data-confirm-ok="Ya, Hapus"
                    data-confirm-cancel="Batal">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-action" data-bs-toggle="tooltip" title="Hapus">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </td>
    </tr>
    @php $rowNumber++; @endphp
@empty
    <tr>
        <td colspan="10" class="text-center py-5">
            <div class="d-flex flex-column align-items-center justify-content-center">
                <div class="bg-light rounded-circle p-3 mb-3">
                    <i class="bi bi-shop fs-3 text-muted"></i>
                </div>
                <h6 class="fw-bold text-dark">Belum Ada Toko</h6>
                <p class="text-muted small mb-3">Tambahkan toko pertama Anda untuk memulai.</p>
                <a href="{{ route('stores.create') }}" class="btn btn-primary px-3">
                    <i class="bi bi-plus-lg me-1"></i>Tambah
                </a>
            </div>
        </td>
    </tr>
@endforelse
