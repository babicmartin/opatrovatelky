import { test, expect } from '@playwright/test';

const EMAIL = process.env.E2E_USER ?? 'admin@example.test';
const PASSWORD = process.env.E2E_PASSWORD ?? 'secret123!';

test.describe('admin login', () => {
  test('logs in and reaches the admin area', async ({ page }) => {
    await page.goto('/');

    await page.getByPlaceholder('Email').fill(EMAIL);
    await page.getByPlaceholder('Heslo').fill(PASSWORD);
    await page.getByRole('button', { name: 'Prihlásiť' }).click();

    // After a successful login the user leaves the login screen.
    await expect(page.getByRole('button', { name: 'Prihlásiť' })).toHaveCount(0);
  });

  test('rejects wrong credentials', async ({ page }) => {
    await page.goto('/');

    await page.getByPlaceholder('Email').fill(EMAIL);
    await page.getByPlaceholder('Heslo').fill('definitely-wrong');
    await page.getByRole('button', { name: 'Prihlásiť' }).click();

    // Still on the login screen.
    await expect(page.getByPlaceholder('Heslo')).toBeVisible();
  });
});
