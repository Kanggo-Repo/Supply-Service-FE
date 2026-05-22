@extends('layouts.app')

@section('title', $store->name)
@section('topbar-title', 'Database Toko')
@section('topbar-title-html')
    Database Toko <i class="bi bi-caret-right-fill"></i> {{ $store->name }}
@endsection

@section('content')
<style>
    .store-show-toolbar {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
    }

    .store-show-toolbar-start {
        justify-self: start;
    }

    .store-show-toolbar-center {
        justify-self: center;
        text-align: center;
    }

    .store-show-toolbar-end {
        justify-self: end;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .store-show-action-btn {
        padding: 6px 16px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none;
        line-height: 1.2;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .store-show-action-btn:hover {
        transform: translateY(-1px);
        text-decoration: none;
    }

    .store-show-action-btn.store-show-back-btn {
        border-color: #891313;
        background-color: transparent;
        color: #891313;
    }

    .store-show-action-btn.store-show-back-btn:hover {
        background-color: rgba(137, 19, 19, 0.08);
        color: #6f0f0f;
    }

    .store-show-action-btn.store-show-edit-btn {
        border-color: #d97706;
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: #fff;
        box-shadow: 0 6px 14px rgba(245, 158, 11, 0.22);
    }

    .store-show-action-btn.store-show-edit-btn:hover {
        background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
        color: #fff;
        box-shadow: 0 8px 18px rgba(245, 158, 11, 0.28);
    }

    .store-show-action-btn.store-show-add-btn {
        border-color: #2563eb;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #fff;
        box-shadow: 0 6px 14px rgba(37, 99, 235, 0.22);
    }

    .store-show-action-btn.store-show-add-btn:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.28);
    }

    @media (max-width: 767.98px) {
        .store-show-toolbar {
            grid-template-columns: 1fr;
        }

        .store-show-toolbar-start,
        .store-show-toolbar-center,
        .store-show-toolbar-end {
            justify-self: stretch;
        }

        .store-show-toolbar-center {
            order: -1;
        }

        .store-show-toolbar-end {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>
<div class="page-content">
    <div class="container-fluid py-4">
        <div class="store-show-toolbar">
            <div class="store-show-toolbar-start">
                <a href="{{ route('stores.index') }}" class="btn-cancel" style="background-color: transparent; color: #64748b; padding: 8px 16px; font-size: 14px; font-weight: 600; border-radius: 10px; text-decoration: none; 
                        display: inline-flex; align-items: center; gap: 10px; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="store-show-toolbar-center">
                <h4 class="fw-bold text-dark mb-0">Daftar Cabang &amp; Lokasi</h4>
            </div>
            <div class="store-show-toolbar-end">
                <a href="{{ route('store-locations.create', $store) }}" class="btn btn-success global-open-modal" style="height: 40px;">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Lokasi
                </a>
            </div>
        </div>

        @php
            $hasIncompleteLocation = $store->locations->contains('is_incomplete', true);
        @endphp

        @if($hasIncompleteLocation)
            <div class="alert alert-warning small p-2 mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    Tanda <i class="bi bi-exclamation-triangle-fill text-warning"></i> menunjukkan data lokasi belum lengkap (contoh: kota, provinsi, atau telepon).
                </div>
            </div>
        @endif

        <div class="table-container text-nowrap">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="store-show-col-no">No</th>
                        <th class="store-show-col-address">Alamat</th>
                        <th class="store-show-col-district">Kecamatan</th>
                        <th class="store-show-col-city">Kota</th>
                        <th class="store-show-col-province">Provinsi</th>
                        <th class="store-show-col-contact">Nama Kontak</th>
                        <th class="store-show-col-phone">No. Telepon</th>
                        <th class="store-show-col-material text-center">Material</th>
                        <th class="store-show-col-action text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($store->locations as $location)
                        <tr>
                            <td class="store-show-col-no">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span>{{ $loop->iteration }}</span>
                                    @if($location->is_incomplete)
                                        <i class="bi bi-exclamation-triangle text-warning" data-bs-toggle="tooltip" title="Data lokasi ini belum lengkap"></i>
                                    @endif
                                </div>
                            </td>
                            <td class="store-show-scroll-td store-show-address-td">
                                <span class="store-show-scroll-cell" title="{{ $location->address ?? '-' }}">{{ $location->address ?? '-' }}</span>
                            </td>
                            <td class="store-show-scroll-td store-show-district-td">
                                <span class="store-show-scroll-cell" title="{{ $location->district ?? '-' }}">{{ $location->district ?? '-' }}</span>
                            </td>
                            <td class="store-show-scroll-td store-show-city-td">
                                <span class="store-show-scroll-cell" title="{{ $location->city ?? '-' }}">{{ $location->city ?? '-' }}</span>
                            </td>
                            <td class="store-show-scroll-td store-show-province-td">
                                <span class="store-show-scroll-cell" title="{{ $location->province ?? '-' }}">{{ $location->province ?? '-' }}</span>
                            </td>
                            <td class="store-show-scroll-td store-show-contact-td">
                                <span class="store-show-scroll-cell" title="{{ $location->contact_name ?? '-' }}">{{ $location->contact_name ?? '-' }}</span>
                            </td>
                            <td class="store-show-scroll-td store-show-phone-td">
                                <span class="store-show-scroll-cell" title="{{ $location->contact_phone ?? '-' }}">{{ $location->contact_phone ?? '-' }}</span>
                            </td>
                            <td class="store-show-col-material text-center">
                                <a href="{{ route('store-locations.materials', [$store, $location]) }}" class="text-decoration-none text-primary fw-medium hover-arrow" style="font-size: 13px;">
                                    {{ $location->resolved_material_count ?? 0 }} Material <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                            <td class="store-show-col-action text-center action-cell">
                                <div class="btn-group-compact">
                                    <a href="{{ route('store-locations.edit', [$store, $location]) }}" class="btn btn-warning btn-action global-open-modal" data-bs-toggle="tooltip" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('store-locations.destroy', [$store, $location]) }}" method="POST" class="d-inline"
                                        data-confirm="Apakah Anda yakin ingin menghapus lokasi ini?"
                                        data-confirm-title="Hapus Lokasi"
                                        data-confirm-type="danger"
                                        data-confirm-ok="Ya, Hapus"
                                        data-confirm-cancel="Batal">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-action btn-danger" data-bs-toggle="tooltip" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="text-center py-5">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                        <i class="bi bi-geo-alt display-6 text-muted opacity-50"></i>
                                    </div>
                                    <h4 class="fw-bold text-dark">Belum Ada Lokasi</h4>
                                    <p class="text-muted mb-4 mw-md mx-auto" style="max-width: 450px;">
                                        Toko ini belum memiliki cabang atau lokasi gudang yang terdaftar.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Table Styles from materials/index.blade.php */
    .table-container {
        position: relative;
        overflow-x: hidden;
        border-radius: 12px;
        box-shadow: none;
        border: 1px solid #e2e8f0;
    }

    .table-container table {
        border-collapse: separate !important;
        border-spacing: 0 !important;
        width: 100%;
        table-layout: fixed !important;
    }

    /* Header Styling */
    .table-container thead th {
        position: sticky;
        top: 0;
        height: 40px !important;
        padding: 8px 12px !important;
        background-color: #f8fafc;
        font-weight: 600;
        letter-spacing: 0.05em;
        color: #64748b;
        font-size: 12px;
        border: 1px solid #cbd5e1 !important;
        vertical-align: middle !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        z-index: 30;
    }

    /* Body Styling */
    .table-container tbody td {
        border: 1px solid #f1f5f9 !important;
        vertical-align: middle !important;
        color: #1e293b !important;
        height: 35px !important;
        padding: 4px 12px !important;
        font-size: 12px !important;
        line-height: 1.3 !important;
        overflow: hidden;
    }

    .table-container tbody tr:hover {
        background-color: #fcfcfc;
    }

    .table-container .store-show-col-no {
        width: 46px !important;
        min-width: 46px !important;
        max-width: 46px !important;
        text-align: center !important;
    }

    .table-container .store-show-col-address {
        width: clamp(190px, 25vw, 330px);
    }

    .table-container .store-show-col-district {
        width: clamp(96px, 9vw, 135px);
    }

    .table-container .store-show-col-city {
        width: clamp(92px, 8vw, 125px);
    }

    .table-container .store-show-col-province {
        width: clamp(100px, 9vw, 140px);
    }

    .table-container .store-show-col-contact {
        width: clamp(120px, 11vw, 165px);
    }

    .table-container .store-show-col-phone {
        width: clamp(112px, 10vw, 150px);
    }

    .table-container .store-show-col-material {
        width: 98px;
    }

    .table-container .store-show-col-action {
        width: 76px !important;
        min-width: 76px !important;
        max-width: 76px !important;
        text-align: center !important;
    }

    .table-container thead th.store-show-col-no,
    .table-container tbody td.store-show-col-no {
        position: sticky;
        left: 0;
        z-index: 24;
        background: #ffffff;
    }

    .table-container thead th.store-show-col-no {
        top: 0;
        z-index: 45;
        background: #f8fafc;
    }

    .table-container thead th.store-show-col-action,
    .table-container tbody td.action-cell {
        position: sticky;
        right: 0;
        z-index: 24;
        background: #ffffff;
        box-shadow: -1px 0 0 #f1f5f9;
    }

    .table-container thead th.store-show-col-action {
        top: 0;
        z-index: 45;
        background: #f8fafc;
    }

    .table-container tbody tr:hover td.store-show-col-no,
    .table-container tbody tr:hover td.action-cell {
        background: #fcfcfc;
    }

    .store-show-scroll-td {
        position: relative;
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
        white-space: nowrap;
    }

    .store-show-scroll-td.is-scrollable::after {
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

    .store-show-scroll-td.is-scrolled-end::after {
        opacity: 0;
    }

    .store-show-scroll-cell {
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

    .store-show-scroll-cell::-webkit-scrollbar {
        height: 0;
    }

    /* Button Group Compact */
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
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        color: #0f172a !important;
    }
    .btn-group-compact .btn-action:hover {
        background: transparent !important;
        box-shadow: none !important;
    }
    .btn-group-compact .btn-action.btn-warning {
        color: #b45309 !important;
    }
    .btn-group-compact .btn-action.btn-danger {
        color: #b91c1c !important;
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
        border-left: 1px solid rgba(0, 0, 0, 0.1);
    }

    .hover-arrow {
        transition: transform 0.2s ease;
        display: inline-block;
    }
    .hover-arrow:hover {
        transform: translateX(3px);
    }

</style>
@endpush

@push('scripts')
<script>
(function() {
    function updateStoreShowScrollIndicators() {
        const cells = document.querySelectorAll('.store-show-scroll-td');
        cells.forEach((td) => {
            const scroller = td.querySelector('.store-show-scroll-cell');
            if (!scroller) return;

            const isScrollable = scroller.scrollWidth > scroller.clientWidth + 1;
            td.classList.toggle('is-scrollable', isScrollable);

            const atEnd = scroller.scrollLeft + scroller.clientWidth >= scroller.scrollWidth - 1;
            td.classList.toggle('is-scrolled-end', isScrollable && atEnd);
        });
    }

    function bindStoreShowScrollHandlers() {
        const cells = document.querySelectorAll('.store-show-scroll-td');
        cells.forEach((td) => {
            const scroller = td.querySelector('.store-show-scroll-cell');
            if (!scroller || scroller.__storeShowScrollBound) return;

            scroller.__storeShowScrollBound = true;
            scroller.addEventListener('scroll', updateStoreShowScrollIndicators, { passive: true });
            scroller.addEventListener('wheel', function(event) {
                const delta = Math.abs(event.deltaX) > 0 ? event.deltaX : event.deltaY;
                if (!delta) return;

                scroller.scrollLeft += delta;
                event.preventDefault();
            }, { passive: false });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateStoreShowScrollIndicators();
        bindStoreShowScrollHandlers();
        requestAnimationFrame(updateStoreShowScrollIndicators);
        setTimeout(updateStoreShowScrollIndicators, 60);
    });

    window.addEventListener('resize', function() {
        updateStoreShowScrollIndicators();
        bindStoreShowScrollHandlers();
    });
    window.addEventListener('load', updateStoreShowScrollIndicators);
})();
</script>
@endpush
@endsection
