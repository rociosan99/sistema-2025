import { expect, test } from '@playwright/test';

test('el alumno inicia un pago completo de $166 con Mercado Pago', async ({ page }) => {
    test.setTimeout(90_000);

    await page.goto('/alumno/login');
    await page.locator('form#form input[type="email"]').fill('alumno1@seed.com');
    await page.locator('form#form input[type="password"]').fill('password');

    await Promise.all([
        page.waitForURL(/\/alumno(?:\/dashboard)?(?:\?.*)?$/, { timeout: 30_000 }),
        page.getByRole('button', { name: 'Entrar', exact: true }).click(),
    ]);

    await page.goto('/alumno/turnos');

    const turno = page
        .getByRole('row')
        .filter({ hasText: 'Pendiente de pago' })
        .filter({ hasText: '31/08/2026' })
        .filter({ hasText: /14:00\s*-\s*15:00/ })
        .filter({ hasText: 'Algoritmos y Estructuras de Datos I' })
        .filter({ hasText: /Luc.*a G.*mez/ });

    await expect(turno).toHaveCount(1);
    await turno.getByRole('link', { name: /Pagar/i }).click();

    await expect(page).toHaveURL(/\/alumno\/completar-pago\/182(?:\?.*)?$/);
    await expect(page.getByText('Turno #182', { exact: true })).toBeVisible();
    await expect(page.getByText('Pendiente de pago', { exact: true })).toBeVisible();
    await expect(page.getByText(/Importe a pagar/)).toBeVisible();
    await expect(page.getByText('$166,00', { exact: true })).toBeVisible();
    await expect(page.getByText(/No ten.*s cr.*ditos disponibles para aplicar a esta clase\./)).toBeVisible();
    await expect(page.getByRole('checkbox', { name: /Usar mi cr.*dito/i })).toHaveCount(0);

    const pagarConMercadoPago = page.getByRole('link', {
        name: 'Pagar $166,00 con Mercado Pago',
        exact: true,
    });

    await expect(pagarConMercadoPago).toBeVisible();

    await Promise.all([
        page.waitForURL((url) => /(^|\.)mercadopago\.com(?:\.ar)?$/i.test(url.hostname), {
            timeout: 60_000,
        }),
        pagarConMercadoPago.click(),
    ]);

    await expect(page).toHaveURL(/mercadopago\.com/i);
    await expect(page.getByText(/\$?\s*166(?:[.,]00)?/).first()).toBeVisible({ timeout: 30_000 });
});
