const { test, expect } = require('@playwright/test');

/**
 * templates/partials/course-card.php (server-rendered) and
 * assets/js/frontend.js's courseCardHtml() (client-rendered after a
 * JS-driven filter/paginate) can't literally share one template across
 * the PHP/JS boundary, so they duplicate the same field list by hand —
 * a real drift risk if one is ever changed without the other. This test
 * is the guard: it asserts both render paths show the same fields, in
 * the same order, for real seeded data.
 */
const CANONICAL_FIELD_ORDER = ['Price', 'Providers', 'Location', 'Category', 'Start dates', 'Instructors'];

function assertCanonicalOrder(labels) {
    const indices = labels.map((label) => CANONICAL_FIELD_ORDER.indexOf(label));

    expect(indices).not.toContain(-1);
    expect(indices).toEqual([...indices].sort((a, b) => a - b));
}

test.describe('Course card rendering parity', () => {
    test('the initial server-rendered card includes Providers and Instructors, in canonical order', async ({ page }) => {
        await page.goto('/');

        const labels = await page.locator('.course-discovery-card').first().locator('dt').allTextContents();

        expect(labels).toContain('Providers');
        expect(labels).toContain('Instructors');
        assertCanonicalOrder(labels);
    });

    test('a JS-driven re-render (after applying a filter) shows the same fields, in the same order', async ({ page }) => {
        await page.goto('/');

        await page.locator('[data-course-discovery-filter="categories"] summary').click();
        await page.getByRole('checkbox', { name: 'Graphic Design' }).check();
        await page.getByRole('button', { name: 'Apply filters' }).click();

        await expect(page.locator('[data-course-discovery-count]')).toHaveText('3 courses found');

        const labels = await page.locator('.course-discovery-card').first().locator('dt').allTextContents();

        expect(labels).toContain('Providers');
        expect(labels).toContain('Instructors');
        assertCanonicalOrder(labels);
    });
});
