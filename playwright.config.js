// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * Playwright configuratie voor het testen van de Haal Centraal test pagina
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig({
  testDir: './tests',
  /* Maximum tijd voor één test */
  timeout: 30 * 1000,
  expect: {
    /* Maximum tijd voor expect assertions */
    timeout: 5000
  },
  /* Testen parallel uitvoeren */
  fullyParallel: true,
  /* Fail de build op CI als je accidentally left test.only in de source code */
  forbidOnly: !!process.env.CI,
  /* Retry op CI only */
  retries: process.env.CI ? 2 : 0,
  /* Opties voor shared settings voor alle projecten */
  use: {
    /* Base URL om te gebruiken in actions zoals `await page.goto('/')` */
    baseURL: 'http://localhost:8080',
    /* Collect trace wanneer retrying de failed test */
    trace: 'on-first-retry',
    /* Screenshot bij failure */
    screenshot: 'only-on-failure',
  },

  /* Configureer projecten voor major browsers */
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],

  /* Run je local dev server voordat je start met testen */
  // webServer: {
  //   command: 'npm run start',
  //   url: 'http://localhost:8080',
  //   reuseExistingServer: !process.env.CI,
  // },
});







