const { defineConfig, devices } = require('@playwright/test');

/**
 * Runs against a real running instance of the site — local Docker stack
 * by default (docker compose up -d first), or any other deployment via
 * COURSE_DISCOVERY_BASE_URL. Assumes bin/seed.php's dataset is loaded
 * (16 courses, including exactly 3 tagged "Graphic Design").
 */
module.exports = defineConfig({
    testDir: './specs',
    timeout: 30_000,
    fullyParallel: true,
    reporter: 'list',
    use: {
        baseURL: process.env.COURSE_DISCOVERY_BASE_URL || 'http://localhost:8080',
        trace: 'on-first-retry',
    },
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    ],
});
