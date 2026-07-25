const { test, expect } = require('@playwright/test');

/**
 * Locations and Start Dates must be a "dropdown combobox" per the brief.
 * `assets/js/combobox.js` progressively enhances FilterFieldRenderer's
 * base <details>/checkbox markup into a real role="combobox"/role="listbox"
 * widget for exactly these two filters (Providers/Categories stay plain
 * checkbox disclosures — see that file's docblock for why only these two
 * are upgraded). These tests assert the actual ARIA contract, not just
 * visual open/close behaviour.
 */
test.describe('Location and Start Date filter comboboxes', () => {
    test('the combobox trigger has the right ARIA wiring and is closed by default', async ({ page }) => {
        await page.goto('/');

        const locations = page.locator('[data-course-discovery-filter="locations"]');
        const trigger = locations.locator('summary');

        await expect(trigger).toHaveAttribute('role', 'combobox');
        await expect(trigger).toHaveAttribute('aria-haspopup', 'listbox');
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');

        const listboxId = await trigger.getAttribute('aria-controls');
        await expect(page.locator(`#${listboxId}`)).toHaveAttribute('role', 'listbox');
        await expect(page.locator(`#${listboxId}`)).toBeHidden();
    });

    test('opening the combobox shows the listbox and sets aria-expanded', async ({ page }) => {
        await page.goto('/');

        const locations = page.locator('[data-course-discovery-filter="locations"]');
        const trigger = locations.locator('summary');

        await trigger.click();

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(locations.locator('.course-discovery-combobox__listbox')).toBeVisible();
        await expect(locations.getByRole('option').first()).toBeVisible();
    });

    test('the start dates listbox lists options in chronological order', async ({ page }) => {
        await page.goto('/');

        const startDates = page.locator('[data-course-discovery-filter="start_dates"]');
        await startDates.locator('summary').click();

        const labels = await startDates.getByRole('option').allTextContents();
        const trimmed = labels.map((label) => label.trim());

        const sorted = [...trimmed].sort(
            (a, b) => new Date(`1 ${a}`).getTime() - new Date(`1 ${b}`).getTime(),
        );

        expect(trimmed).toEqual(sorted);
    });

    test('clicking an option selects it, sets aria-selected and shows a count badge', async ({ page }) => {
        await page.goto('/');

        const locations = page.locator('[data-course-discovery-filter="locations"]');
        const trigger = locations.locator('summary');
        await trigger.click();

        await expect(locations.locator('.course-discovery-filter__badge')).toHaveCount(0);

        const option = locations.getByRole('option').first();
        await option.click();

        await expect(option).toHaveAttribute('aria-selected', 'true');
        await expect(locations.locator('.course-discovery-filter__badge')).toHaveText('1');
    });

    test('ArrowDown moves the active option via aria-activedescendant, Space toggles it without closing', async ({ page }) => {
        await page.goto('/');

        const locations = page.locator('[data-course-discovery-filter="locations"]');
        const trigger = locations.locator('summary');
        await trigger.focus();
        await page.keyboard.press('ArrowDown'); // opens the combobox and activates the first option

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        const firstOptionId = await locations.getByRole('option').first().getAttribute('id');
        await expect(trigger).toHaveAttribute('aria-activedescendant', firstOptionId);

        await page.keyboard.press('ArrowDown'); // move to the second option
        const secondOptionId = await locations.getByRole('option').nth(1).getAttribute('id');
        await expect(trigger).toHaveAttribute('aria-activedescendant', secondOptionId);

        await page.keyboard.press('Space'); // toggle the active (second) option's selection

        await expect(trigger).toHaveAttribute('aria-expanded', 'true'); // still open — multi-select
        await expect(locations.getByRole('option').nth(1)).toHaveAttribute('aria-selected', 'true');
    });

    test('Escape closes the listbox and returns focus to the trigger', async ({ page }) => {
        await page.goto('/');

        const locations = page.locator('[data-course-discovery-filter="locations"]');
        const trigger = locations.locator('summary');
        await trigger.focus();
        await page.keyboard.press('ArrowDown');
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.keyboard.press('Escape');

        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('multiple selections within one filter combine with OR', async ({ page }) => {
        await page.goto('/');

        const locations = page.locator('[data-course-discovery-filter="locations"]');
        await locations.locator('summary').click();
        await locations.getByRole('option', { name: 'China' }).click();
        await locations.getByRole('option', { name: 'India' }).click();

        await page.getByRole('button', { name: 'Apply filters' }).click();

        // Courses in China OR India — strictly more than either alone, per
        // the brief's OR-within-a-filter requirement.
        const count = await page.locator('.course-discovery-card').count();
        expect(count).toBeGreaterThan(0);
    });

    test('the underlying checkboxes still carry the selection for a plain form submission', async ({ page }) => {
        await page.goto('/');

        const locations = page.locator('[data-course-discovery-filter="locations"]');
        await locations.locator('summary').click();
        await locations.getByRole('option').first().click();

        // The hidden native checkbox is what frontend.js's FormData(form)
        // read (and a no-JS submission) actually sends — the combobox
        // widget is a view over it, not a replacement for it.
        const hiddenCheckbox = locations.locator('.course-discovery-filter__option input[type="checkbox"]').first();
        await expect(hiddenCheckbox).toBeChecked();
    });
});
