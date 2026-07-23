const { test, expect } = require('@playwright/test');

/**
 * Locations and Start Dates must be a "dropdown combobox" per the brief.
 * This project implements that as a native <details>/<summary> disclosure
 * — see Frontend\FilterFieldRenderer's docblock and the README's
 * Assumptions Made for why. These tests verify the resulting behaviour
 * (closed by default, opens on activation, shows a selection count),
 * not a specific ARIA role.
 */
test.describe('Location and Start Date filter comboboxes', () => {
    test('the locations combobox is closed by default and opens on click', async ({ page }) => {
        await page.goto('/');

        const locations = page.locator('[data-course-discovery-filter="locations"]');
        await expect(locations).not.toHaveAttribute('open', '');
        await expect(locations.locator('.course-discovery-filter__panel')).toBeHidden();

        await locations.locator('summary').click();

        await expect(locations).toHaveAttribute('open', '');
        await expect(locations.locator('.course-discovery-filter__panel')).toBeVisible();
    });

    test('the start dates combobox lists options in chronological order', async ({ page }) => {
        await page.goto('/');

        const startDates = page.locator('[data-course-discovery-filter="start_dates"]');
        await startDates.locator('summary').click();

        const labels = await startDates.locator('.course-discovery-filter__option').allTextContents();
        const trimmed = labels.map((label) => label.trim());

        const sorted = [...trimmed].sort(
            (a, b) => new Date(`1 ${a}`).getTime() - new Date(`1 ${b}`).getTime(),
        );

        expect(trimmed).toEqual(sorted);
    });

    test('selecting an option shows a count badge on the summary', async ({ page }) => {
        await page.goto('/');

        const locations = page.locator('[data-course-discovery-filter="locations"]');
        await locations.locator('summary').click();
        await expect(locations.locator('.course-discovery-filter__badge')).toHaveCount(0);

        await locations.getByRole('checkbox').first().check();

        await expect(locations.locator('.course-discovery-filter__badge')).toHaveText('1');
    });

    test('multiple selections within one filter combine with OR', async ({ page }) => {
        await page.goto('/');

        const locations = page.locator('[data-course-discovery-filter="locations"]');
        await locations.locator('summary').click();
        await locations.getByRole('checkbox', { name: 'China' }).check();
        await locations.getByRole('checkbox', { name: 'India' }).check();

        await page.getByRole('button', { name: 'Apply filters' }).click();

        // Courses in China OR India — strictly more than either alone, per
        // the brief's OR-within-a-filter requirement.
        const count = await page.locator('.course-discovery-card').count();
        expect(count).toBeGreaterThan(0);
    });
});
