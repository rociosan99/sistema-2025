import { expect, test } from '@playwright/test';

test('Micaela suspende anticipadamente el turno 182 y recibe el credito completo', async ({ page }) => {
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
        .filter({ hasText: '31/08/2026' })
        .filter({ hasText: /14:00\s*-\s*15:00/ })
        .filter({ hasText: 'Algoritmos y Estructuras de Datos I' })
        .filter({ hasText: /Luc.*a G.*mez/ });

    await expect(turno).toHaveCount(1);

    const suspender = turno.getByRole('button', { name: /Suspender clase/i });
    await expect(suspender).toBeVisible();
    await suspender.click();

    const modal = page.getByRole('dialog');
    await expect(modal.getByRole('heading', { name: /T.*rminos y condiciones de suspensi.*n/i })).toBeVisible();

    const aceptaTerminos = modal.locator('input[name="acepta_terminos"]');
    const confirmarSuspension = modal.getByRole('button', { name: /Confirmar suspensi.*n/i });

    await expect(aceptaTerminos).not.toBeChecked();
    await expect(confirmarSuspension).toBeDisabled();

    await aceptaTerminos.check();
    await expect(confirmarSuspension).toBeEnabled();

    await Promise.all([
        page.waitForURL(/\/alumno\/suspension-completada\/182(?:\?.*)?$/, {
            timeout: 30_000,
        }),
        confirmarSuspension.click(),
    ]);

    await expect(
        page.getByRole('heading', {
            level: 1,
            name: 'Clase suspendida correctamente',
            exact: true,
        }),
    ).toBeVisible();
    await expect(page.getByText(/Cr.*dito generado:/)).toBeVisible();
    await expect(page.getByText('$166,00', { exact: true })).toBeVisible();

    const accionPosterior = page.getByRole('link', { name: 'Reprogramar clase', exact: true });
    await expect(accionPosterior).toBeVisible();
    await expect(accionPosterior).toHaveAttribute('href', /\/alumno\/reprogramar-turno\?turno=182$/);
});
