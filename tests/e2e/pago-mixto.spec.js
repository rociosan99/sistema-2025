import { expect, test } from '@playwright/test';

test('Micaela inicia el pago mixto del turno 183 con $166 de credito y $35 por Mercado Pago', async ({ page }) => {
    test.setTimeout(90_000);

    const esperarActualizacionLivewire = () =>
        page.waitForResponse((response) => {
            const url = new URL(response.url());

            return response.request().method() === 'POST'
                && url.pathname === '/livewire/update'
                && response.ok();
        });

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
        .filter({ hasText: '28/08/2026' })
        .filter({ hasText: /10:00\s*-\s*11:00/ })
        .filter({ hasText: 'Bases de Datos' })
        .filter({ hasText: /Luc.*a G.*mez/ });

    await expect(turno).toHaveCount(1);
    await turno.getByRole('link', { name: /Pagar/i }).click();

    await expect(page).toHaveURL(/\/alumno\/completar-pago\/183(?:\?.*)?$/);
    await expect(page.getByText('Turno #183', { exact: true })).toBeVisible();

    const resumen = page.locator('dl');
    const filaPrecio = resumen.getByText('Precio de la clase', { exact: true }).locator('..');
    const filaCreditoDisponible = resumen.getByText(/Cr.*dito disponible/, { exact: true }).locator('..');

    await expect(filaPrecio).toContainText('$201,00');
    await expect(filaCreditoDisponible).toContainText('$166,00');

    const usarCredito = page.getByRole('checkbox', { name: /Usar mi cr.*dito/i });
    await expect(usarCredito).toBeVisible();
    await expect(usarCredito).not.toBeChecked();

    await Promise.all([
        esperarActualizacionLivewire(),
        usarCredito.check(),
    ]);

    await expect(usarCredito).toBeChecked();

    const filaCreditoAplicado = resumen.getByText(/Cr.*dito aplicado/, { exact: true }).locator('..');
    const filaImporteFinal = resumen.getByText('Importe a pagar', { exact: true }).locator('..');

    await expect(filaCreditoAplicado).toContainText('$166,00');
    await expect(filaImporteFinal).toContainText('$35,00');
    await expect(
        page.getByText(/Mercado Pago cobrar.* solamente \$35,00/),
    ).toBeVisible();

    const continuarConMercadoPago = page.getByRole('button', {
        name: 'Pagar $35,00 con Mercado Pago',
        exact: true,
    });

    await expect(continuarConMercadoPago).toBeVisible();

    await Promise.all([
        page.waitForURL((url) => /(^|\.)mercadopago\.com(?:\.ar)?$/i.test(url.hostname), {
            timeout: 60_000,
        }),
        continuarConMercadoPago.click(),
    ]);

    await expect(page).toHaveURL(/mercadopago\.com/i);
    await expect(page.getByText(/\$?\s*35(?:[.,]00)?/).first()).toBeVisible({ timeout: 30_000 });
});
