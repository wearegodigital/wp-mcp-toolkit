import { test, expect } from '@playwright/test';
import { discoverPostTypes } from '../helpers/wp-discover';

test.describe('Frontend Content Rendering', () => {
	let postTypes: Awaited<ReturnType<typeof discoverPostTypes>>;

	test.beforeAll(async ({ request }) => {
		const baseURL = process.env.WPMCP_BASE_URL;
		if (!baseURL) throw new Error('WPMCP_BASE_URL is required');
		postTypes = await discoverPostTypes(request, baseURL);
	});

	test('discovered post types have sample content', () => {
		const withSamples = postTypes.filter((pt) => pt.samplePost);
		expect(withSamples.length).toBeGreaterThanOrEqual(1);
	});

	test('each post type renders without unicode artifacts', async ({ page }) => {
		for (const pt of postTypes) {
			if (!pt.samplePost) continue;

			await page.goto(pt.samplePost.url);
			await page.waitForLoadState('networkidle');

			const body = await page.textContent('body');
			// Check for unicode escape artifacts like "u003c" between word chars.
			expect(body).not.toMatch(/(?<=[a-z])u[0-9a-fA-F]{4}(?=[a-z])/);
		}
	});

	test('each post type visual snapshot', async ({ page }) => {
		for (const pt of postTypes) {
			if (!pt.samplePost) continue;

			await page.goto(pt.samplePost.url);
			await page.waitForLoadState('networkidle');

			await expect(page).toHaveScreenshot(`${pt.slug}-desktop.png`, {
				fullPage: true,
			});
		}
	});
});
