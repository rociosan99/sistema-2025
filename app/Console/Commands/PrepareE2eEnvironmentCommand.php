<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class PrepareE2eEnvironmentCommand extends Command
{
    protected $signature = 'e2e:prepare';

    protected $description = 'Verifica que el entorno E2E esté aislado antes de permitir futuras preparaciones';

    public function handle(): int
    {
        $entorno = (string) app()->environment();
        $conexion = (string) config('database.default');
        $baseConfigurada = (string) config("database.connections.{$conexion}.database");
        $host = (string) config("database.connections.{$conexion}.host");
        $baseE2eEsperada = (string) env('E2E_DB_DATABASE');

        $this->line("Entorno: {$entorno}");
        $this->line("Base: {$baseConfigurada}");
        $this->line("Host: {$host}");

        $comprobaciones = [
            'APP_ENV debe ser e2e.' => $entorno === 'e2e',
            'DB_CONNECTION debe ser mariadb.' => $conexion === 'mariadb',
            'E2E_DB_DATABASE debe estar configurada.' => $baseE2eEsperada !== '',
            'DB_DATABASE debe coincidir con E2E_DB_DATABASE.' => hash_equals($baseE2eEsperada, $baseConfigurada),
            'DB_DATABASE debe ser exactamente sistema_e2e.' => $baseConfigurada === 'sistema_e2e',
            'DB_DATABASE debe terminar en _e2e.' => str_ends_with($baseConfigurada, '_e2e'),
            'DB_DATABASE no puede ser sistema_2025.' => $baseConfigurada !== 'sistema_2025',
        ];

        foreach ($comprobaciones as $mensaje => $cumple) {
            if (! $cumple) {
                $this->error("Protección E2E activada: {$mensaje}");

                return self::FAILURE;
            }
        }

        try {
            $resultado = DB::connection()->selectOne('SELECT DATABASE() AS database_name');
            $baseReal = (string) ($resultado->database_name ?? '');
        } catch (Throwable $exception) {
            $this->error('No se pudo verificar la base MariaDB activa. No se realizó ninguna operación.');
            $this->line($exception->getMessage());

            return self::FAILURE;
        }

        if ($baseReal !== 'sistema_e2e' || ! hash_equals($baseE2eEsperada, $baseReal)) {
            $this->error("Protección E2E activada: SELECT DATABASE() devolvió '{$baseReal}'.");

            return self::FAILURE;
        }

        $this->info('Protecciones E2E verificadas correctamente.');
        $this->comment('Este comando todavía no modifica datos, no ejecuta migraciones y no carga fixtures.');

        return self::SUCCESS;
    }
}
