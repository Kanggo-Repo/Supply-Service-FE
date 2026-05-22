// public/js/store-autocomplete.js
// Store autocomplete integration for material forms
// Integrates with existing autocomplete-input pattern

(function() {
    'use strict';

    function ensureKeyboardStyle() {
        if (document.getElementById('autocomplete-keyboard-style')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'autocomplete-keyboard-style';
        style.textContent = `
            .autocomplete-item.autocomplete-item-active {
                background: linear-gradient(to right, #fef2f2 0%, #fef8f8 100%) !important;
                color: #891313 !important;
                padding-left: 20px !important;
            }
        `;
        document.head.appendChild(style);
    }

    function initKeyboardAutocomplete(scope) {
        const root = (scope && scope.querySelectorAll) ? scope : document;
        ensureKeyboardStyle();

        root.querySelectorAll('.autocomplete-input').forEach(input => {
            if (input.__keyboardAutocompleteInited) {
                return;
            }

            const field = input.dataset?.field;
            if (!field) {
                return;
            }

            const list = root.querySelector('#' + field + '-list') || document.getElementById(field + '-list');
            if (!list) {
                return;
            }

            input.__keyboardAutocompleteInited = true;
            let activeIndex = -1;

            function getItems() {
                return Array.from(list.querySelectorAll('.autocomplete-item'))
                    .filter(item => item.dataset.autocompleteHint !== '1');
            }

            function resetActive() {
                activeIndex = -1;
                list.querySelectorAll('.autocomplete-item').forEach(item => {
                    item.classList.remove('autocomplete-item-active');
                });
            }

            function highlightByIndex(nextIndex) {
                const items = getItems();
                if (!items.length) {
                    resetActive();
                    return;
                }

                if (nextIndex < 0) {
                    nextIndex = items.length - 1;
                } else if (nextIndex >= items.length) {
                    nextIndex = 0;
                }

                activeIndex = nextIndex;
                items.forEach((item, index) => {
                    item.classList.toggle('autocomplete-item-active', index === activeIndex);
                });

                const activeItem = items[activeIndex];
                if (activeItem && typeof activeItem.scrollIntoView === 'function') {
                    activeItem.scrollIntoView({ block: 'nearest' });
                }
            }

            function isListVisible() {
                const style = window.getComputedStyle(list);
                return style.display !== 'none' && getItems().length > 0;
            }

            input.addEventListener('keydown', function(e) {
                if (!['ArrowDown', 'ArrowUp', 'Enter', 'Escape'].includes(e.key)) {
                    return;
                }

                const items = getItems();
                const visible = isListVisible();

                if (e.key === 'Escape' && visible) {
                    e.preventDefault();
                    e.stopPropagation();
                    list.style.display = 'none';
                    resetActive();
                    return;
                }

                if (!items.length) {
                    return;
                }

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    e.stopPropagation();
                    if (!visible) {
                        list.style.display = 'block';
                    }
                    highlightByIndex(activeIndex + 1);
                    return;
                }

                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    e.stopPropagation();
                    if (!visible) {
                        list.style.display = 'block';
                    }
                    highlightByIndex(activeIndex - 1);
                    return;
                }

                if (e.key === 'Enter' && visible && activeIndex >= 0) {
                    e.preventDefault();
                    e.stopPropagation();
                    items[activeIndex]?.click();
                    resetActive();
                }
            });

            input.addEventListener('input', resetActive);
            input.addEventListener('focus', resetActive);
        });
    }

    /**
     * Initialize store autocomplete for material forms
     * Call this after the form's own init function
     *
     * @param {HTMLElement|Document} scope - The form container or document
     */
    function initStoreAutocomplete(scope) {
        scope = scope || document;
        initKeyboardAutocomplete(scope);

        const storeInput = scope.querySelector('#store');
        const addressInput = scope.querySelector('#address');
        const storeList = scope.querySelector('#store-list');
        const addressList = scope.querySelector('#address-list');

        if (!storeInput || !storeList) {
            return; // No store fields found
        }

        // Find or create hidden input for store_location_id
        let storeLocationIdInput = scope.querySelector('input[name="store_location_id"]');
        if (!storeLocationIdInput) {
            const form = storeInput.closest('form');
            if (form) {
                storeLocationIdInput = document.createElement('input');
                storeLocationIdInput.type = 'hidden';
                storeLocationIdInput.name = 'store_location_id';
                storeLocationIdInput.id = 'store_location_id';
                form.appendChild(storeLocationIdInput);
            }
        }

        let storeDebounceTimer = null;
        let addressDebounceTimer = null;
        let isSelectingStore = false;
        let isSelectingAddress = false;
        let suppressAddressSuggest = false;
        let resolvedStoreLocations = [];
        let locationRow = null;
        let locationSelect = null;
        let locationHelp = null;

        // ========== HELPER FUNCTIONS ==========

        function normalizeText(value) {
            return String(value || '').trim().replace(/\s+/g, ' ').toLowerCase();
        }

        function getLocationResolvedAddress(location) {
            return String(
                location?.resolved_address ||
                location?.address ||
                location?.formatted_address ||
                location?.full_address ||
                ''
            ).trim();
        }

        function ensureLocationSelector() {
            if (locationRow && locationSelect && locationHelp) {
                return;
            }

            const anchorRow = (addressInput && addressInput.closest('.row')) || storeInput.closest('.row');
            if (!anchorRow) {
                return;
            }

            locationRow = document.createElement('div');
            locationRow.className = (anchorRow.className || 'row') + ' store-location-selector-row';
            if (anchorRow.getAttribute('style')) {
                locationRow.setAttribute('style', anchorRow.getAttribute('style'));
            }

            const anchorLabel = anchorRow.querySelector('label');
            const label = document.createElement('label');
            label.textContent = 'Lokasi Toko';
            if (anchorLabel && anchorLabel.getAttribute('style')) {
                label.setAttribute('style', anchorLabel.getAttribute('style'));
            }

            const anchorField = anchorRow.children[1];
            const fieldWrap = document.createElement('div');
            fieldWrap.style.cssText = anchorField?.getAttribute?.('style') || 'flex: 1; position: relative;';

            locationSelect = document.createElement('select');
            locationSelect.id = 'store_location_selector';
            locationSelect.style.cssText = 'width: 100%;';
            locationSelect.disabled = true;

            locationHelp = document.createElement('div');
            locationHelp.style.cssText = 'margin-top: 6px; font-size: 12px; color: #64748b;';

            fieldWrap.appendChild(locationSelect);
            fieldWrap.appendChild(locationHelp);
            locationRow.appendChild(label);
            locationRow.appendChild(fieldWrap);

            anchorRow.insertAdjacentElement('afterend', locationRow);

            locationSelect.addEventListener('change', function() {
                const selectedId = String(this.value || '');
                const selectedLocation = resolvedStoreLocations.find(location => String(location.id) === selectedId) || null;

                if (!selectedLocation) {
                    if (storeLocationIdInput) {
                        storeLocationIdInput.value = '';
                    }
                    if (addressInput) {
                        addressInput.value = '';
                    }
                    setLocationHelp('Pilih lokasi toko agar material tersimpan ke cabang yang benar.', 'error');
                    return;
                }

                applyLocationSelection(selectedLocation);
                setLocationHelp('Lokasi toko aktif dan akan dipakai saat simpan.', 'success');
            });
        }

        function setLocationHelp(message, tone) {
            ensureLocationSelector();
            if (!locationHelp) {
                return;
            }

            locationHelp.textContent = message || '';
            locationHelp.style.color = tone === 'error'
                ? '#b91c1c'
                : tone === 'success'
                    ? '#15803d'
                    : '#64748b';
        }

        function setAddressReadonly(readonly) {
            if (!addressInput) {
                return;
            }

            addressInput.readOnly = !!readonly;
            addressInput.style.backgroundColor = readonly ? '#f8fafc' : '';
            addressInput.style.cursor = readonly ? 'not-allowed' : '';
        }

        function clearLocationSelector(options) {
            const opts = options || {};
            resolvedStoreLocations = [];
            ensureLocationSelector();

            if (locationSelect) {
                locationSelect.innerHTML = '';
                locationSelect.disabled = true;
            }

            if (locationRow) {
                locationRow.style.display = 'none';
            }

            if (storeLocationIdInput) {
                storeLocationIdInput.value = '';
            }

            if (opts.clearAddress && addressInput) {
                addressInput.value = '';
            }

            setAddressReadonly(false);
            setLocationHelp('');
        }

        function applyLocationSelection(location) {
            if (!location) {
                return;
            }

            if (storeLocationIdInput) {
                storeLocationIdInput.value = location.id || '';
            }

            if (addressInput) {
                suppressAddressSuggest = true;
                addressInput.value = getLocationResolvedAddress(location);
                addressInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (locationSelect) {
                locationSelect.value = String(location.id || '');
            }
        }

        function matchLocation(locations, selectedId, selectedAddress) {
            const normalizedSelectedId = String(selectedId || '').trim();
            const normalizedSelectedAddress = normalizeText(selectedAddress);

            if (normalizedSelectedId) {
                const byId = locations.find(location => String(location.id) === normalizedSelectedId);
                if (byId) {
                    return byId;
                }
            }

            if (!normalizedSelectedAddress) {
                return null;
            }

            return locations.find(location => {
                const candidates = [
                    getLocationResolvedAddress(location),
                    location?.address,
                    location?.formatted_address,
                    location?.full_address,
                ];

                return candidates.some(candidate => normalizeText(candidate) === normalizedSelectedAddress);
            }) || null;
        }

        function renderLocationSelector(locations, options) {
            const opts = options || {};
            resolvedStoreLocations = Array.isArray(locations) ? locations : [];
            ensureLocationSelector();

            if (!locationRow || !locationSelect) {
                return;
            }

            if (!resolvedStoreLocations.length) {
                clearLocationSelector({ clearAddress: !opts.preserveAddress });
                return;
            }

            locationRow.style.display = '';
            locationSelect.disabled = false;
            locationSelect.innerHTML = '';

            const selectedLocation = matchLocation(
                resolvedStoreLocations,
                opts.selectedId,
                opts.selectedAddress,
            );

            if (resolvedStoreLocations.length > 1) {
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = '-- Pilih lokasi toko --';
                locationSelect.appendChild(placeholder);
            }

            resolvedStoreLocations.forEach(location => {
                const option = document.createElement('option');
                option.value = String(location.id);
                option.textContent = getLocationResolvedAddress(location) || ('Lokasi #' + location.id);
                locationSelect.appendChild(option);
            });

            setAddressReadonly(true);

            if (selectedLocation) {
                applyLocationSelection(selectedLocation);
                setLocationHelp('Lokasi toko aktif dan terhubung langsung ke data cabang.', 'success');
                return;
            }

            if (resolvedStoreLocations.length === 1) {
                applyLocationSelection(resolvedStoreLocations[0]);
                if (locationSelect) {
                    locationSelect.disabled = true;
                }
                setLocationHelp('Toko ini hanya memiliki satu lokasi aktif.', 'success');
                return;
            }

            if (locationSelect) {
                locationSelect.value = '';
            }
            if (storeLocationIdInput) {
                storeLocationIdInput.value = '';
            }
            if (addressInput) {
                addressInput.value = '';
            }
            setLocationHelp('Pilih lokasi toko agar material tersimpan ke cabang yang benar.', 'error');
        }

        function loadStoreLocations(storeName, options) {
            const resolvedStoreName = String(storeName || '').trim();

            if (!resolvedStoreName) {
                clearLocationSelector({ clearAddress: true });
                return Promise.resolve([]);
            }

            const url = '/api/stores/locations-by-store?store=' + encodeURIComponent(resolvedStoreName) + '&limit=20';

            return fetch(url)
                .then(resp => resp.json())
                .then(locations => {
                    renderLocationSelector(locations, options || {});
                    return Array.isArray(locations) ? locations : [];
                })
                .catch(err => {
                    console.error('Store location search error:', err);
                    clearLocationSelector({ clearAddress: false });
                    return [];
                });
        }

        /**
         * Get or create store_location_id for current store+address
         */
        function resolveStoreLocationId(storeName, address, callback) {
            if (!storeName) {
                if (callback) callback(null);
                return;
            }

            const input = storeName + (address ? ' - ' + address : '');

            fetch('/api/stores/quick-create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ input: input })
            })
            .then(resp => resp.json())
            .then(result => {
                if (result.id && storeLocationIdInput) {
                    storeLocationIdInput.value = result.id;
                    console.log('Store location resolved:', result.id, result.display_text);
                }
                if (callback) callback(result);
            })
            .catch(err => {
                console.error('Resolve store location error:', err);
                if (callback) callback(null);
            });
        }

        // ========== STORE FIELD ==========

        function populateStoreList(stores, searchTerm) {
            storeList.innerHTML = '';

            // Add existing stores
            stores.forEach(storeName => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.textContent = storeName;
                item.addEventListener('click', function() {
                    isSelectingStore = true;
                    storeInput.value = storeName;
                    storeList.style.display = 'none';
                    loadStoreLocations(storeName, {
                        selectedId: storeLocationIdInput ? storeLocationIdInput.value : '',
                        selectedAddress: addressInput ? addressInput.value : '',
                    });

                    setTimeout(() => { isSelectingStore = false; }, 300);
                });
                storeList.appendChild(item);
            });

            storeList.style.display = (storeList.children.length > 0) ? 'block' : 'none';
        }

        function loadStores(searchTerm) {
            const url = '/api/stores/all-stores?search=' + encodeURIComponent(searchTerm || '') + '&limit=20';

            fetch(url)
                .then(resp => resp.json())
                .then(stores => populateStoreList(stores, searchTerm))
                .catch(err => console.error('Store search error:', err));
        }

        // Store input events
        storeInput.addEventListener('focus', function() {
            if (!isSelectingStore) {
                loadStores('');
            }
        });

        storeInput.addEventListener('input', function() {
            if (isSelectingStore) return;

            clearTimeout(storeDebounceTimer);
            const term = this.value || '';
            storeDebounceTimer = setTimeout(() => loadStores(term), 200);

            // Clear store_location_id when store changes
            if (storeLocationIdInput) storeLocationIdInput.value = '';
            if (!term.trim()) {
                clearLocationSelector({ clearAddress: true });
            }
        });

        document.addEventListener('click', function(e) {
            if (e.target !== storeInput && !storeList.contains(e.target)) {
                storeList.style.display = 'none';
            }
        });

        // ========== ADDRESS FIELD ==========

        if (addressInput && addressList) {
            function populateAddressList(addresses, searchTerm, storeName) {
                addressList.innerHTML = '';

                // Add existing addresses
                addresses.forEach(addr => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = addr;
                    item.addEventListener('click', function() {
                        isSelectingAddress = true;
                        addressInput.value = addr;
                        addressList.style.display = 'none';

                        // Resolve store_location_id when address is selected
                        resolveStoreLocationId(storeName, addr);

                        setTimeout(() => { isSelectingAddress = false; }, 300);
                    });
                    addressList.appendChild(item);
                });

                // If no store selected, show hint
                if (!storeName) {
                    const hintItem = document.createElement('div');
                    hintItem.className = 'autocomplete-item';
                    hintItem.dataset.autocompleteHint = '1';
                    hintItem.style.color = '#94a3b8';
                    hintItem.style.fontStyle = 'italic';
                    hintItem.textContent = 'Pilih toko terlebih dahulu...';
                    addressList.innerHTML = '';
                    addressList.appendChild(hintItem);
                }

                addressList.style.display = (addressList.children.length > 0) ? 'block' : 'none';
            }

            function loadAddresses(searchTerm) {
                const storeName = storeInput.value.trim();

                if (addressInput.readOnly) {
                    return;
                }

                if (!storeName) {
                    populateAddressList([], searchTerm, '');
                    return;
                }

                const url = '/api/stores/addresses-by-store?store=' + encodeURIComponent(storeName) +
                           '&search=' + encodeURIComponent(searchTerm || '') + '&limit=20';

                fetch(url)
                    .then(resp => resp.json())
                    .then(addresses => populateAddressList(addresses, searchTerm, storeName))
                    .catch(err => console.error('Address search error:', err));
            }

            // Address input events
            addressInput.addEventListener('focus', function() {
                if (!isSelectingAddress) {
                    if (addressInput.readOnly) {
                        return;
                    }
                    if ((addressInput.value || '').trim().length > 0) {
                        return;
                    }
                    loadAddresses('');
                }
            });

            addressInput.addEventListener('input', function() {
                if (isSelectingAddress) return;
                if (suppressAddressSuggest) {
                    suppressAddressSuggest = false;
                    return;
                }
                if (addressInput.readOnly) {
                    return;
                }

                clearTimeout(addressDebounceTimer);
                const term = this.value || '';
                addressDebounceTimer = setTimeout(() => loadAddresses(term), 200);

                // Clear store_location_id when address changes (will be resolved on submit)
                if (storeLocationIdInput) storeLocationIdInput.value = '';
            });

            document.addEventListener('click', function(e) {
                if (e.target !== addressInput && !addressList.contains(e.target)) {
                    addressList.style.display = 'none';
                }
            });
        }

        // ========== RESOLVE STORE_LOCATION_ID ON SUBMIT ==========

        const form = storeInput.closest('form');
        if (form) {
            const initialStoreName = storeInput.value.trim();
            const initialAddress = addressInput ? addressInput.value.trim() : '';
            if (initialStoreName) {
                loadStoreLocations(initialStoreName, {
                    selectedId: storeLocationIdInput ? storeLocationIdInput.value : '',
                    selectedAddress: initialAddress,
                    preserveAddress: true,
                }).then(locations => {
                    if (!locations.length && initialAddress && storeLocationIdInput && !storeLocationIdInput.value) {
                        resolveStoreLocationId(initialStoreName, initialAddress);
                    }
                });
            }

            form.addEventListener('submit', function(e) {
                const storeName = storeInput.value.trim();
                const address = addressInput ? addressInput.value.trim() : '';

                if (storeName && resolvedStoreLocations.length > 1 && (!storeLocationIdInput || !storeLocationIdInput.value)) {
                    e.preventDefault();
                    ensureLocationSelector();
                    setLocationHelp('Toko ini memiliki beberapa lokasi. Pilih lokasi toko sebelum menyimpan.', 'error');
                    if (locationSelect) {
                        locationSelect.focus();
                    }

                    return;
                }

                if (storeName && resolvedStoreLocations.length === 1 && storeLocationIdInput && !storeLocationIdInput.value) {
                    applyLocationSelection(resolvedStoreLocations[0]);
                }

                // Always resolve store_location_id before submit if we have store name
                if (storeName && (!storeLocationIdInput || !storeLocationIdInput.value)) {
                    // Note: We DO NOT preventDefault() here because we want other submit handlers
                    // (like the confirmation dialog in index.blade.php) to run after this.
                    // We use synchronous XHR to ensure the ID is resolved BEFORE those handlers run.

                    const input = storeName + (address ? ' - ' + address : '');

                    // Use sync XHR to ensure store_location_id is set before form submits
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '/api/stores/quick-create', false); // sync
                    xhr.setRequestHeader('Content-Type', 'application/json');
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content || '');

                    try {
                        xhr.send(JSON.stringify({ input: input }));
                        const result = JSON.parse(xhr.responseText || '{}');

                        if (result.id && storeLocationIdInput) {
                            storeLocationIdInput.value = result.id;
                            console.log('Store location resolved on submit:', result.id);
                        } else if (xhr.status === 422 && result.requires_location_selection) {
                            e.preventDefault();
                            renderLocationSelector(result.locations || [], {
                                selectedAddress: address,
                            });
                            setLocationHelp('Pilih lokasi toko yang tersedia sebelum menyimpan material.', 'error');
                            if (locationSelect) {
                                locationSelect.focus();
                            }
                        }
                    } catch (err) {
                        console.error('Store resolve on submit failed:', err);
                    }

                    // No form.submit() call here - let the event propagate
                }
            });
        }
    }

    // Export to window
    window.initStoreAutocomplete = initStoreAutocomplete;

    // Auto-init on DOMContentLoaded if forms exist
    document.addEventListener('DOMContentLoaded', function() {
        // Check if there are store fields that need initialization
        const storeForms = document.querySelectorAll('form:has(#store)');
        storeForms.forEach(form => {
            // Delay to let form-specific JS run first
            setTimeout(() => initStoreAutocomplete(form), 100);
        });
    });
})();
