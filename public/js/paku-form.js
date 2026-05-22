function initPakuForm(root) {
    const scope = root || document;
    const marker = scope.querySelector ? (scope.querySelector('#pakuForm') || scope) : document;
    if (marker.__pakuFormInited) return;
    marker.__pakuFormInited = true;

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

    const loadAutocomplete = (field, term = '') => {
        const input = scope.querySelector(`#${field}`) || document.getElementById(field);
        const list = scope.querySelector(`#${field}-list`) || document.getElementById(`${field}-list`);
        if (!input || !list) return;

        const url = `/api/pakus/field-values/${field}?search=${encodeURIComponent(term)}&limit=20`;
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

    ['type', 'brand', 'color'].forEach((field) => {
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

    const packageContentDisplay = scope.querySelector('#package_content_display') || document.getElementById('package_content_display');
    const packageContent = scope.querySelector('#package_content') || document.getElementById('package_content');
    const packageWeightDisplay = scope.querySelector('#package_weight_display') || document.getElementById('package_weight_display');
    const packageWeight = scope.querySelector('#package_weight') || document.getElementById('package_weight');

    const packagePrice = scope.querySelector('#package_price') || document.getElementById('package_price');
    const packagePriceDisplay = scope.querySelector('#package_price_display') || document.getElementById('package_price_display');
    const comparisonPrice = scope.querySelector('#comparison_price') || document.getElementById('comparison_price');
    const comparisonPriceDisplay = scope.querySelector('#comparison_price_display') || document.getElementById('comparison_price_display');
    const packageUnitSelect = scope.querySelector('#package_unit') || document.getElementById('package_unit');
    const priceUnitDisplay = scope.querySelector('#price_unit_display_inline') || document.getElementById('price_unit_display_inline');
    const mmPerUnit = {
        mm: 1,
        cm: 10,
        m: 1000,
        inch: 25.4,
    };
    const dimensionConfigs = [
        {
            hiddenId: 'dimension_length',
            inputId: 'dimension_length_display',
            unitId: 'dimension_length_unit',
            baseUnit: 'inch',
        },
        {
            hiddenId: 'dimension_length_mm',
            inputId: 'dimension_length_mm_display',
            unitId: 'dimension_length_mm_unit',
            baseUnit: 'mm',
        },
        {
            hiddenId: 'dimension_body_diameter',
            inputId: 'dimension_body_diameter_display',
            unitId: 'dimension_body_diameter_unit',
            baseUnit: 'cm',
        },
        {
            hiddenId: 'dimension_head_diameter',
            inputId: 'dimension_head_diameter_display',
            unitId: 'dimension_head_diameter_unit',
            baseUnit: 'mm',
        },
    ];

    let isSyncingPrice = false;
    let lastEdited = 'price';

    const convertUnitValue = (value, fromUnit, toUnit) => {
        const num = parseDecimal(value);
        if (!Number.isFinite(num) || num < 0) return null;
        const fromFactor = mmPerUnit[fromUnit];
        const toFactor = mmPerUnit[toUnit];
        if (!Number.isFinite(fromFactor) || !Number.isFinite(toFactor) || toFactor === 0) return null;
        return (num * fromFactor) / toFactor;
    };

    const setupDimensionField = (config) => {
        const hiddenElement = scope.querySelector(`#${config.hiddenId}`) || document.getElementById(config.hiddenId);
        const inputElement = scope.querySelector(`#${config.inputId}`) || document.getElementById(config.inputId);
        const unitElement = scope.querySelector(`#${config.unitId}`) || document.getElementById(config.unitId);
        if (!hiddenElement || !inputElement || !unitElement) return;

        const syncToHidden = () => {
            const rawValue = inputElement.value.trim();
            if (rawValue === '') {
                hiddenElement.value = '';
                inputElement.style.borderColor = '#e2e8f0';
                return;
            }

            const converted = convertUnitValue(rawValue, unitElement.value, config.baseUnit);
            if (converted === null) {
                hiddenElement.value = '';
                inputElement.style.borderColor = '#e74c3c';
                return;
            }

            hiddenElement.value = formatPlain(converted);
            inputElement.style.borderColor = '#e2e8f0';
        };

        const syncToDisplay = () => {
            const rawHidden = hiddenElement.value;
            if (!rawHidden) {
                inputElement.value = '';
                inputElement.style.borderColor = '#e2e8f0';
                return;
            }

            const converted = convertUnitValue(rawHidden, config.baseUnit, unitElement.value);
            inputElement.value = converted === null ? '' : formatPlain(converted);
            inputElement.style.borderColor = '#e2e8f0';
        };

        inputElement.addEventListener('input', syncToHidden);
        inputElement.addEventListener('change', syncToHidden);
        unitElement.addEventListener('change', syncToHidden);
        inputElement.addEventListener('blur', () => {
            const parsed = parseDecimal(inputElement.value);
            if (Number.isFinite(parsed) && parsed >= 0) {
                inputElement.value = formatPlain(parsed);
            }
        });

        syncToDisplay();
        syncToHidden();
    };

    const syncDimensionFields = () => {
        dimensionConfigs.forEach((config) => {
            const hiddenElement = scope.querySelector(`#${config.hiddenId}`) || document.getElementById(config.hiddenId);
            const inputElement = scope.querySelector(`#${config.inputId}`) || document.getElementById(config.inputId);
            const unitElement = scope.querySelector(`#${config.unitId}`) || document.getElementById(config.unitId);
            if (!hiddenElement || !inputElement || !unitElement) return;

            const rawValue = inputElement.value.trim();
            if (rawValue === '') {
                hiddenElement.value = '';
                return;
            }

            const converted = convertUnitValue(rawValue, unitElement.value, config.baseUnit);
            hiddenElement.value = converted === null ? '' : formatPlain(converted);
        });
    };

    const syncHiddenFields = () => {
        const content = parseDecimal(packageContentDisplay?.value || '');
        const weight = parseDecimal(packageWeightDisplay?.value || '');
        if (packageContent) packageContent.value = Number.isFinite(content) ? formatPlain(content) : '';
        if (packageWeight) packageWeight.value = Number.isFinite(weight) ? formatPlain(weight) : '';
    };

    const syncPriceFields = () => {
        if (isSyncingPrice) return;
        isSyncingPrice = true;

        const content = parseDecimal(packageContentDisplay?.value || '');
        const totalContent = Number.isFinite(content) && content > 0 ? content : 0;
        const priceNum = Number(unformatRupiah(packagePriceDisplay?.value || ''));
        const comparisonNum = Number(unformatRupiah(comparisonPriceDisplay?.value || ''));
        const hasPrice = Number.isFinite(priceNum) && priceNum > 0;
        const hasComparison = Number.isFinite(comparisonNum) && comparisonNum > 0;

        if (totalContent > 0) {
            if (lastEdited === 'comparison' && hasComparison) {
                const computedPrice = comparisonNum * totalContent;
                if (packagePrice) packagePrice.value = formatPlain(computedPrice, 0);
                if (packagePriceDisplay) packagePriceDisplay.value = formatRupiah(computedPrice);
                if (comparisonPrice) comparisonPrice.value = formatPlain(comparisonNum, 0);
                if (comparisonPriceDisplay) comparisonPriceDisplay.value = formatRupiah(comparisonNum);
            } else if (hasPrice) {
                const computedComparison = priceNum / totalContent;
                if (packagePrice) packagePrice.value = formatPlain(priceNum, 0);
                if (packagePriceDisplay) packagePriceDisplay.value = formatRupiah(priceNum);
                if (comparisonPrice) comparisonPrice.value = formatPlain(computedComparison, 0);
                if (comparisonPriceDisplay) comparisonPriceDisplay.value = formatRupiah(computedComparison);
            }
        } else {
            if (packagePrice) packagePrice.value = hasPrice ? formatPlain(priceNum, 0) : '';
            if (packagePriceDisplay) packagePriceDisplay.value = hasPrice ? formatRupiah(priceNum) : '';
            if (comparisonPrice) comparisonPrice.value = hasComparison ? formatPlain(comparisonNum, 0) : '';
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = hasComparison ? formatRupiah(comparisonNum) : '';
        }

        isSyncingPrice = false;
    };

    [packageContentDisplay, packageWeightDisplay].filter(Boolean).forEach((el) => {
        el.addEventListener('input', () => {
            syncHiddenFields();
            syncPriceFields();
        });
        el.addEventListener('change', () => {
            syncHiddenFields();
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
    dimensionConfigs.forEach(setupDimensionField);

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

    const form = scope.querySelector('#pakuForm') || document.getElementById('pakuForm');
    if (form && !form.__pakuFormSubmitBound) {
        form.__pakuFormSubmitBound = true;
        form.addEventListener('submit', () => {
            syncDimensionFields();
            syncHiddenFields();
            syncPriceFields();
        });
    }

    syncDimensionFields();
    syncHiddenFields();
    syncPriceFields();
}
