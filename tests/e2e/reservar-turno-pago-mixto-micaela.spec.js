import { expect, test } from '@playwright/test';

test('Micaela reserva el turno que se utilizara para probar el pago mixto', async ({ page }) => {
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

    await page.getByRole('link', { name: 'Solicitar turno', exact: true }).first().click();
    await expect(page).toHaveURL(/\/alumno\/solicitar-turno(?:\?.*)?$/);

    const buscador = page.locator('input[wire\\:model\\.live="busqueda"]');
    await buscador.fill('Bases de Datos');

    const materiaSugerida = page.getByRole('button', {
        name: /Materia:\s*Bases de Datos/i,
    });

    await Promise.all([
        esperarActualizacionLivewire(),
        materiaSugerida.click(),
    ]);

    await expect(materiaSugerida).toBeHidden();
    await expect(page.getByText(/para la materia Bases de Datos/i)).toBeVisible();

    const fecha = page.locator('input[type="date"]');

    await Promise.all([
        esperarActualizacionLivewire(),
        fecha.fill('2026-08-28'),
    ]);

    await expect(fecha).toHaveValue('2026-08-28');

    const turnoDisponible = page
        .locator('div')
        .filter({ has: page.getByRole('button', { name: 'Reservar' }) })
        .filter({ hasText: /Luc.*a G.*mez/ })
        .filter({ hasText: /10:00\s*[–-]\s*11:00/ })
        .last();

    await expect(turnoDisponible).toBeVisible();

    await Promise.all([
        esperarActualizacionLivewire(),
        turnoDisponible.getByRole('button', { name: 'Reservar' }).click(),
    ]);

    const modalExito = page.getByRole('dialog');

    await expect(
        modalExito.getByRole('heading', { name: 'Solicitud enviada correctamente' }),
    ).toBeVisible();

    await Promise.all([
        esperarActualizacionLivewire(),
        modalExito.getByRole('button', { name: 'Aceptar', exact: true }).click(),
    ]);

    await expect(modalExito).toBeHidden();

    await page.getByRole('link', { name: 'Turnos', exact: true }).first().click();
    await expect(page).toHaveURL(/\/alumno\/turnos(?:\?.*)?$/);

    const turnoCreado = page
        .getByRole('row')
        .filter({ hasText: 'Pendiente' })
        .filter({ hasText: '28/08/2026' })
        .filter({ hasText: /10:00\s*-\s*11:00/ })
        .filter({ hasText: 'Bases de Datos' })
        .filter({ hasText: /Luc.*a G.*mez/ });

    await expect(turnoCreado).toHaveCount(1);
    await expect(turnoCreado.getByText('Pendiente', { exact: true })).toBeVisible();
});
