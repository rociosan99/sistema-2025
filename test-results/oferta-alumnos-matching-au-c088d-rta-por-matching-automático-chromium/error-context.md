# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: oferta-alumnos-matching-automatico.spec.js >> Micaela crea una solicitud y Lucía recibe la oferta por matching automático
- Location: tests\e2e\oferta-alumnos-matching-automatico.spec.js:7:1

# Error details

```
Test timeout of 120000ms exceeded.
```

```
Error: locator.fill: Test timeout of 120000ms exceeded.
Call log:
  - waiting for getByLabel(/^Correo electrónico\s*\*?$/)

```

# Page snapshot

```yaml
- generic [ref=e1]:
  - main [ref=e4]:
    - generic [ref=e6]:
      - generic [ref=e7]:
        - generic [ref=e8]: Portal Alumnos
        - heading "Entre a su cuenta" [level=1] [ref=e9]
      - generic [ref=e10]:
        - generic [ref=e13]:
          - generic [ref=e17]:
            - generic [ref=e20]:
              - generic [ref=e24]:
                - text: Correo electrónico
                - superscript [ref=e25]: "*"
              - textbox "Correo electrónico*" [active] [ref=e29]
            - generic [ref=e32]:
              - generic [ref=e36]:
                - text: Contraseña
                - superscript [ref=e37]: "*"
              - generic [ref=e39]:
                - textbox "Contraseña*" [ref=e41]
                - button "Mostrar contraseña" [ref=e44] [cursor=pointer]
            - generic [ref=e53]:
              - checkbox "Recordarme" [ref=e54]
              - generic [ref=e55]: Recordarme
          - button "Entrar" [ref=e61] [cursor=pointer]
        - link "Continuar con Google" [ref=e64] [cursor=pointer]:
          - /url: http://127.0.0.1:8000/auth/google?panel=alumno
  - generic:
    - status
```

# Test source

```ts
  1   | import { expect, test } from '@playwright/test';
  2   | 
  3   | // Prueba intencionalmente persistente sobre sistema_2025: ejecutar una sola vez.
  4   | // No acepta/rechaza ofertas ni elimina los registros creados.
  5   | test.describe.configure({ retries: 0 });
  6   | 
  7   | test('Micaela crea una solicitud y Lucía recibe la oferta por matching automático', async ({ page }, testInfo) => {
  8   |     test.setTimeout(120_000);
  9   |     expect(testInfo.project.repeatEach, 'Este escenario no admite repeat-each.').toBe(1);
  10  | 
  11  |     const materia = 'Algoritmos y Estructuras de Datos I';
  12  |     const fechaVisible = '30/11/2026';
  13  |     const horario = /^14:00\s*[–-]\s*15:00$/;
  14  | 
  15  |     // Correlacionar la respuesta con el campo o método que originó la petición.
  16  |     const esperarActualizacionLivewire = ({ campo, metodo }) =>
  17  |         page.waitForResponse((response) => {
  18  |             const request = response.request();
  19  |             if (request.method() !== 'POST'
  20  |                 || new URL(response.url()).pathname !== '/livewire/update'
  21  |                 || !response.ok()) {
  22  |                 return false;
  23  |             }
  24  | 
  25  |             return (request.postDataJSON()?.components ?? []).some((component) =>
  26  |                 campo
  27  |                     ? Object.prototype.hasOwnProperty.call(component.updates ?? {}, campo)
  28  |                     : (component.calls ?? []).some((call) => call.method === metodo),
  29  |             );
  30  |         }, { timeout: 30_000 });
  31  | 
  32  |     // Las labels de este formulario no están asociadas a los controles.
  33  |     const campoSolicitud = (nombre) => page.locator(`[wire\\:model\\.live="${nombre}"]`);
  34  |     const completarCampo = async (nombre, valor) => {
  35  |         await Promise.all([
  36  |             esperarActualizacionLivewire({ campo: nombre }),
  37  |             campoSolicitud(nombre).fill(valor),
  38  |         ]);
  39  |         await expect(campoSolicitud(nombre)).toHaveValue(valor);
  40  |     };
  41  | 
  42  |     const filasDelEscenario = () => page.getByRole('row')
  43  |         .filter({ has: page.getByRole('cell', { name: materia, exact: true }) })
  44  |         .filter({ has: page.getByRole('cell', { name: fechaVisible, exact: true }) })
  45  |         .filter({ has: page.getByRole('cell', { name: horario }) });
  46  | 
  47  |     // Micaela Sosa, alumno ID 49.
  48  |     await page.goto('/alumno/login');
> 49  |     await page.getByLabel(/^Correo electrónico\s*\*?$/).fill('alumno1@seed.com');
      |                                                         ^ Error: locator.fill: Test timeout of 120000ms exceeded.
  50  |     await page.getByLabel(/^Contraseña\s*\*?$/).fill('password');
  51  |     await Promise.all([
  52  |         page.waitForURL(/\/alumno(?:\/dashboard)?(?:\?.*)?$/, { timeout: 30_000 }),
  53  |         page.getByRole('button', { name: 'Entrar', exact: true }).click(),
  54  |     ]);
  55  | 
  56  |     await page.goto('/alumno/solicitudes-disponibilidad');
  57  |     await expect(page.getByRole('heading', {
  58  |         name: 'Solicitudes de disponibilidad', exact: true, level: 1,
  59  |     })).toBeVisible();
  60  |     await expect(page.getByText('Mis solicitudes', { exact: true })).toBeVisible();
  61  | 
  62  |     const solicitud = filasDelEscenario();
  63  |     // Incluye cualquier estado: una solicitud cancelada/expirada también bloquea la repetición.
  64  |     await expect(solicitud, 'Ya existe la solicitud del escenario; no se creará otra.').toHaveCount(0);
  65  | 
  66  |     await expect(campoSolicitud('materiaId')).toBeEnabled();
  67  |     await expect(campoSolicitud('materiaId').getByRole('option', {
  68  |         name: materia, exact: true,
  69  |     })).toHaveAttribute('value', '25');
  70  |     await Promise.all([
  71  |         esperarActualizacionLivewire({ campo: 'materiaId' }),
  72  |         campoSolicitud('materiaId').selectOption('25'),
  73  |     ]);
  74  |     await expect(campoSolicitud('materiaId')).toHaveValue('25');
  75  |     await expect(campoSolicitud('temaId')).toBeEnabled();
  76  |     await expect(campoSolicitud('temaId')).toHaveValue('');
  77  |     await expect(campoSolicitud('expiresAt')).toHaveValue('');
  78  |     await completarCampo('fecha', '2026-11-30');
  79  |     await completarCampo('horaInicio', '14:00');
  80  |     await completarCampo('horaFin', '15:00');
  81  | 
  82  |     await expect(solicitud, 'La solicitud no debe existir antes de crearla.').toHaveCount(0);
  83  |     await Promise.all([
  84  |         esperarActualizacionLivewire({ metodo: 'crearSolicitud' }),
  85  |         page.getByRole('button', { name: 'Crear solicitud', exact: true }).click(),
  86  |     ]);
  87  |     await expect(page.getByText('Solicitud creada', { exact: true })).toBeVisible();
  88  |     await expect(solicitud).toHaveCount(1);
  89  |     await expect(solicitud).toBeVisible();
  90  |     await expect(solicitud.getByRole('cell', { name: 'activa', exact: true })).toBeVisible();
  91  |     await expect(solicitud.getByRole('cell', { name: 'Sin tema', exact: true })).toBeVisible();
  92  | 
  93  |     await page.getByRole('button', { name: 'Menú del Usuario', exact: true }).click();
  94  |     await Promise.all([
  95  |         page.waitForURL(/\/alumno\/login(?:\?.*)?$/, { timeout: 30_000 }),
  96  |         page.getByRole('button', { name: 'Salir', exact: true }).click(),
  97  |     ]);
  98  |     // Confirmar que la página protegida ya no permite acceder como Micaela.
  99  |     await page.goto('/alumno/solicitudes-disponibilidad');
  100 |     await expect(page).toHaveURL(/\/alumno\/login(?:\?.*)?$/);
  101 | 
  102 |     // Lucía Gómez, profesor ID 37.
  103 |     await page.goto('/profesor/login');
  104 |     await page.getByLabel(/^Correo electrónico\s*\*?$/).fill('profesor1@seed.com');
  105 |     await page.getByLabel(/^Contraseña\s*\*?$/).fill('password');
  106 |     await Promise.all([
  107 |         page.waitForURL(/\/profesor(?:\/dashboard)?(?:\?.*)?$/, { timeout: 30_000 }),
  108 |         page.getByRole('button', { name: 'Entrar', exact: true }).click(),
  109 |     ]);
  110 | 
  111 |     await page.goto('/profesor/ofertas-solicitudes');
  112 |     await expect(page.getByRole('heading', {
  113 |         name: 'Oferta de alumnos', exact: true, level: 1,
  114 |     })).toBeVisible();
  115 |     await Promise.all([
  116 |         esperarActualizacionLivewire({ metodo: 'cargar' }),
  117 |         page.getByRole('button', { name: 'Actualizar', exact: true }).click(),
  118 |     ]);
  119 | 
  120 |     const oferta = filasDelEscenario().filter({
  121 |         has: page.getByRole('cell', { name: 'Micaela Sosa', exact: true }),
  122 |     });
  123 |     await expect(oferta).toHaveCount(1);
  124 |     await expect(oferta).toBeVisible();
  125 |     for (const nombre of ['Aceptar', 'Rechazar']) {
  126 |         const accion = oferta.getByRole('button', { name: nombre, exact: true });
  127 |         await expect(accion).toBeVisible();
  128 |         await expect(accion).toBeEnabled();
  129 |     }
  130 | 
  131 |     const captura = testInfo.outputPath('oferta-micaela-2026-11-30.png');
  132 |     await page.screenshot({ path: captura, fullPage: true });
  133 |     await testInfo.attach('Oferta de Micaela visible para Lucía', {
  134 |         path: captura,
  135 |         contentType: 'image/png',
  136 |     });
  137 | });
  138 | 
```