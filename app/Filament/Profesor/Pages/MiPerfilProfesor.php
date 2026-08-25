<?php

namespace App\Filament\Profesor\Pages;

use App\Mail\SolicitudUbicacionCreada;
use App\Models\Ciudad;
use App\Models\Materia;
use App\Models\Pais;
use App\Models\ProfesorProfile;
use App\Models\Provincia;
use App\Models\SolicitudUbicacion;
use App\Models\Tema;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

class MiPerfilProfesor extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'Mi perfil';
    protected static ?string $title = 'Mi perfil';
    protected string $view = 'filament.profesor.pages.mi-perfil-profesor';

    public bool $isEditing = false;

    public string $name = '';
    public string $apellido = '';
    public string $email = '';

    public $foto = null;
    public ?string $profilePhotoUrl = null;

    public ?int $pais_id = null;
    public ?int $provincia_id = null;
    public ?int $ciudad_id = null;

    public bool $modalSolicitudUbicacionAbierto = false;
    public ?string $tipoSolicitudUbicacion = null;
    public string $nombreUbicacionSolicitada = '';
    public ?string $observacionSolicitudUbicacion = null;

    public ?string $bio = null;
    public ?int $experiencia_anios = null;
    public ?string $nivel = null;
    public ?float $precio_por_hora_default = null;
    public ?string $titulo_profesional = null;
    public ?string $enlace_clase_default = null;

    public string $materiaQuery = '';
    public array $materiaResultados = [];
    public array $materiasIds = [];
    public array $materiasPrecios = [];

    public string $temaQuery = '';
    public array $temaResultados = [];
    public array $temasIds = [];

    public function mount(): void
    {
        $this->cargarDesdeDB();
        $this->isEditing = false;
    }

    private function cargarDesdeDB(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw ValidationException::withMessages([
                'perfil' => 'No se pudo cargar el usuario autenticado.',
            ]);
        }

        $this->name = (string) $user->name;
        $this->apellido = (string) $user->apellido;
        $this->email = (string) $user->email;

        $this->profilePhotoUrl = $user->profile_photo_path
            ? Storage::url($user->profile_photo_path)
            : null;

        $profile = $user->profesorProfile ?: ProfesorProfile::create([
            'user_id' => $user->id,
        ]);

        $profile->loadMissing('ciudadItem.provincia.pais');

        $this->ciudad_id = $profile->ciudad_id ? (int) $profile->ciudad_id : null;
        $this->provincia_id = $profile->ciudadItem?->provincia_id ? (int) $profile->ciudadItem->provincia_id : null;
        $this->pais_id = $profile->ciudadItem?->provincia?->pais_id ? (int) $profile->ciudadItem->provincia->pais_id : null;

        $this->bio = $profile->bio;
        $this->experiencia_anios = $profile->experiencia_anios;
        $this->nivel = $profile->nivel;
        $this->precio_por_hora_default = $profile->precio_por_hora_default !== null
            ? (float) $profile->precio_por_hora_default
            : null;
        $this->titulo_profesional = $profile->titulo_profesional;
        $this->enlace_clase_default = $profile->enlace_clase_default;

        $this->materiasIds = $user->materias()
            ->pluck('materias.materia_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $this->materiasPrecios = [];
        foreach ($user->materias as $m) {
            $this->materiasPrecios[(int) $m->materia_id] = $m->pivot?->precio_por_hora !== null
                ? (float) $m->pivot->precio_por_hora
                : null;
        }

        $this->temasIds = $user->temas()
            ->pluck('temas.tema_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $this->materiaQuery = '';
        $this->materiaResultados = [];
        $this->temaQuery = '';
        $this->temaResultados = [];
        $this->foto = null;
    }

    public function editar(): void
    {
        $this->isEditing = true;
    }

    public function cancelarEdicion(): void
    {
        $this->cargarDesdeDB();
        $this->isEditing = false;
    }

    public function updatedPaisId(): void
    {
        $this->provincia_id = null;
        $this->ciudad_id = null;
    }

    public function updatedProvinciaId(): void
    {
        $this->ciudad_id = null;
    }

    public function abrirSolicitudPais(): void
    {
        $this->abrirSolicitudUbicacion(SolicitudUbicacion::TIPO_PAIS);
    }

    public function abrirSolicitudProvincia(): void
    {
        if (! $this->pais_id) {
            Notification::make()
                ->title('Selecciona un pais primero')
                ->warning()
                ->send();

            return;
        }

        $this->abrirSolicitudUbicacion(SolicitudUbicacion::TIPO_PROVINCIA);
    }

    public function abrirSolicitudCiudad(): void
    {
        if (! $this->pais_id || ! $this->provincia_id) {
            Notification::make()
                ->title('Selecciona un pais y una provincia primero')
                ->warning()
                ->send();

            return;
        }

        $this->abrirSolicitudUbicacion(SolicitudUbicacion::TIPO_CIUDAD);
    }

    private function abrirSolicitudUbicacion(string $tipo): void
    {
        if (! $this->isEditing) {
            return;
        }

        $this->resetValidation([
            'nombreUbicacionSolicitada',
            'observacionSolicitudUbicacion',
            'tipoSolicitudUbicacion',
        ]);

        $this->tipoSolicitudUbicacion = $tipo;
        $this->nombreUbicacionSolicitada = '';
        $this->observacionSolicitudUbicacion = null;
        $this->modalSolicitudUbicacionAbierto = true;
    }

    public function cerrarSolicitudUbicacion(): void
    {
        $this->modalSolicitudUbicacionAbierto = false;
        $this->tipoSolicitudUbicacion = null;
        $this->nombreUbicacionSolicitada = '';
        $this->observacionSolicitudUbicacion = null;

        $this->resetValidation([
            'nombreUbicacionSolicitada',
            'observacionSolicitudUbicacion',
            'tipoSolicitudUbicacion',
        ]);
    }

    public function enviarSolicitudUbicacion(): void
    {
        if (! $this->isEditing) {
            return;
        }

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw ValidationException::withMessages([
                'solicitud_ubicacion' => 'No se pudo cargar el usuario.',
            ]);
        }

        $maxNombre = $this->tipoSolicitudUbicacion === SolicitudUbicacion::TIPO_CIUDAD ? 150 : 120;

        $this->validate([
            'tipoSolicitudUbicacion' => ['required', Rule::in([
                SolicitudUbicacion::TIPO_PAIS,
                SolicitudUbicacion::TIPO_PROVINCIA,
                SolicitudUbicacion::TIPO_CIUDAD,
            ])],
            'nombreUbicacionSolicitada' => ['required', 'string', 'max:'.$maxNombre],
            'observacionSolicitudUbicacion' => ['nullable', 'string', 'max:1000'],
        ], [
            'nombreUbicacionSolicitada.required' => 'Ingresa el nombre solicitado.',
        ]);

        // Normalización estricta del nombre (Capitalizado de palabras con soporte UTF-8)
        $nombre = mb_convert_case(trim($this->nombreUbicacionSolicitada), MB_CASE_TITLE, "UTF-8");

        if ($nombre === '') {
            throw ValidationException::withMessages([
                'nombreUbicacionSolicitada' => 'Ingresa el nombre solicitado.',
            ]);
        }

        $data = [
            'tipo' => $this->tipoSolicitudUbicacion,
            'estado' => SolicitudUbicacion::ESTADO_PENDIENTE,
            'observacion_solicitante' => $this->observacionSolicitudUbicacion,
            'solicitado_por_id' => $user->id,
        ];

        // Validaciones contra duplicados según el tipo y su contexto geográfico
        if ($this->tipoSolicitudUbicacion === SolicitudUbicacion::TIPO_PAIS) {
            if (Pais::query()->where('pais_nombre', $nombre)->exists()) {
                throw ValidationException::withMessages(['nombreUbicacionSolicitada' => 'Este país ya se encuentra registrado en el sistema.']);
            }
            if (SolicitudUbicacion::query()->where('tipo', SolicitudUbicacion::TIPO_PAIS)->where('estado', SolicitudUbicacion::ESTADO_PENDIENTE)->where('nombre_pais_solicitado', $nombre)->exists()) {
                throw ValidationException::withMessages(['nombreUbicacionSolicitada' => 'Ya existe una solicitud pendiente para registrar este país.']);
            }
            $data['nombre_pais_solicitado'] = $nombre;
        }

        if ($this->tipoSolicitudUbicacion === SolicitudUbicacion::TIPO_PROVINCIA) {
            if (! $this->pais_id || ! Pais::query()->where('pais_id', $this->pais_id)->exists()) {
                throw ValidationException::withMessages([
                    'pais_id' => 'Selecciona un pais valido.',
                ]);
            }

            if (Provincia::query()->where('pais_id', $this->pais_id)->where('provincia_nombre', $nombre)->exists()) {
                throw ValidationException::withMessages(['nombreUbicacionSolicitada' => 'Esta provincia ya se encuentra registrada para el país seleccionado.']);
            }
            if (SolicitudUbicacion::query()->where('tipo', SolicitudUbicacion::TIPO_PROVINCIA)->where('estado', SolicitudUbicacion::ESTADO_PENDIENTE)->where('pais_id', $this->pais_id)->where('nombre_provincia_solicitada', $nombre)->exists()) {
                throw ValidationException::withMessages(['nombreUbicacionSolicitada' => 'Ya existe una solicitud pendiente para esta provincia en el país seleccionado.']);
            }

            $data['pais_id'] = $this->pais_id;
            $data['nombre_provincia_solicitada'] = $nombre;
        }

        if ($this->tipoSolicitudUbicacion === SolicitudUbicacion::TIPO_CIUDAD) {
            if (! $this->pais_id || ! $this->provincia_id) {
                throw ValidationException::withMessages([
                    'provincia_id' => 'Selecciona un pais y una provincia.',
                ]);
            }

            $provinciaValida = Provincia::query()
                ->where('provincia_id', $this->provincia_id)
                ->where('pais_id', $this->pais_id)
                ->exists();

            if (! $provinciaValida) {
                throw ValidationException::withMessages([
                    'provincia_id' => 'La provincia no pertenece al pais seleccionado.',
                ]);
            }

            if (Ciudad::query()->where('provincia_id', $this->provincia_id)->where('ciudad_nombre', $nombre)->exists()) {
                throw ValidationException::withMessages(['nombreUbicacionSolicitada' => 'Esta ciudad ya se encuentra registrada para la provincia seleccionada.']);
            }
            if (SolicitudUbicacion::query()->where('tipo', SolicitudUbicacion::TIPO_CIUDAD)->where('estado', SolicitudUbicacion::ESTADO_PENDIENTE)->where('provincia_id', $this->provincia_id)->where('nombre_ciudad_solicitada', $nombre)->exists()) {
                throw ValidationException::withMessages(['nombreUbicacionSolicitada' => 'Ya existe una solicitud pendiente para esta ciudad en la provincia seleccionada.']);
            }

            $data['pais_id'] = $this->pais_id;
            $data['provincia_id'] = $this->provincia_id;
            $data['nombre_ciudad_solicitada'] = $nombre;
        }

        $solicitud = SolicitudUbicacion::create($data);

        $this->notificarAdminsSolicitudUbicacion($solicitud);

        $this->cerrarSolicitudUbicacion();

        Notification::make()
            ->title('Solicitud enviada. Un administrador la revisara.')
            ->success()
            ->send();
    }

    private function notificarAdminsSolicitudUbicacion(SolicitudUbicacion $solicitud): void
    {
        try {
            $admins = User::query()
                ->where('role', 'admin')
                ->where('activo', true)
                ->whereNotNull('email')
                ->get();

            if ($admins->isEmpty()) {
                Log::info('No hay administradores activos con email para notificar una solicitud de ubicacion.', [
                    'solicitud_id' => $solicitud->id,
                ]);

                return;
            }

            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new SolicitudUbicacionCreada($solicitud));
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el correo de solicitud de ubicacion al administrador.', [
                'solicitud_id' => $solicitud->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function eliminarFoto(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw ValidationException::withMessages([
                'perfil' => 'No se pudo cargar el usuario.',
            ]);
        }

        if (! $user->profile_photo_path) {
            return;
        }

        DB::transaction(function () use ($user) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->profile_photo_path = null;
            $user->save();
        });

        $this->profilePhotoUrl = null;
        $this->foto = null;

        Notification::make()
            ->title('Foto eliminada')
            ->success()
            ->send();
    }

    public function updatedMateriaQuery(string $value): void
    {
        if (! $this->isEditing) {
            $this->materiaResultados = [];
            return;
        }

        $value = trim($value);

        if (mb_strlen($value) < 2) {
            $this->materiaResultados = [];
            return;
        }

        $this->materiaResultados = Materia::query()
            ->where('materia_nombre', 'like', "%{$value}%")
            ->orderBy('materia_nombre')
            ->limit(10)
            ->get(['materia_id', 'materia_nombre'])
            ->map(fn ($m) => [
                'id' => (int) $m->materia_id,
                'nombre' => $m->materia_nombre,
            ])
            ->toArray();
    }

    public function agregarMateria(int $materiaId): void
    {
        if (! $this->isEditing) {
            return;
        }

        if (! in_array($materiaId, $this->materiasIds, true)) {
            $this->materiasIds[] = $materiaId;
        }

        if (! array_key_exists($materiaId, $this->materiasPrecios)) {
            $this->materiasPrecios[$materiaId] = $this->precio_por_hora_default ?? null;
        }

        $this->materiaQuery = '';
        $this->materiaResultados = [];
    }

    public function quitarMateria(int $materiaId): void
    {
        if (! $this->isEditing) {
            return;
        }

        $this->materiasIds = array_values(array_filter(
            $this->materiasIds,
            fn ($id) => (int) $id !== (int) $materiaId
        ));

        unset($this->materiasPrecios[$materiaId]);
    }

    public function updatedTemaQuery(string $value): void
    {
        if (! $this->isEditing) {
            $this->temaResultados = [];
            return;
        }

        $value = trim($value);

        if (mb_strlen($value) < 2) {
            $this->temaResultados = [];
            return;
        }

        $this->temaResultados = Tema::query()
            ->where('tema_nombre', 'like', "%{$value}%")
            ->orderBy('tema_nombre')
            ->limit(10)
            ->get(['tema_id', 'tema_nombre'])
            ->map(fn ($t) => [
                'id' => (int) $t->tema_id,
                'nombre' => $t->tema_nombre,
            ])
            ->toArray();
    }

    public function agregarTema(int $temaId): void
    {
        if (! $this->isEditing) {
            return;
        }

        if (! in_array($temaId, $this->temasIds, true)) {
            $this->temasIds[] = $temaId;
        }

        $this->temaQuery = '';
        $this->temaResultados = [];
    }

    public function quitarTema(int $temaId): void
    {
        if (! $this->isEditing) {
            return;
        }

        $this->temasIds = array_values(array_filter(
            $this->temasIds,
            fn ($id) => (int) $id !== (int) $temaId
        ));
    }

    public function guardar(): void
    {
        if (! $this->isEditing) {
            return;
        }

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw ValidationException::withMessages([
                'perfil' => 'No se pudo cargar el usuario.',
            ]);
        }

        $enlaceClaseDefault = trim((string) $this->enlace_clase_default);
        $this->enlace_clase_default = $enlaceClaseDefault !== ''
            ? $enlaceClaseDefault
            : null;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'foto' => ['nullable', 'image', 'max:2048'],

            'pais_id' => ['required', 'integer', Rule::exists('paises', 'pais_id')],
            'provincia_id' => ['required', 'integer', Rule::exists('provincias', 'provincia_id')],
            'ciudad_id' => ['required', 'integer', Rule::exists('ciudades', 'ciudad_id')],

            'bio' => ['nullable', 'string', 'max:4000'],
            'experiencia_anios' => ['nullable', 'integer', 'min:0', 'max:80'],
            'nivel' => ['nullable', 'in:junior,semi,senior'],
            'precio_por_hora_default' => ['nullable', 'numeric', 'min:0'],
            'titulo_profesional' => ['nullable', 'string', 'max:180'],
            'enlace_clase_default' => ['required', 'url:http,https', 'max:2048'],

            'materiasIds' => ['array'],
            'materiasIds.*' => ['integer'],
            'materiasPrecios' => ['array'],

            'temasIds' => ['array'],
            'temasIds.*' => ['integer'],
        ], [
            'pais_id.required' => 'Seleccioná un país.',
            'provincia_id.required' => 'Seleccioná una provincia.',
            'ciudad_id.required' => 'Seleccioná una ciudad.',
            'enlace_clase_default.required' => 'El enlace de clase es obligatorio.',
            'enlace_clase_default.url' => 'Ingresá un enlace válido que comience con http:// o https://.',
        ]);

        $provinciaValida = Provincia::query()
            ->where('provincia_id', $this->provincia_id)
            ->where('pais_id', $this->pais_id)
            ->exists();

        if (! $provinciaValida) {
            throw ValidationException::withMessages([
                'provincia_id' => 'La provincia no pertenece al país seleccionado.',
            ]);
        }

        $ciudadValida = Ciudad::query()
            ->where('ciudad_id', $this->ciudad_id)
            ->where('provincia_id', $this->provincia_id)
            ->exists();

        if (! $ciudadValida) {
            throw ValidationException::withMessages([
                'ciudad_id' => 'La ciudad no pertenece a la provincia seleccionada.',
            ]);
        }

        $materiasIds = array_values(array_unique(array_map('intval', $this->materiasIds)));
        $temasIds = array_values(array_unique(array_map('intval', $this->temasIds)));

        $syncMaterias = [];
        foreach ($materiasIds as $mid) {
            $precio = $this->materiasPrecios[$mid] ?? $this->precio_por_hora_default;
            $precio = ($precio === null || $precio === '') ? null : (float) $precio;

            $syncMaterias[$mid] = [
                'precio_por_hora' => $precio,
            ];
        }

        DB::transaction(function () use ($user, $syncMaterias, $temasIds) {
            $user->name = trim($this->name);
            $user->apellido = trim($this->apellido);

            if ($this->foto) {
                if ($user->profile_photo_path) {
                    Storage::disk('public')->delete($user->profile_photo_path);
                }

                $user->profile_photo_path = $this->foto->store('profile-photos', 'public');
            }

            $user->save();

            $profile = $user->profesorProfile ?: ProfesorProfile::create([
                'user_id' => $user->id,
            ]);

            $profile->ciudad_id = $this->ciudad_id;
            $profile->bio = $this->bio;
            $profile->experiencia_anios = $this->experiencia_anios;
            $profile->nivel = $this->nivel;
            $profile->precio_por_hora_default = $this->precio_por_hora_default;
            $profile->titulo_profesional = $this->titulo_profesional;
            $profile->enlace_clase_default = $this->enlace_clase_default;
            $profile->save();

            $user->materias()->sync($syncMaterias);
            $user->temas()->sync($temasIds);
        });

        $user->refresh();

        $this->profilePhotoUrl = $user->profile_photo_path
            ? Storage::url($user->profile_photo_path)
            : null;

        $this->isEditing = false;
        $this->materiaQuery = '';
        $this->materiaResultados = [];
        $this->temaQuery = '';
        $this->temaResultados = [];
        $this->foto = null;

        Notification::make()
            ->title('Perfil actualizado')
            ->success()
            ->send();
    }

    public function getMateriasOptionsProperty(): array
    {
        if (empty($this->materiasIds)) {
            return [];
        }

        return Materia::query()
            ->whereIn('materia_id', $this->materiasIds)
            ->orderBy('materia_nombre')
            ->pluck('materia_nombre', 'materia_id')
            ->toArray();
    }

    public function getTemasOptionsProperty(): array
    {
        if (empty($this->temasIds)) {
            return [];
        }

        return Tema::query()
            ->whereIn('tema_id', $this->temasIds)
            ->orderBy('tema_nombre')
            ->pluck('tema_nombre', 'tema_id')
            ->toArray();
    }

    public function getPaisesOptionsProperty(): array
    {
        return Pais::query()
            ->orderBy('pais_nombre')
            ->pluck('pais_nombre', 'pais_id')
            ->toArray();
    }

    public function getProvinciasOptionsProperty(): array
    {
        if (! $this->pais_id) {
            return [];
        }

        return Provincia::query()
            ->where('pais_id', $this->pais_id)
            ->orderBy('provincia_nombre')
            ->pluck('provincia_nombre', 'provincia_id')
            ->toArray();
    }

    public function getCiudadesOptionsProperty(): array
    {
        if (! $this->provincia_id) {
            return [];
        }

        return Ciudad::query()
            ->where('provincia_id', $this->provincia_id)
            ->orderBy('ciudad_nombre')
            ->pluck('ciudad_nombre', 'ciudad_id')
            ->toArray();
    }
}
