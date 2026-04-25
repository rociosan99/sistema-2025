<?php

namespace App\Console\Commands;

use App\Services\Ubicaciones\ImportarUbicacionesDesdeCsv;
use Illuminate\Console\Command;

class ImportarUbicacionesCommand extends Command
{
    protected $signature = 'ubicaciones:importar
        {archivo=database/data/ubicaciones_argentina.csv : Ruta del CSV local}
        {--dry-run : Calcula cambios sin persistirlos}';

    protected $description = 'Importa paises, provincias y ciudades desde un CSV local sin borrar IDs existentes.';

    public function handle(ImportarUbicacionesDesdeCsv $importador): int
    {
        $archivo = base_path($this->argument('archivo'));
        $dryRun = (bool) $this->option('dry-run');

        $stats = $importador->importar($archivo, $dryRun);

        $this->info($dryRun ? 'Dry run completado. No se guardaron cambios.' : 'Importacion completada.');

        foreach ($stats as $key => $value) {
            $this->line($key . ': ' . $value);
        }

        return self::SUCCESS;
    }
}
