function initPakuTembakForm(root) {
    const scope = root || document;
    const marker = scope.querySelector ? (scope.querySelector('#pakuTembakForm') || scope) : document;
    if (marker.__pakuTembakFormInited) return;
    marker.__pakuTembakFormInited = true;

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

        const url = `/api/paku_tembaks/field-values/${field}?search=${encodeURIComponent(term)}&limit=20`;
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

    ['type', 'brand', 'mesiu_code', 'mesiu_size', 'paku_code', 'paku_size'].forEach((field) => {
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

    const mesiuContentDisplay = scope.querySelector('#mesiu_content_display') || document.getElementById('mesiu_content_display');
    const pakuContentDisplay = scope.querySelector('#paku_content_display') || document.getElementById('paku_content_display');
    const mesiuContent = scope.querySelector('#mesiu_content') || document.getElementById('mesiu_content');
    const pakuContent = scope.querySelector('#paku_content') || document.getElementById('paku_content');

    const packagePrice = scope.querySelector('#package_price') || document.getElementById('package_price');
    const packagePriceDisplay = scope.querySelector('#package_price_display') || document.getElementById('package_price_display');
    const comparisonPrice = scope.querySelector('#comparison_price') || document.getElementById('comparison_price');
    const comparisonPriceDisplay = scope.querySelector('#comparison_price_display') || document.getElementById('comparison_price_display');
    const packageUnitSelect = scope.querySelector('#package_unit') || document.getElementById('package_unit');
    const priceUnitDisplay = scope.querySelector('#price_unit_display_inline') || document.getElementById('price_unit_display_inline');

    let isSyncingPrice = false;
    let lastEdited = 'price';

    const getTotalContent = () => {
        const mesiu = parseDecimal(mesiuContentDisplay?.value || '');
        const paku = parseDecimal(pakuContentDisplay?.value || '');
        const total = (Number.isFinite(mesiu) ? mesiu : 0) + (Number.isFinite(paku) ? paku : 0);
        return total > 0 ? total : 0;
    };

    const syncHiddenContentFields = () => {
        const mesiu = parseDecimal(mesiuContentDisplay?.value || '');
        const paku = parseDecimal(pakuContentDisplay?.value || '');
        if (mesiuContent) mesiuContent.value = Number.isFinite(mesiu) ? formatPlain(mesiu) : '';
        if (pakuContent) pakuContent.value = Number.isFinite(paku) ? formatPlain(paku) : '';
    };

    const syncPriceFields = () => {
        if (isSyncingPrice) return;
        isSyncingPrice = true;

        const totalContent = getTotalContent();
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

    [mesiuContentDisplay, pakuContentDisplay].filter(Boolean).forEach((el) => {
        el.addEventListener('input', () => {
            syncHiddenContentFields();
            syncPriceFields();
        });
        el.addEventListener('change', () => {
            syncHiddenContentFields();
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

    const form = scope.querySelector('#pakuTembakForm') || document.getElementById('pakuTembakForm');
    if (form && !form.__pakuTembakFormSubmitBound) {
        form.__pakuTembakFormSubmitBound = true;
        form.addEventListener('submit', () => {
            syncHiddenContentFields();
            syncPriceFields();
        });
    }

    syncHiddenContentFields();
    syncPriceFields();
}

