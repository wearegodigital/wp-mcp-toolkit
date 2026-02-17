import { test, expect } from '@playwright/test';

test.describe('Abilities Tab', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto('/wp-admin/tools.php?page=wp-mcp-toolkit&tab=abilities');
	});

	test('renders ability groups', async ({ page }) => {
		const groups = page.locator('.wpmcp-ability-group');
		const count = await groups.count();
		expect(count).toBeGreaterThanOrEqual(1);
	});

	test('groups have headers with counts', async ({ page }) => {
		const headers = page.locator('.wpmcp-ability-group-header');
		const count = await headers.count();

		for (let i = 0; i < count; i++) {
			const header = headers.nth(i);
			await expect(header.locator('.wpmcp-ability-group-title')).toBeVisible();
			await expect(header.locator('.wpmcp-ability-group-count')).toBeVisible();
		}
	});

	test('ability rows show human-readable names', async ({ page }) => {
		const names = page.locator('.wpmcp-ability-name');
		const count = await names.count();
		expect(count).toBeGreaterThanOrEqual(1);

		// Verify names are human-readable (not raw slugs).
		const firstName = await names.first().textContent();
		expect(firstName).not.toContain('wpmcp/');
		expect(firstName).not.toContain('wpmcp-');
	});

	test('toggle switches are functional', async ({ page }) => {
		const toggle = page.locator('.wpmcp-toggle input').first();
		const wasChecked = await toggle.isChecked();
		await toggle.click({ force: true });
		const isChecked = await toggle.isChecked();
		expect(isChecked).not.toBe(wasChecked);
		// Reset.
		await toggle.click({ force: true });
	});

	test('groups are collapsible', async ({ page }) => {
		const group = page.locator('.wpmcp-ability-group').first();
		const header = group.locator('.wpmcp-ability-group-header');
		const body = group.locator('.wpmcp-ability-group-body');

		await expect(body).toBeVisible();
		await header.click();
		await expect(body).not.toBeVisible();
		await header.click();
		await expect(body).toBeVisible();
	});

	test('visual snapshot', async ({ page }) => {
		await expect(page.locator('.wpmcp-tab-content')).toHaveScreenshot('abilities-tab.png');
	});
});
