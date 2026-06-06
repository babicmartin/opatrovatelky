import { defineConfig, devices } from '@playwright/test';

/**
 * Browser E2E config. Runs against a live dev server (default http://opatrovatelky.local).
 * Override with E2E_BASE_URL. Credentials come from E2E_USER / E2E_PASSWORD.
 */
export default defineConfig({
  testDir: './tests',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: process.env.E2E_BASE_URL ?? 'http://opatrovatelky.local',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
