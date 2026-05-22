(function () {
    const AUTOCOMPLETE_SELECTOR = '.autocomplete-list';
    let rafId = null;

    function isVisible(el) {
        if (!el) return false;
        const style = window.getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden') return false;
        return true;
    }

    function getAnchorElement(listEl) {
        if (listEl.__autocompleteAnchorEl instanceof HTMLElement) {
            return listEl.__autocompleteAnchorEl;
        }

        const container = listEl.closest('.work-type-autocomplete');
        if (container) {
            const input = container.querySelector('.autocomplete-input');
            return input || container;
        }

        const prev = listEl.previousElementSibling;
        if (prev) {
            const input = prev.matches && prev.matches('input, .autocomplete-input')
                ? prev
                : prev.querySelector
                    ? prev.querySelector('input, .autocomplete-input')
                    : null;
            return input || prev;
        }

        return listEl.parentElement || listEl;
    }

    function parsePx(value, fallback) {
        const parsed = Number.parseFloat(String(value || '').replace('px', ''));
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function updateOne(listEl) {
        if (listEl.dataset.floatingPortal === '1') {
            return;
        }

        if (!isVisible(listEl)) {
            listEl.classList.remove('autocomplete-list--up');
            listEl.style.removeProperty('max-height');
            listEl.style.removeProperty('position');
            listEl.style.removeProperty('top');
            listEl.style.removeProperty('bottom');
            listEl.style.removeProperty('left');
            listEl.style.removeProperty('width');
            listEl.style.removeProperty('min-width');
            listEl.style.removeProperty('max-width');
            listEl.style.removeProperty('z-index');
            return;
        }

        const anchorEl = getAnchorElement(listEl);
        const anchorRect = anchorEl.getBoundingClientRect();
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;

        const computed = window.getComputedStyle(listEl);
        const defaultMaxHeight = parsePx(computed.maxHeight, 240);
        const preferredHeight = Math.min(listEl.scrollHeight || defaultMaxHeight, defaultMaxHeight);

        const safeMargin = 12;
        const gap = 4;
        const minListHeight = 120;
        const spaceAbove = Math.max(0, anchorRect.top - safeMargin);
        const spaceBelow = Math.max(0, viewportHeight - anchorRect.bottom - safeMargin);

        const shouldOpenUp = spaceBelow < Math.min(preferredHeight, 200) && spaceAbove > spaceBelow;
        listEl.classList.toggle('autocomplete-list--up', shouldOpenUp);

        const maxAvailable = shouldOpenUp ? spaceAbove : spaceBelow;
        if (maxAvailable > minListHeight) {
            listEl.style.maxHeight = Math.floor(maxAvailable) + 'px';
        } else {
            listEl.style.maxHeight = defaultMaxHeight + 'px';
        }

        const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
        const anchorWidth = Math.max(anchorRect.width || 0, 320);
        const maxWidth = Math.max(240, viewportWidth - (safeMargin * 2));
        const width = Math.min(Math.max(anchorWidth, 240), maxWidth);
        const maxLeft = Math.max(safeMargin, viewportWidth - width - safeMargin);
        const left = Math.max(safeMargin, Math.min(anchorRect.left, maxLeft));

        listEl.style.position = 'fixed';
        listEl.style.left = Math.round(left) + 'px';
        listEl.style.width = Math.round(width) + 'px';
        listEl.style.minWidth = Math.round(width) + 'px';
        listEl.style.maxWidth = Math.round(maxWidth) + 'px';
        listEl.style.zIndex = '20000';

        if (shouldOpenUp) {
            listEl.style.top = 'auto';
            listEl.style.bottom = Math.max(safeMargin, Math.round(viewportHeight - anchorRect.top + gap)) + 'px';
            return;
        }

        listEl.style.bottom = 'auto';
        listEl.style.top = Math.max(safeMargin, Math.round(anchorRect.bottom + gap)) + 'px';
    }

    function updateAll() {
        document.querySelectorAll(AUTOCOMPLETE_SELECTOR).forEach(updateOne);
    }

    function scheduleUpdate() {
        if (rafId !== null) return;
        rafId = window.requestAnimationFrame(function () {
            rafId = null;
            updateAll();
        });
    }

    function bindGlobalEvents() {
        document.addEventListener('focusin', scheduleUpdate, true);
        document.addEventListener('input', scheduleUpdate, true);
        document.addEventListener('click', scheduleUpdate, true);
        window.addEventListener('resize', scheduleUpdate);
        window.addEventListener('scroll', scheduleUpdate, true);
    }

    function bindMutationObserver() {
        if (!window.MutationObserver || !document.body) return;

        const observer = new MutationObserver(function (mutations) {
            for (const mutation of mutations) {
                if (mutation.type === 'childList') {
                    const hasAutocomplete = Array.from(mutation.addedNodes || []).some(function (node) {
                        return node instanceof Element && (
                            node.matches?.(AUTOCOMPLETE_SELECTOR) ||
                            node.querySelector?.(AUTOCOMPLETE_SELECTOR)
                        );
                    });
                    if (hasAutocomplete) {
                        scheduleUpdate();
                        return;
                    }
                }

                if (mutation.type === 'attributes') {
                    const target = mutation.target;
                    if (!(target instanceof Element)) continue;
                    if (target.matches(AUTOCOMPLETE_SELECTOR) || target.querySelector?.(AUTOCOMPLETE_SELECTOR)) {
                        scheduleUpdate();
                        return;
                    }
                }
            }
        });

        observer.observe(document.body, {
            subtree: true,
            childList: true,
            attributes: true,
            attributeFilter: ['style', 'class'],
        });
    }

    function init() {
        bindGlobalEvents();
        bindMutationObserver();
        scheduleUpdate();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
