<?php

namespace App\Filament\Alumno\Pages;

use App\Mail\TurnoSolicitado;
use App\Models\Materia;
use App\Models\Tema;
use App\Models\Turno;
use App\Models\User;
use App\Services\SlotService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SolicitarTurno extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Solicitar turno';
    protected string $view = 'filament.alumno.pages.solicitar-turno';

    public ?int $profesorId = null;
    public ?int $materiaId  = null;
    public ?int $temaId     = null;

    public ?User $profesor   = null;
    public ?Materia $materia = null;
    public ?Tema $tema       = null;

    public ?string $busqueda = null;
    public array $sugerenciasMaterias = [];
    public array $sugerenciasTemas = [];

    public ?string $fecha = null;
    public array $slotsOriginales = [];
    public array $slots = [];
    public ?int $filtroProfesorId = null;
    public ?string $filtroHoraDesde = null;
    public ?string $filtroHoraHasta = null;
    public array $profesoresDisponibles = [];

    public bool $mostrarModalExito = false;

    protected SlotService $slotService;

    public function boot(SlotService $slotService): void
    {
        $this->slotService = $slotService;
    }

    private function carreraIdActiva(): ?int
    {
        return Auth::user()?->carrera_activa_id;
    }

    public function mount(?int $profesor = null, ?int $materia = null, ?int $tema = null): void
    {
        if ($profesor) {
            $this->profesor = User::query()
                ->whereKey($profesor)
                ->where('role', 'profesor')
                ->where('activo', true)
                ->first();

            if ($this->profesor) {
                $this->profesorId = $this->profesor->id;
            }
        }

        if ($materia) {
            $this->materiaId = $materia;
            $this->materia = Materia::find($materia);
        }

        if ($tema) {
            $this->temaId = $tema;
            $this->tema = Tema::find($tema);
        }

        if ($this->materia && $this->tema) {
            $this->busqueda = "{$this->materia->materia_nombre} - {$this->tema->tema_nombre}";
        } elseif ($this->materia) {
            $this->busqueda = "{$this->materia->materia_nombre}";
        } elseif ($this->tema) {
            $this->busqueda = "{$this->tema->tema_nombre}";
        }
    }

    public function updatedBusqueda(string $value): void
    {
        if (mb_strlen($value) < 2) {
            $this->sugerenciasMaterias = [];
            $this->sugerenciasTemas = [];
            return;
        }

        $carreraId = $this->carreraIdActiva();

        if (! $carreraId) {
            $this->sugerenciasMaterias = [];
            $this->sugerenciasTemas = [];
            return;
        }

        $this->sugerenciasMaterias = Materia::query()
            ->join('programas', 'programas.programa_materia_id', '=', 'materias.materia_id')
            ->join('planes_estudio', 'planes_estudio.plan_id', '=', 'programas.programa_plan_id')
            ->where('planes_estudio.plan_carrera_id', $carreraId)
            ->where('materias.materia_nombre', 'like', "%{$value}%")
            ->select('materias.materia_id', 'materias.materia_nombre')
            ->distinct()
            ->limit(5)
            ->get()
            ->toArray();

        $this->sugerenciasTemas = Tema::query()
            ->join('programa_tema', 'programa_tema.tema_id', '=', 'temas.tema_id')
            ->join('programas', 'programas.programa_id', '=', 'programa_tema.programa_id')
            ->join('planes_estudio', 'planes_estudio.plan_id', '=', 'programas.programa_plan_id')
            ->where('planes_estudio.plan_carrera_id', $carreraId)
            ->where('temas.tema_nombre', 'like', "%{$value}%")
            ->select('temas.tema_id', 'temas.tema_nombre')
            ->distinct()
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function seleccionarMateria(int $materiaId, string $nombre): void
    {
        $this->limpiarFiltrosYResultados();
        $this->materiaId = $materiaId;
        $this->materia = Materia::find($materiaId);
        $this->busqueda = $nombre;

        $this->temaId = null;
        $this->tema = null;

        $this->sugerenciasMaterias = [];
        $this->sugerenciasTemas = [];
    }

    public function seleccionarTema(int $temaId, string $nombre): void
    {
        $this->limpiarFiltrosYResultados();
        $this->temaId = $temaId;
        $this->tema = Tema::find($temaId);
        $this->busqueda = $nombre;

        $this->sugerenciasMaterias = [];
        $this->sugerenciasTemas = [];

        $carreraId = $this->carreraIdActiva();

        if (! $carreraId) {
            return;
        }

        $materiaId = DB::table('programa_tema')
            ->join('programas', 'programa_tema.programa_id', '=', 'programas.programa_id')
            ->join('planes_estudio', 'planes_estudio.plan_id', '=', 'programas.programa_plan_id')
            ->where('programa_tema.tema_id', $temaId)
            ->where('planes_estudio.plan_carrera_id', $carreraId)
            ->value('programas.programa_materia_id');

        if ($materiaId) {
            $this->materiaId = (int) $materiaId;
            $this->materia = Materia::find($materiaId);
        }
    }

    public function updatedFecha(?string $value): void
    {
        $this->profesorId = null;
        $this->profesor = null;
        $this->limpiarFiltrosYResultados();

        $this->resetValidation(['fecha', 'slot']);

        if (! $value || ! $this->materiaId) {
            return;
        }

        $this->consultarAhora();
    }

    public function consultarAhora(): void
    {
        if (! $this->carreraIdActiva()) {
            throw ValidationException::withMessages([
                'busqueda' => 'Completá tu perfil (carrera activa) para poder buscar turnos.',
            ]);
        }

        if (! $this->fecha) {
            throw ValidationException::withMessages([
                'fecha' => 'Seleccioná una fecha.',
            ]);
        }

        if (! $this->materiaId) {
            throw ValidationException::withMessages([
                'busqueda' => 'Seleccioná una materia o tema.',
            ]);
        }

        $fecha = Carbon::createFromFormat('Y-m-d', $this->fecha);

        if ($fecha->isPast() && ! $fecha->isToday()) {
            throw ValidationException::withMessages([
                'fecha' => 'No podés reservar fechas pasadas.',
            ]);
        }

        $slots = $this->slotService
            ->obtenerSlotsPorMateria($this->materiaId, $fecha, $this->temaId)
            ->toArray();

        $this->slotsOriginales = $this->agregarClavesDeSlot(
            $this->filtrarSlotsProfesoresActivos($slots)
        );
        $this->actualizarProfesoresDisponibles();
        $this->aplicarFiltros();
    }

    public function aplicarFiltros(): void
    {
        $this->validate([
            'filtroProfesorId' => ['nullable', 'integer'],
            'filtroHoraDesde' => ['nullable', 'date_format:H:i'],
            'filtroHoraHasta' => ['nullable', 'date_format:H:i'],
        ]);

        $erroresHorasExactas = [];

        if ($this->filtroHoraDesde && substr($this->filtroHoraDesde, 3, 2) !== '00') {
            $erroresHorasExactas['filtroHoraDesde'] = 'Solo se permiten horas exactas, por ejemplo 17:00 o 18:00.';
        }

        if ($this->filtroHoraHasta && substr($this->filtroHoraHasta, 3, 2) !== '00') {
            $erroresHorasExactas['filtroHoraHasta'] = 'Solo se permiten horas exactas, por ejemplo 17:00 o 18:00.';
        }

        if ($erroresHorasExactas) {
            throw ValidationException::withMessages($erroresHorasExactas);
        }

        if (
            $this->filtroHoraDesde
            && $this->filtroHoraHasta
            && $this->filtroHoraDesde >= $this->filtroHoraHasta
        ) {
            throw ValidationException::withMessages([
                'filtroHoraHasta' => 'La hora desde debe ser menor que la hora hasta.',
            ]);
        }

        $this->slots = array_values(array_filter(
            $this->slotsOriginales,
            function (array $slot): bool {
                if (
                    $this->filtroProfesorId
                    && (int) $slot['profesor_id'] !== $this->filtroProfesorId
                ) {
                    return false;
                }

                $horaInicio = substr((string) $slot['hora_inicio'], 0, 5);
                $horaFin = substr((string) $slot['hora_fin'], 0, 5);

                if ($this->filtroHoraDesde && $horaInicio < $this->filtroHoraDesde) {
                    return false;
                }

                if ($this->filtroHoraHasta && $horaFin > $this->filtroHoraHasta) {
                    return false;
                }

                return true;
            }
        ));
    }

    public function limpiarFiltros(): void
    {
        $this->filtroProfesorId = null;
        $this->filtroHoraDesde = null;
        $this->filtroHoraHasta = null;
        $this->resetValidation([
            'filtroProfesorId',
            'filtroHoraDesde',
            'filtroHoraHasta',
        ]);
        $this->slots = array_values($this->slotsOriginales);
    }

    public function reservar(string $slotKey): void
    {
        $alumno = Auth::user();
        $slot = collect($this->slots)->first(
            fn (array $slot): bool => ($slot['slot_key'] ?? null) === $slotKey
        );

        if (! $slot) {
            throw ValidationException::withMessages([
                'slot' => 'Horario inválido.',
            ]);
        }

        DB::transaction(function () use ($slot, $alumno) {
            $alumnoBloqueado = User::query()
                ->whereKey($alumno->id)
                ->where('role', 'alumno')
                ->lockForUpdate()
                ->first();

            if (! $alumnoBloqueado) {
                throw ValidationException::withMessages([
                    'slot' => 'No se pudo validar al alumno.',
                ]);
            }

            $profesorActivo = User::query()
                ->whereKey($slot['profesor_id'])
                ->where('role', 'profesor')
                ->where('activo', true)
                ->lockForUpdate()
                ->first();

            if (! $profesorActivo) {
                throw ValidationException::withMessages([
                    'slot' => 'El profesor de ese horario ya no está disponible.',
                ]);
            }

            $estadosOcupados = [
                Turno::ESTADO_PENDIENTE,
                Turno::ESTADO_ACEPTADO,
                Turno::ESTADO_PENDIENTE_PAGO,
                Turno::ESTADO_CONFIRMADO,
            ];

            $hayChoqueAlumno = Turno::query()
                ->where('alumno_id', $alumnoBloqueado->id)
                ->whereDate('fecha', $slot['fecha'])
                ->where(function ($q) use ($slot) {
                    $q->where('hora_inicio', '<', $slot['hora_fin'])
                        ->where('hora_fin', '>', $slot['hora_inicio']);
                })
                ->whereIn('estado', $estadosOcupados)
                ->lockForUpdate()
                ->exists();

            if ($hayChoqueAlumno) {
                throw ValidationException::withMessages([
                    'slot' => 'Ya tenés otro turno en ese día y horario.',
                ]);
            }

            $hayChoqueProfesor = Turno::query()
                ->where('profesor_id', $profesorActivo->id)
                ->whereDate('fecha', $slot['fecha'])
                ->where(function ($q) use ($slot) {
                    $q->where('hora_inicio', '<', $slot['hora_fin'])
                        ->where('hora_fin', '>', $slot['hora_inicio']);
                })
                ->whereIn('estado', $estadosOcupados)
                ->lockForUpdate()
                ->exists();

            if ($hayChoqueProfesor) {
                throw ValidationException::withMessages([
                    'slot' => 'Ese horario ya no está disponible.',
                ]);
            }

            $turno = Turno::create([
                'alumno_id'       => $alumno->id,
                'profesor_id'     => $profesorActivo->id,
                'materia_id'      => $this->materiaId,
                'tema_id'         => $this->temaId ?: null,
                'fecha'           => $slot['fecha'],
                'hora_inicio'     => $slot['hora_inicio'],
                'hora_fin'        => $slot['hora_fin'],
                'estado'          => 'pendiente',
                'precio_por_hora' => $slot['precio_por_hora'] ?? null,
                'precio_total'    => $slot['precio_total'] ?? null,
            ]);

            $turno->loadMissing(['profesor', 'alumno', 'materia', 'tema']);

            Mail::to($turno->profesor->email)
                ->send(new TurnoSolicitado($turno));
        });

        $this->consultarAhora();
        $this->mostrarModalExito = true;
    }

    public function cerrarModalExito(): void
    {
        $this->mostrarModalExito = false;
    }

    private function limpiarFiltrosYResultados(): void
    {
        $this->filtroProfesorId = null;
        $this->filtroHoraDesde = null;
        $this->filtroHoraHasta = null;
        $this->slotsOriginales = [];
        $this->slots = [];
        $this->profesoresDisponibles = [];
        $this->resetValidation([
            'filtroProfesorId',
            'filtroHoraDesde',
            'filtroHoraHasta',
        ]);
    }

    private function agregarClavesDeSlot(array $slots): array
    {
        return array_values(array_map(function (array $slot): array {
            $slot['slot_key'] = hash('sha256', implode('|', [
                $slot['profesor_id'],
                $slot['fecha'],
                $slot['hora_inicio'],
                $slot['hora_fin'],
            ]));

            return $slot;
        }, $slots));
    }

    private function actualizarProfesoresDisponibles(): void
    {
        $profesores = [];

        foreach ($this->slotsOriginales as $slot) {
            $profesorId = (int) $slot['profesor_id'];
            $profesores[$profesorId] = [
                'id' => $profesorId,
                'nombre' => $slot['profesor_nombre'] ?? 'Profesor',
            ];
        }

        $this->profesoresDisponibles = array_values($profesores);

        usort(
            $this->profesoresDisponibles,
            fn (array $a, array $b): int => strcasecmp($a['nombre'], $b['nombre'])
        );
    }

    private function filtrarSlotsProfesoresActivos(array $slots): array
    {
        $profesoresIds = collect($slots)
            ->pluck('profesor_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($profesoresIds)) {
            return [];
        }

        $activos = User::query()
            ->whereIn('id', $profesoresIds)
            ->where('role', 'profesor')
            ->where('activo', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_filter($slots, function (array $slot) use ($activos) {
            return in_array((int) ($slot['profesor_id'] ?? 0), $activos, true);
        }));
    }
}
