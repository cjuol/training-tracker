import { expect, test } from '@playwright/test';

test('home renders with PWA manifest link', async ({ page }) => {
    const response = await page.goto('/');
    expect(response?.status()).toBe(200);

    const manifestLink = page.locator('link[rel="manifest"]');
    await expect(manifestLink).toHaveAttribute('href', '/manifest.webmanifest');

    await expect(page.locator('h1')).toHaveText('Training Tracker');
});

test('manifest is served with correct MIME', async ({ request }) => {
    const response = await request.get('/manifest.webmanifest');
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('application/manifest+json');

    const manifest = await response.json();
    expect(manifest.name).toBe('Training Tracker');
    expect(manifest.display).toBe('standalone');
});
