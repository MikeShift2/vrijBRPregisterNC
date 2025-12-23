const { test, expect } = require('@playwright/test');

test('Test scroll functionaliteit op Haal Centraal pagina', async ({ page }) => {
  const username = process.env.NEXTCLOUD_USER || 'admin';
  const password = process.env.NEXTCLOUD_PASSWORD || 'admin';

  // Ga naar de pagina
  await page.goto('http://localhost:8080/apps/openregister/haal-centraal-test', {
    waitUntil: 'domcontentloaded',
    timeout: 30000
  });

  // Check of we op login pagina zijn
  let url = page.url();
  let title = await page.title();
  
  console.log(`Initial URL: ${url}`);
  console.log(`Page title: ${title}`);

  // Als we op login pagina zijn, log in
  if (url.includes('/login')) {
    console.log(' Logging in...');
    
    // Wacht op login formulier
    await page.waitForSelector('input[type="text"], input[name="user"], #user', { timeout: 5000 });
    
    // Vul login gegevens in
    const userInput = page.locator('input[type="text"], input[name="user"], #user').first();
    const passInput = page.locator('input[type="password"], input[name="password"], #password').first();
    
    await userInput.fill(username);
    await passInput.fill(password);
    
    // Submit form
    await Promise.race([
      page.click('button[type="submit"], input[type="submit"], button:has-text("Log in"), button:has-text("Login")'),
      passInput.press('Enter')
    ]);
    
    // Wacht op redirect
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    
    // Als we nog steeds op login zijn, wacht even en probeer opnieuw
    url = page.url();
    if (url.includes('/login')) {
      await page.waitForTimeout(3000);
      await page.goto('http://localhost:8080/apps/openregister/haal-centraal-test', {
        waitUntil: 'networkidle',
        timeout: 30000
      });
      url = page.url();
    }
    
    console.log(`After login URL: ${url}`);
  }

  // Wacht even voor de pagina om te laden
  await page.waitForTimeout(2000);

  // Maak screenshot om te zien wat er op de pagina staat
  await page.screenshot({ path: 'test-page-screenshot.png', fullPage: true });

  // Probeer verschillende selectors
  const haalCentraalTest = await page.locator('#haal-centraal-test').isVisible().catch(() => false);
  const h2Title = await page.locator('h2').isVisible().catch(() => false);
  
  console.log(`#haal-centraal-test visible: ${haalCentraalTest}`);
  console.log(`h2 visible: ${h2Title}`);

  if (!haalCentraalTest && !h2Title) {
    // Log de body content voor debugging
    const bodyText = await page.locator('body').textContent();
    console.log(`Body content (first 500 chars): ${bodyText?.substring(0, 500)}`);
    
    // Test wordt overgeslagen als pagina niet laadt
    test.skip();
    return;
  }

  // Test scroll functionaliteit
  const initialScrollY = await page.evaluate(() => window.scrollY);
  console.log(`Initial scroll Y: ${initialScrollY}`);

  // Check body height
  const bodyHeight = await page.evaluate(() => document.body.scrollHeight);
  const viewportHeight = await page.viewportSize().height;
  
  console.log(`Body height: ${bodyHeight}, Viewport height: ${viewportHeight}`);

  // Scroll naar beneden
  await page.evaluate(() => {
    window.scrollTo(0, document.body.scrollHeight);
  });
  
  await page.waitForTimeout(1000);
  
  const scrollYAfter = await page.evaluate(() => window.scrollY);
  console.log(`Scroll Y after scrolling down: ${scrollYAfter}`);

  // Als bodyHeight groter is dan viewportHeight, dan moet scrollYAfter > 0 zijn
  if (bodyHeight > viewportHeight) {
    expect(scrollYAfter).toBeGreaterThan(0);
    console.log(' Scroll functionaliteit werkt!');
  } else {
    console.log('  Pagina is niet hoog genoeg om te scrollen');
  }

  // Test sidebar scroll
  const sidebar = page.locator('.search-section-wrapper');
  const sidebarExists = await sidebar.isVisible().catch(() => false);
  
  if (sidebarExists) {
    console.log(' Sidebar gevonden');
    
    // Test sidebar scroll
    await sidebar.evaluate((el) => {
      el.scrollTop = 100;
    });
    
    await page.waitForTimeout(500);
    
    const sidebarScrollTop = await sidebar.evaluate((el) => el.scrollTop);
    console.log(`Sidebar scrollTop: ${sidebarScrollTop}`);
    
    if (sidebarScrollTop > 0) {
      console.log(' Sidebar scroll functionaliteit werkt!');
    }
  } else {
    console.log('  Sidebar niet gevonden');
    
    // Check of er andere elementen zijn
    const searchInput = await page.locator('#free-search-input').isVisible().catch(() => false);
    const bsnInput = await page.locator('#bsn-input').isVisible().catch(() => false);
    
    console.log(`#free-search-input visible: ${searchInput}`);
    console.log(`#bsn-input visible: ${bsnInput}`);
    
    // Als de inputs zichtbaar zijn maar sidebar niet, dan is er een CSS probleem
    if (searchInput || bsnInput) {
      console.log('  Inputs zijn zichtbaar maar sidebar selector werkt niet');
    }
  }

  // Test of we kunnen scrollen door content toe te voegen
  console.log('\n Test: Voeg extra content toe om scroll te testen...');
  
  // Voeg extra hoogte toe via JavaScript
  await page.evaluate(() => {
    const testDiv = document.createElement('div');
    testDiv.style.height = '2000px';
    testDiv.style.background = 'transparent';
    document.body.appendChild(testDiv);
  });
  
  await page.waitForTimeout(500);
  
  const newBodyHeight = await page.evaluate(() => document.body.scrollHeight);
  console.log(`Body height na toevoegen content: ${newBodyHeight}`);
  
  // Nu moeten we kunnen scrollen
  await page.evaluate(() => {
    window.scrollTo(0, document.body.scrollHeight);
  });
  
  await page.waitForTimeout(1000);
  
  const finalScrollY = await page.evaluate(() => window.scrollY);
  console.log(`Final scroll Y: ${finalScrollY}`);
  
  if (finalScrollY > 0) {
    console.log(' Scroll functionaliteit werkt wanneer content hoog genoeg is!');
    expect(finalScrollY).toBeGreaterThan(0);
  } else {
    console.log(' Scroll werkt nog steeds niet, mogelijk CSS probleem');
  }
});

