import AxeBuilder from '@axe-core/playwright';
import { expect, Page, test } from '@playwright/test';

async function login(page: Page, email: string): Promise<void> {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill(email);
    await page.locator('input[name="password"]').fill('password');
    await page.getByRole('button', { name: /log in/i }).click();
    await expect(page).not.toHaveURL(/\/login$/);
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

    for (const path of ['/dashboard', '/participants', '/events', '/matches', '/results', '/rankings', '/roles', '/reports']) {
        const response = await page.goto(path);
        expect(response?.status(), path).toBeLessThan(400);
        await expect(page.locator('body')).not.toContainText(/server error|exception/i);
    }
});

test('faculty and dean dashboards enforce their role journeys', async ({ page }) => {
    await login(page, 'ftkek@utem.edu.my');
    await expect((await page.goto('/faculty'))?.status()).toBeLessThan(400);
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
