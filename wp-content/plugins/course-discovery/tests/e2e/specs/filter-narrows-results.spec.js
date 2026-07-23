const { test, expect } = require('@playwright/test');

test.describe('Selecting filters narrows results', () => {
    test('shows all seeded courses with no filters applied', async ({ page }) => {
        await page.goto('/');

        await expect(page.locator('[data-course-discovery-count]')).toHaveText('16 courses found');
    });

    test('a category filter narrows to only matching courses', async ({ page }) => {
        await page.goto('/');

        await page.locator('[data-course-discovery-filter="categories"] summary').click();
        await page.getByRole('checkbox', { name: 'Graphic Design' }).check();
        await page.getByRole('button', { name: 'Apply filters' }).click();

        await expect(page.locator('[data-course-discovery-count]')).toHaveText('3 courses found');
        await expect(page.locator('.course-discovery-card')).toHaveCount(3);
    });

    test('combining two filters applies AND, not OR', async ({ page }) => {
        await page.goto('/');

        await page.locator('[data-course-discovery-filter="categories"] summary').click();
        await page.getByRole('checkbox', { name: 'Graphic Design' }).check();

        await page.locator('[data-course-discovery-filter="locations"] summary').click();
        await page.getByRole('checkbox', { name: 'China' }).check();

        await page.getByRole('button', { name: 'Apply filters' }).click();

        // No Graphic Design course is based in China — AND across filters
        // must exclude everything, not fall back to OR.
        await expect(page.locator('[data-course-discovery-count]')).toHaveText('0 courses found');
        await expect(page.locator('.course-discovery-empty')).toBeVisible();
    });

    test('reset clears filters and restores the full result set', async ({ page }) => {
        await page.goto('/');

        await page.locator('[data-course-discovery-filter="categories"] summary').click();
        await page.getByRole('checkbox', { name: 'Graphic Design' }).check();
        await page.getByRole('button', { name: 'Apply filters' }).click();
        await expect(page.locator('[data-course-discovery-count]')).toHaveText('3 courses found');

        await page.getByRole('link', { name: 'Reset' }).click();

        await expect(page.locator('[data-course-discovery-count]')).toHaveText('16 courses found');
    });
});
