import { test, expect } from '@playwright/test';

test.describe('Add-ons Tab', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto('/wp-admin/tools.php?page=wp-mcp-toolkit&tab=addons');
	});

	test('renders addon cards grid', async ({ page }) => {
		const grid = page.locator('.wpmcp-addons-grid');
		await expect(grid).toBeVisible();
		const cards = grid.locator('.wpmcp-addon-card');
		const count = await cards.count();
		expect(count).toBeGreaterThanOrEqual(1);
	});

	test('each card has required elements', async ({ page }) => {
		const cards = page.locator('.wpmcp-addon-card');
		const count = await cards.count();

		for (let i = 0; i < count; i++) {
			const card = cards.nth(i);
			await expect(card.locator('.wpmcp-addon-title')).toBeVisible();
			await expect(card.locator('.wpmcp-addon-description')).toBeVisible();
			await expect(card.locator('.wpmcp-badge')).toHaveCount({ minimum: 1 });
		}
	});

	test('shows correct free/premium badges', async ({ page }) => {
		const badges = page.locator('.wpmcp-badge-free, .wpmcp-badge-premium');
		const count = await badges.count();
		expect(count).toBeGreaterThanOrEqual(1);
	});

	test('visual snapshot', async ({ page }) => {
		await expect(page.locator('.wpmcp-tab-content')).toHaveScreenshot('addons-tab.png');
	});
});
