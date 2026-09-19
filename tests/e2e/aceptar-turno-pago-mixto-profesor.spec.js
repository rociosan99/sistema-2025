import { expect, test } from '@playwright/test';

test('Lucia acepta el turno 183 que se utilizara para probar el pago mixto', async ({ page }) => {
    test.setTimeout(90_000);

    const esperarActualizacionLivewire = () =>
        page.waitForResponse((response) => {
            const url = new URL(response.url());

            return response.request().method() === 'POST'
                && url.pathname === '/livewire/update'
                && response.ok();
        });

    await page.goto('/profesor/login');
    await page.locator('form#form input[type="email"]').fill('profesor1@seed.com');
    await page.locator('form#form input[type="password"]').fill('password');

    await Promise.all([
        page.waitForURL(/\/profesor(?:\/dashboard)?(?:\?.*)?$/, { timeout: 30_000 }),
        page.getByRole('button', { name: 'Entrar', exact: true }).click(),
    ]);

    await page
        .getByRole('link', { name: 'Solicitudes de Turno', exact: true })
        .first()
        .click();

    await expect(page).toHaveURL(/\/profesor\/turnos(?:\?.*)?$/);

    const solicitud = page
        .getByRole('row')
        .filter({ hasText: 'Micaela Sosa' })
        .filter({ hasText: 'Bases de Datos' })
        .filter({ hasText: /28.*2026/ })
        .filter({ hasText: '10:00' })
        .filter({ hasText: '11:00' });

    await expect(solicitud).toHaveCount(1);
    await expect(solicitud.getByText('Pendiente', { exact: true })).toBeVisible();
    await expect(solicitud.getByRole('button', { name: 'Aceptar', exact: true })).toBeVisible();
    await expect(page.getByLabel(/Enlace de clase/i)).toHaveCount(0);

    await Promise.all([
        esperarActualizacionLivewire(),
        solicitud.getByRole('button', { name: 'Aceptar', exact: true }).click(),
    ]);

    await expect(solicitud.getByText('Pendiente de pago', { exact: true })).toBeVisible();
    await expect(solicitud.getByRole('button', { name: 'Aceptar', exact: true })).toHaveCount(0);
    await expect(
        solicitud.locator('a[href="https://meet.google.com/jtq-nqux-gea"]'),
    ).toBeVisible();
});
