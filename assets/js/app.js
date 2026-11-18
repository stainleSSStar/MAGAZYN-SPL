/* =====================================================================
   Panel Magazynu - skrypty pomocnicze
   ===================================================================== */
(function () {
    'use strict';

    /* ==============================================================
       |  KALENDARZ (date picker)
       | ============================================================== */
    var MONTHS = ['styczeń', 'luty', 'marzec', 'kwiecień', 'maj', 'czerwiec',
                  'lipiec', 'sierpień', 'wrzesień', 'październik', 'listopad', 'grudzień'];
    var DOW = ['pn', 'wt', 'śr', 'cz', 'pt', 'so', 'nd'];

    var calendar = null;   // aktywny popup
    var calInput = null;   // input, do którego przypisano kalendarz
    var calYear = 0, calMonth = 0;

    function pad(n) { return (n < 10 ? '0' : '') + n; }

    function isoDate(y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); }

    function buildCalendar() {
        var box = document.createElement('div');
        box.className = 'cal';
        box.innerHTML =
            '<div class="cal__head">' +
                '<button type="button" class="cal__nav" data-nav="prev" title="Poprzedni miesiąc">‹</button>' +
                '<span class="cal__title" data-title></span>' +
                '<button type="button" class="cal__nav" data-nav="next" title="Następny miesiąc">›</button>' +
            '</div>' +
            '<div class="cal__grid" data-grid></div>' +
            '<div class="cal__foot">' +
                '<button type="button" class="btn btn--sm btn--ghost" data-act="today">Dzisiaj</button>' +
                '<button type="button" class="btn btn--sm btn--ghost" data-act="clear">Wyczyść</button>' +
            '</div>';
        document.body.appendChild(box);
        return box;
    }

    function renderCalendar() {
        var grid = calendar.querySelector('[data-grid]');
        var title = calendar.querySelector('[data-title]');
        title.textContent = MONTHS[calMonth] + ' ' + calYear;

        var first = new Date(calYear, calMonth, 1);
        var offset = (first.getDay() + 6) % 7;   // tydzień od poniedziałku
        var daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
        var daysInPrev = new Date(calYear, calMonth, 0).getDate();

        var html = '';
        DOW.forEach(function (d) { html += '<div class="cal__dow">' + d + '</div>'; });

        var today = new Date();
        var todayIso = isoDate(today.getFullYear(), today.getMonth(), today.getDate());
        var selected = calInput ? calInput.value : '';

        for (var i = 0; i < offset; i++) {
            var dayPrev = daysInPrev - offset + i + 1;
            html += '<button type="button" class="cal__day is-other" data-date="' +
                    isoDate(calMonth === 0 ? calYear - 1 : calYear, calMonth === 0 ? 11 : calMonth - 1, dayPrev) +
                    '">' + dayPrev + '</button>';
        }
        for (var d = 1; d <= daysInMonth; d++) {
            var iso = isoDate(calYear, calMonth, d);
            var cls = 'cal__day';
            if (iso === todayIso) { cls += ' is-today'; }
            if (iso === selected) { cls += ' is-selected'; }
            html += '<button type="button" class="' + cls + '" data-date="' + iso + '">' + d + '</button>';
        }
        var used = offset + daysInMonth;
        var tail = (7 - (used % 7)) % 7;
        for (var k = 1; k <= tail; k++) {
            html += '<button type="button" class="cal__day is-other" data-date="' +
                    isoDate(calMonth === 11 ? calYear + 1 : calYear, calMonth === 11 ? 0 : calMonth + 1, k) + '">' + k + '</button>';
        }
        grid.innerHTML = html;
    }

    function positionCalendar() {
        if (!calendar || !calInput) { return; }
        var rect = calInput.getBoundingClientRect();
        var top = rect.bottom + window.scrollY + 4;
        var left = rect.left + window.scrollX;

        calendar.style.position = 'absolute';
        calendar.style.top = top + 'px';
        calendar.style.left = left + 'px';

        // Poprawa pozycji, gdy kalendarz wychodzi poza prawą krawędź
        calendar.style.visibility = 'hidden';
        calendar.style.display = 'block';
        var cw = calendar.offsetWidth;
        var maxLeft = document.documentElement.clientWidth - cw - 12;
        if (left > maxLeft) { left = Math.max(8, maxLeft); }
        calendar.style.left = left + 'px';
        calendar.style.visibility = 'visible';
    }

    function openCalendar(input) {
        calInput = input;
        if (!calendar) { calendar = buildCalendar(); }

        var val = input.value ? new Date(input.value + 'T00:00:00') : new Date();
        calYear = val.getFullYear();
        calMonth = val.getMonth();

        renderCalendar();
        positionCalendar();
    }

    function closeCalendar() {
        if (calendar) { calendar.style.display = 'none'; }
        calInput = null;
    }

    document.addEventListener('click', function (ev) {
        // --- kliknięcie w kalendarz ---
        if (calendar && calendar.contains(ev.target)) {
            var nav = ev.target.getAttribute('data-nav');
            if (nav === 'prev') {
                calMonth--;
                if (calMonth < 0) { calMonth = 11; calYear--; }
                renderCalendar();
                return;
            }
            if (nav === 'next') {
                calMonth++;
                if (calMonth > 11) { calMonth = 0; calYear++; }
                renderCalendar();
                return;
            }
            var act = ev.target.getAttribute('data-act');
            if (act === 'today' && calInput) {
                var t = new Date();
                calInput.value = isoDate(t.getFullYear(), t.getMonth(), t.getDate());
                calInput.dispatchEvent(new Event('change', { bubbles: true }));
                closeCalendar();
                return;
            }
            if (act === 'clear' && calInput) {
                calInput.value = '';
                calInput.dispatchEvent(new Event('change', { bubbles: true }));
                closeCalendar();
                return;
            }
            var iso = ev.target.getAttribute('data-date');
            if (iso && calInput) {
                calInput.value = iso;
                calInput.dispatchEvent(new Event('change', { bubbles: true }));
                closeCalendar();
            }
            return;
        }

        // --- otwarcie kalendarza po kliknięciu w pole daty ---
        var dateInput = ev.target.closest('input[data-calendar], input[type="date"]');
        if (dateInput && !dateInput.hasAttribute('disabled')) {
            if (calInput === dateInput && calendar && calendar.style.display === 'block') {
                closeCalendar();
            } else {
                openCalendar(dateInput);
            }
            return;
        }

        closeCalendar();
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && calInput) {
            closeCalendar();
        }
    });

    window.addEventListener('resize', function () { if (calInput) { positionCalendar(); } });
    window.addEventListener('scroll', function () { if (calInput) { positionCalendar(); } }, true);

    /* ==============================================================
       |  FILTROWANIE LIST WYBORU
       |  - zawężanie do modelu (data-filter-by="IDmodel")
       |  - wyszukiwanie tekstowe (input.selectfilter)
       |  - model ustawiany automatycznie po wyborze urządzenia (data-set-model)
       | ============================================================== */
    function initSelectFilters() {
        var selects = Array.prototype.slice.call(document.querySelectorAll('select[data-filterable="1"]'));
        if (!selects.length) { return; }

        var stores = selects.map(function (sel) {
            var opts = [];
            for (var i = 0; i < sel.options.length; i++) { opts.push(sel.options[i]); }
            var placeholder = (opts.length && !opts[0].value) ? opts[0] : null;
            return {
                sel: sel,
                opts: opts.filter(function (o) { return o.value !== ''; }),
                placeholder: placeholder,
                input: document.querySelector('.selectfilter[data-target="' + sel.id + '"]'),
                filterBy: sel.getAttribute('data-filter-by'),
                setModel: sel.getAttribute('data-set-model')
            };
        });

        function valueOf(name) {
            var s = document.querySelector('select[name="' + name + '"]');
            return s ? s.value : '';
        }

        function labelOf(name) {
            var s = document.querySelector('select[name="' + name + '"]');
            if (!s) { return ''; }
            for (var i = 0; i < s.options.length; i++) {
                if (s.options[i].value === s.value) {
                    return s.options[i].textContent.replace(/\s*\(#\d+\)\s*$/, '').trim();
                }
            }
            return '';
        }

        function rebuild(store) {
            var model = store.filterBy ? valueOf(store.filterBy) : '';
            var needle = store.input ? store.input.value.toLowerCase().trim() : '';
            var chosen = store.sel.value;
            var list = [];

            for (var i = 0; i < store.opts.length; i++) {
                var opt = store.opts[i];
                var optModel = opt.getAttribute('data-model') || '';

                // zawężenie do modelu (materiały bez modelu widoczne tylko bez filtru)
                if (model !== '' && store.filterBy) {
                    if (optModel === '' || optModel !== model) { continue; }
                }
                // wyszukiwanie tekstowe
                if (needle !== '' && opt.textContent.toLowerCase().indexOf(needle) === -1) { continue; }

                list.push(opt);
            }

            store.sel.innerHTML = '';
            if (store.placeholder) { store.sel.appendChild(store.placeholder); }
            for (var j = 0; j < list.length; j++) { store.sel.appendChild(list[j]); }

            // przywróć zaznaczenie, jeśli wartość nadal pasuje do filtra
            if (chosen) {
                for (var k = 0; k < store.sel.options.length; k++) {
                    if (store.sel.options[k].value === chosen) { store.sel.value = chosen; break; }
                }
            }

            // podpowiedź: ile pozycji jest widocznych
            var hint = document.querySelector('[data-model-hint="' + store.sel.id + '"]');
            if (hint) {
                if (model === '') {
                    hint.textContent = 'Wszystkie materiały (' + store.opts.length + '). '
                        + 'Wybierz model lub urządzenie, aby zawęzić listę.';
                } else {
                    hint.textContent = 'Dla modelu „' + labelOf(store.filterBy) + '” pasuje '
                        + list.length + ' z ' + store.opts.length + ' materiałów.';
                }
            }
        }

        function rebuildAll() {
            stores.forEach(rebuild);
        }

        // Zmiana modelu / dowolnego filtra -> przebudowa list
        stores.forEach(function (store) {
            if (store.filterBy) {
                var source = document.querySelector('select[name="' + store.filterBy + '"]');
                if (source) { source.addEventListener('change', rebuildAll); }
            }
        });

        // Wybór urządzenia ustawia model i przebudowuje listy materiałów
        stores.forEach(function (store) {
            if (!store.setModel) { return; }

            function syncModel() {
                var opt = store.sel.options[store.sel.selectedIndex];
                if (!opt) { return; }
                var model = opt.getAttribute('data-model');
                var target = document.querySelector('select[name="' + store.setModel + '"]');
                if (target && model) {
                    target.value = model;
                    target.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            store.sel.addEventListener('change', syncModel);
            store.syncModel = syncModel;
        });

        // Wyszukiwanie tekstowe w liście
        stores.forEach(function (store) {
            if (!store.input) { return; }
            store.input.addEventListener('input', function () { rebuild(store); });
        });

        rebuildAll();

        // Przy starcie: urządzenie wskazane z adresu URL ustawia model
        stores.forEach(function (store) {
            if (store.syncModel) { store.syncModel(); }
        });
    }

    initSelectFilters();

    /* ==============================================================
       |  ZAZNACZANIE WIELU REKORDÓW
       | ============================================================== */
    function updateBulkBar() {
        var checks = document.querySelectorAll('.row-check');
        var counter = document.getElementById('bulk-count');
        var button = document.getElementById('bulk-btn');
        var all = document.getElementById('check-all');
        var selected = 0;

        checks.forEach(function (c) { if (c.checked) { selected++; } });
        if (counter) { counter.textContent = 'Zaznaczono: ' + selected; }
        if (button) { button.disabled = selected === 0; }
        if (all) {
            all.checked = selected > 0 && selected === checks.length;
            all.indeterminate = selected > 0 && selected < checks.length;
        }
    }

    document.addEventListener('change', function (ev) {
        if (ev.target.id === 'check-all') {
            document.querySelectorAll('.row-check').forEach(function (c) { c.checked = ev.target.checked; });
            updateBulkBar();
        } else if (ev.target.classList && ev.target.classList.contains('row-check')) {
            updateBulkBar();
        }
    });

    /* ==============================================================
       |  DROBIAZGI
       | ============================================================== */

    // Zamykanie rozwijanych menu po kliknięciu poza nimi
    document.addEventListener('click', function (ev) {
        var open = document.querySelectorAll('details.dropdown[open]');
        for (var i = 0; i < open.length; i++) {
            if (!open[i].contains(ev.target)) { open[i].removeAttribute('open'); }
        }
    });

    // Automatyczne zamknięcie komunikatów
    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () {
            if (el && el.parentNode) {
                el.style.transition = 'opacity .4s';
                el.style.opacity = '0';
                setTimeout(function () { if (el.parentNode) { el.parentNode.removeChild(el); } }, 400);
            }
        }, 9000);
    });

    // Skrót klawiaturowy "/" -> wyszukiwarka
    document.addEventListener('keydown', function (ev) {
        if (ev.key !== '/' || ev.ctrlKey || ev.metaKey || ev.altKey) { return; }
        var tag = (document.activeElement && document.activeElement.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select') { return; }
        var box = document.querySelector('.topbar__search input[name="q"]');
        if (box) { ev.preventDefault(); box.focus(); box.select(); }
    });

    // Podgląd wiersza tabeli po kliknięciu (bez linków/przycisków)
    document.addEventListener('click', function (ev) {
        var row = ev.target.closest('tr.clickable');
        if (!row) { return; }
        if (ev.target.closest('a, button, input, select, form')) { return; }
        row.click();
    });

    // Automatyczne odznaczanie zaznaczenia w formularzach przy zmianie filtra
    var filterForm = document.getElementById('wymiany-filter');
    if (filterForm) {
        filterForm.addEventListener('submit', function () {
            var all = document.getElementById('check-all');
            if (all) { all.checked = false; }
        });
    }
})();
