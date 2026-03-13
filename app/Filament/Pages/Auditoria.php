<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Auditoria extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Auditoría';
    protected static ?string $title = 'Auditoría';
    protected static ?string $slug = 'auditoria';
    protected static string|\UnitEnum|null $navigationGroup = 'General';

    protected string $view = 'filament.pages.auditoria';

    public ?string $fechaInicio = null;
    public ?string $fechaFin = null;
    public ?string $logName = '';
    public ?string $evento = '';

    /** @var array<int, array<string, mixed>> */
    public array $registros = [];

    public function mount(): void
    {
        $this->fechaFin = now()->toDateString();
        $this->fechaInicio = now()->subDays(30)->toDateString();
        $this->logName = '';
        $this->evento = '';

        $this->cargarDatos();
    }

    public function aplicarFiltros(): void
    {
        $this->validarFechas();
        $this->cargarDatos();
    }

    private function validarFechas(): void
    {
        if (! $this->fechaInicio || ! $this->fechaFin) {
            throw ValidationException::withMessages([
                'fechas' => 'Seleccioná fecha inicio y fecha fin.',
            ]);
        }

        if ($this->fechaInicio > $this->fechaFin) {
            throw ValidationException::withMessages([
                'fechas' => 'La fecha inicio no puede ser mayor que la fecha fin.',
            ]);
        }
    }

    private function cargarDatos(): void
    {
        $query = DB::table('activity_log as al')
            ->leftJoin('users as u', 'u.id', '=', 'al.causer_id')
            ->selectRaw("
                al.id,
                al.log_name,
                al.description,
                al.subject_type,
                al.subject_id,
                al.event,
                al.properties,
                al.created_at,
                CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.apellido, '')) as causer_nombre,
                u.email as causer_email
            ")
            ->whereBetween(DB::raw('DATE(al.created_at)'), [$this->fechaInicio, $this->fechaFin]);

        if (! empty($this->logName)) {
            $query->where('al.log_name', $this->logName);
        }

        if (! empty($this->evento)) {
            $query->where('al.event', $this->evento);
        }

        $rows = $query
            ->orderByDesc('al.created_at')
            ->limit(300)
            ->get();

        $this->registros = $rows->map(function ($r) {
            $subjectType = (string) ($r->subject_type ?? '');
            $subjectBase = $subjectType ? class_basename($subjectType) : '—';

            $properties = [];
            if (! empty($r->properties)) {
                $decoded = json_decode($r->properties, true);
                if (is_array($decoded)) {
                    $properties = $decoded;
                }
            }

            return [
                'id' => (int) $r->id,
                'fecha' => $r->created_at ? (string) $r->created_at : '—',
                'log_name' => (string) ($r->log_name ?? '—'),
                'description' => (string) ($r->description ?? '—'),
                'causer_nombre' => trim((string) ($r->causer_nombre ?? '')) ?: 'Sistema / Sin usuario',
                'causer_email' => (string) ($r->causer_email ?? '—'),
                'subject_type' => $subjectBase,
                'subject_id' => $r->subject_id ? (string) $r->subject_id : '—',
                'event' => (string) ($r->event ?? '—'),
                'properties_pretty' => ! empty($properties)
                    ? json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : 'Sin cambios / propiedades',
            ];
        })->all();
    }
}