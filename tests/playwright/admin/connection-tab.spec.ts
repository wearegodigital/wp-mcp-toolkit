import { test, expect } from '@playwright/test';

test.describe('Connection Tab', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto('/wp-admin/tools.php?page=wp-mcp-toolkit&tab=connection');
	});

	test('renders page header and version badge', async ({ page }) => {
		await expect(page.locator('.wpmcp-admin h1')).toBeVisible();
		await expect(page.locator('.wpmcp-version-badge')).toBeVisible();
	});

	test('shows STDIO configuration block', async ({ page }) => {
		const stdioBlock = page.locator('#wpmcp-stdio-config');
		await expect(stdioBlock).toBeVisible();
		const text = await stdioBlock.textContent();
		expect(text).toContain('wp');
		expect(text).toContain('mcp-adapter');
	});

	test('shows HTTP configuration block', async ({ page }) => {
		const httpBlock = page.locator('#wpmcp-http-config');
		await expect(httpBlock).toBeVisible();
		const text = await httpBlock.textContent();
		expect(text).toContain('npx');
		expect(text).toContain('WP_API_URL');
	});

	test('copy buttons are visible', async ({ page }) => {
		const buttons = page.locator('.wpmcp-copy-btn');
		await expect(buttons).toHaveCount(2);
	});

	test('visual snapshot', async ({ page }) => {
		await expect(page.locator('.wpmcp-tab-content')).toHaveScreenshot('connection-tab.png');
	});
});
