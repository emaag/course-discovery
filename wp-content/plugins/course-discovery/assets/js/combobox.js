/**
 * Upgrades the Locations and Start Dates filter disclosures into real
 * WAI-ARIA multi-select comboboxes: a `role="combobox"` trigger with a
 * `role="listbox"` popup, arrow-key/Home/End/typeahead navigation, and
 * `aria-activedescendant` tracking the active option (focus itself never
 * leaves the trigger, per the ARIA 1.2 combobox pattern — the more robust
 * of the two documented focus-management models).
 *
 * Providers and Categories stay as the plain <details> checkbox
 * disclosure from FilterFieldRenderer — the brief only requires a
 * "dropdown combobox" for Locations and Start Dates, so only those two
 * are upgraded here.
 *
 * This is a layer on top of, not a replacement for, the server-rendered
 * markup: the underlying <details>/checkboxes are never removed from the
 * DOM, only hidden, so the exact same name[]/value pairs still get
 * submitted via a plain form submission — the page keeps working
 * correctly with this script disabled, or if it fails to load. Every
 * state change here (selection, badge count) is mirrored back onto the
 * real checkbox so frontend.js's FormData(form) read on submit needs no
 * changes at all.
 */
(function () {
    'use strict';

    var TARGET_SELECTOR = '[data-course-discovery-filter="locations"], [data-course-discovery-filter="start_dates"]';

    function enhance(details) {
        var summary = details.querySelector('summary');
        var panel = details.querySelector('.course-discovery-filter__panel');

        if (!summary || !panel || !summary.id) {
            return;
        }

        var rows = Array.prototype.slice.call(panel.querySelectorAll('.course-discovery-filter__option'));
        var checkboxes = rows.map(function (row) {
            return row.querySelector('input[type="checkbox"]');
        });

        if (rows.length === 0 || checkboxes.indexOf(null) !== -1) {
            return;
        }

        var baseId = summary.id.replace(/-summary$/, '');
        var listboxId = baseId + '-listbox';

        // The native checkboxes stay in the DOM (still part of the form, so
        // they're still submitted) but are removed from the visual/AT
        // surface — the new listbox below is what's seen and interacted
        // with instead.
        rows.forEach(function (row) {
            row.hidden = true;
        });

        var listbox = document.createElement('ul');
        listbox.id = listboxId;
        listbox.className = 'course-discovery-combobox__listbox';
        listbox.setAttribute('role', 'listbox');
        listbox.setAttribute('aria-multiselectable', 'true');
        listbox.setAttribute('aria-labelledby', summary.id);
        panel.appendChild(listbox);

        var options = checkboxes.map(function (checkbox, index) {
            var option = document.createElement('li');
            option.id = baseId + '-option-' + index;
            option.className = 'course-discovery-combobox__option' + (checkbox.checked ? ' is-selected' : '');
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', checkbox.checked ? 'true' : 'false');
            option.textContent = rows[index].textContent.trim();

            option.addEventListener('mouseenter', function () {
                setActive(index);
            });
            option.addEventListener('click', function () {
                setActive(index);
                toggle(index);
            });

            listbox.appendChild(option);

            return option;
        });

        summary.setAttribute('role', 'combobox');
        summary.setAttribute('aria-haspopup', 'listbox');
        summary.setAttribute('aria-controls', listboxId);
        summary.setAttribute('aria-expanded', details.open ? 'true' : 'false');

        var activeIndex = -1;

        function setActive(index) {
            if (activeIndex >= 0 && options[activeIndex]) {
                options[activeIndex].classList.remove('is-active');
            }

            activeIndex = index;

            if (index >= 0 && options[index]) {
                options[index].classList.add('is-active');
                summary.setAttribute('aria-activedescendant', options[index].id);
                options[index].scrollIntoView({ block: 'nearest' });
            } else {
                summary.removeAttribute('aria-activedescendant');
            }
        }

        function toggle(index) {
            var checkbox = checkboxes[index];
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));

            options[index].setAttribute('aria-selected', checkbox.checked ? 'true' : 'false');
            options[index].classList.toggle('is-selected', checkbox.checked);

            updateBadge();
        }

        function updateBadge() {
            var count = checkboxes.filter(function (checkbox) {
                return checkbox.checked;
            }).length;

            var badge = summary.querySelector('.course-discovery-filter__badge');

            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'course-discovery-filter__badge';
                    summary.appendChild(badge);
                }
                badge.textContent = String(count);
            } else if (badge) {
                badge.remove();
            }
        }

        details.addEventListener('toggle', function () {
            summary.setAttribute('aria-expanded', details.open ? 'true' : 'false');

            if (details.open) {
                var firstSelected = checkboxes.findIndex(function (checkbox) {
                    return checkbox.checked;
                });
                setActive(firstSelected >= 0 ? firstSelected : 0);
            } else {
                setActive(-1);
            }
        });

        var typeahead = '';
        var typeaheadTimer = null;

        summary.addEventListener('keydown', function (event) {
            if (!details.open) {
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    details.open = true;
                }
                return;
            }

            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    setActive(Math.min(activeIndex + 1, options.length - 1));
                    break;

                case 'ArrowUp':
                    event.preventDefault();
                    setActive(Math.max(activeIndex - 1, 0));
                    break;

                case 'Home':
                    event.preventDefault();
                    setActive(0);
                    break;

                case 'End':
                    event.preventDefault();
                    setActive(options.length - 1);
                    break;

                case ' ':
                case 'Enter':
                    // Multi-select: toggling the active option should not
                    // close the popup, so this must override <summary>'s
                    // native Enter/Space-toggles-<details> behaviour.
                    event.preventDefault();
                    if (activeIndex >= 0) {
                        toggle(activeIndex);
                    }
                    break;

                case 'Escape':
                    event.preventDefault();
                    details.open = false;
                    summary.focus();
                    break;

                default:
                    if (event.key.length === 1 && /\S/.test(event.key)) {
                        typeahead += event.key.toLowerCase();
                        window.clearTimeout(typeaheadTimer);
                        typeaheadTimer = window.setTimeout(function () {
                            typeahead = '';
                        }, 500);

                        var afterActive = options.findIndex(function (option, index) {
                            return index > activeIndex && option.textContent.toLowerCase().indexOf(typeahead) === 0;
                        });
                        var anyMatch = options.findIndex(function (option) {
                            return option.textContent.toLowerCase().indexOf(typeahead) === 0;
                        });
                        var target = afterActive !== -1 ? afterActive : anyMatch;

                        if (target !== -1) {
                            setActive(target);
                        }
                    }
            }
        });

        document.addEventListener('click', function (event) {
            if (details.open && !details.contains(event.target)) {
                details.open = false;
            }
        });
    }

    document.querySelectorAll(TARGET_SELECTOR).forEach(enhance);
})();
