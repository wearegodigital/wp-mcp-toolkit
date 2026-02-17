import { test, expect } from '@playwright/test';

test.describe('Templates Tab', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto('/wp-admin/tools.php?page=wp-mcp-toolkit&tab=templates');
	});

	test('renders template extraction form', async ({ page }) => {
		await expect(page.locator('.wpmcp-template-form')).toBeVisible();
		await expect(page.locator('#wpmcp_template_post_type')).toBeVisible();
	});

	test('post type select has options', async ({ page }) => {
		const options = page.locator('#wpmcp_template_post_type option');
		const count = await options.count();
		expect(count).toBeGreaterThanOrEqual(1);
	});

	test('post search field renders', async ({ page }) => {
		// Select2 or native search field should be present.
		const searchField = page.locator('#wpmcp_template_reference_post, .select2-container');
		await expect(searchField.first()).toBeVisible();
	});

	test('visual snapshot', async ({ page }) => {
		await expect(page.locator('.wpmcp-tab-content')).toHaveScreenshot('templates-tab.png');
	});
});
