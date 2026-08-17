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

    for (const path of ['/dashboard', '/participants', '/events', '/matches', '/results/manage', '/rankings', '/roles', '/reports']) {
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
    await expectAccessible(page);
});

test('public home and contact pages support keyboard navigation and accessibility smoke checks', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('main')).toBeVisible();
    await expectAccessible(page);

    await page.keyboard.press('Tab');
    await expect(page.locator(':focus')).toBeVisible();

    const contactResponse = await page.goto('/contact-us');
    expect(contactResponse?.status()).toBeLessThan(400);
    await expect(page.locator('main')).toBeVisible();
    await expectAccessible(page);
});
