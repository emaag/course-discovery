/**
 * Progressive enhancement for the Course archive filter form. The form
 * already works with this script disabled — a normal GET submission
 * reloads the page, and templates/archive-course.php renders the exact
 * same filtered/paginated result through the same Filter/Query layer the
 * REST API uses. This script only replaces the full-page reload with a
 * fetch against course-discovery/v1/courses and an in-place DOM update.
 */
(function () {
    'use strict';

    var form = document.querySelector('[data-course-discovery-form]');
    var resultsList = document.querySelector('[data-course-discovery-results]');
    var pagination = document.querySelector('[data-course-discovery-pagination]');
    var countEl = document.querySelector('[data-course-discovery-count]');

    if (!form || !resultsList || !pagination || !countEl || typeof window.CourseDiscoveryConfig === 'undefined') {
        return;
    }

    var restUrl = window.CourseDiscoveryConfig.restUrl;

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = value === null || value === undefined ? '' : String(value);
        return div.innerHTML;
    }

    function metaRow(label, items, key) {
        if (!items.length) {
            return '';
        }

        var values = items
            .map(function (item) {
                return escapeHtml(item[key]);
            })
            .join(', ');

        return '<div><dt>' + escapeHtml(label) + '</dt><dd>' + values + '</dd></div>';
    }

    function courseCardHtml(course) {
        var meta =
            metaRow('Location', course.locations, 'name') +
            metaRow('Category', course.categories, 'name') +
            metaRow('Start dates', course.start_dates, 'label');

        return (
            '<li class="course-discovery-card"><article>' +
            '<h2 class="course-discovery-card__title">' + escapeHtml(course.name) + '</h2>' +
            '<p class="course-discovery-card__description">' + escapeHtml(course.short_description) + '</p>' +
            '<dl class="course-discovery-card__meta"><div><dt>Price</dt><dd>' +
            escapeHtml(course.price.formatted) +
            '</dd></div>' + meta + '</dl>' +
            '</article></li>'
        );
    }

    function renderResults(data) {
        resultsList.innerHTML = data.courses.length
            ? data.courses.map(courseCardHtml).join('')
            : '<li class="course-discovery-empty">No courses match your filters.</li>';

        var p = data.pagination;
        countEl.textContent = p.total + (p.total === 1 ? ' course found' : ' courses found');

        var parts = [];
        if (p.page > 1) {
            parts.push('<button type="button" data-page="' + (p.page - 1) + '">Previous</button>');
        }
        parts.push('<span>Page ' + p.page + ' of ' + p.total_pages + '</span>');
        if (p.page < p.total_pages) {
            parts.push('<button type="button" data-page="' + (p.page + 1) + '">Next</button>');
        }
        pagination.innerHTML = parts.join(' ');
    }

    function currentParams(page) {
        var params = new URLSearchParams(new FormData(form));
        if (page) {
            params.set('course_page', String(page));
        }
        return params;
    }

    function fetchAndRender(params) {
        fetch(restUrl + 'courses?' + params.toString())
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                renderResults(data);

                var url = new URL(window.location.href);
                url.search = params.toString();
                window.history.pushState({}, '', url);
            });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        fetchAndRender(currentParams());
    });

    pagination.addEventListener('click', function (event) {
        var target = event.target.closest('[data-page]');
        if (!target) {
            return;
        }
        fetchAndRender(currentParams(target.getAttribute('data-page')));
    });
})();
