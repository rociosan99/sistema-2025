<?php

namespace Tests\Support;

use App\Models\Disponibilidad;
use App\Models\Credito;
use App\Models\Materia;
use App\Models\Pago;
use App\Models\PoliticaCancelacion;
use App\Models\OfertaSolicitud;
use App\Models\SolicitudDisponibilidad;
use App\Models\ProfesorProfile;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Support\Facades\DB;

trait CreatesTurnoScenarios
{
    protected function crearAlumno(array $attributes = []): User
    {
        return User::factory()->alumno()->create($attributes);
    }

    protected function crearProfesor(array $attributes = [], ?string $enlace = 'https://meet.google.com/test-clase'): User
    {
        $profesor = User::factory()->profesor()->create($attributes);

        ProfesorProfile::query()->create([
            'user_id' => $profesor->id,
            'enlace_clase_default' => $enlace,
        ]);

        return $profesor;
    }

    protected function crearMateria(array $attributes = []): Materia
    {
        return Materia::query()->create(array_merge([
            'materia_nombre' => 'Matemática Discreta',
            'materia_descripcion' => 'Materia para pruebas automatizadas.',
            'materia_anio' => 2026,
        ], $attributes));
    }

    protected function asignarMateriaProfesor(User $profesor, Materia $materia, string $precioPorHora = '100.00'): void
    {
        DB::table('profesor_materia')->insert([
            'profesor_id' => $profesor->id,
            'materia_id' => $materia->materia_id,
            'precio_por_hora' => $precioPorHora,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function crearDisponibilidad(User $profesor, array $attributes = []): Disponibilidad
    {
        return Disponibilidad::query()->create(array_merge([
            'profesor_id' => $profesor->id,
            'dia_semana' => 4,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '18:00:00',
        ], $attributes));
    }

    protected function crearTurno(User $alumno, User $profesor, Materia $materia, array $attributes = []): Turno
    {
        return Turno::query()->create(array_merge([
            'alumno_id' => $alumno->id,
            'profesor_id' => $profesor->id,
            'materia_id' => $materia->materia_id,
            'tema_id' => null,
            'fecha' => '2026-08-27',
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'estado' => Turno::ESTADO_PENDIENTE,
            'precio_por_hora' => '100.00',
            'precio_total' => '100.00',
        ], $attributes));
    }

    protected function crearPago(Turno $turno, array $attributes = []): Pago
    {
        return Pago::query()->create(array_merge([
            'turno_id' => $turno->id,
            'monto' => $turno->precio_total,
            'monto_mercadopago' => $turno->precio_total,
            'moneda' => 'ARS',
            'estado' => Pago::ESTADO_APROBADO,
            'provider' => 'mercadopago',
        ], $attributes));
    }

    protected function crearPoliticaCancelacion(array $attributes = []): PoliticaCancelacion
    {
        return PoliticaCancelacion::query()->create(array_merge([
            'codigo' => PoliticaCancelacion::CODIGO_CANCELACION_ALUMNO,
            'horas_cancelacion_sin_penalizacion' => 24,
            'porcentaje_credito_anticipado' => '100.00',
            'porcentaje_credito_tardio' => '75.00',
            'porcentaje_penalizacion_tardia' => '25.00',
            'vigencia_creditos_dias' => 90,
            'porcentaje_profesor_penalizacion' => '80.00',
            'porcentaje_plataforma_penalizacion' => '20.00',
        ], $attributes));
    }

    protected function crearCreditoDisponible(User $alumno, Turno $turnoOrigen, array $attributes = []): Credito
    {
        return Credito::query()->create(array_merge([
            'alumno_id' => $alumno->id,
            'turno_id' => $turnoOrigen->id,
            'pago_id' => null,
            'importe_pagado' => '100.00',
            'importe_credito' => '100.00',
            'importe_penalizacion' => '0.00',
            'importe_penalizacion_profesor' => '0.00',
            'importe_penalizacion_plataforma' => '0.00',
            'saldo_disponible' => '100.00',
            'porcentaje_credito_aplicado' => '100.00',
            'porcentaje_penalizacion_aplicado' => '0.00',
            'porcentaje_profesor_penalizacion_aplicado' => '80.00',
            'porcentaje_plataforma_penalizacion_aplicado' => '20.00',
            'horas_limite_aplicadas' => 24,
            'vigencia_dias_aplicada' => 90,
            'estado' => Credito::ESTADO_DISPONIBLE,
            'idempotency_key' => "credito-testing:{$turnoOrigen->id}",
            'cancelado_at' => now()->subDay(),
            'vence_at' => now()->addDays(90),
        ], $attributes));
    }

    protected function crearSolicitudDisponibilidad(User $alumno, Materia $materia, array $attributes = []): SolicitudDisponibilidad
    {
        return SolicitudDisponibilidad::query()->create(array_merge([
            'alumno_id' => $alumno->id,
            'materia_id' => $materia->materia_id,
            'tema_id' => null,
            'fecha' => '2026-08-27',
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'estado' => SolicitudDisponibilidad::ESTADO_ACTIVA,
            'expires_at' => now()->addDay(),
        ], $attributes));
    }

    protected function crearOfertaSolicitud(SolicitudDisponibilidad $solicitud, User $profesor, array $attributes = []): OfertaSolicitud
    {
        return OfertaSolicitud::query()->create(array_merge([
            'solicitud_id' => $solicitud->id,
            'profesor_id' => $profesor->id,
            'hora_inicio' => (string) $solicitud->hora_inicio,
            'hora_fin' => (string) $solicitud->hora_fin,
            'estado' => OfertaSolicitud::ESTADO_PENDIENTE,
            'expires_at' => now()->addHours(2),
        ], $attributes));
    }

    protected function slotPara(Turno|array $source): array
    {
        $data = $source instanceof Turno ? [
            'profesor_id' => $source->profesor_id,
            'fecha' => $source->fecha->format('Y-m-d'),
            'hora_inicio' => $source->hora_inicio,
            'hora_fin' => $source->hora_fin,
            'precio_por_hora' => $source->precio_por_hora,
            'precio_total' => $source->precio_total,
        ] : $source;

        $data['slot_key'] = hash('sha256', implode('|', [
            $data['profesor_id'],
            $data['fecha'],
            $data['hora_inicio'],
            $data['hora_fin'],
        ]));

        return $data;
    }
}
