<?php

namespace App\Filament\Profesor\Pages;

use App\Models\Materia;
use App\Models\Tema;
use App\Models\User;
use App\Models\ProfesorProfile;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    // Usuario
    public string $name = '';
    public string $apellido = '';
    public string $email = '';

    // Foto
    public $foto = null;
    public ?string $profilePhotoUrl = null;

    // Perfil profesional
    public ?string $ciudad = null;
    public ?string $bio = null;
    public ?int $experiencia_anios = null;
    public ?string $nivel = null; // junior|semi|senior
    public ?float $precio_por_hora_default = null;

    // Título profesional (antes headline)
    public ?string $titulo_profesional = null;

    // Materias
    public string $materiaQuery = '';
    public array $materiaResultados = [];
    public array $materiasIds = [];
    public array $materiasPrecios = []; // precio_por_hora por materia

    // Temas
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
            throw ValidationException::withMessages(['perfil' => 'No se pudo cargar el usuario autenticado.']);
        }

        $this->name = (string) $user->name;
        $this->apellido = (string) $user->apellido;
        $this->email = (string) $user->email;

        $this->profilePhotoUrl = $user->profile_photo_path
            ? Storage::url($user->profile_photo_path)
            : null;

        $profile = $user->profesorProfile ?: ProfesorProfile::create(['user_id' => $user->id]);

        $this->ciudad = $profile->ciudad;
        $this->bio = $profile->bio;
        $this->experiencia_anios = $profile->experiencia_anios;
        $this->nivel = $profile->nivel;
        $this->precio_por_hora_default = $profile->precio_por_hora_default !== null ? (float) $profile->precio_por_hora_default : null;
        $this->titulo_profesional = $profile->titulo_profesional;

        // Materias + precio_por_hora
        $this->materiasIds = $user->materias()->pluck('materias.materia_id')->map(fn ($v) => (int)$v)->toArray();
        $this->materiasPrecios = [];
        foreach ($user->materias as $m) {
            $this->materiasPrecios[(int)$m->materia_id] = $m->pivot?->precio_por_hora !== null ? (float)$m->pivot->precio_por_hora : null;
        }

        // Temas
        $this->temasIds = $user->temas()->pluck('temas.tema_id')->map(fn ($v) => (int)$v)->toArray();

        // Reset UI
        $this->materiaQuery = '';
        $this->materiaResultados = [];
        $this->temaQuery = '';
        $this->temaResultados = [];
        $this->foto = null;
    }

    public function editar(): void { $this->isEditing = true; }
    public function cancelarEdicion(): void { $this->cargarDesdeDB(); $this->isEditing = false; }

    public function eliminarFoto(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) throw ValidationException::withMessages(['perfil' => 'No se pudo cargar el usuario.']);
        if (! $user->profile_photo_path) return;

        DB::transaction(function () use ($user) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->profile_photo_path = null;
            $user->save();
        });

        $this->profilePhotoUrl = null;
        $this->foto = null;

        Notification::make()->title('Foto eliminada')->success()->send();
    }

    // Autocomplete materias
    public function updatedMateriaQuery(string $value): void
    {
        if (! $this->isEditing) { $this->materiaResultados = []; return; }
        $value = trim($value);
        if (mb_strlen($value) < 2) { $this->materiaResultados = []; return; }

        $this->materiaResultados = Materia::query()
            ->where('materia_nombre', 'like', "%{$value}%")
            ->orderBy('materia_nombre')
            ->limit(10)
            ->get(['materia_id', 'materia_nombre'])
            ->map(fn ($m) => ['id' => (int)$m->materia_id, 'nombre' => $m->materia_nombre])
            ->toArray();
    }

    public function agregarMateria(int $materiaId): void
    {
        if (! $this->isEditing) return;

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
        if (! $this->isEditing) return;

        $this->materiasIds = array_values(array_filter($this->materiasIds, fn ($id) => (int)$id !== (int)$materiaId));
        unset($this->materiasPrecios[$materiaId]);
    }

    // Autocomplete temas
    public function updatedTemaQuery(string $value): void
    {
        if (! $this->isEditing) { $this->temaResultados = []; return; }
        $value = trim($value);
        if (mb_strlen($value) < 2) { $this->temaResultados = []; return; }

        $this->temaResultados = Tema::query()
            ->where('tema_nombre', 'like', "%{$value}%")
            ->orderBy('tema_nombre')
            ->limit(10)
            ->get(['tema_id', 'tema_nombre'])
            ->map(fn ($t) => ['id' => (int)$t->tema_id, 'nombre' => $t->tema_nombre])
            ->toArray();
    }

    public function agregarTema(int $temaId): void
    {
        if (! $this->isEditing) return;
        if (! in_array($temaId, $this->temasIds, true)) $this->temasIds[] = $temaId;

        $this->temaQuery = '';
        $this->temaResultados = [];
    }

    public function quitarTema(int $temaId): void
    {
        if (! $this->isEditing) return;
        $this->temasIds = array_values(array_filter($this->temasIds, fn ($id) => (int)$id !== (int)$temaId));
    }

    public function guardar(): void
    {
        if (! $this->isEditing) return;

        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) throw ValidationException::withMessages(['perfil' => 'No se pudo cargar el usuario.']);

        $this->validate([
            'name' => ['required','string','max:255'],
            'apellido' => ['required','string','max:255'],
            'foto' => ['nullable','image','max:2048'],

            'ciudad' => ['nullable','string','max:120'],
            'bio' => ['nullable','string','max:4000'],
            'experiencia_anios' => ['nullable','integer','min:0','max:80'],
            'nivel' => ['nullable','in:junior,semi,senior'],
            'precio_por_hora_default' => ['nullable','numeric','min:0'],

            'titulo_profesional' => ['nullable','string','max:180'],

            'materiasIds' => ['array'],
            'materiasIds.*' => ['integer'],
            'materiasPrecios' => ['array'],

            'temasIds' => ['array'],
            'temasIds.*' => ['integer'],
        ]);

        $materiasIds = array_values(array_unique(array_map('intval', $this->materiasIds)));
        $temasIds = array_values(array_unique(array_map('intval', $this->temasIds)));

        $syncMaterias = [];
        foreach ($materiasIds as $mid) {
            $precio = $this->materiasPrecios[$mid] ?? $this->precio_por_hora_default;
            $precio = ($precio === null || $precio === '') ? null : (float) $precio;

            $syncMaterias[$mid] = [
                'precio_por_hora' => $precio,
                // precio_por_clase lo dejamos intacto por ahora
            ];
        }

        DB::transaction(function () use ($user, $syncMaterias, $temasIds) {
            $user->name = $this->name;
            $user->apellido = $this->apellido;

            if ($this->foto) {
                if ($user->profile_photo_path) {
                    Storage::disk('public')->delete($user->profile_photo_path);
                }
                $user->profile_photo_path = $this->foto->store('profile-photos', 'public');
            }

            $user->save();

            $profile = $user->profesorProfile ?: ProfesorProfile::create(['user_id' => $user->id]);
            $profile->ciudad = $this->ciudad;
            $profile->bio = $this->bio;
            $profile->experiencia_anios = $this->experiencia_anios;
            $profile->nivel = $this->nivel;
            $profile->precio_por_hora_default = $this->precio_por_hora_default;
            $profile->titulo_profesional = $this->titulo_profesional; // al final
            $profile->save();

            $user->materias()->sync($syncMaterias);
            $user->temas()->sync($temasIds);
        });

        $user->refresh();
        $this->profilePhotoUrl = $user->profile_photo_path ? Storage::url($user->profile_photo_path) : null;

        $this->isEditing = false;
        $this->materiaQuery = '';
        $this->materiaResultados = [];
        $this->temaQuery = '';
        $this->temaResultados = [];
        $this->foto = null;

        Notification::make()->title('Perfil actualizado')->success()->send();
    }

    public function getMateriasOptionsProperty(): array
    {
        if (empty($this->materiasIds)) return [];
        return Materia::query()
            ->whereIn('materia_id', $this->materiasIds)
            ->orderBy('materia_nombre')
            ->pluck('materia_nombre', 'materia_id')
            ->toArray();
    }

    public function getTemasOptionsProperty(): array
    {
        if (empty($this->temasIds)) return [];
        return Tema::query()
            ->whereIn('tema_id', $this->temasIds)
            ->orderBy('tema_nombre')
            ->pluck('tema_nombre', 'tema_id')
            ->toArray();
    }
}