@extends('layouts.app')

@section('title', 'Database Satuan')

@section('content')
    <div class="unit-sticky-toolbar">
        <!-- Filter Form -->
        <form action="{{ route('units.index') }}" method="GET" style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 320px; margin: 0;">
            <div style="flex: 1;">
                <select name="material_type"
                        style="width: 100%; padding: 11px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: inherit;">
                    <option value="">Semua Material Type</option>
                    @foreach($materialTypes as $type => $label)
                        <option value="{{ $type }}" {{ request('material_type') == $type ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary ">
                <i class="bi bi-funnel"></i> Filter
            </button>
            @if(request('material_type'))
                <a href="{{ route('units.index') }}" class="btn btn-secondary ">
                    <i class="bi bi-x-lg"></i> Reset
                </a>
            @endif
        </form>

        <a href="{{ route('units.create') }}" class="btn btn-success open-modal" style="flex-shrink: 0;">
            <i class="bi bi-plus-lg"></i> Tambah Satuan
        </a>
    </div>

    @if($units->count() > 0)
        <!-- Grid 2 Kolom Tabel -->
        <div class="unit-table-grid">
            @php
                $unitsArray = $units->values()->all();

                $totalUnits = count($unitsArray);
                $halfCount = ceil($totalUnits / 2);
                $leftColumn = array_slice($unitsArray, 0, $halfCount);
                $rightColumn = array_slice($unitsArray, $halfCount);
            @endphp

            <!-- Kolom Kiri -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            @php
                                function getUnitSortUrl($column, $currentSortBy, $currentDirection, $requestQuery) {
                                    $params = array_merge($requestQuery, []);
                                    unset($params['sort_by'], $params['sort_direction']);
                                    if ($currentSortBy === $column) {
                                        if ($currentDirection === 'asc') {
                                            $params['sort_by'] = $column;
                                            $params['sort_direction'] = 'desc';
                                        }
                                    } else {
                                        $params['sort_by'] = $column;
                                        $params['sort_direction'] = 'asc';
                                    }
                                    return route('units.index', $params);
                                }
                                $unitSortColumns = [
                                    'name' => 'Nama',
                                    'code' => 'Kode',
                                    'package_weight' => 'Berat (Kg)',
                                    'material_type' => 'Material',
                                ];
                            @endphp

                            @foreach(['name', 'code', 'package_weight'] as $col)
                                <th class="sortable" style="width: {{ $col == 'name' ? 'auto' : ($col == 'code' ? '80px' : '90px') }};">
                                    <a href="{{ getUnitSortUrl($col, request('sort_by'), request('sort_direction'), request()->query()) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                        <span>{{ $unitSortColumns[$col] }}</span>
                                        @if(request('sort_by') == $col)
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt-alt' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                        @endif
                                    </a>
                                </th>
                            @endforeach
                            <th style="width: 150px;">Material</th>
                            <th style="width: 100px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leftColumn as $index => $unit)
                        <tr>
                            <td style="text-align: center; font-weight: 500; color: #64748b;">
                                {{ $index + 1 }}
                            </td>
                            <td style="color: #475569; font-size: 13px; font-weight: 500;">
                                {{ $unit->name }}
                            </td>
                            <td style="text-align: center;">
                                <strong style="color: #0f172a; font-weight: 600; font-size: 13px;">{{ $unit->code }}</strong>
                            </td>
                            <td style="text-align: right; color: #475569; font-size: 13px;">
                                @if($unit->package_weight && $unit->package_weight > 0)
                                    @format($unit->package_weight)
                                @else
                                    <span style="color: #cbd5e1;">-</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <div class="unit-material-scroll-shell">
                                    <div class="unit-material-scroll-track">
                                    @foreach($unit->materialTypes as $mt)
                                        <span style="display: inline-block; padding: 4px 8px; background: #f1f5f9; border-radius: 6px; font-size: 11px; font-weight: 600; color: #475569;">
                                            {{ $materialTypes[$mt->material_type] ?? ucfirst($mt->material_type) }}
                                        </span>
                                    @endforeach
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="btn-group-compact">
                                    <a href="{{ route('units.edit', $unit->id) }}"
                                       class="btn btn-warning btn-action open-modal"
                                       title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('units.destroy', $unit->id) }}"
                                          method="POST"
                                          data-confirm="Yakin ingin menghapus satuan ini?"
                                          data-confirm-ok="Hapus"
                                          data-confirm-cancel="Batal"
                                          style="display: inline; margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-danger btn-action"
                                                title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Kolom Kanan -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            @foreach(['name', 'code', 'package_weight'] as $col)
                                <th class="sortable" style="width: {{ $col == 'name' ? 'auto' : ($col == 'code' ? '80px' : '90px') }};">
                                    <a href="{{ getUnitSortUrl($col, request('sort_by'), request('sort_direction'), request()->query()) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                        <span>{{ $unitSortColumns[$col] }}</span>
                                        @if(request('sort_by') == $col)
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                        @endif
                                    </a>
                                </th>
                            @endforeach
                            <th style="width: 150px;">Material</th>
                            <th style="width: 100px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rightColumn as $index => $unit)
                        <tr>
                            <td style="text-align: center; font-weight: 500; color: #64748b;">
                                {{ $halfCount + $index + 1 }}
                            </td>
                            <td style="color: #475569; font-size: 13px; font-weight: 500;">
                                {{ $unit->name }}
                            </td>
                            <td style="text-align: center;">
                                <strong style="color: #0f172a; font-weight: 600; font-size: 13px;">{{ $unit->code }}</strong>
                            </td>
                            <td style="text-align: right; color: #475569; font-size: 13px;">
                                @if($unit->package_weight && $unit->package_weight > 0)
                                    @format($unit->package_weight)
                                @else
                                    <span style="color: #cbd5e1;">-</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <div class="unit-material-scroll-shell">
                                    <div class="unit-material-scroll-track">
                                    @foreach($unit->materialTypes as $mt)
                                        <span style="display: inline-block; padding: 4px 8px; background: #f1f5f9; border-radius: 6px; font-size: 11px; font-weight: 600; color: #475569;">
                                            {{ $materialTypes[$mt->material_type] ?? ucfirst($mt->material_type) }}
                                        </span>
                                    @endforeach
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="btn-group-compact">
                                    <a href="{{ route('units.edit', $unit->id) }}"
                                       class="btn btn-warning btn-action open-modal"
                                       title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('units.destroy', $unit->id) }}"
                                          method="POST"
                                          data-confirm="Yakin ingin menghapus satuan ini?"
                                          data-confirm-ok="Hapus"
                                          data-confirm-cancel="Batal"
                                          style="display: inline; margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-danger btn-action"
                                                title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @else
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <p>Belum ada satuan yang terdaftar</p>
        </div>
    @endif

<!-- Floating Modal -->
<div id="floatingModal" class="floating-modal">
    <div class="floating-modal-backdrop"></div>
    <div class="floating-modal-content">
        <div class="floating-modal-header">
            <h3 id="modalTitle">Form Satuan</h3>
            <button type="button" id="closeModal" class="floating-modal-close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="floating-modal-body" id="modalBody">
            <!-- Content akan di-load via AJAX -->
            <div style="text-align: center; padding: 40px;">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Button Group Compact - sama seperti materials.index */
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

/* Responsive untuk layar kecil */
@media (max-width: 1024px) {
    .card > div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
}

/* Floating Modal Styles - Scoped to this specific modal */
#floatingModal.floating-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

#floatingModal.floating-modal.active {
    display: block;
    opacity: 1;
}

#floatingModal .floating-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(4px);
}

#floatingModal .floating-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    width: 90%;
    max-width: 600px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
}

#floatingModal .floating-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 28px;
    border-bottom: 1.5px solid #f1f5f9;
}

#floatingModal .floating-modal-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #0f172a;
}

#floatingModal .floating-modal-close {
    background: none;
    border: none;
    font-size: 20px;
    color: #64748b;
    cursor: pointer;
    padding: 8px;
    line-height: 1;
    transition: all 0.2s ease;
    border-radius: 6px;
}

#floatingModal .floating-modal-close:hover {
    background: #f1f5f9;
    color: #0f172a;
}

#floatingModal .floating-modal-body {
    overflow-y: auto;
    flex: 1;
}

/* Form inside modal */
#floatingModal .floating-modal-body .form-group {
    margin-bottom: 20px;
}

#floatingModal .floating-modal-body label {
    display: block;
    font-weight: 500;
    color: #475569;
    font-size: 14px;
}

#floatingModal .floating-modal-body .form-control {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: all 0.2s ease;
}

#floatingModal .floating-modal-body .form-control:focus {
    outline: none;
    border-color: #891313;
    box-shadow: 0 0 0 3px rgba(137, 19, 19, 0.1);
}

#floatingModal .floating-modal-body .form-text {
    display: block;
    margin-top: 6px;
    font-size: 12px;
    color: #94a3b8;
}

#floatingModal .floating-modal-body .text-danger {
    color: #dc2626;
    font-size: 12px;
    margin-top: 4px;
    display: block;
}

/* Sortable header styles */
th.sortable {
    cursor: pointer;
    user-select: none;
}

th.sortable a {
    transition: all 0.2s ease;
}

th.sortable:hover a {
    color: #891313 !important;
}

th.sortable:hover i {
    opacity: 1 !important;
}

th.sortable i {
    transition: opacity 0.2s ease;
}

.unit-style {
    color: var(--text-color);
    font-weight: var(--special-font-weight);
    -webkit-text-stroke: var(--special-text-stroke);
    text-shadow: var(--special-text-shadow);
    font-size: 32px;
}

.unit-sticky-toolbar {
    position: sticky;
    top: 72px;
    z-index: 140;
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 18px;
    padding: 12px 14px;
    border-radius: 16px;
    background: rgba(245, 247, 250, 0.96);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
}

.unit-table-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

.unit-table-grid .table-container {
    max-height: calc(100vh - 220px);
    overflow-y: auto !important;
    overflow-x: auto !important;
    margin-top: 0 !important;
}

.unit-table-grid .table-container thead th {
    position: sticky;
    top: 0;
    z-index: 30;
    box-shadow:
        inset 0 -1px 0 rgba(255, 255, 255, 0.12),
        0 2px 0 rgba(0, 0, 0, 0.08);
}

@media (max-width: 1024px) {
    .unit-table-grid {
        grid-template-columns: 1fr;
    }

    .unit-table-grid .table-container {
        max-height: none;
    }
}

.unit-material-scroll-shell {
    position: relative;
    max-width: 150px;
    margin: 0 auto;
}

.unit-material-scroll-shell.is-overflowing::after {
    content: '...';
    position: absolute;
    top: 50%;
    right: 0;
    transform: translateY(-50%);
    padding-left: 10px;
    background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, #ffffff 40%);
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    pointer-events: none;
}

.unit-material-scroll-shell.at-end::after {
    opacity: 0;
}

.unit-material-scroll-track {
    display: flex;
    gap: 4px;
    flex-wrap: nowrap;
    justify-content: flex-start;
    overflow-x: auto;
    overflow-y: hidden;
    white-space: nowrap;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding-bottom: 2px;
}

.unit-material-scroll-track::-webkit-scrollbar {
    display: none;
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('floatingModal');
    const modalBody = document.getElementById('modalBody');
    const modalTitle = document.getElementById('modalTitle');
    const closeBtn = document.getElementById('closeModal');
    const backdrop = modal.querySelector('.floating-modal-backdrop');
    let isFormDirty = false;

    // Intercept form submission in modal
    function interceptFormSubmit() {
        const form = modalBody.querySelector('form');
        if (form) {
            if (form.__unitDirtyTracked) {
                return;
            }
            form.__unitDirtyTracked = true;

            // Track dirty state
            form.addEventListener('input', () => { isFormDirty = true; });
            form.addEventListener('change', () => { isFormDirty = true; });
        }
    }

    function runInlineScripts(container) {
        const scripts = container.querySelectorAll('script');

        scripts.forEach((script) => {
            const executableScript = document.createElement('script');

            Array.from(script.attributes).forEach((attribute) => {
                executableScript.setAttribute(attribute.name, attribute.value);
            });

            executableScript.textContent = script.textContent;
            script.parentNode?.replaceChild(executableScript, script);
        });
    }

    // Open modal
    document.querySelectorAll('.open-modal').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.href;

            // Reset dirty flag
            isFormDirty = false;

            // Show modal
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Update title
            if (url.includes('/create')) {
                modalTitle.textContent = 'Tambah Satuan Baru';
            } else if (url.includes('/edit')) {
                modalTitle.textContent = 'Edit Satuan';
            } else {
                modalTitle.textContent = 'Detail Satuan';
            }

            // Load content via AJAX
            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const content = doc.querySelector('.unit-modal-fragment') || doc.querySelector('.unit-modal-card') || doc.querySelector('.card') || doc.querySelector('form') || doc.body;
                modalBody.innerHTML = content ? content.outerHTML : html;
                runInlineScripts(modalBody);
                interceptFormSubmit();
            })
            .catch(err => {
                modalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #ef4444;"><div style="font-size: 48px; margin-bottom: 16px;">⚠️</div><div style="font-weight: 500;">Gagal memuat form. Silakan coba lagi.</div></div>';
                console.error('Fetch error:', err);
            });
        });
    });

    // Close modal function - Local
    window.closeFloatingModalLocal = async function() {
        if (isFormDirty) {
            const confirmed = await window.showConfirm({
                title: 'Batalkan Perubahan?',
                message: 'Anda memiliki perubahan yang belum disimpan. Yakin ingin menutup?',
                confirmText: 'Ya, Tutup',
                cancelText: 'Kembali',
                type: 'warning'
            });
            if (!confirmed) return;
        }

        modal.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => {
            modalBody.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner-border" role="status"></div></div>';
            isFormDirty = false;
        }, 300);
    }

    closeBtn.addEventListener('click', window.closeFloatingModalLocal);
    backdrop.addEventListener('click', window.closeFloatingModalLocal);

    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            window.closeFloatingModalLocal();
        }
    });

    document.querySelectorAll('.unit-material-scroll-shell').forEach(shell => {
        const track = shell.querySelector('.unit-material-scroll-track');

        if (!track) {
            return;
        }

        const syncOverflowState = () => {
            const hasOverflow = track.scrollWidth - track.clientWidth > 4;
            const isAtEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;

            shell.classList.toggle('is-overflowing', hasOverflow);
            shell.classList.toggle('at-end', !hasOverflow || isAtEnd);
        };

        track.addEventListener('scroll', syncOverflowState, { passive: true });
        window.addEventListener('resize', syncOverflowState, { passive: true });
        syncOverflowState();
    });

});
</script>
@endsection
