<h2>Te falta abonar tu clase</h2>

<p>
Clase: {{ $turno->fecha_formateada }} ({{ $turno->horario }})<br>
Profesor: {{ $turno->profesor?->name }}<br>
Materia: {{ $turno->materia?->materia_nombre ?? '-' }}<br>
Monto: ${{ $turno->precio_total }}
</p>

<p>
<a href="{{ $urlPago }}" style="display:inline-block;padding:10px 14px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;">
Pagar ahora
</a>
</p>
