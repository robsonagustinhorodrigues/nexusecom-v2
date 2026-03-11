const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', args: ['--no-sandbox'] });
    const page = await browser.newPage();
    
    // Catch console logs
    page.on('console', msg => console.log('BROWSER_LOG:', msg.text()));
    page.on('pageerror', err => console.log('BROWSER_ERROR:', err.message));
    page.on('response', resp => {
        if (!resp.ok() && resp.url().includes('/api/')) {
            console.log('API_ERROR:', resp.url(), resp.status());
        }
    });

    try {
        await page.goto('http://localhost:8000/fiscal/nfe?view=saidas&data_inicio=2025-01-01&data_fim=2026-03-10');
        await page.waitForTimeout(5000);
        console.log('Done waiting.');
    } catch (e) {
        console.log('Exception: ' + e);
    }
    await browser.close();
})();
