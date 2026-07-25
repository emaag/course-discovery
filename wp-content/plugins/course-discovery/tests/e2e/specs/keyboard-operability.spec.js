const { test, expect } = require('@playwright/test');

/**
 * No pointing device required, per the brief. Every interaction here uses
 * page.keyboard, never page.mouse/click. These tests target the
 * Categories filter specifically, which (unlike Locations/Start Dates —
 * see combobox-filters.spec.js) is left as the plain native <details>/
 * <summary> disclosure from Frontend\FilterFieldRenderer with no JS
 * layered on top, proving that pattern gets correct keyboard behaviour
 * from the browser for free.
 */
test.describe('Keyboard-only operation', () => {
    test('a filter disclosure opens with Enter on its summary', async ({ page }) => {
        await page.goto('/');

        const categories = page.locator('[data-course-discovery-filter="categories"]');
        await categories.locator('summary').focus();
        await expect(categories).not.toHaveAttribute('open', '');

        await page.keyboard.press('Enter');

        await expect(categories).toHaveAttribute('open', '');
    });

    test('a filter disclosure also opens with Space on its summary', async ({ page }) => {
        await page.goto('/');

        const providers = page.locator('[data-course-discovery-filter="providers"]');
        await providers.locator('summary').focus();

        await page.keyboard.press('Space');

        await expect(providers).toHaveAttribute('open', '');
    });

    test('Tab moves from an opened summary into its checkbox options', async ({ page }) => {
        await page.goto('/');

        const categories = page.locator('[data-course-discovery-filter="categories"]');
        await categories.locator('summary').focus();
        await page.keyboard.press('Enter');

        await page.keyboard.press('Tab');

        const focusedIsCheckboxInPanel = await page.evaluate(() => {
            const el = document.activeElement;
            return el instanceof HTMLInputElement
                && el.type === 'checkbox'
                && el.closest('.course-discovery-filter__panel') !== null;
        });

        expect(focusedIsCheckboxInPanel).toBe(true);
    });

    test('a full keyboard-only journey selects a filter and submits the form', async ({ page }) => {
        await page.goto('/');
        await expect(page.locator('[data-course-discovery-count]')).toHaveText('16 courses found');

        const categoriesSummary = page.locator('[data-course-discovery-filter="categories"] summary');
        await categoriesSummary.focus();
        await page.keyboard.press('Enter'); // open the disclosure
        await page.keyboard.press('Tab'); // focus moves to the first checkbox
        await page.keyboard.press('Space'); // check it

        const applyButton = page.getByRole('button', { name: 'Apply filters' });
        await applyButton.focus();
        await page.keyboard.press('Enter'); // submit

        await expect(page.locator('[data-course-discovery-count]')).not.toHaveText('16 courses found');
    });

    test('the search input has an accessible label', async ({ page }) => {
        await page.goto('/');

        const search = page.getByLabel('Search courses');
        await expect(search).toBeVisible();
        await search.focus();
        await page.keyboard.type('design');
        await expect(search).toHaveValue('design');
    });
});
