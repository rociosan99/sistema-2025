<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatosSistema extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $startDate = Carbon::create(2026, 1, 13, 8, 0, 0);

            [$careerMateriaMap, $planByCareer, $programByMateriaPlan, $materiaStrategyMap, $materiaNameMap] = $this->seedCatalog();
            $temaMap = $this->seedTemas();
            $this->seedMateriaTemaAndProgramaTema($careerMateriaMap, $planByCareer, $programByMateriaPlan, $temaMap, $materiaNameMap);

            [$profesores, $alumnos, $profesorCareerMap, $alumnoCareerMap] = $this->seedUsers(array_keys($careerMateriaMap));

            $this->cleanupPreviousSeedData($profesores, $alumnos);

            $this->seedProfesorProfiles($profesores, $profesorCareerMap, $careerMateriaMap, $materiaStrategyMap);
            $profesorMateriaMap = $this->seedProfesorMaterias($profesores, $profesorCareerMap, $careerMateriaMap, $materiaStrategyMap);
            $this->seedProfesorTemas($profesorMateriaMap, $temaMap, $materiaNameMap);
            $this->seedDisponibilidades($profesores);
            $this->seedSolicitudesYOfertas($startDate, $alumnos, $alumnoCareerMap, $careerMateriaMap, $profesores, $profesorMateriaMap, $materiaStrategyMap);

            $turnosInfo = $this->seedTurnosYPagos(
                $startDate,
                $alumnos,
                $alumnoCareerMap,
                $careerMateriaMap,
                $profesores,
                $profesorMateriaMap,
                $temaMap,
                $materiaStrategyMap,
                $materiaNameMap
            );

            $this->seedTurnoReemplazos($turnosInfo);
            $this->seedCalificaciones($turnosInfo);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function seedCatalog(): array
    {
        $catalog = [
            [
                'nombre' => 'UBA - Facultad de Ciencias Exactas y Naturales',
                'descripcion' => 'Facultad de la Universidad de Buenos Aires.',
                'carreras' => [
                    [
                        'nombre' => 'Licenciatura en Ciencias de la Computación',
                        'descripcion' => 'Carrera universitaria orientada a informática.',
                        'plan_anio' => 2023,
                        'materias' => [
                            ['nombre' => 'Algoritmos y Estructuras de Datos I', 'anio' => 1, 'descripcion' => 'Fundamentos de programación.', 'tendencia' => 'subir'],
                            ['nombre' => 'Matemática Discreta', 'anio' => 1, 'descripcion' => 'Lógica y combinatoria.', 'tendencia' => 'mantener'],
                            ['nombre' => 'Bases de Datos', 'anio' => 2, 'descripcion' => 'Modelado relacional y SQL.', 'tendencia' => 'subir'],
                            ['nombre' => 'Sistemas Operativos', 'anio' => 2, 'descripcion' => 'Procesos y memoria.', 'tendencia' => 'bajar'],
                        ],
                    ],
                ],
            ],
            [
                'nombre' => 'UTN - Facultad Regional Buenos Aires',
                'descripcion' => 'Facultad regional de la Universidad Tecnológica Nacional.',
                'carreras' => [
                    [
                        'nombre' => 'Ingeniería en Sistemas de Información',
                        'descripcion' => 'Carrera de grado orientada a software y gestión.',
                        'plan_anio' => 2022,
                        'materias' => [
                            ['nombre' => 'Programación I', 'anio' => 1, 'descripcion' => 'Introducción a la programación.', 'tendencia' => 'subir'],
                            ['nombre' => 'Análisis Matemático I', 'anio' => 1, 'descripcion' => 'Funciones y derivadas.', 'tendencia' => 'mantener'],
                            ['nombre' => 'Diseño de Sistemas', 'anio' => 2, 'descripcion' => 'Análisis y diseño.', 'tendencia' => 'subir'],
                            ['nombre' => 'Redes de Datos', 'anio' => 3, 'descripcion' => 'Protocolos y redes.', 'tendencia' => 'bajar'],
                        ],
                    ],
                ],
            ],
            [
                'nombre' => 'UBA - Facultad de Derecho',
                'descripcion' => 'Facultad de la Universidad de Buenos Aires.',
                'carreras' => [
                    [
                        'nombre' => 'Abogacía',
                        'descripcion' => 'Carrera universitaria jurídica.',
                        'plan_anio' => 2021,
                        'materias' => [
                            ['nombre' => 'Introducción al Derecho', 'anio' => 1, 'descripcion' => 'Conceptos jurídicos iniciales.', 'tendencia' => 'subir'],
                            ['nombre' => 'Derecho Constitucional', 'anio' => 1, 'descripcion' => 'Derechos fundamentales.', 'tendencia' => 'mantener'],
                            ['nombre' => 'Derecho Civil I', 'anio' => 2, 'descripcion' => 'Parte general del derecho civil.', 'tendencia' => 'mantener'],
                            ['nombre' => 'Derecho Penal I', 'anio' => 2, 'descripcion' => 'Teoría del delito.', 'tendencia' => 'bajar'],
                        ],
                    ],
                ],
            ],
            [
                'nombre' => 'UBA - Facultad de Ciencias Económicas',
                'descripcion' => 'Facultad de la Universidad de Buenos Aires.',
                'carreras' => [
                    [
                        'nombre' => 'Contador Público',
                        'descripcion' => 'Carrera universitaria contable y financiera.',
                        'plan_anio' => 2021,
                        'materias' => [
                            ['nombre' => 'Contabilidad I', 'anio' => 1, 'descripcion' => 'Principios contables básicos.', 'tendencia' => 'subir'],
                            ['nombre' => 'Matemática Financiera', 'anio' => 1, 'descripcion' => 'Interés y rentas.', 'tendencia' => 'mantener'],
                            ['nombre' => 'Derecho Privado', 'anio' => 1, 'descripcion' => 'Derecho civil y comercial.', 'tendencia' => 'mantener'],
                            ['nombre' => 'Impuestos I', 'anio' => 2, 'descripcion' => 'Sistema tributario argentino.', 'tendencia' => 'bajar'],
                        ],
                    ],
                ],
            ],
            [
                'nombre' => 'UNC - FAMAF',
                'descripcion' => 'Facultad de Matemática, Astronomía, Física y Computación de la Universidad Nacional de Córdoba.',
                'carreras' => [
                    [
                        'nombre' => 'Licenciatura en Matemática',
                        'descripcion' => 'Carrera universitaria orientada a matemática.',
                        'plan_anio' => 2022,
                        'materias' => [
                            ['nombre' => 'Análisis I', 'anio' => 1, 'descripcion' => 'Funciones y continuidad.', 'tendencia' => 'subir'],
                            ['nombre' => 'Álgebra I', 'anio' => 1, 'descripcion' => 'Estructuras algebraicas.', 'tendencia' => 'mantener'],
                            ['nombre' => 'Geometría I', 'anio' => 1, 'descripcion' => 'Geometría analítica.', 'tendencia' => 'mantener'],
                            ['nombre' => 'Probabilidad', 'anio' => 2, 'descripcion' => 'Variables aleatorias.', 'tendencia' => 'bajar'],
                        ],
                    ],
                ],
            ],
            [
                'nombre' => 'UNR - Facultad de Ciencias Médicas',
                'descripcion' => 'Facultad de la Universidad Nacional de Rosario.',
                'carreras' => [
                    [
                        'nombre' => 'Medicina',
                        'descripcion' => 'Carrera universitaria médica.',
                        'plan_anio' => 2022,
                        'materias' => [
                            ['nombre' => 'Anatomía Humana', 'anio' => 1, 'descripcion' => 'Morfología del cuerpo humano.', 'tendencia' => 'subir'],
                            ['nombre' => 'Histología', 'anio' => 1, 'descripcion' => 'Tejidos y microscopía.', 'tendencia' => 'mantener'],
                            ['nombre' => 'Fisiología', 'anio' => 2, 'descripcion' => 'Funciones de órganos.', 'tendencia' => 'mantener'],
                            ['nombre' => 'Bioquímica', 'anio' => 2, 'descripcion' => 'Procesos bioquímicos.', 'tendencia' => 'bajar'],
                        ],
                    ],
                ],
            ],
        ];

        $careerMateriaMap = [];
        $planByCareer = [];
        $programByMateriaPlan = [];
        $materiaStrategyMap = [];
        $materiaNameMap = [];

        foreach ($catalog as $institutionData) {
            $institucionId = $this->upsertAndGetId(
                'instituciones',
                ['institucion_nombre' => $institutionData['nombre']],
                [
                    'institucion_nombre' => $institutionData['nombre'],
                    'institucion_descripcion' => $institutionData['descripcion'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                'institucion_id'
            );

            foreach ($institutionData['carreras'] as $careerData) {
                $carreraId = $this->upsertAndGetId(
                    'carreras',
                    [
                        'carrera_institucion_id' => $institucionId,
                        'carrera_nombre' => $careerData['nombre'],
                    ],
                    [
                        'carrera_institucion_id' => $institucionId,
                        'carrera_nombre' => $careerData['nombre'],
                        'carrera_descripcion' => $careerData['descripcion'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    'carrera_id'
                );

                $planId = $this->upsertAndGetId(
                    'planes_estudio',
                    [
                        'plan_carrera_id' => $carreraId,
                        'plan_anio' => $careerData['plan_anio'],
                    ],
                    [
                        'plan_carrera_id' => $carreraId,
                        'plan_anio' => $careerData['plan_anio'],
                        'plan_descripcion' => 'Plan ' . $careerData['plan_anio'] . ' - ' . $careerData['nombre'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    'plan_id'
                );

                $planByCareer[$carreraId] = $planId;
                $careerMateriaMap[$carreraId] = [];

                foreach ($careerData['materias'] as $materiaData) {
                    $materiaId = $this->upsertAndGetId(
                        'materias',
                        [
                            'materia_nombre' => $materiaData['nombre'],
                            'materia_anio' => $materiaData['anio'],
                        ],
                        [
                            'materia_nombre' => $materiaData['nombre'],
                            'materia_descripcion' => $materiaData['descripcion'],
                            'materia_anio' => $materiaData['anio'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        'materia_id'
                    );

                    $careerMateriaMap[$carreraId][] = $materiaId;
                    $materiaStrategyMap[$materiaId] = $materiaData['tendencia'];
                    $materiaNameMap[$materiaId] = $materiaData['nombre'];

                    DB::table('carrera_materias')->updateOrInsert(
                        [
                            'carreramateria_carrera_id' => $carreraId,
                            'carreramateria_materia_id' => $materiaId,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $programaId = $this->upsertAndGetId(
                        'programas',
                        [
                            'programa_plan_id' => $planId,
                            'programa_materia_id' => $materiaId,
                        ],
                        [
                            'programa_plan_id' => $planId,
                            'programa_materia_id' => $materiaId,
                            'programa_anio' => $careerData['plan_anio'],
                            'programa_descripcion' => 'Programa de ' . $materiaData['nombre'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        'programa_id'
                    );

                    $programByMateriaPlan[$planId . '-' . $materiaId] = $programaId;
                }
            }
        }

        return [$careerMateriaMap, $planByCareer, $programByMateriaPlan, $materiaStrategyMap, $materiaNameMap];
    }

    private function seedTemas(): array
    {
        $temas = [
            'Funciones', 'Límites', 'Derivadas', 'Integrales', 'Algoritmos', 'SQL', 'Redes',
            'Constitución', 'Contabilidad básica', 'Anatomía', 'Bioquímica',
            'Repaso de parcial', 'Trabajo práctico', 'Consulta general', 'Examen final',
            'Casos prácticos', 'Ejercitación', 'Teoría general',
        ];

        $map = [];
        foreach ($temas as $temaNombre) {
            $temaId = $this->upsertAndGetId(
                'temas',
                ['tema_nombre' => $temaNombre],
                [
                    'tema_nombre' => $temaNombre,
                    'tema_descripcion' => 'Tema académico: ' . $temaNombre,
                    'tema_id_tema_padre' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                'tema_id'
            );

            $map[$temaNombre] = $temaId;
        }

        return $map;
    }

    private function seedMateriaTemaAndProgramaTema(
        array $careerMateriaMap,
        array $planByCareer,
        array $programByMateriaPlan,
        array $temaMap,
        array $materiaNameMap
    ): void {
        foreach ($careerMateriaMap as $careerId => $materiaIds) {
            $planId = $planByCareer[$careerId] ?? null;
            if (!$planId) {
                continue;
            }

            foreach ($materiaIds as $materiaId) {
                $temaIds = $this->temaIdsForMateria($materiaNameMap[$materiaId] ?? '', $temaMap);

                foreach ($temaIds as $temaId) {
                    DB::table('materia_tema')->updateOrInsert(
                        ['materia_id' => $materiaId, 'tema_id' => $temaId],
                        ['created_at' => now(), 'updated_at' => now()]
                    );

                    $programaId = $programByMateriaPlan[$planId . '-' . $materiaId] ?? null;
                    if ($programaId) {
                        DB::table('programa_tema')->updateOrInsert(
                            ['programa_id' => $programaId, 'tema_id' => $temaId],
                            ['created_at' => now(), 'updated_at' => now()]
                        );
                    }
                }
            }
        }
    }

    private function seedUsers(array $careerIds): array
    {
        $profesoresData = [
            ['name' => 'Lucía', 'apellido' => 'Gómez'],
            ['name' => 'Santiago', 'apellido' => 'Pérez'],
            ['name' => 'Valentina', 'apellido' => 'Suárez'],
            ['name' => 'Martín', 'apellido' => 'Fernández'],
            ['name' => 'Camila', 'apellido' => 'Benítez'],
            ['name' => 'Nicolás', 'apellido' => 'Ríos'],
            ['name' => 'Agustina', 'apellido' => 'Quiroga'],
            ['name' => 'Franco', 'apellido' => 'Ledesma'],
            ['name' => 'Josefina', 'apellido' => 'Márquez'],
            ['name' => 'Ignacio', 'apellido' => 'Moreno'],
            ['name' => 'Florencia', 'apellido' => 'Castro'],
            ['name' => 'Leandro', 'apellido' => 'Serrano'],
        ];

        $alumnosData = [
            ['name' => 'Micaela', 'apellido' => 'Sosa'],
            ['name' => 'Tomás', 'apellido' => 'Romero'],
            ['name' => 'Julieta', 'apellido' => 'Acosta'],
            ['name' => 'Bruno', 'apellido' => 'Medina'],
            ['name' => 'Sofía', 'apellido' => 'Torres'],
            ['name' => 'Thiago', 'apellido' => 'Cáceres'],
            ['name' => 'Milagros', 'apellido' => 'Vega'],
            ['name' => 'Joaquín', 'apellido' => 'Molina'],
            ['name' => 'Catalina', 'apellido' => 'Navarro'],
            ['name' => 'Bautista', 'apellido' => 'Herrera'],
            ['name' => 'Renata', 'apellido' => 'Silva'],
            ['name' => 'Lautaro', 'apellido' => 'Ponce'],
            ['name' => 'Emilia', 'apellido' => 'Godoy'],
            ['name' => 'Mateo', 'apellido' => 'Aguirre'],
            ['name' => 'Abril', 'apellido' => 'Peralta'],
            ['name' => 'Benjamín', 'apellido' => 'Farías'],
            ['name' => 'Victoria', 'apellido' => 'Méndez'],
            ['name' => 'Felipe', 'apellido' => 'Correa'],
            ['name' => 'Valentino', 'apellido' => 'Luna'],
            ['name' => 'Malena', 'apellido' => 'Rivero'],
            ['name' => 'Facundo', 'apellido' => 'Ruiz'],
            ['name' => 'Candela', 'apellido' => 'Domínguez'],
            ['name' => 'Ramiro', 'apellido' => 'Nuñez'],
            ['name' => 'Martina', 'apellido' => 'Paz'],
        ];

        $profesores = [];
        $profesorCareerMap = [];
        foreach ($profesoresData as $index => $prof) {
            $careerId = $careerIds[$index % count($careerIds)];
            $profesorId = $this->upsertAndGetId(
                'users',
                ['email' => 'profesor' . ($index + 1) . '@seed.com'],
                [
                    'name' => $prof['name'],
                    'apellido' => $prof['apellido'],
                    'profile_photo_path' => null,
                    'email' => 'profesor' . ($index + 1) . '@seed.com',
                    'google_id' => null,
                    'google_avatar_url' => null,
                    'role' => 'profesor',
                    'activo' => 1,
                    'carrera_activa_id' => $careerId,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'remember_token' => Str::random(10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                'id'
            );
            $profesores[] = $profesorId;
            $profesorCareerMap[$profesorId] = $careerId;
        }

        $alumnos = [];
        $alumnoCareerMap = [];
        foreach ($alumnosData as $index => $alu) {
            $careerId = $careerIds[$index % count($careerIds)];
            $alumnoId = $this->upsertAndGetId(
                'users',
                ['email' => 'alumno' . ($index + 1) . '@seed.com'],
                [
                    'name' => $alu['name'],
                    'apellido' => $alu['apellido'],
                    'profile_photo_path' => null,
                    'email' => 'alumno' . ($index + 1) . '@seed.com',
                    'google_id' => null,
                    'google_avatar_url' => null,
                    'role' => 'alumno',
                    'activo' => 1,
                    'carrera_activa_id' => $careerId,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'remember_token' => Str::random(10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                'id'
            );
            $alumnos[] = $alumnoId;
            $alumnoCareerMap[$alumnoId] = $careerId;
        }

        return [$profesores, $alumnos, $profesorCareerMap, $alumnoCareerMap];
    }

    private function cleanupPreviousSeedData(array $profesores, array $alumnos): void
    {
        $seedUserIds = array_values(array_unique(array_merge($profesores, $alumnos)));

        $turnoIds = DB::table('turnos')
            ->whereIn('alumno_id', $seedUserIds)
            ->orWhereIn('profesor_id', $seedUserIds)
            ->pluck('id')
            ->all();

        if (!empty($turnoIds)) {
            DB::table('calificaciones_profesor')->whereIn('turno_id', $turnoIds)->delete();
            DB::table('calificaciones_alumno')->whereIn('turno_id', $turnoIds)->delete();
            DB::table('pagos')->whereIn('turno_id', $turnoIds)->delete();
            DB::table('turno_reemplazos')->whereIn('turno_cancelado_id', $turnoIds)->delete();
            DB::table('turnos')->whereIn('id', $turnoIds)->delete();
        }

        DB::table('ofertas_solicitud')->whereIn('profesor_id', $profesores)->delete();
        DB::table('solicitudes_disponibilidad')->whereIn('alumno_id', $alumnos)->delete();
        DB::table('disponibilidades')->whereIn('profesor_id', $profesores)->delete();
        DB::table('profesor_tema')->whereIn('profesor_id', $profesores)->delete();
        DB::table('profesor_materia')->whereIn('profesor_id', $profesores)->delete();
        DB::table('profesor_profiles')->whereIn('user_id', $profesores)->delete();
    }

    private function seedProfesorProfiles(array $profesores, array $profesorCareerMap, array $careerMateriaMap, array $materiaStrategyMap): void
    {
        $ciudades = ['Buenos Aires', 'Córdoba', 'La Plata', 'Rosario'];
        $niveles = ['inicial', 'intermedio', 'avanzado'];
        $titulos = ['Licenciado/a', 'Ingeniero/a', 'Profesor/a', 'Abogado/a', 'Contador/a', 'Médico/a'];

        foreach ($profesores as $index => $profesorId) {
            $careerId = $profesorCareerMap[$profesorId];
            $materias = $careerMateriaMap[$careerId] ?? [];
            $firstMateriaId = $materias[0] ?? null;
            $strategy = $firstMateriaId ? ($materiaStrategyMap[$firstMateriaId] ?? 'mantener') : 'mantener';

            DB::table('profesor_profiles')->updateOrInsert(
                ['user_id' => $profesorId],
                [
                    'ciudad' => $ciudades[$index % count($ciudades)],
                    'bio' => 'Docente particular especializado en acompañamiento académico universitario.',
                    'experiencia_anios' => rand(2, 12),
                    'nivel' => $niveles[array_rand($niveles)],
                    'precio_por_hora_default' => $this->priceForStrategy($strategy),
                    'titulo_profesional' => $titulos[$index % count($titulos)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedProfesorMaterias(array $profesores, array $profesorCareerMap, array $careerMateriaMap, array $materiaStrategyMap): array
    {
        $map = [];

        foreach ($profesores as $profesorId) {
            $careerId = $profesorCareerMap[$profesorId];
            $materias = $careerMateriaMap[$careerId] ?? [];
            $map[$profesorId] = [];

            foreach ($materias as $materiaId) {
                $strategy = $materiaStrategyMap[$materiaId] ?? 'mantener';
                $precioHora = $this->priceForStrategy($strategy);

                DB::table('profesor_materia')->updateOrInsert(
                    [
                        'profesor_id' => $profesorId,
                        'materia_id' => $materiaId,
                    ],
                    [
                        'precio_por_hora' => $precioHora,
                        'precio_por_clase' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $map[$profesorId][$materiaId] = [
                    'precio' => $precioHora,
                    'strategy' => $strategy,
                ];
            }
        }

        return $map;
    }

    private function seedProfesorTemas(array $profesorMateriaMap, array $temaMap, array $materiaNameMap): void
    {
        foreach ($profesorMateriaMap as $profesorId => $materias) {
            $temaIds = [];

            foreach (array_keys($materias) as $materiaId) {
                $temaIds = array_merge($temaIds, $this->temaIdsForMateria($materiaNameMap[$materiaId] ?? '', $temaMap));
            }

            foreach (array_values(array_unique($temaIds)) as $temaId) {
                DB::table('profesor_tema')->updateOrInsert(
                    ['profesor_id' => $profesorId, 'tema_id' => $temaId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    private function seedDisponibilidades(array $profesores): void
    {
        $bloques = [
            [1, '08:00:00', '11:00:00'],
            [2, '14:00:00', '18:00:00'],
            [3, '09:00:00', '12:00:00'],
            [4, '17:00:00', '21:00:00'],
            [5, '10:00:00', '13:00:00'],
        ];

        foreach ($profesores as $index => $profesorId) {
            $seleccion = [
                $bloques[$index % 5],
                $bloques[($index + 2) % 5],
                $bloques[($index + 4) % 5],
            ];

            foreach ($seleccion as [$dia, $inicio, $fin]) {
                DB::table('disponibilidades')->updateOrInsert(
                    [
                        'profesor_id' => $profesorId,
                        'dia_semana' => $dia,
                        'hora_inicio' => $inicio,
                        'hora_fin' => $fin,
                    ],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    private function seedSolicitudesYOfertas(
        Carbon $startDate,
        array $alumnos,
        array $alumnoCareerMap,
        array $careerMateriaMap,
        array $profesores,
        array $profesorMateriaMap,
        array $materiaStrategyMap
    ): void {
        $plan = [
            ['strategy' => 'subir', 'cantidad' => 14, 'estado' => 'activa'],
            ['strategy' => 'mantener', 'cantidad' => 10, 'estado' => 'expirada'],
            ['strategy' => 'bajar', 'cantidad' => 8, 'estado' => 'expirada'],
        ];

        foreach ($plan as $bloque) {
            for ($i = 0; $i < $bloque['cantidad']; $i++) {
                $materiaId = $this->pickMateriaByStrategy($materiaStrategyMap, $bloque['strategy']);
                $alumnoId = $this->pickUserForMateria($alumnos, $alumnoCareerMap, $careerMateriaMap, $materiaId);

                if (!$materiaId || !$alumnoId) {
                    continue;
                }

                $fecha = (clone $startDate)->copy()->addDays(rand(10, 85));
                $horaInicio = rand(8, 19);
                $createdAt = (clone $fecha)->copy()->subDays(rand(1, 4))->setTime(rand(8, 20), 0);

                $solicitudId = DB::table('solicitudes_disponibilidad')->insertGetId([
                    'alumno_id' => $alumnoId,
                    'materia_id' => $materiaId,
                    'tema_id' => null,
                    'fecha' => $fecha->toDateString(),
                    'hora_inicio' => sprintf('%02d:00:00', $horaInicio),
                    'hora_fin' => sprintf('%02d:00:00', $horaInicio + 1),
                    'estado' => $bloque['estado'],
                    'expires_at' => $bloque['estado'] === 'activa' ? (clone $createdAt)->copy()->addHours(8) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $bloque['estado'] === 'expirada' ? (clone $createdAt)->copy()->addHours(6) : $createdAt,
                ]);

                $profesCompatibles = [];
                foreach ($profesores as $profesorId) {
                    if (isset($profesorMateriaMap[$profesorId][$materiaId])) {
                        $profesCompatibles[] = $profesorId;
                    }
                }

                shuffle($profesCompatibles);
                $profesCompatibles = array_slice($profesCompatibles, 0, min(count($profesCompatibles), rand(1, 3)));

                foreach ($profesCompatibles as $idx => $profesorId) {
                    $ofertaCreada = (clone $createdAt)->copy()->addMinutes(rand(10, 70));
                    $ofertaEstado = $bloque['estado'] === 'activa' && $idx === 0 ? 'pendiente' : 'expirada';

                    DB::table('ofertas_solicitud')->insert([
                        'solicitud_id' => $solicitudId,
                        'profesor_id' => $profesorId,
                        'hora_inicio' => sprintf('%02d:00:00', $horaInicio),
                        'hora_fin' => sprintf('%02d:00:00', $horaInicio + 1),
                        'estado' => $ofertaEstado,
                        'origen' => 'batch',
                        'expires_at' => (clone $ofertaCreada)->copy()->addHour(),
                        'recommended_turno_id' => null,
                        'recommended_reason' => null,
                        'created_at' => $ofertaCreada,
                        'updated_at' => $ofertaEstado === 'expirada' ? (clone $ofertaCreada)->copy()->addHour() : $ofertaCreada,
                    ]);
                }
            }
        }
    }

    private function seedTurnosYPagos(
        Carbon $startDate,
        array $alumnos,
        array $alumnoCareerMap,
        array $careerMateriaMap,
        array $profesores,
        array $profesorMateriaMap,
        array $temaMap,
        array $materiaStrategyMap,
        array $materiaNameMap
    ): array {
        $turnosInfo = [];

        $plan = [
            ['strategy' => 'subir', 'estado_turno' => 'confirmado', 'estado_pago' => 'aprobado', 'cantidad' => 22],
            ['strategy' => 'subir', 'estado_turno' => 'pendiente_pago', 'estado_pago' => 'pendiente', 'cantidad' => 6],
            ['strategy' => 'subir', 'estado_turno' => 'cancelado', 'estado_pago' => 'aprobado', 'cantidad' => 2],

            ['strategy' => 'mantener', 'estado_turno' => 'confirmado', 'estado_pago' => 'aprobado', 'cantidad' => 14],
            ['strategy' => 'mantener', 'estado_turno' => 'pendiente_pago', 'estado_pago' => 'pendiente', 'cantidad' => 6],
            ['strategy' => 'mantener', 'estado_turno' => 'vencido', 'estado_pago' => 'pendiente', 'cantidad' => 4],
            ['strategy' => 'mantener', 'estado_turno' => 'cancelado', 'estado_pago' => 'cancelado', 'cantidad' => 4],

            ['strategy' => 'bajar', 'estado_turno' => 'confirmado', 'estado_pago' => 'aprobado', 'cantidad' => 3],
            ['strategy' => 'bajar', 'estado_turno' => 'rechazado', 'estado_pago' => 'rechazado', 'cantidad' => 8],
            ['strategy' => 'bajar', 'estado_turno' => 'vencido', 'estado_pago' => 'pendiente', 'cantidad' => 5],
            ['strategy' => 'bajar', 'estado_turno' => 'cancelado', 'estado_pago' => 'cancelado', 'cantidad' => 4],
        ];

        foreach ($plan as $bloque) {
            for ($i = 0; $i < $bloque['cantidad']; $i++) {
                $materiaId = $this->pickMateriaByStrategy($materiaStrategyMap, $bloque['strategy']);
                $alumnoId = $this->pickUserForMateria($alumnos, $alumnoCareerMap, $careerMateriaMap, $materiaId);
                $profesorId = $this->pickProfesorForMateriaConcentrado($profesores, $profesorMateriaMap, $materiaId, $bloque['strategy']);

                if (!$materiaId || !$alumnoId || !$profesorId) {
                    continue;
                }

                $temaIds = $this->temaIdsForMateria($materiaNameMap[$materiaId] ?? '', $temaMap);
                $temaId = $temaIds[array_rand($temaIds)];

                $fecha = $this->turnoDateForState($startDate, $bloque['estado_turno']);
                $horaInicio = rand(8, 20);
                $precioHora = $profesorMateriaMap[$profesorId][$materiaId]['precio'];
                $createdAt = (clone $fecha)->copy()->subDays(rand(2, 10))->setTime(rand(8, 20), 0);
                $updatedAt = (clone $createdAt)->copy()->addMinutes(rand(20, 180));

                $canceladoAt = null;
                $cancelacionTipo = null;
                $asistenciaConfirmadaAt = null;
                $recordatorio24h = null;

                if ($bloque['estado_turno'] === 'cancelado') {
                    $canceladoAt = (clone $updatedAt)->copy();
                    $cancelacionTipo = $bloque['estado_pago'] === 'aprobado' ? 'con_cargo' : 'sin_cargo';
                }

                if ($bloque['estado_turno'] === 'confirmado' && $fecha->isPast()) {
                    $asistenciaConfirmadaAt = (clone $fecha)->copy()->setTime($horaInicio + 1, 0);
                    $recordatorio24h = (clone $fecha)->copy()->subDay()->setTime(10, 0);
                }

                $turnoId = DB::table('turnos')->insertGetId([
                    'alumno_id' => $alumnoId,
                    'profesor_id' => $profesorId,
                    'materia_id' => $materiaId,
                    'tema_id' => $temaId,
                    'fecha' => $fecha->toDateString(),
                    'hora_inicio' => sprintf('%02d:00:00', $horaInicio),
                    'hora_fin' => sprintf('%02d:00:00', $horaInicio + 1),
                    'estado' => $bloque['estado_turno'],
                    'cancelado_at' => $canceladoAt,
                    'cancelacion_tipo' => $cancelacionTipo,
                    'reemplazado_por_turno_id' => null,
                    'reprogramado_por_turno_id' => null,
                    'reprogramado_at' => null,
                    'asistencia_confirmada_at' => $asistenciaConfirmadaAt,
                    'asistencia_cancelada_at' => null,
                    'precio_por_hora' => $precioHora,
                    'precio_total' => $precioHora,
                    'recordatorio_24h_enviado_at' => $recordatorio24h,
                    'created_at' => $createdAt,
                    'updated_at' => $canceladoAt ?? $asistenciaConfirmadaAt ?? $updatedAt,
                ]);

                [$mpPaymentId, $mpStatus, $mpStatusDetail, $mpPaymentType, $mpPaymentMethod, $fechaAprobado, $recordatorioPago] =
                    $this->paymentData($bloque['estado_pago'], $updatedAt);

                DB::table('pagos')->insert([
                    'turno_id' => $turnoId,
                    'monto' => $precioHora,
                    'moneda' => 'ARS',
                    'estado' => $bloque['estado_pago'],
                    'provider' => 'mercadopago',
                    'mp_preference_id' => 'pref-' . Str::uuid(),
                    'mp_init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=' . Str::uuid(),
                    'mp_payment_id' => $mpPaymentId,
                    'mp_status' => $mpStatus,
                    'mp_status_detail' => $mpStatusDetail,
                    'mp_payment_type' => $mpPaymentType,
                    'mp_payment_method' => $mpPaymentMethod,
                    'external_reference' => 'turno:' . $turnoId,
                    'detalle_externo' => json_encode([
                        'seed' => true,
                        'fuente' => 'DatosSistema',
                        'estrategia_precio' => $bloque['strategy'],
                    ], JSON_UNESCAPED_UNICODE),
                    'fecha_aprobado' => $fechaAprobado,
                    'recordatorio_pago_enviado_at' => $recordatorioPago,
                    'created_at' => $createdAt,
                    'updated_at' => $fechaAprobado ?? $recordatorioPago ?? $updatedAt,
                ]);

                $turnosInfo[] = [
                    'id' => $turnoId,
                    'alumno_id' => $alumnoId,
                    'profesor_id' => $profesorId,
                    'materia_id' => $materiaId,
                    'tema_id' => $temaId,
                    'fecha' => $fecha->toDateString(),
                    'hora_inicio' => sprintf('%02d:00:00', $horaInicio),
                    'hora_fin' => sprintf('%02d:00:00', $horaInicio + 1),
                    'estado' => $bloque['estado_turno'],
                    'cancelacion_tipo' => $cancelacionTipo,
                    'created_at' => $createdAt,
                    'strategy' => $bloque['strategy'],
                ];
            }
        }

        return $turnosInfo;
    }

    private function seedCalificaciones(array $turnosInfo): void
    {
        $comentariosProfesor = [
            'Explicó muy claro y fue puntual.',
            'La clase me sirvió mucho para preparar el parcial.',
            'Buen profesor, resolvió todas las dudas.',
            'Muy recomendable para repasar temas difíciles.',
            'Excelente predisposición y claridad.',
            'Clase correcta, aunque podría profundizar más.',
            'No me terminó de cerrar la dinámica de la clase.',
        ];

        $comentariosAlumno = [
            'Alumno muy comprometido y participativo.',
            'Buena predisposición durante toda la clase.',
            'Llegó puntual y trabajó bien el contenido.',
            'Mostró interés y avanzó bastante.',
            'Muy respetuoso y atento en la clase.',
            'Le faltó constancia en algunos temas.',
            'Necesita más práctica entre clases.',
        ];

        $confirmadosPasados = array_values(array_filter($turnosInfo, function ($turno) {
            return $turno['estado'] === 'confirmado'
                && Carbon::parse($turno['fecha'] . ' ' . $turno['hora_fin'])->isPast();
        }));

        usort($confirmadosPasados, function ($a, $b) {
            if ($a['profesor_id'] === $b['profesor_id']) {
                return strcmp($a['fecha'], $b['fecha']);
            }
            return $a['profesor_id'] <=> $b['profesor_id'];
        });

        $porProfesor = [];
        foreach ($confirmadosPasados as $turno) {
            $porProfesor[$turno['profesor_id']][] = $turno;
        }

        uasort($porProfesor, function ($a, $b) {
            return count($b) <=> count($a);
        });

        $profesoresObjetivo = array_slice(array_keys($porProfesor), 0, min(3, count($porProfesor)));

        foreach ($porProfesor as $profesorId => $turnosProfesor) {
            $limiteProfesor = in_array($profesorId, $profesoresObjetivo, true) ? 16 : 8;
            $insertadosProfesor = 0;
            $insertadosAlumno = 0;

            foreach ($turnosProfesor as $turno) {
                if ($insertadosProfesor < $limiteProfesor) {
                    DB::table('calificaciones_profesor')->insert([
                        'turno_id' => $turno['id'],
                        'alumno_id' => $turno['alumno_id'],
                        'profesor_id' => $turno['profesor_id'],
                        'estrellas' => $this->ratingForStrategy($turno['strategy']),
                        'comentario' => $comentariosProfesor[array_rand($comentariosProfesor)],
                        'created_at' => Carbon::parse($turno['fecha'] . ' ' . $turno['hora_fin'])->addHours(rand(3, 48)),
                        'updated_at' => Carbon::parse($turno['fecha'] . ' ' . $turno['hora_fin'])->addHours(rand(3, 48)),
                    ]);
                    $insertadosProfesor++;
                }

                if ($insertadosAlumno < min(10, $limiteProfesor) && rand(0, 100) < 70) {
                    DB::table('calificaciones_alumno')->insert([
                        'turno_id' => $turno['id'],
                        'profesor_id' => $turno['profesor_id'],
                        'alumno_id' => $turno['alumno_id'],
                        'estrellas' => $this->ratingForStrategy($turno['strategy']),
                        'comentario' => $comentariosAlumno[array_rand($comentariosAlumno)],
                        'created_at' => Carbon::parse($turno['fecha'] . ' ' . $turno['hora_fin'])->addHours(rand(3, 72)),
                        'updated_at' => Carbon::parse($turno['fecha'] . ' ' . $turno['hora_fin'])->addHours(rand(3, 72)),
                    ]);
                    $insertadosAlumno++;
                }
            }
        }
    }

    private function seedTurnoReemplazos(array $turnosInfo): void
    {
        $candidatos = array_values(array_filter($turnosInfo, function ($turno) {
            return $turno['estado'] === 'cancelado' && $turno['cancelacion_tipo'] === 'con_cargo';
        }));

        shuffle($candidatos);
        $candidatos = array_slice($candidatos, 0, min(6, count($candidatos)));

        foreach ($candidatos as $turno) {
            DB::table('turno_reemplazos')->insert([
                'turno_cancelado_id' => $turno['id'],
                'alumno_id' => $turno['alumno_id'],
                'profesor_id' => $turno['profesor_id'],
                'materia_id' => $turno['materia_id'],
                'tema_id' => $turno['tema_id'],
                'fecha' => $turno['fecha'],
                'hora_inicio' => $turno['hora_inicio'],
                'hora_fin' => $turno['hora_fin'],
                'estado' => rand(0, 100) < 60 ? 'aceptada' : 'pendiente',
                'expires_at' => Carbon::parse($turno['created_at'])->addHour(),
                'notificado_at' => Carbon::parse($turno['created_at'])->addMinutes(10),
                'created_at' => Carbon::parse($turno['created_at'])->addMinutes(5),
                'updated_at' => Carbon::parse($turno['created_at'])->addMinutes(25),
            ]);
        }
    }

    private function temaIdsForMateria(string $nombre, array $temaMap): array
    {
        if (stripos($nombre, 'Derecho') !== false || stripos($nombre, 'Constitucional') !== false) {
            return [$temaMap['Constitución'], $temaMap['Casos prácticos'], $temaMap['Consulta general'], $temaMap['Examen final']];
        }

        if (stripos($nombre, 'Contabilidad') !== false || stripos($nombre, 'Impuestos') !== false || stripos($nombre, 'Financiera') !== false) {
            return [$temaMap['Contabilidad básica'], $temaMap['Ejercitación'], $temaMap['Trabajo práctico'], $temaMap['Examen final']];
        }

        if (stripos($nombre, 'Anatomía') !== false || stripos($nombre, 'Bioquímica') !== false || stripos($nombre, 'Fisiología') !== false || stripos($nombre, 'Histología') !== false) {
            return [$temaMap['Anatomía'], $temaMap['Bioquímica'], $temaMap['Teoría general'], $temaMap['Repaso de parcial']];
        }

        if (stripos($nombre, 'Redes') !== false) {
            return [$temaMap['Redes'], $temaMap['Trabajo práctico'], $temaMap['Consulta general'], $temaMap['Examen final']];
        }

        if (stripos($nombre, 'Base') !== false || stripos($nombre, 'SQL') !== false) {
            return [$temaMap['SQL'], $temaMap['Trabajo práctico'], $temaMap['Consulta general'], $temaMap['Repaso de parcial']];
        }

        if (stripos($nombre, 'Programación') !== false || stripos($nombre, 'Algoritmos') !== false || stripos($nombre, 'Sistemas') !== false) {
            return [$temaMap['Algoritmos'], $temaMap['Trabajo práctico'], $temaMap['Consulta general'], $temaMap['Examen final']];
        }

        if (stripos($nombre, 'Análisis') !== false || stripos($nombre, 'Álgebra') !== false || stripos($nombre, 'Geometría') !== false || stripos($nombre, 'Probabilidad') !== false) {
            return [$temaMap['Funciones'], $temaMap['Límites'], $temaMap['Derivadas'], $temaMap['Integrales']];
        }

        return [$temaMap['Ejercitación'], $temaMap['Trabajo práctico'], $temaMap['Consulta general'], $temaMap['Repaso de parcial']];
    }

    private function priceForStrategy(string $strategy): int
    {
        if ($strategy === 'subir') {
            return rand(120, 250);
        }

        if ($strategy === 'bajar') {
            return rand(780, 1000);
        }

        return rand(420, 650);
    }

    private function ratingForStrategy(string $strategy): int
    {
        if ($strategy === 'subir') {
            $pool = [4, 4, 5, 5, 5];
            return $pool[array_rand($pool)];
        }

        if ($strategy === 'bajar') {
            $pool = [1, 2, 2, 3, 3];
            return $pool[array_rand($pool)];
        }

        $pool = [3, 3, 4, 4, 5];
        return $pool[array_rand($pool)];
    }

    private function pickMateriaByStrategy(array $materiaStrategyMap, string $strategy): ?int
    {
        $materias = [];
        foreach ($materiaStrategyMap as $materiaId => $value) {
            if ($value === $strategy) {
                $materias[] = $materiaId;
            }
        }

        return empty($materias) ? null : $materias[array_rand($materias)];
    }

    private function pickUserForMateria(array $userIds, array $userCareerMap, array $careerMateriaMap, ?int $materiaId): ?int
    {
        if (!$materiaId) {
            return null;
        }

        $candidatos = [];
        foreach ($userIds as $userId) {
            $careerId = $userCareerMap[$userId] ?? null;
            if ($careerId && in_array($materiaId, $careerMateriaMap[$careerId] ?? [], true)) {
                $candidatos[] = $userId;
            }
        }

        return empty($candidatos) ? null : $candidatos[array_rand($candidatos)];
    }

    private function pickProfesorForMateriaConcentrado(array $profesores, array $profesorMateriaMap, ?int $materiaId, string $strategy): ?int
    {
        if (!$materiaId) {
            return null;
        }

        $candidatos = [];
        foreach ($profesores as $profesorId) {
            if (isset($profesorMateriaMap[$profesorId][$materiaId])) {
                $candidatos[] = $profesorId;
            }
        }

        if (empty($candidatos)) {
            return null;
        }

        sort($candidatos);

        if ($strategy === 'subir') {
            if (count($candidatos) >= 1 && rand(1, 100) <= 75) {
                return $candidatos[0];
            }
            if (count($candidatos) >= 2 && rand(1, 100) <= 55) {
                return $candidatos[1];
            }
        }

        if ($strategy === 'mantener') {
            if (count($candidatos) >= 1 && rand(1, 100) <= 45) {
                return $candidatos[0];
            }
        }

        return $candidatos[array_rand($candidatos)];
    }

    private function turnoDateForState(Carbon $startDate, string $estadoTurno): Carbon
    {
        if ($estadoTurno === 'pendiente_pago') {
            return (clone $startDate)->copy()->addDays(rand(75, 95));
        }

        if ($estadoTurno === 'vencido') {
            return (clone $startDate)->copy()->addDays(rand(5, 45));
        }

        return (clone $startDate)->copy()->addDays(rand(0, 72));
    }

    private function paymentData(string $estadoPago, Carbon $updatedAt): array
    {
        if ($estadoPago === 'aprobado') {
            return [
                (string) rand(100000000000, 999999999999),
                'approved',
                'accredited',
                rand(0, 100) < 70 ? 'credit_card' : 'debit_card',
                rand(0, 100) < 50 ? 'visa' : 'master',
                (clone $updatedAt)->copy()->addMinutes(rand(5, 60)),
                null,
            ];
        }

        if ($estadoPago === 'rechazado') {
            return [
                (string) rand(100000000000, 999999999999),
                'rejected',
                'cc_rejected_insufficient_amount',
                'credit_card',
                'visa',
                null,
                null,
            ];
        }

        if ($estadoPago === 'cancelado') {
            return [
                null,
                'cancelled',
                'cancelled',
                null,
                null,
                null,
                null,
            ];
        }

        return [
            null,
            'pending',
            'pending_waiting_payment',
            null,
            null,
            null,
            (clone $updatedAt)->copy()->addHours(rand(6, 30)),
        ];
    }

    private function upsertAndGetId(string $table, array $unique, array $data, string $pk)
    {
        $existing = DB::table($table)->where($unique)->first();

        if ($existing) {
            $updateData = $data;
            unset($updateData['created_at']);

            DB::table($table)->where($unique)->update($updateData);

            return $existing->{$pk};
        }

        return DB::table($table)->insertGetId($data, $pk);
    }
}
