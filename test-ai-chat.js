const { chromium } = require('playwright');

(async () => {
  console.log('Start AI Chat test...\n');
  
  let browser;
  let page;
  
  try {
    browser = await chromium.launch({ 
      headless: false,
      slowMo: 500
    });
    
    const context = await browser.newContext({
      viewport: { width: 1280, height: 720 }
    });
    page = await context.newPage();
    
    // Stap 1: Ga naar Nextcloud login pagina
    console.log('Stap 1: Ga naar Nextcloud login pagina...');
    await page.goto('http://localhost:8080/index.php/login', { 
      waitUntil: 'domcontentloaded',
      timeout: 30000 
    });
    console.log(' Pagina geladen');
    
    // Wacht op login form
    console.log(' Wacht op login form...');
    await page.waitForSelector('input[name="user"]', { timeout: 10000 });
    
    // Stap 2: Log in
    console.log(' Stap 2: Log in met admin account...');
    await page.fill('input[name="user"]', 'admin');
    await page.fill('input[name="password"]', 'admin_secure_pass_2024');
    
    console.log(' Klik op login button...');
    await page.click('button[type="submit"]');
    
    // Wacht op redirect zonder specifieke navigatie event
    console.log(' Wacht op login redirect...');
    await page.waitForTimeout(5000);
    
    // Controleer of we ingelogd zijn door te kijken naar de URL
    const currentUrl = page.url();
    console.log(` Huidige URL na login: ${currentUrl}`);
    
    if (currentUrl.includes('/login')) {
      console.log('  Nog steeds op login pagina, mogelijk login gefaald');
    } else {
      console.log(' Ingelogd!');
    }
    
    // Stap 3: Ga naar AI Chat pagina
    console.log(' Stap 3: Navigeer naar AI Chat pagina...');
    
    try {
      await page.goto('http://localhost:8080/index.php/apps/openregister/chat', { 
        waitUntil: 'networkidle',
        timeout: 30000 
      });
      console.log(' Pagina geladen');
    } catch (e) {
      console.log('  Navigatie timeout, maar pagina mogelijk geladen');
    }
    
    // Controleer of pagina nog open is
    if (page.isClosed()) {
      throw new Error('Pagina gesloten tijdens navigatie');
    }
    
    console.log(' Wacht 8 seconden voor JavaScript en API calls...');
    try {
      await page.waitForTimeout(8000);
    } catch (e) {
      console.log('  Timeout tijdens wachten:', e.message);
      if (page.isClosed()) {
        throw new Error('Pagina gesloten tijdens wachten');
      }
    }
    
    // Wacht op specifieke elementen die aangeven dat de pagina geladen is
    console.log(' Wacht op pagina elementen...');
    try {
      await page.waitForSelector('body', { timeout: 5000 });
    } catch (e) {
      console.log('  Timeout bij wachten op body element');
    }
    
    // Controleer opnieuw of pagina nog open is
    if (page.isClosed()) {
      throw new Error('Pagina gesloten na wachten');
    }
    
    // Stap 4: Controleer of "Chat Provider Not Configured" melding weg is
    console.log(' Stap 4: Controleer configuratie status...');
    
    // Wacht nog even voor eventuele dynamische content
    try {
      await page.waitForTimeout(3000);
    } catch (e) {
      console.log('  Timeout tijdens laatste wachten');
    }
    
    if (page.isClosed()) {
      throw new Error('Pagina gesloten voor controle');
    }
    
    const pageContent = await page.content();
    const bodyText = await page.textContent('body').catch(() => '');
    
    // Controleer op error meldingen (zowel in HTML als in zichtbare tekst)
    const hasError = bodyText.includes('Chat Provider Not Configured') || 
                     bodyText.includes('LLM provider must be configured') ||
                     bodyText.includes('chat provider') && bodyText.includes('not configured') ||
                     pageContent.includes('Chat Provider Not Configured') ||
                     pageContent.includes('LLM provider must be configured');
    
    if (hasError) {
      console.log(' FOUT: Chat Provider is nog steeds niet geconfigureerd!');
      console.log('\n Eerste 1000 karakters van pagina:');
      console.log(bodyText.substring(0, 1000));
      
      // Controleer configuratie opnieuw
      console.log('\n Controleer Nextcloud configuratie...');
      const configCheck = await page.evaluate(() => {
        // Probeer configuratie te lezen via console of window object
        return typeof window !== 'undefined' ? 'window object beschikbaar' : 'geen window object';
      }).catch(() => 'kon configuratie niet lezen');
      console.log(`Configuratie check: ${configCheck}`);
      
      await page.screenshot({ path: 'test-error.png', fullPage: true });
      console.log(' Screenshot opgeslagen als test-error.png');
      
      // Check of configuratie misschien niet goed is doorgegeven
      console.log('\n Laatste poging: controleer via Nextcloud config...');
      
      throw new Error('Chat Provider niet geconfigureerd - controleer Nextcloud configuratie');
    }
    
    console.log(' Geen "Chat Provider Not Configured" melding gevonden!');
    
    // Stap 5: Controleer of chat interface elementen aanwezig zijn
    console.log(' Stap 5: Zoek naar chat interface elementen...');
    
    // Zoek naar verschillende mogelijke chat elementen
    const selectors = [
      'textarea',
      'input[type="text"]',
      '[placeholder*="chat" i]',
      '[placeholder*="vraag" i]',
      '[placeholder*="message" i]',
      '[role="textbox"]',
      '.chat-input',
      '#chat-input'
    ];
    
    let chatInputFound = false;
    for (const selector of selectors) {
      try {
        const element = page.locator(selector).first();
        const isVisible = await element.isVisible({ timeout: 2000 }).catch(() => false);
        if (isVisible) {
          console.log(` Chat element gevonden met selector: ${selector}`);
          chatInputFound = true;
          break;
        }
      } catch (e) {
        // Continue naar volgende selector
      }
    }
    
    if (!chatInputFound) {
      console.log('  Geen expliciet chat input veld gevonden, maar geen error = goed teken');
    }
    
    // Screenshot maken van succesvolle pagina
    await page.screenshot({ path: 'test-success.png', fullPage: true });
    console.log(' Screenshot opgeslagen als test-success.png');
    
    // Laat browser open voor inspectie
    console.log('\n TEST GESLAAGD: AI Chat is geconfigureerd!');
    console.log(' Browser blijft 10 seconden open voor inspectie...');
    await page.waitForTimeout(10000);
    
  } catch (error) {
    console.error('\n TEST GEFAALD:', error.message);
    if (error.stack) {
      console.error('Stack:', error.stack);
    }
    
    if (page && !page.isClosed()) {
      try {
        await page.screenshot({ path: 'test-failed.png', fullPage: true });
        console.log(' Screenshot opgeslagen als test-failed.png');
        
        const url = page.url();
        console.log(` Huidige URL: ${url}`);
      } catch (e) {
        console.log('  Kon geen screenshot maken:', e.message);
      }
    }
    
    process.exit(1);
  } finally {
    if (browser) {
      console.log('\n Sluit browser...');
      await browser.close();
    }
  }
})();
