<?php

namespace App\Filament\Alumno\Pages;

use App\Models\Turno;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class VerTurnoAlumno extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $title = 'Detalle del turno';
    protected static ?string $slug = 'turnos/{record}';

    protected string $view = 'filament.alumno.pages.ver-turno-alumno';

    public Turno $turno;

    public function mount(int|string $record): void
    {
        $this->turno = Turno::query()
            ->with([
                'profesor:id,name,apellido',
                'materia:materia_id,materia_nombre',
                'tema:tema_id,tema_nombre',
            ])
            ->findOrFail($record);

        abort_unless((int) $this->turno->alumno_id === (int) Auth::id(), 404);
    }

    public function puedeVerEnlace(): bool
    {
        return $this->turno->estado === Turno::ESTADO_CONFIRMADO
            && filled(trim((string) $this->turno->enlace_clase))
            && now()->lt($this->turno->finDateTime());
    }

    public function estadoVisible(): string
    {
        if (
            $this->turno->estado === Turno::ESTADO_CONFIRMADO
            && now()->gte($this->turno->finDateTime())
        ) {
            return 'Finalizada';
        }

        return match ($this->turno->estado) {
            Turno::ESTADO_PENDIENTE => 'Pendiente',
            Turno::ESTADO_ACEPTADO => 'Aceptado',
            Turno::ESTADO_PENDIENTE_PAGO => 'Pendiente de pago',
            Turno::ESTADO_CONFIRMADO => 'Confirmado',
            Turno::ESTADO_CANCELADO => 'Cancelado',
            Turno::ESTADO_RECHAZADO => 'Rechazado',
            Turno::ESTADO_VENCIDO => 'Vencido',
            Turno::ESTADO_SUSPENDIDO_PROFESOR => 'Suspendido por profesor',
            default => ucfirst((string) $this->turno->estado),
        };
    }
}
