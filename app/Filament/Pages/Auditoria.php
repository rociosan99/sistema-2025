<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
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
    public ?string $modelo = '';
    public ?string $evento = '';
    public ?string $buscarUsuario = '';
    public ?string $rolUsuario = '';

    /** @var array<int, array<string, mixed>> */
    public array $registros = [];

    /** @var array<string, string> */
    public array $modelosOptions = [];

    /** @var array<string, string> */
    public array $eventosOptions = [];

    /** @var array<string, string> */
    public array $rolesOptions = [];

    /** @var array<string, mixed>|null */
    public ?array $detalle = null;

    public function mount(): void
    {
        $this->fechaFin = now()->toDateString();
        $this->fechaInicio = now()->subDays(30)->toDateString();
        $this->modelo = '';
        $this->evento = '';
        $this->buscarUsuario = '';
        $this->rolUsuario = '';

        $this->cargarModelosOptions();
        $this->cargarEventosOptions();
        $this->cargarRolesOptions();
        $this->cargarDatos();
    }

    public function updated($property): void
    {
        if (in_array($property, ['fechaInicio', 'fechaFin', 'modelo', 'evento', 'buscarUsuario', 'rolUsuario'], true)) {
            if ($this->fechaInicio && $this->fechaFin && $this->fechaInicio <= $this->fechaFin) {
                $this->cargarDatos();
            }
        }
    }

    public function aplicarFiltros(): void
    {
        $this->validarFechas();
        $this->cargarDatos();
    }

    public function verDetalle(int $activityId): void
    {
        $activity = Activity::query()->find($activityId);

        if (! $activity) {
            $this->detalle = null;
            return;
        }

        $causer = null;

        if (
            $activity->causer_type === \App\Models\User::class
            && $activity->causer_id
        ) {
            $causer = \App\Models\User::query()->find($activity->causer_id);
        }

        $usuario = 'Sistema';
        $email = '-';
        $rol = '-';

        if ($causer) {
            $nombreCompleto = trim(((string) $causer->name) . ' ' . ((string) $causer->apellido));

            $usuario = $nombreCompleto !== '' ? $nombreCompleto : ((string) $causer->email ?: 'Sistema');
            $email = (string) ($causer->email ?? '-');
            $rol = $this->rolLegible((string) ($causer->role ?? ''));
        }

        $propiedades = $this->normalizarProperties($activity->properties);
        $cambios = $this->armarCambios($propiedades);

        $this->detalle = [
            'id' => (int) $activity->id,
            'fecha' => $this->formatFecha($activity->created_at),
            'modelo' => $this->modeloLegible((string) $activity->subject_type),
            'subject_id' => $activity->subject_id !== null ? (string) $activity->subject_id : '-',
            'evento' => $this->eventoLegible((string) ($activity->event ?? '')),
            'descripcion' => $this->descripcionLegible((string) ($activity->description ?? '')),
            'usuario' => $usuario,
            'email' => $email,
            'rol' => $rol,
            'cambios' => $cambios,
        ];
    }

    public function cerrarDetalle(): void
    {
        $this->detalle = null;
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
            ->where('subject_type', '!=', \App\Models\OfertaSolicitud::class)
            ->select('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type');

        $options = [];

        foreach ($rows as $subjectType) {
            $subjectType = (string) $subjectType;
            $options[$subjectType] = $this->modeloLegible($subjectType);
        }

        $this->modelosOptions = $options;
    }

    private function cargarEventosOptions(): void
    {
        $this->eventosOptions = [
            '' => 'Todos',
            'created' => 'Creación',
            'updated' => 'Actualización',
            'deleted' => 'Eliminación',
        ];
    }

    private function cargarRolesOptions(): void
    {
        $this->rolesOptions = [
            '' => 'Todos',
            'admin' => 'Admin',
            'profesor' => 'Profesor',
            'alumno' => 'Alumno',
        ];
    }

    private function cargarDatos(): void
    {
        $query = Activity::query()
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'activity_log.causer_id')
                    ->where('activity_log.causer_type', '=', \App\Models\User::class);
            })
            ->whereDate('activity_log.created_at', '>=', $this->fechaInicio)
            ->whereDate('activity_log.created_at', '<=', $this->fechaFin)
            ->whereNotNull('activity_log.subject_type')
            ->where('activity_log.subject_type', '!=', \App\Models\OfertaSolicitud::class)
            ->select([
                'activity_log.id',
                'activity_log.description',
                'activity_log.event',
                'activity_log.created_at',
                'activity_log.subject_type',
                'activity_log.subject_id',
                'users.name as causer_name',
                'users.apellido as causer_apellido',
                'users.email as causer_email',
                'users.role as causer_role',
            ]);

        if ($this->modelo !== '') {
            $query->where('activity_log.subject_type', $this->modelo);
        }

        if ($this->evento !== '') {
            $query->where('activity_log.event', $this->evento);
        }

        if ($this->rolUsuario !== '') {
            $query->where('users.role', $this->rolUsuario);
        }

        $buscar = trim((string) $this->buscarUsuario);

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('users.name', 'like', "%{$buscar}%")
                    ->orWhere('users.apellido', 'like', "%{$buscar}%")
                    ->orWhere('users.email', 'like', "%{$buscar}%");
            });
        }

        $rows = $query
            ->orderByDesc('activity_log.created_at')
            ->limit(300)
            ->get();

        $this->registros = $rows->map(function ($r) {
            return [
                'id' => (int) $r->id,
                'modelo' => $this->modeloLegible((string) $r->subject_type),
                'fecha' => $this->formatFecha($r->created_at),
                'descripcion' => $this->descripcionLegible((string) ($r->description ?? '')),
                'usuario' => $this->usuarioLegible(
                    (string) ($r->causer_name ?? ''),
                    (string) ($r->causer_apellido ?? ''),
                    (string) ($r->causer_email ?? '')
                ),
                'email' => (string) ($r->causer_email ?? '-'),
                'rol' => $this->rolLegible((string) ($r->causer_role ?? '')),
                'evento' => $this->eventoLegible((string) ($r->event ?? '')),
            ];
        })->all();

        if ($this->detalle !== null && isset($this->detalle['id'])) {
            $detalleId = (int) $this->detalle['id'];
            $this->verDetalle($detalleId);
        }
    }

    private function modeloLegible(string $subjectType): string
    {
        return match (class_basename($subjectType)) {
            'User' => 'Usuario',
            'Turno' => 'Turno',
            'TurnoReemplazo' => 'Turno reemplazo',
            'CalificacionProfesor' => 'Calificación profesor',
            default => class_basename($subjectType),
        };
    }

    private function usuarioLegible(string $name, string $apellido, string $email): string
    {
        $usuario = trim($name . ' ' . $apellido);

        if ($usuario !== '') {
            return $usuario;
        }

        if ($email !== '') {
            return $email;
        }

        return 'Sistema';
    }

    private function rolLegible(string $role): string
    {
        return match ($role) {
            'admin' => 'Admin',
            'profesor' => 'Profesor',
            'alumno' => 'Alumno',
            default => $role !== '' ? Str::headline($role) : '-',
        };
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

            default => $value !== ''
                ? Str::headline(str_replace(['.', '_'], ' ', $value))
                : '-',
        };
    }

    private function eventoLegible(string $value): string
    {
        return match ($value) {
            'created' => 'Creación',
            'updated' => 'Actualización',
            'deleted' => 'Eliminación',
            default => $value !== '' ? Str::headline($value) : '-',
        };
    }

    private function formatFecha(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function normalizarProperties(mixed $properties): array
    {
        if ($properties instanceof \Illuminate\Support\Collection) {
            return $properties->toArray();
        }

        if (is_array($properties)) {
            return $properties;
        }

        if (is_object($properties) && method_exists($properties, 'toArray')) {
            return $properties->toArray();
        }

        if (is_string($properties) && trim($properties) !== '') {
            $decoded = json_decode($properties, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function armarCambios(array $propiedades): array
    {
        $attributes = is_array($propiedades['attributes'] ?? null) ? $propiedades['attributes'] : [];
        $old = is_array($propiedades['old'] ?? null) ? $propiedades['old'] : [];

        $campos = array_unique(array_merge(array_keys($old), array_keys($attributes)));

        $cambios = [];

        foreach ($campos as $campo) {
            $antes = array_key_exists($campo, $old) ? $old[$campo] : null;
            $despues = array_key_exists($campo, $attributes) ? $attributes[$campo] : null;

            $cambios[] = [
                'campo' => (string) $campo,
                'antes' => $this->valorAuditable($antes),
                'despues' => $this->valorAuditable($despues),
            ];
        }

        return $cambios;
    }

    private function valorAuditable(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return '-';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }
}