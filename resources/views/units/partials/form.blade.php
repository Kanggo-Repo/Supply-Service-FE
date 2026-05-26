<div class="unit-modal-fragment">
    <div class="card unit-modal-card">
        @if($errors->any())
            <div class="alert alert-danger">
                <div>
                    <strong>Terdapat kesalahan pada input:</strong>
                    <ul style="margin: 8px 0 0 20px; line-height: 1.8;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form action="{{ $action }}" method="POST" id="unitForm" class="unit-modal-form">
            @csrf
            @isset($method)
                @method($method)
            @endisset

            <div class="unit-form-shell">

                <div class="unit-form-panel">
                    <div class="unit-form-row unit-form-row-stack">
                        <label class="unit-form-label">Material Type <span class="unit-required">*</span></label>
                        <div class="unit-form-field">
                            <div class="unit-type-select" data-unit-type-select>
                                <button type="button" class="unit-type-trigger" data-unit-type-trigger aria-expanded="false">
                                    <span class="unit-type-trigger-label">
                                        <span class="unit-type-trigger-title">Pilih material type</span>
                                        <span class="unit-type-trigger-value" id="unitTypeSelectionCount">0 material dipilih</span>
                                    </span>
                                    <i class="bi bi-chevron-down"></i>
                                </button>

                                <div class="unit-type-dropdown" data-unit-type-dropdown hidden>
                                    <div class="unit-type-search-shell">
                                        <i class="bi bi-search"></i>
                                        <input type="text"
                                               class="unit-type-search"
                                               data-unit-type-search
                                               placeholder="Cari material type...">
                                    </div>

                                    <div class="unit-type-options" data-unit-type-options>
                                        @foreach($materialTypes as $type => $label)
                                            @php
                                                $isChecked = in_array($type, old('material_types', $selectedTypes ?? []), true);
                                            @endphp
                                            <label class="unit-type-option {{ $isChecked ? 'is-checked' : '' }}"
                                                   data-unit-type-option
                                                   data-search-text="{{ strtolower($label.' '.$type) }}">
                                                <input type="checkbox"
                                                       name="material_types[]"
                                                       value="{{ $type }}"
                                                       {{ $isChecked ? 'checked' : '' }}>
                                                <span class="unit-type-option-check">
                                                    <i class="bi bi-check-lg"></i>
                                                </span>
                                                <span class="unit-type-option-text">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="unit-type-selected" data-unit-type-selected></div>
                            @error('material_types')
                                <small class="unit-form-error">
                                    <i class="bi bi-exclamation-circle"></i> {{ $message }}
                                </small>
                            @enderror
                        </div>
                    </div>

                    <div class="unit-form-fields-grid">
                        <div class="unit-form-row">
                            <label class="unit-form-label">Kode Satuan <span class="unit-required">*</span></label>
                            <div class="unit-form-field">
                                <input type="text"
                                       name="code"
                                       id="code"
                                       value="{{ old('code', $unit->code ?? '') }}"
                                       required
                                       placeholder="Contoh: Kg, Galon, Sak"
                                       class="unit-form-input">
                                @error('code')
                                    <small class="unit-form-error">
                                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                                    </small>
                                @enderror
                            </div>
                        </div>

                        <div class="unit-form-row">
                            <label class="unit-form-label">Nama Satuan <span class="unit-required">*</span></label>
                            <div class="unit-form-field">
                                <input type="text"
                                       name="name"
                                       id="name"
                                       value="{{ old('name', $unit->name ?? '') }}"
                                       required
                                       placeholder="Contoh: Kilogram, Galon, Sak"
                                       class="unit-form-input">
                                @error('name')
                                    <small class="unit-form-error">
                                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                                    </small>
                                @enderror
                            </div>
                        </div>

                        <div class="unit-form-row">
                            <label class="unit-form-label">Berat Kemasan <span class="unit-required">*</span></label>
                            <div class="unit-form-field">
                                <div class="unit-form-input-group">
                                    <input type="text"
                                           inputmode="decimal"
                                           name="package_weight"
                                           id="package_weight"
                                           value="{{ old('package_weight', $unit->package_weight ?? 0) }}"
                                           required
                                           placeholder="0"
                                           class="unit-form-input">
                                    <span class="unit-form-suffix">Kg</span>
                                </div>
                                <small class="unit-form-hint">Berat kemasan kosong. Isi `0` jika satuan ini tidak memakai kemasan.</small>
                                @error('package_weight')
                                    <small class="unit-form-error">
                                        <i class="bi bi-exclamation-circle"></i> {{ $message }}
                                    </small>
                                @enderror
                            </div>
                        </div>

                        <div class="unit-form-row unit-form-row-description">
                            <label class="unit-form-label">Keterangan</label>
                            <div class="unit-form-field">
                                <textarea name="description"
                                          id="description"
                                          rows="3"
                                          placeholder="Keterangan tambahan (opsional)"
                                          class="unit-form-input unit-form-textarea">{{ old('description', $unit->description ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="unit-form-actions">
                <button type="button" class="btn-cancel"
                        onclick="if(typeof window.closeFloatingModalLocal === 'function'){ window.closeFloatingModalLocal(); } else if(typeof window.closeFloatingModal === 'function'){ window.closeFloatingModal(); }">Batal</button>
                <button type="submit" class="btn-save">{{ $submitLabel }}</button>
            </div>
        </form>
    </div>

<style>
    .unit-modal-card {
        background: #ffffff;
        box-shadow: none;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 20px;
    }

    .unit-modal-form {
        color: #334155;
    }

    .unit-form-shell {
        display: grid;
        gap: 20px;
    }

    .unit-form-intro {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 18px 20px;
        border: 1px solid #f1f5f9;
        border-radius: 18px;
        background: linear-gradient(135deg, #fff8f8 0%, #ffffff 55%, #fff3f3 100%);
    }

    .unit-form-intro-icon {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #891313 0%, #b91c1c 100%);
        color: #ffffff;
        font-size: 22px;
        box-shadow: 0 14px 28px rgba(137, 19, 19, 0.18);
    }

    .unit-form-intro h4 {
        margin: 0 0 4px;
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
    }

    .unit-form-intro p {
        margin: 0;
        color: #64748b;
        font-size: 13px;
        line-height: 1.5;
    }

    .unit-form-panel {
        display: grid;
        gap: 18px;
        padding: 6px 2px 0;
    }

    .unit-form-fields-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px 20px;
    }

    .unit-form-row {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 0;
    }

    .unit-form-row-stack {
        gap: 10px;
    }

    .unit-form-row-description {
        align-self: stretch;
    }

    .unit-form-label {
        width: auto;
        padding: 0;
        margin: 0;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        letter-spacing: 0.01em;
    }

    .unit-required {
        color: #dc2626;
    }

    .unit-form-field {
        min-width: 0;
    }

    .unit-form-input,
    .unit-form-textarea {
        width: 100%;
        padding: 12px 14px;
        border: 1.5px solid #e2e8f0;
        border-radius: 4px;
        font-size: 13.5px;
        font-family: inherit;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        background: #ffffff;
    }

    .unit-form-textarea {
        min-height: 46px;
        height: 46px;
        resize: vertical;
    }

    .unit-form-input:focus,
    .unit-form-textarea:focus {
        outline: none;
        border-color: #891313;
        box-shadow: 0 0 0 4px rgba(137, 19, 19, 0.08);
        background: #fffbfb;
    }

    .unit-form-input::placeholder,
    .unit-form-textarea::placeholder {
        color: #94a3b8;
    }

    .unit-form-input-group {
        position: relative;
    }

    .unit-form-input-group .unit-form-input {
        padding-right: 48px;
    }

    .unit-form-suffix {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        font-size: 12.5px;
        font-weight: 700;
        pointer-events: none;
    }

    .unit-form-hint,
    .unit-form-error,
    .unit-type-footnote {
        display: block;
        margin-top: 6px;
        font-size: 12px;
        line-height: 1.5;
    }

    .unit-form-hint,
    .unit-type-footnote {
        color: #64748b;
    }

    .unit-type-footnote {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .unit-form-error {
        color: #ef4444;
        font-weight: 600;
    }

    .unit-type-select {
        position: relative;
    }

    .unit-type-trigger {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 12px 14px;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        color: #334155;
        text-align: left;
        transition: all 0.2s ease;
    }

    .unit-type-trigger:hover,
    .unit-type-select.is-open .unit-type-trigger {
        border-color: #d8b4b4;
        box-shadow: 0 0 0 4px rgba(137, 19, 19, 0.05);
    }

    .unit-type-trigger i {
        color: #64748b;
        transition: transform 0.2s ease;
    }

    .unit-type-select.is-open .unit-type-trigger i {
        transform: rotate(180deg);
    }

    .unit-type-trigger-label {
        min-width: 0;
        display: grid;
        gap: 2px;
    }

    .unit-type-trigger-title {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .unit-type-trigger-value {
        font-size: 13.5px;
        font-weight: 600;
        color: #0f172a;
    }

    .unit-type-dropdown {
        position: absolute;
        z-index: 40;
        top: calc(100% + 8px);
        left: 0;
        right: 0;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 22px 44px rgba(15, 23, 42, 0.14);
        overflow: hidden;
    }

    .unit-type-search-shell {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
    }

    .unit-type-search-shell i {
        color: #94a3b8;
    }

    .unit-type-search {
        width: 100%;
        border: none;
        background: transparent;
        font-size: 13px;
        color: #334155;
        outline: none;
    }

    .unit-type-options {
        max-height: 240px;
        overflow-y: auto;
        padding: 8px;
        display: grid;
        gap: 8px;
    }

    .unit-type-option {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 42px;
        padding: 10px 12px;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }

    .unit-type-option:hover {
        border-color: #f0b4b4;
        background: #fff8f8;
    }

    .unit-type-option input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .unit-type-option-check {
        width: 20px;
        height: 20px;
        border-radius: 999px;
        border: 1.5px solid #cbd5e1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: transparent;
        background: #ffffff;
        transition: all 0.2s ease;
        flex-shrink: 0;
        font-size: 12px;
    }

    .unit-type-option-text {
        color: #475569;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.35;
    }

    .unit-type-option.is-checked,
    .unit-type-option:has(input:checked) {
        border-color: rgba(137, 19, 19, 0.24);
        background: linear-gradient(135deg, rgba(137, 19, 19, 0.08) 0%, rgba(185, 28, 28, 0.04) 100%);
    }

    .unit-type-option.is-checked .unit-type-option-check,
    .unit-type-option:has(input:checked) .unit-type-option-check {
        border-color: #891313;
        background: #891313;
        color: #ffffff;
        box-shadow: 0 8px 18px rgba(137, 19, 19, 0.18);
    }

    .unit-type-option.is-checked .unit-type-option-text,
    .unit-type-option:has(input:checked) .unit-type-option-text {
        color: #7f1d1d;
    }

    .unit-type-selected {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
        min-height: 28px;
    }

    .unit-type-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: 12px;
        font-weight: 600;
    }

    .unit-type-empty {
        color: #94a3b8;
        font-size: 12px;
        font-style: italic;
    }

    .unit-form-actions {
        display: flex;
        justify-content: center;
        gap: 20px;
        padding-top: 22px;
        border-top: 1px solid #f1f5f9;
    }

    @media (max-width: 768px) {
        .unit-form-fields-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    (function () {
        const form = document.getElementById('unitForm');

        if (!form || form.dataset.unitFormInitialized === 'true') {
            return;
        }

        form.dataset.unitFormInitialized = 'true';

        const select = form.querySelector('[data-unit-type-select]');
        const trigger = form.querySelector('[data-unit-type-trigger]');
        const dropdown = form.querySelector('[data-unit-type-dropdown]');
        const searchInput = form.querySelector('[data-unit-type-search]');
        const options = Array.from(form.querySelectorAll('[data-unit-type-option]'));
        const selectedContainer = form.querySelector('[data-unit-type-selected]');
        const countLabel = form.querySelector('#unitTypeSelectionCount');

        const closeDropdown = () => {
            if (!select || !dropdown || !trigger) {
                return;
            }

            select.classList.remove('is-open');
            dropdown.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
        };

        const openDropdown = () => {
            if (!select || !dropdown || !trigger) {
                return;
            }

            select.classList.add('is-open');
            dropdown.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            window.setTimeout(() => searchInput?.focus(), 0);
        };

        const syncSelectionState = () => {
            let checkedCount = 0;
            const selectedLabels = [];

            options.forEach((option) => {
                const input = option.querySelector('input[type="checkbox"]');
                const isChecked = Boolean(input?.checked);

                option.classList.toggle('is-checked', isChecked);

                if (isChecked) {
                    checkedCount += 1;
                    selectedLabels.push(option.querySelector('.unit-type-option-text')?.textContent?.trim() || '');
                }
            });

            if (countLabel) {
                countLabel.textContent = checkedCount > 0
                    ? `${checkedCount} material dipilih`
                    : 'Belum ada material dipilih';
            }

            if (selectedContainer) {
                if (selectedLabels.length === 0) {
                    selectedContainer.innerHTML = '<span class="unit-type-empty">Belum ada material yang dipilih.</span>';
                } else {
                    selectedContainer.innerHTML = selectedLabels
                        .map((label) => `<span class="unit-type-chip">${label}</span>`)
                        .join('');
                }
            }
        };

        const syncSearchState = () => {
            const keyword = (searchInput?.value || '').trim().toLowerCase();

            options.forEach((option) => {
                const haystack = option.dataset.searchText || '';
                option.hidden = keyword !== '' && !haystack.includes(keyword);
            });
        };

        options.forEach((option) => {
            const input = option.querySelector('input[type="checkbox"]');

            if (!input) {
                return;
            }

            input.addEventListener('change', syncSelectionState);
        });

        trigger?.addEventListener('click', () => {
            if (dropdown?.hidden) {
                openDropdown();
                return;
            }

            closeDropdown();
        });

        searchInput?.addEventListener('input', syncSearchState);

        document.addEventListener('click', (event) => {
            if (!select || !select.contains(event.target)) {
                closeDropdown();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeDropdown();
            }
        });

        syncSelectionState();
        syncSearchState();
    })();
</script>
</div>
