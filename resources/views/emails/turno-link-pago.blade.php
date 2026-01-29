@component('mail::message')
# Hola {{ $turno->alumno?->name ?? '!' }}

Ya confirmaste tu asistencia. Para asegurar la clase, realizá el pago:

**Fecha:** {{ $turno->fecha_formateada }}  
**Horario:** {{ $turno->horario }}  
**Profesor:** {{ $turno->profesor?->name ?? '-' }}  
**Materia:** {{ $turno->materia?->materia_nombre ?? '-' }}  
**Tema:** {{ $turno->tema?->tema_nombre ?? '-' }}  
**Monto:** ${{ number_format((float) $turno->precio_total, 2, ',', '.') }}

@component('mail::button', ['url' => $pago->mp_init_point])
Pagar con Mercado Pago
@endcomponent

Si ya pagaste, podés ignorar este mail.

Gracias,  
{{ config('app.name') }}
@endcomponent
