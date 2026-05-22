@extends('layouts.app')

@section('title', 'Recycle Bin Material')

@section('content')
@php
    $groupedMaterials = $groupedMaterials ?? collect();
    $materialSummary = $materialSummary ?? collect();
    $deletedTotal = count($deletedMaterials ?? []);
    $activeType = $activeType ?? array_key_first($materialTypes);
@endphp

<script>
(function () {
    document.documentElement.classList.add('recycle-bin-lock');
})();

document.addEventListener('DOMContentLoaded', function () {
    document.body.classList.add('recycle-bin-lock');
});
</script>

<style>
:root {
    --tab-foot-radius: 18px;
    --tab-action-foot-radius: 13px;
    --tab-active-bg: #83b3aa;
    --recycle-accent-bg: #83b3aa;
}

html.recycle-bin-lock,
body.recycle-bin-lock {
    overflow: hidden !important;
    height: 100% !important;
}

html.recycle-bin-lock .page-content,
body.recycle-bin-lock .page-content {
    height: calc(100vh - 70px);
    overflow: hidden;
}

@media (max-width: 992px) {
    html.recycle-bin-lock .page-content,
    body.recycle-bin-lock .page-content {
        height: calc(100vh - 120px);
    }
}

.material-inline-create-handle,
.material-footer-sticky,
[data-inline-form],
.material-inline-editor-row,
#emptyMaterialState,
.material-settings-dropdown {
    display: none !important;
}

.material-tab-header {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    position: relative;
    margin-bottom: 0;
    overflow: visible !important;
}

.material-tab-header::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    border-bottom: none !important;
}

.material-tab-header .material-tabs {
    display: flex;
    align-items: flex-end;
    gap: 0;
    position: relative;
    z-index: 12;
    flex: 1 1 auto;
    min-width: 0;
    bottom: 0 !important;
}

.recycle-bulk-actions {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
    padding: 8px 10px 4px;
    border: 2px solid var(--recycle-accent-bg);
    border-bottom: none;
    border-radius: 10px 10px 0 0;
    background: var(--recycle-accent-bg);
    transform: translateY(2px);
    white-space: nowrap;
}

.recycle-bulk-actions.hidden {
    display: none !important;
}

.recycle-bulk-count {
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
    margin-right: 4px;
}

.recycle-bulk-form {
    display: inline-flex;
}

.recycle-bulk-btn {
    border: 1px solid rgba(15, 23, 42, 0.25);
    border-radius: 8px;
    background: #fff;
    color: #0f172a;
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
    padding: 7px 10px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.recycle-bulk-btn:hover {
    background: #f8fafc;
}

.recycle-bulk-btn.danger {
    border-color: rgba(185, 28, 28, 0.35);
    color: #991b1b;
}

.recycle-bulk-btn.danger:hover {
    background: #fff5f5;
}

.material-tab-btn {
    border: 2px solid var(--recycle-accent-bg);
    border-bottom: none;
    border-radius: 12px 12px 0 0;
    background: #f8f5cc;
    color: #1f2937;
    padding: 10px 12px 8px;
    font-weight: 700;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
    gap: 5px !important;
    width: auto !important;
    min-width: 0 !important;
    max-width: max-content;
    line-height: 1;
    margin-right: 6px;
    cursor: pointer;
    position: relative;
    overflow: visible !important;
}

.material-tab-btn.active {
    background: var(--recycle-accent-bg);
    --tab-border-color: var(--recycle-accent-bg);
}

.material-tab-btn .material-tab-label {
    display: inline-block;
    white-space: nowrap;
}

.material-tab-badge {
    position: static;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(239, 68, 68, 0.5);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    line-height: 1;
    padding: 3px 6px;
    border-radius: 999px;
    box-shadow: 0 2px 6px rgba(239, 68, 68, 0.2);
    white-space: nowrap;
}

.material-tab-btn.active .material-tab-badge {
    background: #ef4444;
    box-shadow: 0 4px 10px rgba(239, 68, 68, 0.35);
}

.material-tab-btn.active::before {
    content: "" !important;
    position: absolute;
    bottom: -2px;
    right: calc(100% - 1px);
    width: var(--tab-foot-radius);
    height: var(--tab-foot-radius);
    background: radial-gradient(
        circle at 0 0,
        transparent calc(var(--tab-foot-radius) - 2px),
        var(--tab-border-color) calc(var(--tab-foot-radius) - 2px),
        var(--tab-border-color) var(--tab-foot-radius),
        var(--tab-active-bg) var(--tab-foot-radius)
    );
    background-position: bottom right;
    pointer-events: none;
    z-index: 5;
}

.material-tab-btn.active::after {
    content: "" !important;
    position: absolute;
    bottom: -2px;
    left: calc(100% - 1px);
    width: var(--tab-foot-radius);
    height: var(--tab-foot-radius);
    background: radial-gradient(
        circle at 100% 0,
        transparent calc(var(--tab-foot-radius) - 2px),
        var(--tab-border-color) calc(var(--tab-foot-radius) - 2px),
        var(--tab-border-color) var(--tab-foot-radius),
        var(--tab-active-bg) var(--tab-foot-radius)
    );
    background-position: bottom left;
    pointer-events: none;
    z-index: 5;
}

.material-tab-btn.active.first-visible::before {
    content: none !important;
}

.material-tab-btn.active.last-visible::after {
    content: none !important;
}

.recycle-tab-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #10313a;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.35;
}

.material-tab-wrapper {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
}

.material-tab-panel {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    overflow: hidden;
    min-height: 0;
}

.material-tab-card {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    overflow: hidden;
    min-height: 0;
    padding: 20px;
    border: 2px solid var(--recycle-accent-bg);
    background: var(--recycle-accent-bg);
    border-radius: 0 0 12px 12px !important;
}

.material-tab-card .material-table-frame {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
}

.material-tab-panel.active .material-tab-card {
    border-color: none !important;
    box-shadow:
        0 4px 6px -1px rgba(137, 19, 19, 0.08),
        0 2px 4px -1px rgba(137, 19, 19, 0.06);
}

.table-container {
    overflow-y: auto;
    overflow-x: auto;
    scroll-padding-top: 80px;
    scroll-behavior: smooth;
    flex: 1 1 auto;
    min-height: 0;
    background: #ffffff;
}

.material-tab-panel .table-container table thead tr:first-child th.recycle-select-all-th,
.material-tab-panel .table-container table tbody tr td.recycle-select-cell-td {
    width: 34px !important;
    min-width: 34px !important;
    padding-left: 6px !important;
    padding-right: 6px !important;
}

.material-tab-panel .table-container table tbody tr td.recycle-no-cell-td {
    width: 40px !important;
    min-width: 40px !important;
    text-align: center !important;
}

.recycle-select-cell {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.recycle-row-checkbox {
    width: 14px;
    height: 14px;
    accent-color: #0f766e;
    cursor: pointer;
}

.table-container thead {
    position: sticky;
    top: 0;
    z-index: 10;
    background: white;
}

.table-container thead.single-header th {
    height: 40px !important;
    padding: 8px 12px !important;
    line-height: 1.1;
    vertical-align: top !important;
    box-sizing: border-box;
}

.table-container thead.single-header th a {
    align-items: flex-start !important;
}

.table-container thead.has-dim-sub th {
    padding: 6px 12px !important;
    line-height: 1.1;
    vertical-align: top !important;
    box-sizing: border-box;
}

.table-container thead.has-dim-sub tr.dim-group-row th {
    height: 26px !important;
}

.table-container thead.has-dim-sub tr.dim-sub-row th {
    height: 14px !important;
    padding: 1px 2px !important;
    font-size: 11px !important;
}

#section-brick .table-container thead,
#section-sand .table-container thead,
#section-cat .table-container thead,
#section-cement .table-container thead,
#section-paku .table-container thead,
#section-ceramic .table-container thead {
    height: 40px !important;
}

#section-cat .table-container thead.single-header tr th,
#section-cement .table-container thead.single-header tr th {
    height: 40px !important;
    line-height: 1.2 !important;
    vertical-align: top !important;
    padding: 8px 12px !important;
}

#section-brick .table-container thead.has-dim-sub tr.dim-group-row th,
#section-sand .table-container thead.has-dim-sub tr.dim-group-row th,
#section-paku .table-container thead.has-dim-sub tr.dim-group-row th,
#section-ceramic .table-container thead.has-dim-sub tr.dim-group-row th {
    height: 26px !important;
    vertical-align: top !important;
    padding: 6px 12px !important;
}

#section-brick .table-container thead.has-dim-sub tr.dim-sub-row th,
#section-sand .table-container thead.has-dim-sub tr.dim-sub-row th,
#section-paku .table-container thead.has-dim-sub tr.dim-sub-row th,
#section-ceramic .table-container thead.has-dim-sub tr.dim-sub-row th {
    height: 14px !important;
    padding: 1px 2px !important;
    font-size: 11px !important;
    vertical-align: top !important;
}

.table-container thead.has-dim-sub tr.dim-sub-row th:first-child {
    border-left: 1px solid #cbd5e1 !important;
}

.table-container thead.has-dim-sub tr.dim-sub-row th:last-child {
    border-right: 1px solid #cbd5e1 !important;
}

#section-paku .table-container thead.has-dim-sub tr.paku-dim-sub-row th:nth-child(n+3)::before {
    content: none !important;
    display: none !important;
}

#section-brick .table-container thead th,
#section-sand .table-container thead th,
#section-cat .table-container thead th,
#section-cement .table-container thead th,
#section-ceramic .table-container thead th {
    vertical-align: top !important;
    font-size: 14px !important;
}

#section-paku .table-container thead th {
    text-align: center !important;
}

#section-paku .table-container thead th a {
    justify-content: center;
    width: 100%;
}

.table-container table {
    border-collapse: separate !important;
    border-spacing: 0 !important;
}

.material-tab-panel table {
    table-layout: auto !important;
}

.table-container thead th {
    border: 1px solid #cbd5e1 !important;
    z-index: 20;
}

.table-container tbody td {
    border: 1px solid #f1f5f9 !important;
    padding: 14px 16px !important;
    vertical-align: middle !important;
    font-size: 13px !important;
    color: #1e293b !important;
    text-shadow: none !important;
    -webkit-text-stroke: 0 !important;
}

.material-tab-panel .table-container tbody td.border-left-none {
    border-left: 0 !important;
    border-left-style: none !important;
    border-left-color: transparent !important;
}

.material-tab-panel .table-container tbody td.border-right-none {
    border-right: 0 !important;
    border-right-style: none !important;
    border-right-color: transparent !important;
}

.table-container thead tr:not(.dim-sub-row) th.action-cell,
.table-container tbody td.action-cell {
    width: 90px !important;
    min-width: 90px !important;
    max-width: 90px !important;
    text-align: center !important;
}

.table-container thead tr:not(.dim-sub-row) th.deleted-meta-col,
.table-container tbody td.deleted-meta-col {
    min-width: 150px !important;
    width: 150px !important;
    max-width: 180px !important;
}

.table-container tbody td.dim-cell {
    text-align: center !important;
    font-size: 12px !important;
    width: 40px !important;
    padding: 0 2px !important;
}

.table-container tbody td.volume-cell {
    text-align: right !important;
    font-size: 12px !important;
    padding: 0 8px !important;
    width: auto !important;
}

#section-brick .table-container tbody td,
#section-sand .table-container tbody td,
#section-cat .table-container tbody td,
#section-cement .table-container tbody td,
#section-ceramic .table-container tbody td {
    height: 35px !important;
    padding: 0 4px !important;
    font-size: 12px !important;
    line-height: 1.3 !important;
}

#section-brick .table-container tbody td.dim-cell,
#section-sand .table-container tbody td.dim-cell {
    text-align: center !important;
    font-size: 12px !important;
    width: 40px !important;
    padding: 2px 2px !important;
    height: 35px !important;
}

#section-ceramic .table-container tbody td.dim-cell {
    text-align: center !important;
    font-size: 12px !important;
    width: 50px !important;
    min-width: 50px !important;
    max-width: 50px !important;
    padding: 2px 2px !important;
    height: 35px !important;
}

#section-paku .table-container tbody td.dim-cell {
    text-align: right !important;
}

#section-ceramic .ceramic-scroll-td,
#section-cement .cement-scroll-td,
#section-sand .sand-scroll-td,
#section-cat .cat-scroll-td,
#section-brick .brick-scroll-td {
    position: relative;
    overflow: hidden;
}

#section-ceramic .ceramic-scroll-td.is-scrollable::after,
#section-cement .cement-scroll-td.is-scrollable::after,
#section-sand .sand-scroll-td.is-scrollable::after,
#section-cat .cat-scroll-td.is-scrollable::after,
#section-brick .brick-scroll-td.is-scrollable::after {
    content: "...";
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

#section-ceramic .ceramic-scroll-td.is-scrolled-end::after,
#section-cement .cement-scroll-td.is-scrolled-end::after,
#section-sand .sand-scroll-td.is-scrolled-end::after,
#section-cat .cat-scroll-td.is-scrolled-end::after,
#section-brick .brick-scroll-td.is-scrolled-end::after {
    opacity: 0;
}

#section-ceramic .ceramic-scroll-cell,
#section-cement .cement-scroll-cell,
#section-sand .sand-scroll-cell,
#section-cat .cat-scroll-cell,
#section-brick .brick-scroll-cell {
    display: block;
    overflow-x: auto;
    overflow-y: hidden;
    scrollbar-width: none;
    scrollbar-color: transparent transparent;
}

#section-ceramic .ceramic-scroll-cell::-webkit-scrollbar,
#section-cement .cement-scroll-cell::-webkit-scrollbar,
#section-sand .sand-scroll-cell::-webkit-scrollbar,
#section-cat .cat-scroll-cell::-webkit-scrollbar,
#section-brick .brick-scroll-cell::-webkit-scrollbar {
    height: 0;
}

#section-brick .table-container tbody td.volume-cell,
#section-sand .table-container tbody td.volume-cell {
    text-align: right !important;
    font-size: 12px !important;
    padding: 2px 8px !important;
    height: 35px !important;
}

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

.btn-group-compact .btn-action.btn-danger {
    color: #b91c1c !important;
}

.btn-group-compact .btn-action.btn-success {
    color: #15803d !important;
}

.btn-group-compact .btn-action + .btn-action {
    border-left: 1px solid rgba(15, 23, 42, 0.15);
}

#section-brick .brick-sticky-col,
#section-ceramic .ceramic-sticky-col,
#section-cat .cat-sticky-col,
#section-cement .cement-sticky-col {
    position: sticky;
    background: #fff;
    z-index: 3;
}

#section-brick thead .brick-sticky-col,
#section-ceramic thead .ceramic-sticky-col,
#section-cat thead .cat-sticky-col,
#section-cement thead .cement-sticky-col {
    z-index: 30;
}

#section-brick .brick-sticky-edge,
#section-ceramic .ceramic-sticky-edge,
#section-cat .cat-sticky-edge,
#section-cement .cement-sticky-edge {
    box-shadow: 2px 0 0 rgba(148, 163, 184, 0.2);
}

.recycle-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 240px;
    border: 2px dashed #e2e8f0;
    border-radius: 12px;
    color: #64748b;
    text-align: center;
    padding: 24px;
}

.recycle-empty-state i {
    font-size: 32px;
}

.recycle-page-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-bottom: 14px;
}

.recycle-page-heading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    width: 100%;
    min-width: 0;
    position: relative;
}

.recycle-page-heading > div {
    text-align: center;
}

.recycle-back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 12px;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #0f172a;
    font-weight: 600;
    text-decoration: none;
    line-height: 1;
    white-space: nowrap;
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
}

.recycle-back-link:hover {
    color: #0f172a;
    border-color: #94a3b8;
    background: #f8fafc;
    text-decoration: none;
}

.recycle-page-title {
    margin: 0;
    font-size: 28px;
    line-height: 1.1;
    font-weight: 800;
    color: #0f172a;
}

.recycle-page-subtitle {
    margin: 4px 0 0;
    color: #64748b;
    font-size: 14px;
}

.recycle-page-layout {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
    padding-bottom: 20px;
    box-sizing: border-box;
}

.recycle-flash-stack {
    flex: 0 0 auto;
}

.material-tab-panel.active {
    display: flex;
}

.material-tab-panel.hidden {
    display: none !important;
}

@media (max-width: 768px) {
    .recycle-page-header,
    .recycle-page-heading {
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-start;
    }

    .recycle-page-heading > div {
        text-align: left;
    }

    .recycle-back-link {
        position: static;
        top: auto;
        left: auto;
        transform: none;
    }

    .recycle-page-title {
        font-size: 24px;
    }
}
</style>

<div class="recycle-page-layout">
    <div class="recycle-page-header">
        <div class="recycle-page-heading">
            <a href="{{ route('materials.index') }}" class="recycle-back-link">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali</span>
            </a>
            <div>
                <h1 class="recycle-page-title">Recycle Bin Material</h1>
            </div>
        </div>
    </div>

    <div class="recycle-flash-stack">
        @if(session('success'))
            <div class="alert alert-success mb-3">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger mb-3">{{ session('error') }}</div>
        @endif
    </div>

    @if(count($materialTypes) > 0)
        <div class="material-tab-wrapper">
            <div class="material-tab-header">
                <div class="material-tabs">
                    @foreach($materialTypes as $typeKey => $label)
                        <button type="button"
                            class="material-tab-btn {{ $typeKey === $activeType ? 'active' : '' }}"
                            data-tab="{{ $typeKey }}"
                            aria-selected="{{ $typeKey === $activeType ? 'true' : 'false' }}">
                            <span class="material-tab-label">{{ $label }}</span>
                            <span class="material-tab-badge">@format($materialSummary[$typeKey] ?? 0)</span>
                        </button>
                    @endforeach
                </div>
                <div id="recycleBulkActions" class="recycle-bulk-actions hidden">
                    <span id="recycleBulkCount" class="recycle-bulk-count">0 dipilih</span>
                    <form id="recycleBulkRestoreForm" class="recycle-bulk-form" method="POST" action="{{ route('materials.bulk-restore') }}">
                        @csrf
                        <div id="recycleBulkRestoreItems"></div>
                        <button type="submit" class="recycle-bulk-btn">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span>Restore</span>
                        </button>
                    </form>
                    @can('materials.recycle-bin.delete')
                        <form
                            id="recycleBulkDeleteForm"
                            class="recycle-bulk-form"
                            method="POST"
                            action="{{ route('materials.bulk-force-delete') }}"
                            data-confirm-title="Hapus Permanen"
                            data-confirm-type="danger"
                            data-confirm-ok="Ya, Hapus"
                            data-confirm-cancel="Batal">
                            @csrf
                            <div id="recycleBulkDeleteItems"></div>
                            <button type="submit" class="recycle-bulk-btn danger">
                                <i class="bi bi-trash"></i>
                                <span>Hapus</span>
                            </button>
                        </form>
                    @endcan
                </div>
            </div>

            @foreach($materialTypes as $typeKey => $label)
                @php
                    $items = $groupedMaterials->get($typeKey, collect());
                @endphp

                <div class="material-tab-panel {{ $typeKey === $activeType ? 'active' : 'hidden' }}" data-tab="{{ $typeKey }}" id="section-{{ $typeKey }}" style="margin-bottom: 0;">
                    <div class="material-tab-card">
                        @if($items->count() > 0)
                            @include('materials.partials.table', [
                                'material' => [
                                    'type' => $typeKey,
                                    'label' => $label,
                                    'data' => $items,
                                    'is_loaded' => true,
                                ],
                                'showActions' => true,
                                'showStoreInfo' => true,
                                'actionMode' => 'recycle-bin',
                                'isStoreLocation' => true,
                            ])
                        @else
                            <div class="recycle-empty-state">
                                <i class="bi bi-inbox"></i>
                                <p>Tidak ada {{ strtolower($label) }} di recycle bin.</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="recycle-empty-state">
            <i class="bi bi-inbox"></i>
            <p>Recycle bin material kosong.</p>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = Array.from(document.querySelectorAll('.material-tab-btn'));
    const tabPanels = Array.from(document.querySelectorAll('.material-tab-panel'));
    const tabActions = Array.from(document.querySelectorAll('.material-tab-action'));
    const bulkActions = document.getElementById('recycleBulkActions');
    const bulkCount = document.getElementById('recycleBulkCount');
    const bulkRestoreForm = document.getElementById('recycleBulkRestoreForm');
    const bulkDeleteForm = document.getElementById('recycleBulkDeleteForm');
    const bulkRestoreItems = document.getElementById('recycleBulkRestoreItems');
    const bulkDeleteItems = document.getElementById('recycleBulkDeleteItems');
    const selectAllCheckboxes = Array.from(document.querySelectorAll('.recycle-select-all-checkbox'));

    const getSelectedItems = () => {
        return Array.from(document.querySelectorAll('.recycle-bulk-checkbox:checked')).map((checkbox) => ({
            type: checkbox.dataset.materialType,
            id: checkbox.dataset.materialId || checkbox.value,
        }));
    };

    const fillBulkInputs = (container, items) => {
        if (!container) return;
        container.innerHTML = '';
        items.forEach((item, index) => {
            const inputType = document.createElement('input');
            inputType.type = 'hidden';
            inputType.name = `items[${index}][type]`;
            inputType.value = item.type;
            container.appendChild(inputType);

            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = `items[${index}][id]`;
            inputId.value = item.id;
            container.appendChild(inputId);
        });
    };

    const syncSelectAllState = () => {
        selectAllCheckboxes.forEach((masterCheckbox) => {
            const panel = masterCheckbox.closest('.material-tab-panel');
            if (!panel) return;

            const rowCheckboxes = Array.from(panel.querySelectorAll('.recycle-bulk-checkbox'));
            if (rowCheckboxes.length === 0) {
                masterCheckbox.checked = false;
                masterCheckbox.indeterminate = false;
                return;
            }

            const checkedCount = rowCheckboxes.filter((checkbox) => checkbox.checked).length;
            masterCheckbox.checked = checkedCount > 0 && checkedCount === rowCheckboxes.length;
            masterCheckbox.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
        });
    };

    const updateBulkActionState = () => {
        if (!bulkActions) return;

        const items = getSelectedItems();
        const hasItems = items.length > 0;
        bulkActions.classList.toggle('hidden', !hasItems);

        if (bulkCount) {
            bulkCount.textContent = `${items.length} dipilih`;
        }

        fillBulkInputs(bulkRestoreItems, items);
        fillBulkInputs(bulkDeleteItems, items);
        syncSelectAllState();
    };

    function updateTabVisualFootState() {
        const visibleButtons = tabButtons.filter((btn) => {
            if (btn.offsetParent === null) return false;
            return window.getComputedStyle(btn).display !== 'none';
        });

        tabButtons.forEach((btn) => {
            btn.classList.remove('first-visible', 'last-visible');
        });

        if (visibleButtons.length === 0) return;

        visibleButtons[0].classList.add('first-visible');
        visibleButtons[visibleButtons.length - 1].classList.add('last-visible');
    }

    function updateCeramicScrollIndicators() {
        const cells = document.querySelectorAll('#section-ceramic .ceramic-scroll-td, #section-cement .cement-scroll-td, #section-sand .sand-scroll-td, #section-cat .cat-scroll-td, #section-brick .brick-scroll-td');
        cells.forEach((td) => {
            const scroller = td.querySelector('.ceramic-scroll-cell, .cement-scroll-cell, .sand-scroll-cell, .cat-scroll-cell, .brick-scroll-cell');
            if (!scroller) return;
            const isScrollable = scroller.scrollWidth > scroller.clientWidth + 1;
            td.classList.toggle('is-scrollable', isScrollable);
            const atEnd = scroller.scrollLeft + scroller.clientWidth >= scroller.scrollWidth - 1;
            td.classList.toggle('is-scrolled-end', isScrollable && atEnd);
        });
    }

    function bindCeramicScrollHandlers() {
        const cells = document.querySelectorAll('#section-ceramic .ceramic-scroll-td, #section-cement .cement-scroll-td, #section-sand .sand-scroll-td, #section-cat .cat-scroll-td, #section-brick .brick-scroll-td');
        cells.forEach((td) => {
            const scroller = td.querySelector('.ceramic-scroll-cell, .cement-scroll-cell, .sand-scroll-cell, .cat-scroll-cell, .brick-scroll-cell');
            if (!scroller || scroller.__ceramicScrollBound) return;
            scroller.__ceramicScrollBound = true;
            scroller.addEventListener('scroll', updateCeramicScrollIndicators, { passive: true });
        });
    }

    function applyAllStickyOffsets() {
        const applyToSection = (sectionId, stickyClass) => {
            const panel = document.getElementById(sectionId);
            if (!panel || panel.offsetParent === null) return;

            const tables = panel.querySelectorAll('table');
            tables.forEach((table) => {
                const rows = table.querySelectorAll('tr');
                const headerRow = Array.from(table.querySelectorAll('thead tr')).find((row) => row.querySelector('.' + stickyClass));

                if (!headerRow) {
                    return;
                }

                const headerStickyCells = Array.from(headerRow.querySelectorAll('.' + stickyClass));
                const leftOffsets = headerStickyCells.map((cell) => {
                    cell.style.left = '';
                    return cell.offsetLeft;
                });

                rows.forEach((row) => {
                    const stickyCells = Array.from(row.querySelectorAll('.' + stickyClass));
                    stickyCells.forEach((cell, index) => {
                        const leftOffset = leftOffsets[index] ?? 0;
                        cell.style.left = `${leftOffset}px`;
                    });
                });
            });
        };

        applyToSection('section-brick', 'brick-sticky-col');
        applyToSection('section-ceramic', 'ceramic-sticky-col');
        applyToSection('section-cat', 'cat-sticky-col');
        applyToSection('section-cement', 'cement-sticky-col');
    }

    function setActiveTab(tabType, updateUrl = true) {
        if (!tabType) return;

        updateTabVisualFootState();

        tabButtons.forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.tab === tabType);
            btn.setAttribute('aria-selected', btn.dataset.tab === tabType ? 'true' : 'false');
        });

        tabPanels.forEach((panel) => {
            const isActive = panel.dataset.tab === tabType;
            panel.classList.toggle('active', isActive);
            panel.classList.toggle('hidden', !isActive);
        });

        tabActions.forEach((action) => {
            action.classList.toggle('active', action.dataset.tab === tabType);
        });

        if (updateUrl) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabType);
            history.replaceState(null, '', url.toString());
        }

        const activePanel = document.querySelector(`.material-tab-panel[data-tab="${tabType}"]`);
        const tableContainer = activePanel ? activePanel.querySelector('.table-container') : null;
        if (tableContainer) {
            tableContainer.scrollLeft = 0;
        }

        updateCeramicScrollIndicators();
        bindCeramicScrollHandlers();
        requestAnimationFrame(() => {
            applyAllStickyOffsets();
            requestAnimationFrame(applyAllStickyOffsets);
        });
        updateBulkActionState();
    }

    tabButtons.forEach((btn) => {
        btn.addEventListener('click', function () {
            setActiveTab(this.dataset.tab);
        });
    });

    document.addEventListener('change', (event) => {
        if (event.target.classList.contains('recycle-select-all-checkbox')) {
            const panel = event.target.closest('.material-tab-panel');
            if (panel) {
                panel.querySelectorAll('.recycle-bulk-checkbox').forEach((checkbox) => {
                    checkbox.checked = event.target.checked;
                });
            }
            updateBulkActionState();
            return;
        }

        if (event.target.classList.contains('recycle-bulk-checkbox')) {
            updateBulkActionState();
        }
    });

    if (bulkRestoreForm) {
        bulkRestoreForm.addEventListener('submit', function (event) {
            if (getSelectedItems().length === 0) {
                event.preventDefault();
            }
        });
    }

    if (bulkDeleteForm) {
        bulkDeleteForm.addEventListener('submit', function (event) {
            const selectedCount = getSelectedItems().length;
            if (selectedCount === 0) {
                event.preventDefault();
                return;
            }

            bulkDeleteForm.dataset.confirm = `Hapus permanen ${selectedCount} material? Tindakan ini tidak bisa dibatalkan.`;
        });
    }

    const initialActiveTab = @json($activeType);
    updateTabVisualFootState();
    setActiveTab(initialActiveTab || (tabButtons[0] ? tabButtons[0].dataset.tab : null), false);
    updateBulkActionState();
    requestAnimationFrame(() => {
        applyAllStickyOffsets();
        requestAnimationFrame(applyAllStickyOffsets);
    });

    window.addEventListener('resize', function () {
        updateTabVisualFootState();
        updateCeramicScrollIndicators();
        bindCeramicScrollHandlers();
        applyAllStickyOffsets();
    });
});
</script>
@endsection
