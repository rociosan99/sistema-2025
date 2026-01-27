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
use Filament\Notifications\Notification;
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

    // 🔹 Contexto
    public ?int $profesorId = null;
    public ?int $materiaId  = null;
    public ?int $temaId     = null;

    public ?User $profesor   = null;
    public ?Materia $materia = null;
    public ?Tema $tema       = null;

    // 🔹 Buscador
    public ?string $busqueda = null;
    public array $sugerenciasMaterias = [];
    public array $sugerenciasTemas = [];

    // 🔹 Fecha
    public ?string $fecha = null;

    // 🔹 Slots
    public array $slots = [];

    protected SlotService $slotService;

    public function boot(SlotService $slotService): void
    {
        $this->slotService = $slotService;
    }

    public function mount(?int $profesor = null, ?int $materia = null, ?int $tema = null): void
    {
        if ($profesor) {
            $this->profesorId = $profesor;
            $this->profesor = User::find($profesor);
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

    /* =========================
       🔍 BUSCADOR (NO TOCAR)
       ========================= */

    public function updatedBusqueda(string $value): void
    {
        if (mb_strlen($value) < 2) {
            $this->sugerenciasMaterias = [];
            $this->sugerenciasTemas = [];
            return;
        }

        $this->sugerenciasMaterias = Materia::where('materia_nombre', 'like', "%$value%")
            ->limit(5)
            ->get(['materia_id', 'materia_nombre'])
            ->toArray();

        $this->sugerenciasTemas = Tema::where('tema_nombre', 'like', "%$value%")
            ->limit(5)
            ->get(['tema_id', 'tema_nombre'])
            ->toArray();
    }

    public function seleccionarMateria(int $materiaId, string $nombre): void
    {
        $this->materiaId = $materiaId;
        $this->materia = Materia::find($materiaId);
        $this->busqueda = $nombre;

        // ✅ si elige materia, el tema se limpia.
        $this->temaId = null;
        $this->tema = null;

        $this->sugerenciasMaterias = [];
        $this->sugerenciasTemas = [];
    }

    public function seleccionarTema(int $temaId, string $nombre): void
    {
        $this->temaId = $temaId;
        $this->tema = Tema::find($temaId);
        $this->busqueda = $nombre;

        $this->sugerenciasMaterias = [];
        $this->sugerenciasTemas = [];

        $materiaId = DB::table('programa_tema')
            ->join('programas', 'programa_tema.programa_id', '=', 'programas.programa_id')
            ->where('programa_tema.tema_id', $temaId)
            ->value('programas.programa_materia_id');

        if ($materiaId) {
            $this->materiaId = $materiaId;
            $this->materia = Materia::find($materiaId);
        }
    }

    /* =========================
       CONSULTAR DISPONIBILIDAD
       ========================= */

    public function consultarAhora(): void
    {
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

        $this->slots = $this->slotService
            ->obtenerSlotsPorMateria($this->materiaId, $fecha, $this->temaId)
            ->toArray();
    }

    /* =========================
       RESERVAR TURNO (VALIDADO)
       ========================= */

    public function reservar(int $index): void
    {
        $alumno = Auth::user();

        if (! isset($this->slots[$index])) {
            throw ValidationException::withMessages([
                'slot' => 'Horario inválido.',
            ]);
        }

        $slot = $this->slots[$index];

        DB::transaction(function () use ($slot, $alumno) {

            // 🔒 VALIDACIÓN DE SOLAPAMIENTO (por PROFESOR, no importa la materia)
            $hayChoque = Turno::where('profesor_id', $slot['profesor_id'])
                ->whereDate('fecha', $slot['fecha'])
                ->where(function ($q) use ($slot) {
                    $q->where('hora_inicio', '<', $slot['hora_fin'])
                      ->where('hora_fin', '>', $slot['hora_inicio']);
                })
                ->whereIn('estado', ['pendiente', 'aceptado', 'pendiente_pago', 'confirmado'])
                ->lockForUpdate()
                ->exists();

            if ($hayChoque) {
                throw ValidationException::withMessages([
                    'slot' => 'Ese horario ya no está disponible.',
                ]);
            }

            $turno = Turno::create([
                'alumno_id'       => $alumno->id,
                'profesor_id'     => $slot['profesor_id'],
                'materia_id'      => $this->materiaId,
                'tema_id'         => $this->temaId ?: null,
                'fecha'           => $slot['fecha'],
                'hora_inicio'     => $slot['hora_inicio'],
                'hora_fin'        => $slot['hora_fin'],
                'estado'          => 'pendiente', // ✅ siempre pendiente al reservar
                'precio_por_hora' => $slot['precio_por_hora'] ?? null,
                'precio_total'    => $slot['precio_total'] ?? null,
            ]);

            // por si alguna relación no estaba cargada aún
            $turno->loadMissing(['profesor']);

            Mail::to($turno->profesor->email)
                ->send(new TurnoSolicitado($turno));
        });

        Notification::make()
            ->title('Turno solicitado')
            ->body('El profesor recibirá tu solicitud.')
            ->success()
            ->send();

        // refrescar slots para que desaparezca el que reservó
        $this->consultarAhora();
    }
}
