<div class="card">
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

    <form action="{{ route('pakus.store') }}" method="POST" enctype="multipart/form-data" id="pakuForm">
        @csrf

        <input type="hidden" name="material_name" id="material_name" value="{{ old('material_name') }}">
        <input type="hidden" name="comparison_price" id="comparison_price" value="{{ old('comparison_price') }}">
        <input type="hidden" name="package_price" id="package_price" value="{{ old('package_price') }}">
        <input type="hidden" name="package_weight" id="package_weight" value="{{ old('package_weight') }}">
        <input type="hidden" name="package_content" id="package_content" value="{{ old('package_content') }}">
        <input type="hidden" name="dimension_length" id="dimension_length" value="{{ old('dimension_length') }}">
        <input type="hidden" name="dimension_length_mm" id="dimension_length_mm" value="{{ old('dimension_length_mm') }}">
        <input type="hidden" name="dimension_body_diameter" id="dimension_body_diameter" value="{{ old('dimension_body_diameter') }}">
        <input type="hidden" name="dimension_head_diameter" id="dimension_head_diameter" value="{{ old('dimension_head_diameter') }}">

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <div>
                <div class="row">
                    <label>Jenis</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="type" id="type" value="{{ old('type') }}" class="autocomplete-input" data-field="type" autocomplete="off" placeholder="Pilih atau ketik jenis paku...">
                        <div class="autocomplete-list" id="type-list"></div>
                    </div>
                </div>

                <div class="row">
                    <label>Merek</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="brand" id="brand" value="{{ old('brand') }}" class="autocomplete-input" data-field="brand" autocomplete="off" placeholder="Pilih atau ketik merek...">
                        <div class="autocomplete-list" id="brand-list"></div>
                    </div>
                </div>

                <div class="row" style="align-items: flex-start; margin-top: 10px;">
                    <label style="padding-top: 8px;">Dimensi</label>
                    <div style="flex: 1; display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 8px;">
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-style: italic; font-size: 13px; margin-bottom: 2px;">Panjang (inci)</span>
                            <div class="dimensi-input-with-unit">
                                <input type="text" id="dimension_length_display" value="" class="autocomplete-input" inputmode="decimal" placeholder="0">
                                <select id="dimension_length_unit" name="dimension_length_unit" class="unit-selector">
                                    <option value="mm" {{ old('dimension_length_unit', 'inch') === 'mm' ? 'selected' : '' }}>mm</option>
                                    <option value="cm" {{ old('dimension_length_unit', 'inch') === 'cm' ? 'selected' : '' }}>cm</option>
                                    <option value="m" {{ old('dimension_length_unit', 'inch') === 'm' ? 'selected' : '' }}>M</option>
                                    <option value="inch" {{ old('dimension_length_unit', 'inch') === 'inch' ? 'selected' : '' }}>"</option>
                                </select>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-style: italic; font-size: 13px; margin-bottom: 2px;">Panjang (mm)</span>
                            <div class="dimensi-input-with-unit">
                                <input type="text" id="dimension_length_mm_display" value="" class="autocomplete-input" inputmode="decimal" placeholder="0">
                                <select id="dimension_length_mm_unit" name="dimension_length_mm_unit" class="unit-selector">
                                    <option value="mm" {{ old('dimension_length_mm_unit', 'mm') === 'mm' ? 'selected' : '' }}>mm</option>
                                    <option value="cm" {{ old('dimension_length_mm_unit', 'mm') === 'cm' ? 'selected' : '' }}>cm</option>
                                    <option value="m" {{ old('dimension_length_mm_unit', 'mm') === 'm' ? 'selected' : '' }}>M</option>
                                    <option value="inch" {{ old('dimension_length_mm_unit', 'mm') === 'inch' ? 'selected' : '' }}>"</option>
                                </select>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-style: italic; font-size: 13px; margin-bottom: 2px;">Diameter Badan</span>
                            <div class="dimensi-input-with-unit">
                                <input type="text" id="dimension_body_diameter_display" value="" class="autocomplete-input" inputmode="decimal" placeholder="0">
                                <select id="dimension_body_diameter_unit" name="dimension_body_diameter_unit" class="unit-selector">
                                    <option value="mm" {{ old('dimension_body_diameter_unit', 'cm') === 'mm' ? 'selected' : '' }}>mm</option>
                                    <option value="cm" {{ old('dimension_body_diameter_unit', 'cm') === 'cm' ? 'selected' : '' }}>cm</option>
                                    <option value="m" {{ old('dimension_body_diameter_unit', 'cm') === 'm' ? 'selected' : '' }}>M</option>
                                    <option value="inch" {{ old('dimension_body_diameter_unit', 'cm') === 'inch' ? 'selected' : '' }}>"</option>
                                </select>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-style: italic; font-size: 13px; margin-bottom: 2px;">Diameter Kepala</span>
                            <div class="dimensi-input-with-unit">
                                <input type="text" id="dimension_head_diameter_display" value="" class="autocomplete-input" inputmode="decimal" placeholder="0">
                                <select id="dimension_head_diameter_unit" name="dimension_head_diameter_unit" class="unit-selector">
                                    <option value="mm" {{ old('dimension_head_diameter_unit', 'mm') === 'mm' ? 'selected' : '' }}>mm</option>
                                    <option value="cm" {{ old('dimension_head_diameter_unit', 'mm') === 'cm' ? 'selected' : '' }}>cm</option>
                                    <option value="m" {{ old('dimension_head_diameter_unit', 'mm') === 'm' ? 'selected' : '' }}>M</option>
                                    <option value="inch" {{ old('dimension_head_diameter_unit', 'mm') === 'inch' ? 'selected' : '' }}>"</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <label>Warna</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="color" id="color" value="{{ old('color') }}" class="autocomplete-input" data-field="color" autocomplete="off" placeholder="Pilih atau ketik warna...">
                        <div class="autocomplete-list" id="color-list"></div>
                    </div>
                </div>

                <div class="row" style="align-items: stretch; margin-top: 15px;">
                    <label style="padding-top: 10px;">Kemasan</label>
                    <div style="flex: 1;">
                        <select name="package_unit" id="package_unit" style="width: 100%; height: 100%;">
                            <option value="">-- Pilih satuan kemasan --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->code }}"
                                    data-name="{{ $unit->name }}"
                                    {{ old('package_unit') == $unit->code ? 'selected' : '' }}>
                                    {{ $unit->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row" style="align-items: flex-start; margin-top: 10px;">
                    <label style="padding-top: 8px;">Isi Kemasan</label>
                    <div style="flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <div style="position: relative;">
                            <input type="text" id="package_weight_display" value="{{ old('package_weight') }}" class="autocomplete-input" inputmode="decimal" placeholder="Berat isi" style="padding-right: 42px;">
                            <span class="unit-inside" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #94a3b8; pointer-events: none;">Kg</span>
                        </div>
                        <div style="position: relative;">
                            <input type="text" id="package_content_display" value="{{ old('package_content') }}" class="autocomplete-input" inputmode="decimal" placeholder="Jumlah isi" style="padding-right: 46px;">
                            <span class="unit-inside" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #94a3b8; pointer-events: none;">Pcs</span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <label>Toko</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="store" id="store" value="{{ old('store') }}" class="autocomplete-input" data-field="store" autocomplete="off" placeholder="Pilih atau ketik nama toko...">
                        <div class="autocomplete-list" id="store-list"></div>
                    </div>
                </div>

                <div class="row">
                    <label>Alamat</label>
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="address" id="address" value="{{ old('address') }}" class="autocomplete-input" data-field="address" autocomplete="off" placeholder="Alamat toko...">
                        <div class="autocomplete-list" id="address-list"></div>
                    </div>
                </div>

                <div class="row" style="align-items: stretch; margin-top: 15px;">
                    <label style="padding-top: 10px;">Harga Beli</label>
                    <div style="flex: 1; display: flex; gap: 15px; align-items: stretch;">
                        <div style="flex: 1; display: flex; flex-direction: column; position: relative;">
                            <div style="flex: 1; display: flex; align-items: center; position: relative;">
                                <span class="price-prefix" style="position: absolute; left: 10px; font-size: 14px; font-weight: 600; color: #64748b; pointer-events: none; z-index: 1;">Rp</span>
                                <input type="text" id="package_price_display" value="{{ old('package_price') }}" class="autocomplete-input" data-field="package_price" inputmode="numeric" placeholder="0" autocomplete="off" style="width: 100%; height: 100%; padding: 10px 70px 10px 38px; font-size: 14px;">
                                <span id="price_unit_display_inline" class="price-suffix" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #94a3b8; pointer-events: none;">/ -</span>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; flex: 1; min-width: 0;">
                            <span style="font-size: 13px; font-style: italic; margin-bottom: 4px; color: #64748b;">Harga Komparasi</span>
                            <div style="display: flex; align-items: center; position: relative;">
                                <div style="flex: 1; display: flex; align-items: center; position: relative;">
                                    <span class="price-prefix" style="position: absolute; left: 10px; font-size: 14px; font-weight: 600; color: #64748b; pointer-events: none; z-index: 1;">Rp</span>
                                    <input type="text" id="comparison_price_display" value="{{ old('comparison_price') }}" class="autocomplete-input" data-field="comparison_price" inputmode="numeric" placeholder="0" autocomplete="off" style="width: 100%; height: 38px; padding: 10px 72px 10px 38px; font-size: 14px;">
                                    <span class="price-suffix" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #94a3b8; pointer-events: none;">/ Pcs</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="image-section" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div class="image-preview-box" id="photoPreviewArea" style="width: 100%; min-height: 200px; max-height: 400px; height: 320px; background-color: #ffffff; border: 2px dashed #e2e8f0; display: flex; align-items: center; justify-content: center; color: #cbd5e1; cursor: pointer; position: relative; overflow: hidden;">
                        <div id="photoPlaceholder" style="text-align: center;">
                            <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.6;">📷</div>
                            <div style="font-size: 14px; font-weight: 600; color: #64748b; margin-bottom: 6px;">Foto</div>
                        </div>
                        <img id="photoPreview" src="" alt="Preview" style="display: none; max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain;">
                    </div>

                    <input type="file" name="photo" id="photo" accept="image/*" style="display: none;">

                    <div class="image-actions" style="margin-top: 5px; display: flex; justify-content: center; font-weight: bold; font-size: 14px; padding: 0 10px; gap: 10px;">
                        <span class="text-upload" id="uploadBtn" style="color: #5cb85c; cursor: pointer;"><i class="bi bi-upload"></i> Upload</span>
                        <span class="text-delete" id="deletePhotoBtn" style="color: #d9534f; cursor: pointer; display: none;"><i class="bi bi-trash"></i> Hapus</span>
                    </div>
                </div>

                <div style="display: flex; justify-content: center; gap: 20px; padding-bottom: 15px;">
                    <button type="button" class="btn-cancel" onclick="if(typeof window.closeFloatingModal === 'function'){ window.closeFloatingModal(); }">Batal</button>
                    <button type="submit" class="btn-save">Simpan</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="/js/paku-form.js?v={{ time() }}"></script>
<script src="{{ asset('js/store-autocomplete.js') }}?v={{ time() }}"></script>
<script>
    if (typeof initPakuForm === 'function') {
        initPakuForm();
    }
    if (typeof initStoreAutocomplete === 'function') {
        initStoreAutocomplete(document.getElementById('pakuForm')?.parentElement);
    }
</script>
