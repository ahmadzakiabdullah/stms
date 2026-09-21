import AxeBuilder from '@axe-core/playwright';
import { expect, Page, test } from '@playwright/test';

async function login(page: Page, email: string): Promise<void> {
    await page.goto('/login');
    await page.getByRole('textbox', { name: /email or username/i }).fill(email);
    await page.locator('input[name="password"]').fill('password');
    await page.getByRole('button', { name: /log in/i }).click();
    await expect(page).not.toHaveURL(/\/login$/, { timeout: 15_000 });
}

async function expectAccessible(page: Page): Promise<void> {
    const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'])
        .analyze();

    expect(results.violations.filter((violation) => ['critical', 'serious'].includes(violation.impact ?? '')))
        .toEqual([]);
}

test('super-admin can reach core competition and administration journeys', async ({ page }) => {
    await login(page, 'admin@saf.test');

    for (const path of ['/dashboard', '/participants', '/events', '/manage/matches', '/results/manage', '/rankings', '/roles', '/reports']) {
        const response = await page.goto(path);
        expect(response?.status(), path).toBeLessThan(400);
        await expect(page.locator('body')).not.toContainText(/server error|exception/i);
    }
});

test('faculty and dean dashboards enforce their role journeys', async ({ page }) => {
    await login(page, 'ftkek@utem.edu.my');
    await expect((await page.goto('/dashboard'))?.status()).toBeLessThan(400);
    await page.context().clearCookies();

    await login(page, 'dean@ftkek.utem.edu.my');
    await expect((await page.goto('/dean'))?.status()).toBeLessThan(400);
});

test('login and dashboard have no serious automated accessibility violations', async ({ page }) => {
    await page.goto('/login');
    await expectAccessible(page);

    await login(page, 'admin@saf.test');
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/dashboard$/);
    await expect(page.locator('main').first()).toBeVisible();
    await expectAccessible(page);
});

test('public home and contact pages support keyboard navigation and accessibility smoke checks', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('main')).toBeVisible();
    await expect(page.getByRole('link', { name: /Explore sports programme|View schedule & results/ }).first()).toBeVisible();
    await expectAccessible(page);

    await page.keyboard.press('Tab');
    await expect(page.locator(':focus')).toBeVisible();

    const contactResponse = await page.goto('/contact-us');
    expect(contactResponse?.status()).toBeLessThan(400);
    await expect(page.locator('main')).toBeVisible();
    await expectAccessible(page);
});

test('all public routes pass serious axe checks and expose a keyboard focus target', async ({ page }) => {
    for (const path of ['/', '/schedule', '/athletes', '/sports', '/faculties', '/venues', '/contact-us', '/news', '/downloads', '/faq', '/about']) {
        const response = await page.goto(path);
        expect(response?.status(), path).toBeLessThan(400);
        await expect(page.locator('main')).toBeVisible();
        await expect(page.locator('#public-content')).toHaveAttribute('aria-busy', 'false');
        await expectAccessible(page);

        await page.keyboard.press('Tab');
        await expect(page.locator(':focus')).toBeVisible();
    }
});

test('public routes expose consistent SEO metadata and crawler policy', async ({ page }) => {
    for (const path of ['/', '/schedule', '/venues', '/contact-us', '/faq']) {
        await page.goto(path);
        await expect(page).toHaveTitle(/.+/);
        await expect(page.locator('meta[name="description"]')).toHaveAttribute('content', /.+/);
        const canonical = await page.locator('link[rel="canonical"]').getAttribute('href');
        expect(canonical).toBeTruthy();
        expect(new URL(canonical!).pathname).toBe(path);
        await expect(page.locator('meta[property="og:title"]')).toHaveAttribute('content', /.+/);
        await expect(page.locator('meta[property="og:description"]')).toHaveAttribute('content', /.+/);
        await expect(page.locator('meta[name="twitter:card"]')).toHaveAttribute('content', 'summary_large_image');
        await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', 'index, follow');
    }

    const robots = await page.request.get('/robots.txt');
    expect(robots.ok()).toBe(true);
    expect(await robots.text()).toContain('Sitemap:');
});

test('public navigation supports Escape and focus wrapping on mobile', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/');

    const menuButton = page.getByRole('button', { name: 'Open menu' });
    await menuButton.focus();
    await menuButton.press('Enter');

    const mobileNav = page.locator('#public-mobile-navigation');
    await expect(mobileNav).toBeVisible();
    await expect(mobileNav.getByRole('link').first()).toBeFocused();

    await page.keyboard.press('Escape');
    await expect(page.getByRole('button', { name: 'Open menu' })).toBeFocused();

    await page.getByRole('button', { name: 'Open menu' }).press('Enter');
    const focusable = mobileNav.locator('a[href], button:not([disabled])');
    await focusable.last().focus();
    await page.keyboard.press('Tab');
    await expect(focusable.first()).toBeFocused();
});

test('public pages avoid horizontal overflow and support locale switching', async ({ page }) => {
    for (const path of ['/', '/schedule', '/athletes', '/sports', '/faculties', '/venues', '/contact-us']) {
        await page.goto(path);
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        expect(overflow, `${path} horizontal overflow`).toBeLessThanOrEqual(1);
        await expect(page.locator('body')).not.toContainText(/Invalid Date|NaN/);
    }

    await page.goto('/venues');
    await expect(page.getByRole('heading', { name: 'Venues', exact: true })).toBeVisible();

    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/');
    await page.getByRole('button', { name: /Open menu/i }).click();
    const mobileNav = page.locator('#public-mobile-navigation');
    await expect(mobileNav.getByRole('heading', { name: 'Competition', exact: true })).toBeVisible();
    await expect(mobileNav.getByRole('heading', { name: 'Information', exact: true })).toBeVisible();
    await expect(mobileNav.getByRole('link', { name: 'Sports', exact: true })).toBeVisible();
    await expect(mobileNav.getByRole('link', { name: 'News', exact: true })).toBeVisible();
    const ms = mobileNav.getByRole('button', { name: 'MS' }).first();
    await expect(ms).toBeVisible();
    await ms.click();
    await expect(page.locator('html')).toHaveAttribute('lang', /ms/i);
});

test('desktop and mobile public navigation stay in sync', async ({ page }) => {
    const primaryLinks = ['Home', 'Schedule & Results', 'Athletes & Teams', 'Contact'];
    const competitionLinks = ['Sports', 'Faculties', 'Venues'];
    const informationLinks = ['News', 'Downloads', 'FAQ', 'About'];

    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto('/');
    const desktopNav = page.locator('nav[aria-label="Public navigation"]:visible');

    for (const label of primaryLinks) {
        await expect(desktopNav.getByRole('link', { name: label })).toBeVisible();
    }

    await page.getByRole('button', { name: 'Competition' }).click();
    for (const label of competitionLinks) {
        await expect(desktopNav.getByRole('link', { name: label })).toBeVisible();
    }

    await page.getByRole('button', { name: 'Information' }).click();
    for (const label of informationLinks) {
        await expect(desktopNav.getByRole('link', { name: label })).toBeVisible();
    }

    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/');
    await page.getByRole('button', { name: /Open menu/i }).click();
    const mobileNav = page.locator('#public-mobile-navigation');
    const mobileLinks = (await mobileNav.locator('a').evaluateAll((anchors) => anchors
        .filter((anchor) => anchor.getAttribute('aria-label') !== 'Log in')
        .map((anchor) => anchor.textContent?.trim() ?? '')));

    expect(mobileLinks).toEqual([...primaryLinks, ...competitionLinks, ...informationLinks]);
    await expect(mobileNav.getByRole('heading', { name: 'Competition', exact: true })).toBeVisible();
    await expect(mobileNav.getByRole('heading', { name: 'Information', exact: true })).toBeVisible();
});

test('public pages emit no CSP violations in the browser console', async ({ page }) => {
    const violations: string[] = [];

    page.on('console', (message) => {
        if (/content security policy|violates the following directive|refused to/i.test(message.text())) {
            violations.push(message.text());
        }
    });

    for (const path of ['/', '/schedule', '/contact-us']) {
        const response = await page.goto(path);
        expect(response?.status(), path).toBeLessThan(400);
        await expect(page.locator('main')).toBeVisible();
    }

    expect(violations).toEqual([]);
});

test('public images and external links have accessible and resilient contracts', async ({ page }) => {
    await page.route('**/images/banner/banner-saf-20-2026.jpeg', (route) => route.fulfill({
        status: 404,
        contentType: 'image/jpeg',
        body: '',
    }));

    await page.goto('/');
    await expect(page.locator('[data-image-fallback="official-banner"]')).toBeVisible();

    const imageAudit = await page.locator('img').evaluateAll((images) => images.map((image) => ({
        alt: image.getAttribute('alt'),
        broken: image.complete && image.currentSrc !== '' && image.naturalWidth === 0,
    })));
    expect(imageAudit.every((image) => image.alt !== null)).toBe(true);
    expect(imageAudit.filter((image) => image.broken)).toEqual([]);

    const unsafeExternalLinks = await page.locator('a[target="_blank"]').evaluateAll((links) => links
        .filter((link) => !new Set((link.getAttribute('rel') ?? '').split(/\s+/).filter(Boolean)).has('noopener'))
        .map((link) => link.getAttribute('href')));
    expect(unsafeExternalLinks).toEqual([]);
});

test('public shell stays usable on mobile and tablet with reduced motion', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });

    for (const viewport of [
        { width: 390, height: 844, name: 'mobile' },
        { width: 768, height: 1024, name: 'tablet' },
        { width: 1440, height: 900, name: 'desktop' },
    ]) {
        await page.setViewportSize(viewport);
        await page.goto('/');
        await expect(page.locator('main')).toBeVisible();

        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        expect(overflow, `${viewport.name} horizontal overflow`).toBeLessThanOrEqual(1);

        await expect.poll(() => page.evaluate(() => window.matchMedia('(prefers-reduced-motion: reduce)').matches)).toBe(true);

        const undersizedControls = viewport.width <= 768
            ? await page.locator('header a, header button, main button, main [role="tab"], main a[class*="min-h-11"]').evaluateAll((elements) => elements
                .filter((element) => {
                    const rect = element.getBoundingClientRect();
                    const style = window.getComputedStyle(element);
                    return style.visibility !== 'hidden' && style.display !== 'none' && rect.width > 0 && rect.height > 0 && (rect.width < 44 || rect.height < 44);
                })
                .map((element) => ({ tag: element.tagName, text: element.textContent?.trim().slice(0, 40) })))
            : [];

        expect(undersizedControls, `${viewport.name} controls below 44px`).toEqual([]);
    }
});
