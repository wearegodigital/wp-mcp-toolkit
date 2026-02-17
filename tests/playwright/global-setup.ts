import { test as setup, expect } from '@playwright/test';

const authFile = './tests/playwright/.auth/user.json';

setup('authenticate', async ({ page }) => {
	const baseURL = process.env.WPMCP_BASE_URL;
	if (!baseURL) {
		throw new Error('WPMCP_BASE_URL environment variable is required');
	}

	await page.goto(`${baseURL}/wp-login.php`);
	await page.fill('#user_login', process.env.WPMCP_WP_USER || 'admin');
	await page.fill('#user_pass', process.env.WPMCP_WP_PASS || 'admin');
	await page.click('#wp-submit');
	await page.waitForURL('**/wp-admin/**');

	await page.context().storageState({ path: authFile });
});
