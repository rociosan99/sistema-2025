@component('mail::message')
# Clase cancelada 

Hola {{ $turno->profesor?->name ?? 'Profesor/a' }},

Te avisamos que el alumno **{{ $turno->alumno?->name ?? '—' }}** canceló una clase.

**Detalle de la clase cancelada:**
- **Fecha:** {{ $fecha }}
- **Horario:** {{ $horaInicio }} - {{ $horaFin }}
- **Materia:** {{ $turno->materia?->materia_nombre ?? '—' }}
- **Tema:** {{ $turno->tema?->tema_nombre ?? '—' }}

Para intentar cubrir ese horario con otro alumno, ingresá al panel:

@component('mail::button', ['url' => url('/profesor/ofertas-solicitudes')])
Ver ofertas de solicitudes
@endcomponent

Gracias
@endcomponent
