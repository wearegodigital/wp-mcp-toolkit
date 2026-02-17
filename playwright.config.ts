import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
	testDir: './tests/playwright',
	outputDir: './tests/playwright/results',
	snapshotDir: './tests/playwright/snapshots',
	fullyParallel: true,
	forbidOnly: !!process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: process.env.CI ? 1 : undefined,
	reporter: 'html',
	use: {
		baseURL: process.env.WPMCP_BASE_URL,
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
	},
	expect: {
		toHaveScreenshot: {
			maxDiffPixelRatio: 0.002,
		},
	},
	projects: [
		{
			name: 'setup',
			testMatch: /global-setup\.ts/,
		},
		{
			name: 'desktop',
			use: {
				...devices['Desktop Chrome'],
				storageState: './tests/playwright/.auth/user.json',
			},
			dependencies: ['setup'],
		},
		{
			name: 'mobile',
			use: {
				...devices['iPhone 13'],
				storageState: './tests/playwright/.auth/user.json',
			},
			dependencies: ['setup'],
		},
	],
});
