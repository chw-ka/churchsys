/**
 * ChurchSys 教會系統易 — Responsive behaviour + PWA glue
 *
 * Pairs with public/css/responsive.css. Loaded after jQuery and the
 * legacy jqueryslidemenu.js. Desktop (>1024px) behaviour is untouched;
 * everything below activates inside a <=1024px viewport.
 *
 * Responsibilities:
 *   1. Wrap every table in a horizontal-scroll container and freeze
 *      the first N columns (data-freeze-cols, default 1) on <=1024px.
 *   2. Replace the legacy hover menu with a tap-friendly hamburger
 *      accordion on <=1024px.
 *   3. Show an "install app" menu item when the browser allows it.
 *   4. Register the service worker (PWA).
 */
(function () {
    'use strict';

    var MOBILE_MAX = 1024;

    function isMobileWidth() {
        return window.innerWidth <= MOBILE_MAX;
    }

    function debounce(fn, ms) {
        var t;
        return function () {
            clearTimeout(t);
            t = setTimeout(fn, ms);
        };
    }

    /* =================================================================
     * 1. Tables: horizontal scroll + frozen first columns
     * ================================================================= */

    function wrapTable(table) {
        if (table.parentNode && table.parentNode.classList &&
            table.parentNode.classList.contains('table-scroll')) {
            return table.parentNode;
        }
        var wrapper = document.createElement('div');
        wrapper.className = 'table-scroll';
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
        return wrapper;
    }

    function cellBackground(row, cell) {
        var bg = window.getComputedStyle(cell).backgroundColor;
        if (!bg || bg === 'rgba(0, 0, 0, 0)' || bg === 'transparent') {
            bg = window.getComputedStyle(row).backgroundColor;
        }
        if (!bg || bg === 'rgba(0, 0, 0, 0)' || bg === 'transparent') {
            bg = '#ffffff';
        }
        return bg;
    }

    function applySticky(table) {
        var cols = parseInt(table.getAttribute('data-freeze-cols') || '1', 10);
        if (!isMobileWidth() || cols < 1 || !table.rows.length) {
            clearSticky(table);
            return;
        }
        var refRow = (table.tHead && table.tHead.rows.length)
            ? table.tHead.rows[0]
            : table.rows[0];
        // Freezing a column on a tiny table adds noise without benefit
        if (refRow.cells.length <= 3) {
            clearSticky(table);
            return;
        }
        var tableLeft = table.getBoundingClientRect().left;
        var lefts = [];
        for (var i = 0; i < cols && i < refRow.cells.length; i++) {
            lefts.push(refRow.cells[i].getBoundingClientRect().left - tableLeft);
        }
        for (var r = 0; r < table.rows.length; r++) {
            var row = table.rows[r];
            for (var c = 0; c < lefts.length && c < row.cells.length; c++) {
                var cell = row.cells[c];
                cell.classList.add('js-sticky');
                cell.style.left = lefts[c] + 'px';
                cell.style.backgroundColor = cellBackground(row, cell);
            }
        }
    }

    function clearSticky(table) {
        var stuck = table.querySelectorAll('.js-sticky');
        for (var i = 0; i < stuck.length; i++) {
            stuck[i].classList.remove('js-sticky');
            stuck[i].style.left = '';
            stuck[i].style.backgroundColor = '';
        }
    }

    function setupTables() {
        var tables = document.querySelectorAll('table');
        for (var i = 0; i < tables.length; i++) {
            wrapTable(tables[i]);
            applySticky(tables[i]);
        }
    }

    /* =================================================================
     * 2. Navigation: hamburger accordion on mobile
     * ================================================================= */

    function injectToggle() {
        var headerBox = document.getElementById('header-box');
        if (!headerBox || document.getElementById('nav-toggle')) {
            return;
        }
        var btn = document.createElement('button');
        btn.id = 'nav-toggle';
        btn.className = 'nav-toggle';
        btn.type = 'button';
        btn.setAttribute('aria-label', '開合選單');
        btn.setAttribute('aria-expanded', 'false');
        btn.innerHTML = '<span></span><span></span><span></span>';
        headerBox.insertBefore(btn, headerBox.firstChild);

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = document.body.classList.toggle('nav-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        // Tap outside the header closes the menu
        document.addEventListener('click', function (e) {
            if (document.body.classList.contains('nav-open') &&
                !e.target.closest('#header-box')) {
                document.body.classList.remove('nav-open');
                btn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function setupMenu() {
        var menu = document.getElementById('myslidemenu');
        if (!menu || !window.jQuery) {
            return;
        }
        var $menu = window.jQuery(menu);

        // Neutralise the legacy hover/click handlers on mobile. They are
        // bound on document-ready by jqueryslidemenu.js, which runs before
        // this script, so unbinding here is safe and definitive.
        function neutralizeLegacy() {
            if (isMobileWidth()) {
                $menu.find('li').off('mouseenter mouseleave click');
            }
        }

        // Accordion: tapping a parent item toggles its submenu (mobile only;
        // on desktop the legacy hover menu keeps working).
        document.addEventListener('click', function (e) {
            if (!isMobileWidth()) {
                return;
            }
            var li = e.target.closest('#myslidemenu li');
            if (!li) {
                return;
            }
            var sub = li.querySelector(':scope > ul');
            if (sub) {
                e.preventDefault();
                e.stopPropagation();
                li.classList.toggle('has-open');
            }
        });

        // Timing: with `defer`, this script executes at readyState
        // "interactive", i.e. BEFORE jQuery's document-ready callbacks,
        // where jqueryslidemenu.js binds its hover/click handlers. Arm the
        // unbind on a macrotask so it always runs AFTER the legacy menu
        // has bound (DOMContentLoaded listeners -> then timeouts), and
        // re-arm on resize in case handlers get re-bound.
        function neutralizeAfterLegacy() {
            setTimeout(neutralizeLegacy, 0);
        }
        neutralizeAfterLegacy();
        window.jQuery(window).on('resize', debounce(neutralizeAfterLegacy, 150));
    }

    /* =================================================================
     * 3. PWA install prompt
     * ================================================================= */

    var deferredPrompt = null;

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        var items = document.querySelectorAll('.install-item');
        for (var i = 0; i < items.length; i++) {
            items[i].classList.add('is-available');
        }
    });

    document.addEventListener('click', function (e) {
        var item = e.target.closest ? e.target.closest('.install-item') : null;
        if (!item || !deferredPrompt) {
            return;
        }
        e.preventDefault();
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(function () {
            deferredPrompt = null;
            item.classList.remove('is-available');
        });
    });

    /* =================================================================
     * 4. Service worker
     * ================================================================= */

    function registerSW() {
        if (!('serviceWorker' in navigator) || location.protocol !== 'https:') {
            return;
        }
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () {
                /* PWA is an enhancement; never break the page over it */
            });
        });
    }

    /* =================================================================
     * Boot
     * ================================================================= */

    function boot() {
        setupTables();
        injectToggle();
        setupMenu();
        registerSW();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    // Recompute frozen-column offsets when the layout shifts
    window.addEventListener('resize', debounce(function () {
        var tables = document.querySelectorAll('table');
        for (var i = 0; i < tables.length; i++) {
            applySticky(tables[i]);
        }
    }, 200));
    window.addEventListener('load', function () {
        var tables = document.querySelectorAll('table');
        for (var i = 0; i < tables.length; i++) {
            applySticky(tables[i]);
        }
    });
})();
