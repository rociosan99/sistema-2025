<?php

namespace App\Services\Ubicaciones;

use App\Models\Ciudad;
use App\Models\Pais;
use App\Models\Provincia;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use SplFileObject;

class ImportarUbicacionesDesdeCsv
{
    public function importar(string $path, bool $dryRun = false): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("No existe el archivo CSV: {$path}");
        }

        $stats = [
            'filas' => 0,
            'paises_creados' => 0,
            'paises_actualizados' => 0,
            'provincias_creadas' => 0,
            'provincias_actualizadas' => 0,
            'ciudades_creadas' => 0,
            'ciudades_actualizadas' => 0,
            'omitidas' => 0,
        ];

        $callback = function () use ($path, $dryRun, &$stats): void {
            $file = new SplFileObject($path);
            $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
            $file->setCsvControl(',');

            $headers = null;

            foreach ($file as $row) {
                if ($row === [null] || $row === false) {
                    continue;
                }

                $row = array_map(
                    fn ($value) => is_string($value) ? trim($this->removeBom($value)) : $value,
                    $row
                );

                if ($headers === null) {
                    $headers = $row;
                    $this->validarHeaders($headers);
                    continue;
                }

                if (count($headers) !== count($row)) {
                    $stats['omitidas']++;
                    continue;
                }

                $data = array_combine($headers, $row);

                $paisNombre = $this->limpiar($data['pais_nombre'] ?? null);
                $provinciaNombre = $this->limpiar($data['provincia_nombre'] ?? null);
                $ciudadNombre = $this->limpiar($data['ciudad_nombre'] ?? null);

                if (! $paisNombre || ! $provinciaNombre || ! $ciudadNombre) {
                    $stats['omitidas']++;
                    continue;
                }

                $stats['filas']++;

                $pais = $this->resolverPais($paisNombre, $this->limpiar($data['pais_codigo'] ?? null), $stats);
                $provincia = $this->resolverProvincia($pais, $provinciaNombre, $this->limpiar($data['provincia_codigo'] ?? null), $stats);

                $this->resolverCiudad(
                    $provincia,
                    $ciudadNombre,
                    $this->limpiar($data['ciudad_codigo'] ?? null),
                    $stats
                );
            }

            if ($dryRun) {
                throw new RuntimeException('__DRY_RUN__');
            }
        };

        if ($dryRun) {
            try {
                DB::transaction($callback);
            } catch (RuntimeException $exception) {
                if ($exception->getMessage() !== '__DRY_RUN__') {
                    throw $exception;
                }
            }

            return $stats;
        }

        DB::transaction($callback);

        return $stats;
    }

    private function resolverPais(string $nombre, ?string $codigo, array &$stats): Pais
    {
        $pais = $codigo
            ? Pais::query()->where('codigo_externo', $codigo)->first()
            : null;

        $pais ??= Pais::query()->where('pais_nombre', $nombre)->first();

        if (! $pais) {
            $pais = Pais::create([
                'pais_nombre' => $nombre,
                'codigo_externo' => $codigo,
            ]);

            $stats['paises_creados']++;

            return $pais;
        }

        $dirty = false;

        if ($pais->pais_nombre !== $nombre) {
            $pais->pais_nombre = $nombre;
            $dirty = true;
        }

        if ($codigo && $pais->codigo_externo !== $codigo) {
            $pais->codigo_externo = $codigo;
            $dirty = true;
        }

        if ($dirty) {
            $pais->save();
            $stats['paises_actualizados']++;
        }

        return $pais;
    }

    private function resolverProvincia(Pais $pais, string $nombre, ?string $codigo, array &$stats): Provincia
    {
        $provincia = $codigo
            ? Provincia::query()
                ->where('pais_id', $pais->pais_id)
                ->where('codigo_externo', $codigo)
                ->first()
            : null;

        $provincia ??= Provincia::query()
            ->where('pais_id', $pais->pais_id)
            ->whereIn('provincia_nombre', $this->variantesTexto($nombre))
            ->first();

        if (! $provincia) {
            $provincia = Provincia::create([
                'pais_id' => $pais->pais_id,
                'provincia_nombre' => $nombre,
                'codigo_externo' => $codigo,
            ]);

            $stats['provincias_creadas']++;

            return $provincia;
        }

        $dirty = false;

        if ($provincia->provincia_nombre !== $nombre) {
            $provincia->provincia_nombre = $nombre;
            $dirty = true;
        }

        if ($codigo && $provincia->codigo_externo !== $codigo) {
            $provincia->codigo_externo = $codigo;
            $dirty = true;
        }

        if ($dirty) {
            $provincia->save();
            $stats['provincias_actualizadas']++;
        }

        return $provincia;
    }

    private function resolverCiudad(Provincia $provincia, string $nombre, ?string $codigo, array &$stats): Ciudad
    {
        $ciudad = $codigo
            ? Ciudad::query()
                ->where('provincia_id', $provincia->provincia_id)
                ->where('codigo_externo', $codigo)
                ->first()
            : null;

        $ciudad ??= Ciudad::query()
            ->where('provincia_id', $provincia->provincia_id)
            ->whereIn('ciudad_nombre', $this->variantesTexto($nombre))
            ->first();

        if (! $ciudad) {
            $ciudad = Ciudad::create([
                'provincia_id' => $provincia->provincia_id,
                'ciudad_nombre' => $nombre,
                'codigo_externo' => $codigo,
            ]);

            $stats['ciudades_creadas']++;

            return $ciudad;
        }

        $dirty = false;

        if ($ciudad->ciudad_nombre !== $nombre) {
            $ciudad->ciudad_nombre = $nombre;
            $dirty = true;
        }

        if ($codigo && $ciudad->codigo_externo !== $codigo) {
            $ciudad->codigo_externo = $codigo;
            $dirty = true;
        }

        if ($dirty) {
            $ciudad->save();
            $stats['ciudades_actualizadas']++;
        }

        return $ciudad;
    }

    private function variantesTexto(string $texto): array
    {
        $variantes = [$texto];
        $mojibake = mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');

        if ($mojibake !== $texto) {
            $variantes[] = $mojibake;
        }

        return array_values(array_unique($variantes));
    }

    private function validarHeaders(array $headers): void
    {
        $requeridos = [
            'pais_codigo',
            'pais_nombre',
            'provincia_codigo',
            'provincia_nombre',
            'ciudad_codigo',
            'ciudad_nombre',
        ];

        foreach ($requeridos as $header) {
            if (! in_array($header, $headers, true)) {
                throw new InvalidArgumentException("Falta la columna requerida en el CSV: {$header}");
            }
        }
    }

    private function limpiar(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($this->removeBom($value));

        return $value === '' ? null : $value;
    }

    private function removeBom(string $value): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    }
}
