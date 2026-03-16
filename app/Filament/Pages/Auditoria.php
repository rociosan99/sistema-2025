<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

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
    public ?string $tipoAuditoria = '';
    public ?string $modelo = '';

    /** @var array<int, array<string, mixed>> */
    public array $registros = [];

    /** @var array<string, string> */
    public array $modelosOptions = [];

    public function mount(): void
    {
        $this->fechaFin = now()->toDateString();
        $this->fechaInicio = now()->subDays(30)->toDateString();
        $this->tipoAuditoria = '';
        $this->modelo = '';

        $this->cargarModelosOptions();
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

    private function cargarModelosOptions(): void
    {
        $rows = Activity::query()
            ->whereNotNull('subject_type')
            ->select('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type');

        $options = [];

        foreach ($rows as $subjectType) {
            $subjectType = (string) $subjectType;
            $options[$subjectType] = class_basename($subjectType);
        }

        $this->modelosOptions = $options;
    }

    private function cargarDatos(): void
    {
        $query = Activity::query()
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'activity_log.causer_id')
                    ->where('activity_log.causer_type', '=', \App\Models\User::class);
            })
            ->whereBetween(DB::raw('DATE(activity_log.created_at)'), [$this->fechaInicio, $this->fechaFin])
            ->select([
                'activity_log.id',
                'activity_log.description',
                'activity_log.subject_type',
                'activity_log.subject_id',
                'activity_log.event',
                'activity_log.log_name',
                'activity_log.properties',
                'activity_log.created_at',
                'users.name as causer_name',
                'users.apellido as causer_apellido',
                'users.email as causer_email',
            ]);

        if ($this->tipoAuditoria !== '') {
            $query->where('activity_log.log_name', $this->tipoAuditoria);
        }

        if ($this->modelo !== '') {
            $query->where('activity_log.subject_type', $this->modelo);
        }

        $rows = $query
            ->orderByDesc('activity_log.created_at')
            ->limit(300)
            ->get();

        $this->registros = $rows->map(function ($r) {
            $usuario = trim(((string) ($r->causer_name ?? '')) . ' ' . ((string) ($r->causer_apellido ?? '')));
            $usuario = $usuario !== '' ? $usuario : '-';

            $properties = $this->formatearProperties($r->properties);

            return [
                'id' => (int) $r->id,
                'fecha' => optional($r->created_at)?->format('Y-m-d H:i:s') ?? (string) $r->created_at,
                'descripcion' => $this->descripcionLegible((string) $r->description),
                'usuario' => $usuario,
                'email' => (string) ($r->causer_email ?? '-'),
                'registro' => $r->subject_id ? (string) $r->subject_id : '-',
                'evento' => $this->eventoLegible((string) ($r->event ?? '')),
                'log' => $this->logLegible((string) ($r->log_name ?? '')),
                'properties' => $properties,
            ];
        })->all();
    }

    private function descripcionLegible(string $value): string
    {
        return match ($value) {
            'turno_created' => 'Turno creado',
            'turno_updated' => 'Turno actualizado',
            'turno_deleted' => 'Turno eliminado',

            'calificacion_profesor_created' => 'Calificación de profesor creada',
            'calificacion_profesor_updated' => 'Calificación de profesor actualizada',
            'calificacion_profesor_deleted' => 'Calificación de profesor eliminada',

            'turno.cancelado_alumno' => 'Turno cancelado por alumno',
            'turno.vencido' => 'Turno vencido',
            'reemplazo.turno_cancelado_disparado' => 'Búsqueda de reemplazo iniciada',

            'pago.link_creado' => 'Link de pago creado',
            'pago.estado_actualizado' => 'Estado de pago actualizado',
            'pago.aprobado' => 'Pago aprobado',
            'pago.rechazado' => 'Pago rechazado',
            'pago.webhook_recibido' => 'Webhook de pago recibido',
            'pago.webhook_error' => 'Error en webhook de pago',

            default => str_replace(['_', '.'], ' ', ucfirst($value)),
        };
    }

    private function eventoLegible(string $value): string
    {
        return match ($value) {
            'created' => 'Creación',
            'updated' => 'Actualización',
            'deleted' => 'Eliminación',
            default => $value !== '' ? ucfirst($value) : '-',
        };
    }

    private function logLegible(string $value): string
    {
        return match ($value) {
            'audit' => 'Sistema',
            'business' => 'Negocio',
            default => $value !== '' ? ucfirst($value) : '-',
        };
    }

    private function formatearProperties(mixed $properties): string
    {
        if (is_string($properties)) {
            $decoded = json_decode($properties, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $properties = $decoded;
            }
        }

        if (! is_array($properties)) {
            return '-';
        }

        $texto = json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $texto ?: '-';
    }
}