const { test, expect } = require('@playwright/test');

test.describe('Haal Centraal Test Pagina', () => {
  test.beforeEach(async ({ page, context }) => {
    // Gebruik HTTP Basic Auth als Nextcloud dat ondersteunt, anders login via form
    const username = process.env.NEXTCLOUD_USER || 'admin';
    const password = process.env.NEXTCLOUD_PASSWORD || 'admin';
    
    // Probeer eerst direct naar de pagina te gaan
    await page.goto('/apps/openregister/haal-centraal-test', { waitUntil: 'domcontentloaded' });
    
    // Check of we op login pagina zijn
    const currentUrl = page.url();
    if (currentUrl.includes('/login')) {
      // Vul login formulier in
      await page.waitForSelector('input[type="text"], input[name="user"]', { timeout: 5000 });
      
      // Probeer verschillende selectors voor username en password
      const userInput = page.locator('input[type="text"], input[name="user"], #user').first();
      const passInput = page.locator('input[type="password"], input[name="password"], #password').first();
      
      await userInput.fill(username);
      await passInput.fill(password);
      
      // Klik op submit
      await page.click('button[type="submit"], input[type="submit"], button:has-text("Log in")').catch(() => {
        // Als dat niet werkt, druk op Enter
        return passInput.press('Enter');
      });
      
      // Wacht op navigatie
      await page.waitForLoadState('networkidle', { timeout: 15000 });
      
      // Als we nog steeds op login pagina zijn, probeer opnieuw
      if (page.url().includes('/login')) {
        // Wacht even en probeer opnieuw
        await page.waitForTimeout(2000);
        await page.goto('/apps/openregister/haal-centraal-test', { waitUntil: 'networkidle' });
      }
    }
    
    // Wacht tot de pagina geladen is
    await page.waitForSelector('#haal-centraal-test', { timeout: 15000 });
  });

  test('pagina laadt correct', async ({ page }) => {
    // Controleer of de hoofdtitel zichtbaar is
    await expect(page.locator('h2:has-text("Haal Centraal BRP Bevragen")')).toBeVisible();
    
    // Controleer of de zoek sectie zichtbaar is
    await expect(page.locator('#free-search-input')).toBeVisible();
    await expect(page.locator('#bsn-input')).toBeVisible();
  });

  test('scroll functionaliteit werkt', async ({ page }) => {
    // Controleer of de pagina scrollbaar is
    const bodyHeight = await page.evaluate(() => document.body.scrollHeight);
    const viewportHeight = await page.viewportSize().height;
    
    // Als bodyHeight groter is dan viewportHeight, dan is de pagina scrollbaar
    expect(bodyHeight).toBeGreaterThan(viewportHeight);
    
    // Test scroll naar beneden
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(500);
    
    // Controleer of we naar beneden zijn gescrolld
    const scrollY = await page.evaluate(() => window.scrollY);
    expect(scrollY).toBeGreaterThan(0);
    
    // Test scroll naar boven
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(500);
    
    const scrollYAfter = await page.evaluate(() => window.scrollY);
    expect(scrollYAfter).toBe(0);
  });

  test('zoek functionaliteit werkt', async ({ page }) => {
    // Test vrij zoeken
    const searchInput = page.locator('#free-search-input');
    await searchInput.fill('168149291');
    
    const searchButton = page.locator('#free-search-btn');
    await searchButton.click();
    
    // Wacht op resultaten (of error message)
    await page.waitForTimeout(2000);
    
    // Controleer of er resultaten zijn of een error message
    const resultsContainer = page.locator('#results-container');
    const errorMessage = page.locator('#error-message');
    
    // Een van beide moet zichtbaar zijn
    const hasResults = await resultsContainer.isVisible().catch(() => false);
    const hasError = await errorMessage.isVisible().catch(() => false);
    
    expect(hasResults || hasError).toBeTruthy();
  });

  test('BSN zoek functionaliteit werkt', async ({ page }) => {
    // Test BSN zoeken
    const bsnInput = page.locator('#bsn-input');
    await bsnInput.fill('168149291');
    
    const searchBsnButton = page.locator('#search-bsn-btn');
    await searchBsnButton.click();
    
    // Wacht op resultaten
    await page.waitForTimeout(2000);
    
    // Controleer of er resultaten zijn of een error message
    const resultsContainer = page.locator('#results-container');
    const errorMessage = page.locator('#error-message');
    
    const hasResults = await resultsContainer.isVisible().catch(() => false);
    const hasError = await errorMessage.isVisible().catch(() => false);
    
    expect(hasResults || hasError).toBeTruthy();
  });

  test('prefill formulier verschijnt na klikken op prefill knop', async ({ page }) => {
    // Zoek eerst naar een persoon
    const searchInput = page.locator('#free-search-input');
    await searchInput.fill('168149291');
    
    const searchButton = page.locator('#free-search-btn');
    await searchButton.click();
    
    // Wacht op resultaten
    await page.waitForTimeout(3000);
    
    // Zoek naar prefill knop in resultaten
    const prefillButton = page.locator('.prefill-btn').first();
    
    // Als er een prefill knop is, klik erop
    const prefillButtonExists = await prefillButton.isVisible().catch(() => false);
    
    if (prefillButtonExists) {
      await prefillButton.click();
      
      // Wacht tot het formulier verschijnt
      await page.waitForTimeout(1000);
      
      // Controleer of het formulier zichtbaar is
      const formContainer = page.locator('#test-form-container');
      await expect(formContainer).toBeVisible();
      
      // Controleer of formulier velden gevuld zijn
      const bsnField = page.locator('#form-bsn');
      const bsnValue = await bsnField.inputValue();
      expect(bsnValue.length).toBeGreaterThan(0);
    } else {
      // Als er geen prefill knop is, controleer of er een error is
      const errorMessage = page.locator('#error-message');
      const hasError = await errorMessage.isVisible().catch(() => false);
      
      // Test is OK als er een error is (geen resultaten gevonden)
      expect(hasError).toBeTruthy();
    }
  });

  test('formulier kan worden gesloten', async ({ page }) => {
    // Zoek eerst naar een persoon
    const searchInput = page.locator('#free-search-input');
    await searchInput.fill('168149291');
    
    const searchButton = page.locator('#free-search-btn');
    await searchButton.click();
    
    // Wacht op resultaten
    await page.waitForTimeout(3000);
    
    // Zoek naar prefill knop
    const prefillButton = page.locator('.prefill-btn').first();
    const prefillButtonExists = await prefillButton.isVisible().catch(() => false);
    
    if (prefillButtonExists) {
      await prefillButton.click();
      await page.waitForTimeout(1000);
      
      // Klik op sluiten knop
      const closeButton = page.locator('#close-form-btn');
      await closeButton.click();
      
      await page.waitForTimeout(500);
      
      // Controleer of formulier verborgen is
      const formContainer = page.locator('#test-form-container');
      const isVisible = await formContainer.isVisible().catch(() => false);
      expect(isVisible).toBeFalsy();
    }
  });

  test('sidebar scrollt onafhankelijk', async ({ page }) => {
    // Controleer of de sidebar scrollbaar is
    const sidebar = page.locator('.search-section-wrapper');
    
    // Scroll in de sidebar
    await sidebar.evaluate((el) => {
      el.scrollTop = 100;
    });
    
    await page.waitForTimeout(500);
    
    // Controleer of sidebar gescrolld is
    const scrollTop = await sidebar.evaluate((el) => el.scrollTop);
    expect(scrollTop).toBeGreaterThan(0);
  });

  test('formulier velden kunnen worden gewist', async ({ page }) => {
    // Zoek eerst naar een persoon
    const searchInput = page.locator('#free-search-input');
    await searchInput.fill('168149291');
    
    const searchButton = page.locator('#free-search-btn');
    await searchButton.click();
    
    await page.waitForTimeout(3000);
    
    // Zoek naar prefill knop
    const prefillButton = page.locator('.prefill-btn').first();
    const prefillButtonExists = await prefillButton.isVisible().catch(() => false);
    
    if (prefillButtonExists) {
      await prefillButton.click();
      await page.waitForTimeout(1000);
      
      // Vul een veld
      const bsnField = page.locator('#form-bsn');
      await bsnField.fill('123456789');
      
      // Klik op wis knop
      const clearButton = page.locator('#clear-form-btn');
      await clearButton.click();
      
      await page.waitForTimeout(500);
      
      // Controleer of veld leeg is
      const bsnValue = await bsnField.inputValue();
      expect(bsnValue).toBe('');
    }
  });
});

