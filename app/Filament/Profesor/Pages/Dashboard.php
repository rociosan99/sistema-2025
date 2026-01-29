<?php

namespace App\Filament\Profesor\Pages;

use App\Models\Turno;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Panel del Profesor';
    protected static ?string $slug = 'dashboard';
    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.profesor.pages.dashboard';

    public array $materias = [];
    public array $temas = [];

    /** Agenda del día (solo clases pagadas) */
    public array $agendaHoy = [];

    /** Config del “calendario” */
    public string $dayStart = '07:00'; // ajustá a tu gusto
    public string $dayEnd   = '22:00'; // ajustá a tu gusto
    public float $pxPerMin  = 1.2;     // “escala” vertical del calendario

    public function mount(): void
    {
        $user = Auth::user();

        $this->materias = $user->materias()
            ->orderBy('materia_nombre')
            ->pluck('materia_nombre')
            ->toArray();

        $this->temas = $user->temas()
            ->orderBy('tema_nombre')
            ->pluck('tema_nombre')
            ->toArray();

        // ✅ Agenda de HOY: solo turnos pagados (confirmado)
        $turnos = Turno::query()
            ->where('profesor_id', $user->id)
            ->whereDate('fecha', now()->toDateString())
            ->where('estado', Turno::ESTADO_CONFIRMADO)
            ->with(['alumno', 'materia', 'tema'])
            ->orderBy('hora_inicio')
            ->get();

        $this->agendaHoy = $turnos->map(function (Turno $t) {
            $inicio = substr((string) $t->hora_inicio, 0, 5);
            $fin    = substr((string) $t->hora_fin, 0, 5);

            return [
                'id' => $t->id,
                'inicio' => $inicio,
                'fin' => $fin,
                'alumno' => $t->alumno?->name ?? '-',
                'materia' => $t->materia?->materia_nombre ?? '-',
                'tema' => $t->tema?->tema_nombre ?? null,
                'estado' => $t->estado,
            ];
        })->toArray();
    }
}
