import { expect, test } from '@playwright/test';

// Prueba intencionalmente persistente sobre sistema_2025: ejecutar una sola vez.
// No acepta/rechaza ofertas ni elimina los registros creados.
test.describe.configure({ retries: 0 });

test('Micaela crea una solicitud y Lucía recibe la oferta por matching automático', async ({ page }, testInfo) => {
    test.setTimeout(120_000);
    expect(testInfo.project.repeatEach, 'Este escenario no admite repeat-each.').toBe(1);

    const materia = 'Algoritmos y Estructuras de Datos I';
    const fechaVisible = '30/11/2026';
    const horario = /^14:00\s*[–-]\s*15:00$/;

    // Correlacionar la respuesta con el campo o método que originó la petición.
    const esperarActualizacionLivewire = ({ campo, metodo }) =>
        page.waitForResponse((response) => {
            const request = response.request();
            if (request.method() !== 'POST'
                || new URL(response.url()).pathname !== '/livewire/update'
                || !response.ok()) {
                return false;
            }

            return (request.postDataJSON()?.components ?? []).some((component) =>
                campo
                    ? Object.prototype.hasOwnProperty.call(component.updates ?? {}, campo)
                    : (component.calls ?? []).some((call) => call.method === metodo),
            );
        }, { timeout: 30_000 });

    // Las labels de este formulario no están asociadas a los controles.
    const campoSolicitud = (nombre) => page.locator(`[wire\\:model\\.live="${nombre}"]`);
    const completarCampo = async (nombre, valor) => {
        await Promise.all([
            esperarActualizacionLivewire({ campo: nombre }),
            campoSolicitud(nombre).fill(valor),
        ]);
        await expect(campoSolicitud(nombre)).toHaveValue(valor);
    };

    const filasDelEscenario = () => page.getByRole('row')
        .filter({ has: page.getByRole('cell', { name: materia, exact: true }) })
        .filter({ has: page.getByRole('cell', { name: fechaVisible, exact: true }) })
        .filter({ has: page.getByRole('cell', { name: horario }) });

    // Micaela Sosa, alumno ID 49.
    await page.goto('/alumno/login');
    await page.getByLabel(/^Correo electrónico\s*\*?$/).fill('alumno1@seed.com');
    await page.getByLabel(/^Contraseña\s*\*?$/).fill('password');
    await Promise.all([
        page.waitForURL(/\/alumno(?:\/dashboard)?(?:\?.*)?$/, { timeout: 30_000 }),
        page.getByRole('button', { name: 'Entrar', exact: true }).click(),
    ]);

    await page.goto('/alumno/solicitudes-disponibilidad');
    await expect(page.getByRole('heading', {
        name: 'Solicitudes de disponibilidad', exact: true, level: 1,
    })).toBeVisible();
    await expect(page.getByText('Mis solicitudes', { exact: true })).toBeVisible();

    const solicitud = filasDelEscenario();
    // Incluye cualquier estado: una solicitud cancelada/expirada también bloquea la repetición.
    await expect(solicitud, 'Ya existe la solicitud del escenario; no se creará otra.').toHaveCount(0);

    await expect(campoSolicitud('materiaId')).toBeEnabled();
    await expect(campoSolicitud('materiaId').getByRole('option', {
        name: materia, exact: true,
    })).toHaveAttribute('value', '25');
    await Promise.all([
        esperarActualizacionLivewire({ campo: 'materiaId' }),
        campoSolicitud('materiaId').selectOption('25'),
    ]);
    await expect(campoSolicitud('materiaId')).toHaveValue('25');
    await expect(campoSolicitud('temaId')).toBeEnabled();
    await expect(campoSolicitud('temaId')).toHaveValue('');
    await expect(campoSolicitud('expiresAt')).toHaveValue('');
    await completarCampo('fecha', '2026-11-30');
    await completarCampo('horaInicio', '14:00');
    await completarCampo('horaFin', '15:00');

    await expect(solicitud, 'La solicitud no debe existir antes de crearla.').toHaveCount(0);
    await Promise.all([
        esperarActualizacionLivewire({ metodo: 'crearSolicitud' }),
        page.getByRole('button', { name: 'Crear solicitud', exact: true }).click(),
    ]);
    await expect(page.getByText('Solicitud creada', { exact: true })).toBeVisible();
    await expect(solicitud).toHaveCount(1);
    await expect(solicitud).toBeVisible();
    await expect(solicitud.getByRole('cell', { name: 'activa', exact: true })).toBeVisible();
    await expect(solicitud.getByRole('cell', { name: 'Sin tema', exact: true })).toBeVisible();

    await page.getByRole('button', { name: 'Menú del Usuario', exact: true }).click();
    await Promise.all([
        page.waitForURL(/\/alumno\/login(?:\?.*)?$/, { timeout: 30_000 }),
        page.getByRole('button', { name: 'Salir', exact: true }).click(),
    ]);
    // Confirmar que la página protegida ya no permite acceder como Micaela.
    await page.goto('/alumno/solicitudes-disponibilidad');
    await expect(page).toHaveURL(/\/alumno\/login(?:\?.*)?$/);

    // Lucía Gómez, profesor ID 37.
    await page.goto('/profesor/login');
    await page.getByLabel(/^Correo electrónico\s*\*?$/).fill('profesor1@seed.com');
    await page.getByLabel(/^Contraseña\s*\*?$/).fill('password');
    await Promise.all([
        page.waitForURL(/\/profesor(?:\/dashboard)?(?:\?.*)?$/, { timeout: 30_000 }),
        page.getByRole('button', { name: 'Entrar', exact: true }).click(),
    ]);

    await page.goto('/profesor/ofertas-solicitudes');
    await expect(page.getByRole('heading', {
        name: 'Oferta de alumnos', exact: true, level: 1,
    })).toBeVisible();
    await Promise.all([
        esperarActualizacionLivewire({ metodo: 'cargar' }),
        page.getByRole('button', { name: 'Actualizar', exact: true }).click(),
    ]);

    const oferta = filasDelEscenario().filter({
        has: page.getByRole('cell', { name: 'Micaela Sosa', exact: true }),
    });
    await expect(oferta).toHaveCount(1);
    await expect(oferta).toBeVisible();
    for (const nombre of ['Aceptar', 'Rechazar']) {
        const accion = oferta.getByRole('button', { name: nombre, exact: true });
        await expect(accion).toBeVisible();
        await expect(accion).toBeEnabled();
    }

    const captura = testInfo.outputPath('oferta-micaela-2026-11-30.png');
    await page.screenshot({ path: captura, fullPage: true });
    await testInfo.attach('Oferta de Micaela visible para Lucía', {
        path: captura,
        contentType: 'image/png',
    });
});
