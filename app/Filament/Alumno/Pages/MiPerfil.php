<?php

namespace App\Filament\Alumno\Pages;

use App\Models\Carrera;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

class MiPerfil extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'Mi perfil';
    protected static ?string $title = 'Mi perfil';
    protected string $view = 'filament.alumno.pages.mi-perfil';

    public bool $isEditing = false;

    public string $name = '';
    public string $apellido = '';
    public string $email = '';

    public $foto = null;
    public ?string $profilePhotoUrl = null;

    public array $carrerasSeleccionadas = [];
    public ?int $carreraActivaId = null;

    public string $carreraQuery = '';
    public array $carreraResultados = [];

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

        $this->carrerasSeleccionadas = $user->carrerasComoAlumno()
            ->pluck('carreras.carrera_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $this->carreraActivaId = $user->carrera_activa_id ? (int) $user->carrera_activa_id : null;

        $this->profilePhotoUrl = $user->profile_photo_path
            ? Storage::url($user->profile_photo_path)
            : null;

        $this->carreraQuery = '';
        $this->carreraResultados = [];
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

    public function updatedCarreraQuery(string $value): void
    {
        if (! $this->isEditing) {
            $this->carreraResultados = [];
            return;
        }

        $value = trim($value);

        if (mb_strlen($value) < 2) {
            $this->carreraResultados = [];
            return;
        }

        $this->carreraResultados = Carrera::query()
            ->where('carrera_nombre', 'like', "%{$value}%")
            ->orderBy('carrera_nombre')
            ->limit(10)
            ->get(['carrera_id', 'carrera_nombre'])
            ->map(fn ($c) => ['id' => (int) $c->carrera_id, 'nombre' => $c->carrera_nombre])
            ->toArray();
    }

    public function agregarCarrera(int $carreraId): void
    {
        if (! $this->isEditing) return;

        if (! in_array($carreraId, $this->carrerasSeleccionadas, true)) {
            $this->carrerasSeleccionadas[] = $carreraId;
        }

        if (! $this->carreraActivaId) {
            $this->carreraActivaId = $carreraId;
        }

        $this->carreraQuery = '';
        $this->carreraResultados = [];
    }

    public function quitarCarrera(int $carreraId): void
    {
        if (! $this->isEditing) return;

        $this->carrerasSeleccionadas = array_values(array_filter(
            $this->carrerasSeleccionadas,
            fn ($id) => (int) $id !== (int) $carreraId
        ));

        if ($this->carreraActivaId === (int) $carreraId) {
            $this->carreraActivaId = $this->carrerasSeleccionadas[0] ?? null;
        }
    }

    public function eliminarFoto(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw ValidationException::withMessages(['perfil' => 'No se pudo cargar el usuario autenticado.']);
        }

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

    public function guardar(): void
    {
        if (! $this->isEditing) return;

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw ValidationException::withMessages(['perfil' => 'No se pudo cargar el usuario autenticado.']);
        }

        $this->validate([
            'name' => ['required','string','max:255'],
            'apellido' => ['required','string','max:255'],
            'foto' => ['nullable','image','max:2048'],
            'carrerasSeleccionadas' => ['array'],
            'carrerasSeleccionadas.*' => ['integer'],
            'carreraActivaId' => ['nullable','integer'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $this->carrerasSeleccionadas)));
        $activa = $this->carreraActivaId ? (int) $this->carreraActivaId : null;

        if ($activa && ! in_array($activa, $ids, true)) {
            $activa = null;
        }
        if (! $activa && count($ids) > 0) {
            $activa = $ids[0];
        }

        DB::transaction(function () use ($user, $ids, $activa) {
            $user->name = $this->name;
            $user->apellido = $this->apellido;

            if ($this->foto) {
                if ($user->profile_photo_path) {
                    Storage::disk('public')->delete($user->profile_photo_path);
                }
                $user->profile_photo_path = $this->foto->store('profile-photos', 'public');
            }

            $user->carrera_activa_id = $activa;
            $user->save();

            $user->carrerasComoAlumno()->sync($ids);
        });

        $user->refresh();
        $this->profilePhotoUrl = $user->profile_photo_path ? Storage::url($user->profile_photo_path) : null;

        $this->carreraActivaId = $activa;
        $this->isEditing = false;
        $this->carreraQuery = '';
        $this->carreraResultados = [];
        $this->foto = null;

        Notification::make()->title('Perfil actualizado')->success()->send();
    }

    public function getCarrerasOptionsProperty(): array
    {
        if (empty($this->carrerasSeleccionadas)) return [];

        return Carrera::query()
            ->whereIn('carrera_id', $this->carrerasSeleccionadas)
            ->orderBy('carrera_nombre')
            ->pluck('carrera_nombre', 'carrera_id')
            ->toArray();
    }
}