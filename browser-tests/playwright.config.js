import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  timeout: 30_000,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [['line'], ['html', { open: 'never' }]] : 'line',
  use: {
    baseURL: process.env.LARAVEL_GUARD_BROWSER_URL || 'http://127.0.0.1:8127',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'desktop', use: { ...devices['Desktop Chrome'] } },
    { name: 'mobile', use: { ...devices['Pixel 7'] } },
  ],
  webServer: process.env.LARAVEL_GUARD_EXTERNAL_SERVER ? undefined : {
    command: 'php vendor/bin/testbench serve --host=127.0.0.1 --port=8127',
    cwd: '..',
    url: 'http://127.0.0.1:8127/laravel-guard',
    reuseExistingServer: !process.env.CI,
    timeout: 120_000,
  },
});