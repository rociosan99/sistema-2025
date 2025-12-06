<?php

namespace App\Filament\Alumno\Pages;

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
use Illuminate\Validation\ValidationException;

class SolicitarTurno extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Solicitar turno';

    protected string $view = 'filament.alumno.pages.solicitar-turno';

    // 🔹 Datos de contexto
    public ?int $profesorId = null; // ahora es opcional (se resuelve por materia)
    public ?int $materiaId  = null;
    public ?int $temaId     = null;

    public ?User $profesor   = null;
    public ?Materia $materia = null;
    public ?Tema $tema       = null;

    // 🔹 Buscador de materia/tema
    public ?string $busqueda          = null;
    public array $sugerenciasMaterias = [];
    public array $sugerenciasTemas    = [];

    // 🔹 Fecha seleccionada (Y-m-d)
    public ?string $fecha = null;

    // 🔹 Slots generados
    public array $slots = [];

    protected SlotService $slotService;

    public function boot(SlotService $slotService): void
    {
        $this->slotService = $slotService;
    }

    /**
     * Puede recibir:
     * /alumno/solicitar-turno/{profesor?}/{materia?}/{tema?}
     */
    public function mount(?int $profesor = null, ?int $materia = null, ?int $tema = null): void
    {
        if ($profesor !== null) {
            $this->profesorId = $profesor;
            $this->profesor   = User::find($profesor);
        }

        if ($materia !== null) {
            $this->materiaId = $materia;
            $this->materia   = Materia::find($materia);
        }

        if ($tema !== null) {
            $this->temaId = $tema;
            $this->tema   = Tema::find($tema);
        }

        if ($this->materia && $this->tema) {
            $this->busqueda = $this->materia->materia_nombre . ' - ' . $this->tema->tema_nombre;
        }
    }

    /**
     * Autocompletar del buscador (materias + temas)
     */
    public function updatedBusqueda(string $value): void
    {
        if (mb_strlen($value) < 2) {
            $this->sugerenciasMaterias = [];
            $this->sugerenciasTemas    = [];
            return;
        }

        $this->sugerenciasMaterias = Materia::query()
            ->where('materia_nombre', 'like', '%' . $value . '%')
            ->orderBy('materia_nombre')
            ->limit(5)
            ->get(['materia_id', 'materia_nombre'])
            ->toArray();

        $this->sugerenciasTemas = Tema::query()
            ->where('tema_nombre', 'like', '%' . $value . '%')
            ->orderBy('tema_nombre')
            ->limit(5)
            ->get(['tema_id', 'tema_nombre'])
            ->toArray();
    }

    public function seleccionarMateria(int $materiaId, string $nombre): void
    {
        $this->materiaId = $materiaId;
        $this->materia   = Materia::find($materiaId);
        $this->busqueda  = $nombre;

        // Si el usuario ya tenía un tema seleccionado que no corresponde,
        // lo dejamos tal cual, pero la lógica principal se basa en materiaId.
        $this->sugerenciasMaterias = [];
        $this->sugerenciasTemas    = [];
    }

    public function seleccionarTema(int $temaId, string $nombre): void
    {
        $this->temaId   = $temaId;
        $this->tema     = Tema::find($temaId);
        $this->busqueda = $nombre;

        $this->sugerenciasMaterias = [];
        $this->sugerenciasTemas    = [];

        // 🟢 Intentar resolver la MATERIA a partir del TEMA
        // Usando la pivot programa_tema + programas
        $materiaIdFromTema = DB::table('programa_tema')
            ->join('programas', 'programa_tema.programa_id', '=', 'programas.programa_id')
            ->where('programa_tema.tema_id', $temaId)
            ->value('programas.programa_materia_id');

        if ($materiaIdFromTema) {
            $this->materiaId = $materiaIdFromTema;
            $this->materia   = Materia::find($materiaIdFromTema);
        }
    }

    /**
     * Botón "Consultar ahora"
     */
    public function consultarAhora(): void
    {
        if (empty($this->fecha)) {
            throw ValidationException::withMessages([
                'fecha' => 'Seleccioná una fecha.',
            ]);
        }

        if (! $this->materiaId && ! $this->temaId) {
            throw ValidationException::withMessages([
                'busqueda' => 'Buscá y seleccioná una materia o un tema.',
            ]);
        }

        // A esta altura, si viene por tema, ya intentamos resolver la materia.
        if (! $this->materiaId) {
            throw ValidationException::withMessages([
                'materia' => 'No se pudo determinar la materia a partir del tema seleccionado. Verificá la configuración del programa.',
            ]);
        }

        $fecha = Carbon::createFromFormat('Y-m-d', $this->fecha);

        if ($fecha->isPast() && ! $fecha->isToday()) {
            throw ValidationException::withMessages([
                'fecha' => 'No podés reservar en fechas pasadas.',
            ]);
        }

        // Usa el servicio para traer TODOS los profesores con disponibilidad para esa materia
        $slotsCollection = $this->slotService->obtenerSlotsPorMateria(
            $this->materiaId,
            $fecha,
            $this->temaId
        );

        $this->slots = $slotsCollection->toArray();
    }

    /**
     * Botón "Reservar" en cada card
     */
    public function reservar(int $index): void
    {
        $user = Auth::user();

        if (! $user) {
            throw ValidationException::withMessages([
                'auth' => 'Tenés que iniciar sesión como alumno.',
            ]);
        }

        if (! isset($this->slots[$index])) {
            throw ValidationException::withMessages([
                'slot' => 'No se encontró el horario seleccionado.',
            ]);
        }

        if (! $this->materiaId || ! $this->temaId) {
            throw ValidationException::withMessages([
                'busqueda' => 'Seleccioná una materia y un tema antes de reservar.',
            ]);
        }

        $slot = $this->slots[$index];

        if (empty($slot['profesor_id'])) {
            throw ValidationException::withMessages([
                'profesor' => 'No se encontró el profesor para este horario.',
            ]);
        }

        // Doble chequeo de superposición
        $hayChoque = Turno::query()
            ->where('profesor_id', $slot['profesor_id'])
            ->whereDate('fecha', $slot['fecha'])
            ->where(function ($q) use ($slot) {
                $q->where('hora_inicio', '<', $slot['hora_fin'])
                  ->where('hora_fin', '>', $slot['hora_inicio']);
            })
            ->whereIn('estado', ['pendiente', 'confirmado'])
            ->exists();

        if ($hayChoque) {
            throw ValidationException::withMessages([
                'slot' => 'Ese horario acaba de ser tomado por otro alumno. Actualizá y elegí otro.',
            ]);
        }

        Turno::create([
            'alumno_id'       => $user->id,
            'profesor_id'     => $slot['profesor_id'],
            'materia_id'      => $this->materiaId,
            'tema_id'         => $this->temaId,
            'fecha'           => $slot['fecha'],
            'hora_inicio'     => $slot['hora_inicio'],
            'hora_fin'        => $slot['hora_fin'],
            'estado'          => 'pendiente',
            'precio_por_hora' => $slot['precio_por_hora'] ?? null,
            'precio_total'    => $slot['precio_total'] ?? null,
        ]);

        Notification::make()
            ->title('Turno reservado correctamente')
            ->body('Tu solicitud quedó registrada como pendiente.')
            ->success()
            ->send();

        // Volvemos a cargar slots para actualizar la lista
        $this->consultarAhora();
    }
}
