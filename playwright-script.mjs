import { chromium } from 'playwright';

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  const response = await page.goto('http://127.0.0.1:8000/login');
  console.log("Status:", response.status());
  console.log("Body:", await page.content());

  await browser.close();
})();
