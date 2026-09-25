<?php

namespace Tests\Feature\Profesor;

use App\Filament\Profesor\Resources\MisClases\Pages\ListMisClases;
use App\Models\CalificacionProfesor;
use App\Models\Materia;
use App\Models\Turno;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class MisClasesFiltersTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase;

    private User $profesor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-09-25 12:00:00');
        $this->profesor = $this->crearProfesor([
            'name' => 'Profesor',
            'apellido' => 'Historial',
        ]);
        $this->actingAs($this->profesor);
        Filament::setCurrentPanel(Filament::getPanel('profesor'));
    }

    public function test_conserva_el_conjunto_base_del_historial_del_profesor(): void
    {
        $materia = $this->crearMateria();
        $alumno = $this->crearAlumno();
        $otroProfesor = $this->crearProfesor();

        $pasadoConfirmado = $this->crearClase($alumno, $materia, ['fecha' => '2026-09-24']);
        $hoyFinalizado = $this->crearClase($alumno, $materia, [
            'fecha' => '2026-09-25',
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
        ]);
        $hoyEnCurso = $this->crearClase($alumno, $materia, [
            'fecha' => '2026-09-25',
            'hora_inicio' => '11:30:00',
            'hora_fin' => '12:30:00',
        ]);
        $pasadoPendiente = $this->crearClase($alumno, $materia, [
            'fecha' => '2026-09-24',
            'estado' => Turno::ESTADO_PENDIENTE,
        ]);
        $deOtroProfesor = $this->crearTurno($alumno, $otroProfesor, $materia, [
            'fecha' => '2026-09-24',
            'estado' => Turno::ESTADO_CONFIRMADO,
        ]);

        Livewire::test(ListMisClases::class)
            ->assertCanSeeTableRecords([$pasadoConfirmado, $hoyFinalizado])
            ->assertCanNotSeeTableRecords([$hoyEnCurso, $pasadoPendiente, $deOtroProfesor]);
    }

    public function test_filtra_por_materia(): void
    {
        $alumno = $this->crearAlumno();
        $materiaBuscada = $this->crearMateria(['materia_nombre' => 'Bases de Datos']);
        $otraMateria = $this->crearMateria(['materia_nombre' => 'Redes']);
        $coincidente = $this->crearClase($alumno, $materiaBuscada);
        $distinta = $this->crearClase($alumno, $otraMateria);

        Livewire::test(ListMisClases::class)
            ->filterTable('materia_id', $materiaBuscada->materia_id)
            ->assertCanSeeTableRecords([$coincidente])
            ->assertCanNotSeeTableRecords([$distinta]);
    }

    public function test_filtra_por_alumno(): void
    {
        $materia = $this->crearMateria();
        $alumnoBuscado = $this->crearAlumno(['name' => 'Micaela', 'apellido' => 'Sosa']);
        $otroAlumno = $this->crearAlumno(['name' => 'Facundo', 'apellido' => 'Perez']);
        $coincidente = $this->crearClase($alumnoBuscado, $materia);
        $distinta = $this->crearClase($otroAlumno, $materia);

        Livewire::test(ListMisClases::class)
            ->filterTable('alumno_id', $alumnoBuscado->id)
            ->assertCanSeeTableRecords([$coincidente])
            ->assertCanNotSeeTableRecords([$distinta]);
    }

    public function test_filtra_desde_una_fecha_de_clase_inclusive(): void
    {
        $anterior = $this->crearClaseConDatos(['fecha' => '2026-09-10']);
        $desde = $this->crearClaseConDatos(['fecha' => '2026-09-15']);

        Livewire::test(ListMisClases::class)
            ->filterTable('fecha_clase', ['desde' => '2026-09-15', 'hasta' => null])
            ->assertCanSeeTableRecords([$desde])
            ->assertCanNotSeeTableRecords([$anterior]);
    }

    public function test_filtra_hasta_una_fecha_de_clase_inclusive(): void
    {
        $hasta = $this->crearClaseConDatos(['fecha' => '2026-09-15']);
        $posterior = $this->crearClaseConDatos(['fecha' => '2026-09-20']);

        Livewire::test(ListMisClases::class)
            ->filterTable('fecha_clase', ['desde' => null, 'hasta' => '2026-09-15'])
            ->assertCanSeeTableRecords([$hasta])
            ->assertCanNotSeeTableRecords([$posterior]);
    }

    public function test_filtra_por_rango_inclusive_de_fecha_de_clase(): void
    {
        $antes = $this->crearClaseConDatos(['fecha' => '2026-09-09']);
        $inicio = $this->crearClaseConDatos(['fecha' => '2026-09-10']);
        $fin = $this->crearClaseConDatos(['fecha' => '2026-09-20']);
        $despues = $this->crearClaseConDatos(['fecha' => '2026-09-21']);

        Livewire::test(ListMisClases::class)
            ->filterTable('fecha_clase', ['desde' => '2026-09-10', 'hasta' => '2026-09-20'])
            ->assertCanSeeTableRecords([$inicio, $fin])
            ->assertCanNotSeeTableRecords([$antes, $despues]);
    }

    #[DataProvider('busquedas')]
    public function test_busca_por_alumno_y_materia(string $campo, string $valor, string $busqueda): void
    {
        $alumno = $this->crearAlumno($campo === 'materia' ? [] : [$campo => $valor]);
        $materia = $this->crearMateria($campo === 'materia' ? ['materia_nombre' => $valor] : []);
        $encontrada = $this->crearClase($alumno, $materia);
        $noRelacionada = $this->crearClase(
            $this->crearAlumno(['name' => 'AlumnoDistinto', 'apellido' => 'SinCoincidencia']),
            $this->crearMateria(['materia_nombre' => 'Materia Diferente']),
        );

        Livewire::test(ListMisClases::class)
            ->searchTable($busqueda)
            ->assertCanSeeTableRecords([$encontrada])
            ->assertCanNotSeeTableRecords([$noRelacionada]);
    }

    public static function busquedas(): array
    {
        return [
            'Nombre del alumno' => ['name', 'MicaelaBuscada', 'MicaelaBuscada'],
            'Apellido del alumno' => ['apellido', 'SosaBuscada', 'SosaBuscada'],
            'Materia' => ['materia', 'Algoritmos Buscada', 'Algoritmos Buscada'],
        ];
    }

    public function test_combina_materia_alumno_y_rango_de_fechas(): void
    {
        $materia = $this->crearMateria(['materia_nombre' => 'Programacion Concurrente']);
        $otraMateria = $this->crearMateria(['materia_nombre' => 'Sistemas Operativos']);
        $alumno = $this->crearAlumno(['name' => 'Alumno', 'apellido' => 'Objetivo']);
        $otroAlumno = $this->crearAlumno();
        $coincidente = $this->crearClase($alumno, $materia, ['fecha' => '2026-09-15']);
        $alumnoDistinto = $this->crearClase($otroAlumno, $materia, ['fecha' => '2026-09-15']);
        $materiaDistinta = $this->crearClase($alumno, $otraMateria, ['fecha' => '2026-09-15']);
        $fueraDeRango = $this->crearClase($alumno, $materia, ['fecha' => '2026-09-05']);

        Livewire::test(ListMisClases::class)
            ->filterTable('materia_id', $materia->materia_id)
            ->filterTable('alumno_id', $alumno->id)
            ->filterTable('fecha_clase', ['desde' => '2026-09-10', 'hasta' => '2026-09-20'])
            ->assertCanSeeTableRecords([$coincidente])
            ->assertCanNotSeeTableRecords([$alumnoDistinto, $materiaDistinta, $fueraDeRango]);
    }

    #[DataProvider('calificaciones')]
    public function test_filtra_por_calificacion_del_profesor(int $estrellas): void
    {
        $coincidente = $this->crearClaseConDatos();
        $distinta = $this->crearClaseConDatos();
        $this->calificar($coincidente, $estrellas);
        $this->calificar($distinta, $estrellas === 5 ? 4 : 5);

        Livewire::test(ListMisClases::class)
            ->filterTable('calificacion', $estrellas)
            ->assertCanSeeTableRecords([$coincidente])
            ->assertCanNotSeeTableRecords([$distinta]);
    }

    public static function calificaciones(): array
    {
        return [
            '1 estrella' => [1],
            '2 estrellas' => [2],
            '3 estrellas' => [3],
            '4 estrellas' => [4],
            '5 estrellas' => [5],
        ];
    }

    public function test_muestra_los_filtros_sobre_el_contenido(): void
    {
        $tabla = Livewire::test(ListMisClases::class)->instance()->getTable();

        $this->assertSame(FiltersLayout::AboveContent, $tabla->getFiltersLayout());
        $this->assertSame(5, $tabla->getFiltersFormColumns());
    }

    private function crearClaseConDatos(array $turno = []): Turno
    {
        return $this->crearClase($this->crearAlumno(), $this->crearMateria(), $turno);
    }

    private function crearClase(User $alumno, Materia $materia, array $attributes = []): Turno
    {
        return $this->crearTurno($alumno, $this->profesor, $materia, array_merge([
            'fecha' => '2026-09-20',
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'estado' => Turno::ESTADO_CONFIRMADO,
        ], $attributes));
    }

    private function calificar(Turno $turno, int $estrellas): void
    {
        CalificacionProfesor::query()->create([
            'turno_id' => $turno->id,
            'alumno_id' => $turno->alumno_id,
            'profesor_id' => $turno->profesor_id,
            'estrellas' => $estrellas,
            'comentario' => null,
        ]);
    }
}
