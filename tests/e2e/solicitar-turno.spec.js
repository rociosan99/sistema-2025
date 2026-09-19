import { expect, test } from '@playwright/test';

test('el alumno puede consultar un turno disponible sin reservarlo', async ({ page }) => {
    test.setTimeout(60_000);

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
    await buscador.fill('Algoritmos y Estructuras de Datos I');

    const materiaSugerida = page.getByRole('button', {
        name: /Materia:\s*Algoritmos y Estructuras de Datos I/i,
    });

    await Promise.all([
        esperarActualizacionLivewire(),
        materiaSugerida.click(),
    ]);

    await expect(materiaSugerida).toBeHidden();
    await expect(page.getByText(/para la materia Algoritmos y Estructuras de Datos I/i)).toBeVisible();

    const fecha = page.locator('input[type="date"]');

    await Promise.all([
        esperarActualizacionLivewire(),
        fecha.fill('2026-08-31'),
    ]);

    await expect(fecha).toHaveValue('2026-08-31');

    const turnoEsperado = page
        .locator('div')
        .filter({ has: page.getByRole('button', { name: 'Reservar' }) })
        .filter({ hasText: 'Lucía Gómez' })
        .filter({ hasText: /14:00\s*[–-]\s*15:00/ })
        .last();

    await expect(turnoEsperado).toBeVisible();
    await expect(turnoEsperado).toContainText(/14:00\s*[–-]\s*15:00/);
    await expect(turnoEsperado.getByRole('button', { name: 'Reservar' })).toBeVisible();
});
