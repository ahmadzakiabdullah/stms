import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 1 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: process.env.CI ? [['html', { open: 'never' }], ['github']] : 'list',
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8000',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        { name: 'desktop-chromium', use: { ...devices['Desktop Chrome'] } },
        { name: 'mobile-chromium', use: { ...devices['Pixel 7'] } },
    ],
    webServer: process.env.E2E_BASE_URL ? undefined : {
        command: 'php artisan serve --host=127.0.0.1 --port=8000',
        // Startup readiness must only prove that the HTTP application is serving.
        // The operational /health endpoint can intentionally return 503 when an
        // optional dependency is degraded, which would stall browser tests.
        url: 'http://127.0.0.1:8000/login',
        reuseExistingServer: !process.env.CI,
        timeout: 120_000,
    },
});
