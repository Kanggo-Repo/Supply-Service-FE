function initKasaGypsumForm(root) {
    const scope = root || document;
    const marker = scope.querySelector ? (scope.querySelector('#kasaGypsumForm') || scope) : document;
    if (marker.__kasaGypsumFormInited) return;
    marker.__kasaGypsumFormInited = true;

    const parseDecimal = (value) => {
        if (typeof value === 'number') return Number.isFinite(value) ? value : NaN;
        if (typeof value !== 'string') return NaN;
        let str = value.trim();
        if (str === '') return NaN;
        str = str.replace(/\s/g, '');
        if (str.includes(',') && str.includes('.')) {
            if (str.lastIndexOf(',') > str.lastIndexOf('.')) {
                str = str.replace(/\./g, '').replace(/,/g, '.');
            } else {
                str = str.replace(/,/g, '');
            }
        } else if (str.includes(',')) {
            str = str.replace(/,/g, '.');
        }
        str = str.replace(/[^0-9.-]/g, '');
        const num = Number(str);
        return Number.isFinite(num) ? num : NaN;
    };

    const formatPlain = (value, decimals = 15) => {
        const num = Number(value);
        if (!Number.isFinite(num)) return '';
        return num.toFixed(decimals).replace(/(\.\d*?[1-9])0+$/, '$1').replace(/\.0+$/, '');
    };

    const formatRupiah = (value) => {
        const num = Number(value);
        if (!Number.isFinite(num)) return '';
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    };

    const unformatRupiah = (value) => String(value || '').replace(/\./g, '').replace(/[^0-9]/g, '');

    const convertToMeters = (value, unit) => {
        const num = parseDecimal(value);
        if (!Number.isFinite(num)) return NaN;
        if (unit === 'mm') return num / 1000;
        if (unit === 'cm') return num / 100;
        if (unit === 'inch') return num * 0.0254;
        return num;
    };

    const convertToCentimeters = (value, unit) => {
        const num = parseDecimal(value);
        if (!Number.isFinite(num)) return NaN;
        if (unit === 'mm') return num / 10;
        if (unit === 'm') return num * 100;
        if (unit === 'inch') return num * 2.54;
        return num;
    };

    const loadAutocomplete = (field, term = '') => {
        const input = scope.querySelector(`#${field}`) || document.getElementById(field);
        const list = scope.querySelector(`#${field}-list`) || document.getElementById(`${field}-list`);
        if (!input || !list) return;

        const url = `/api/kasa_gypsums/field-values/${field}?search=${encodeURIComponent(term)}&limit=20`;
        fetch(url)
            .then((resp) => resp.json())
            .then((values) => {
                list.innerHTML = '';
                values.forEach((value) => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = value;
                    item.addEventListener('click', () => {
                        input.value = value;
                        list.style.display = 'none';
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                    list.appendChild(item);
                });
                list.style.display = values.length ? 'block' : 'none';
            })
            .catch(() => {});
    };

    ['type', 'brand'].forEach((field) => {
        const input = scope.querySelector(`#${field}`) || document.getElementById(field);
        const list = scope.querySelector(`#${field}-list`) || document.getElementById(`${field}-list`);
        if (!input || !list) return;
        let timer;
        input.addEventListener('focus', () => loadAutocomplete(field, ''));
        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => loadAutocomplete(field, input.value || ''), 220);
        });
        document.addEventListener('click', (e) => {
            if (e.target !== input && !list.contains(e.target)) {
                list.style.display = 'none';
            }
        });
    });

    const dimLengthInput = scope.querySelector('#dimension_length_input') || document.getElementById('dimension_length_input');
    const dimWidthInput = scope.querySelector('#dimension_width_input') || document.getElementById('dimension_width_input');
    const dimLengthUnit = scope.querySelector('#dimension_length_unit') || document.getElementById('dimension_length_unit');
    const dimWidthUnit = scope.querySelector('#dimension_width_unit') || document.getElementById('dimension_width_unit');
    const dimLength = scope.querySelector('#dimension_length') || document.getElementById('dimension_length');
    const dimWidth = scope.querySelector('#dimension_width') || document.getElementById('dimension_width');

    const packagePrice = scope.querySelector('#package_price') || document.getElementById('package_price');
    const packagePriceDisplay = scope.querySelector('#package_price_display') || document.getElementById('package_price_display');
    const comparisonPrice = scope.querySelector('#comparison_price_per_m') || document.getElementById('comparison_price_per_m');
    const comparisonPriceDisplay = scope.querySelector('#comparison_price_display') || document.getElementById('comparison_price_display');
    const packageUnitSelect = scope.querySelector('#package_unit') || document.getElementById('package_unit');
    const priceUnitDisplay = scope.querySelector('#price_unit_display_inline') || document.getElementById('price_unit_display_inline');

    let comparisonBase = 0;
    let isSyncingPrice = false;
    let lastEdited = 'price';

    const syncDimensionHiddenFields = () => {
        const lengthM = convertToMeters(dimLengthInput?.value || '', dimLengthUnit?.value || 'm');
        const widthCm = convertToCentimeters(dimWidthInput?.value || '', dimWidthUnit?.value || 'cm');

        if (dimLength) dimLength.value = Number.isFinite(lengthM) ? formatPlain(lengthM) : '';
        if (dimWidth) dimWidth.value = Number.isFinite(widthCm) ? formatPlain(widthCm) : '';

        if (Number.isFinite(lengthM) && lengthM > 0) {
            comparisonBase = lengthM;
        } else {
            comparisonBase = 0;
        }
    };

    const syncPriceFields = () => {
        if (isSyncingPrice) return;
        isSyncingPrice = true;

        const priceNum = Number(unformatRupiah(packagePriceDisplay?.value || ''));
        const comparisonNum = Number(unformatRupiah(comparisonPriceDisplay?.value || ''));
        const hasPrice = Number.isFinite(priceNum) && priceNum > 0;
        const hasComparison = Number.isFinite(comparisonNum) && comparisonNum > 0;

        if (comparisonBase > 0) {
            if (lastEdited === 'comparison' && hasComparison) {
                const computedPrice = comparisonNum * comparisonBase;
                if (packagePrice) packagePrice.value = formatPlain(computedPrice, 0);
                if (packagePriceDisplay) packagePriceDisplay.value = formatRupiah(computedPrice);
                if (comparisonPrice) comparisonPrice.value = formatPlain(comparisonNum, 0);
                if (comparisonPriceDisplay) comparisonPriceDisplay.value = formatRupiah(comparisonNum);
            } else if (hasPrice) {
                const computedComparison = priceNum / comparisonBase;
                if (packagePrice) packagePrice.value = formatPlain(priceNum, 0);
                if (packagePriceDisplay) packagePriceDisplay.value = formatRupiah(priceNum);
                if (comparisonPrice) comparisonPrice.value = formatPlain(computedComparison, 0);
                if (comparisonPriceDisplay) comparisonPriceDisplay.value = formatRupiah(computedComparison);
            }
        } else {
            if (hasPrice) {
                if (packagePrice) packagePrice.value = formatPlain(priceNum, 0);
                if (packagePriceDisplay) packagePriceDisplay.value = formatRupiah(priceNum);
            } else {
                if (packagePrice) packagePrice.value = '';
                if (packagePriceDisplay) packagePriceDisplay.value = '';
            }

            if (hasComparison) {
                if (comparisonPrice) comparisonPrice.value = formatPlain(comparisonNum, 0);
                if (comparisonPriceDisplay) comparisonPriceDisplay.value = formatRupiah(comparisonNum);
            } else {
                if (comparisonPrice) comparisonPrice.value = '';
                if (comparisonPriceDisplay) comparisonPriceDisplay.value = '';
            }
        }

        isSyncingPrice = false;
    };

    [dimLengthInput, dimWidthInput, dimLengthUnit, dimWidthUnit]
        .filter(Boolean)
        .forEach((el) => {
            el.addEventListener('input', () => {
                syncDimensionHiddenFields();
                syncPriceFields();
            });
            el.addEventListener('change', () => {
                syncDimensionHiddenFields();
                syncPriceFields();
            });
        });

    packagePriceDisplay?.addEventListener('input', () => {
        lastEdited = 'price';
        syncPriceFields();
    });
    comparisonPriceDisplay?.addEventListener('input', () => {
        lastEdited = 'comparison';
        syncPriceFields();
    });

    const updatePriceUnitLabel = () => {
        if (!packageUnitSelect || !priceUnitDisplay) return;
        const selected = packageUnitSelect.selectedOptions[0];
        const unitName = selected?.dataset?.name || selected?.text || '';
        priceUnitDisplay.textContent = unitName && unitName !== '-- Pilih satuan kemasan --' ? `/ ${unitName}` : '/ -';
    };
    packageUnitSelect?.addEventListener('change', updatePriceUnitLabel);
    updatePriceUnitLabel();

    const photoInput = scope.querySelector('#photo') || document.getElementById('photo');
    const photoPreview = scope.querySelector('#photoPreview') || document.getElementById('photoPreview');
    const photoPlaceholder = scope.querySelector('#photoPlaceholder') || document.getElementById('photoPlaceholder');
    const photoPreviewArea = scope.querySelector('#photoPreviewArea') || document.getElementById('photoPreviewArea');
    const uploadBtn = scope.querySelector('#uploadBtn') || document.getElementById('uploadBtn');
    const deletePhotoBtn = scope.querySelector('#deletePhotoBtn') || document.getElementById('deletePhotoBtn');

    photoPreviewArea?.addEventListener('click', () => photoInput?.click());
    uploadBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        photoInput?.click();
    });
    photoInput?.addEventListener('change', function () {
        if (!this.files || !this.files[0]) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            if (photoPreview) {
                photoPreview.src = e.target?.result || '';
                photoPreview.style.display = 'block';
            }
            if (photoPlaceholder) photoPlaceholder.style.display = 'none';
            if (deletePhotoBtn) deletePhotoBtn.style.display = 'inline';
        };
        reader.readAsDataURL(this.files[0]);
    });
    deletePhotoBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        if (photoInput) photoInput.value = '';
        if (photoPreview) {
            photoPreview.src = '';
            photoPreview.style.display = 'none';
        }
        if (photoPlaceholder) photoPlaceholder.style.display = 'block';
        deletePhotoBtn.style.display = 'none';
    });

    const form = scope.querySelector('#kasaGypsumForm') || document.getElementById('kasaGypsumForm');
    if (form && !form.__kasaGypsumFormSubmitBound) {
        form.__kasaGypsumFormSubmitBound = true;
        form.addEventListener('submit', () => {
            syncDimensionHiddenFields();
            syncPriceFields();
            if (comparisonPriceDisplay && comparisonPrice) {
                const raw = unformatRupiah(comparisonPriceDisplay.value || '');
                comparisonPrice.value = raw ? formatPlain(raw, 0) : comparisonPrice.value;
            }
        });
    }

    syncDimensionHiddenFields();
    syncPriceFields();
}
