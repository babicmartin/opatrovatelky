import { expect, test } from '@playwright/test';

test('updates password requirements and confirmation while editing a profile', async ({ page }) => {
	await page.goto('/login/');
	await page.getByPlaceholder('Email').fill(process.env.E2E_USER ?? 'admin@example.test');
	await page.getByPlaceholder('Heslo').fill(process.env.E2E_PASSWORD ?? 'secret123!');
	await page.getByRole('button', { name: 'Prihlásiť' }).click();
	await page.goto('/user-management/update');

	const card = page.getByRole('region', { name: 'Zmena vlastného hesla' });
	const password = card.getByLabel('Nové heslo', { exact: true });
	const repeat = card.getByLabel('Zopakovať heslo');
	const met = card.locator('.password-requirements .is-met');
	const match = card.getByRole('status');
	await expect(card.getByLabel('Aktuálne heslo')).toHaveAccessibleDescription('Na zmenu hesla zadajte aj svoje pôvodné heslo.');
	await expect(card.getByRole('listitem')).toHaveCount(3);
	await expect(met).toHaveCount(0);
	await expect(match).toBeEmpty();

	await password.fill('abcdefg');
	await password.press('1');
	await expect(met).toHaveCount(2);
	await expect(met.nth(0)).toContainText('8 znakov');
	await expect(met.nth(1)).toContainText('číslica');
	await password.press('!');
	await expect(met).toHaveCount(3);
	await repeat.fill('abcdefg1!');
	await expect(match).toHaveText('Heslá sa zhodujú.');
	await password.press('End');
	await password.press('Backspace');
	await expect(met).toHaveCount(2);
	await expect(match).toHaveText('Heslá sa nezhodujú.');

	await password.fill('Abcd1!🙂');
	await expect(met).toHaveCount(2);
	await expect(card.getByRole('listitem').filter({ hasText: 'Aspoň 8 znakov' })).not.toHaveClass(/is-met/);
	await repeat.fill('');
	await expect(match).toBeEmpty();

	const heading = card.getByRole('heading');
	await heading.evaluate(element => {
		const selection = window.getSelection()!;
		selection.selectAllChildren(element);
	});
	await page.keyboard.press('ControlOrMeta+c');
	await password.focus();
	await password.press('ControlOrMeta+a');
	await password.press('ControlOrMeta+v');
	await expect(password).toHaveValue('Zmena vlastného hesla');
	await expect(met).toHaveCount(2);
	await password.fill('');
	await expect(met).toHaveCount(0);

	await page.setViewportSize({ width: 375, height: 812 });
	await card.scrollIntoViewIfNeeded();
	const bounds = await card.boundingBox();
	expect(bounds).not.toBeNull();
	expect(bounds!.x).toBeGreaterThanOrEqual(0);
	expect(bounds!.width).toBeGreaterThan(300);
	expect(bounds!.x + bounds!.width).toBeLessThanOrEqual(375);
});
