@extends('reportes.layouts.pdf_base')

@section('title', $titulo ?? 'Análisis de recorrido - Mensual')

@section('content')
<h1>{{ $titulo ?? 'ANÁLISIS DE RECORRIDO DE KILOMETRAJE' }}</h1>
<p>
    Período:
    {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}<br>
    Modo: MENSUAL (SOLO TOTALES)
</p>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>CÓDIGO</th>
            <th>DISPOSITIVO</th>
            <th>MARCA</th>
            <th>CLASE</th>
            <th>MODELO</th>
            <th>TIPO</th>
            <th>AÑO</th>
            <th>PLACAS</th>
            <th>ÁREA</th>
            <th>RESPONSABLE</th>
            <th>GERENCIA</th>
            <th>MES</th>
            <th>KM TOTAL MES</th>
            <th>PARAM TOTAL MES</th>
            <th>KM PROMEDIO MES</th>
            <!-- OJO <th>PARAM PROMEDIO MES</th> -->
            <th>CONCLUSIÓN MES</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($resultado as $index => $item)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td class="text-center">{{ $item['codigo'] ?? '' }}</td>
            <td>{{ $item['nombre_api'] ?? '' }}</td>
            <td>{{ $item['marca'] ?? '' }}</td>
            <td>{{ $item['clase'] ?? '' }}</td>
            <td>{{ $item['modelo'] ?? '' }}</td>
            <td>{{ $item['tipo'] ?? '' }}</td>
            <td class="text-center">{{ $item['anio'] ?? '' }}</td>
            <td>{{ $item['placas'] ?? '' }}</td>
            <td>{{ $item['area_asignada'] ?? '' }}</td>
            <td>{{ $item['responsable'] ?? '' }}</td>
            <td>{{ $item['gerencia_asignada'] ?? '' }}</td>

            <td class="text-center">{{ $item['mes_label'] ?? '' }}</td>
            <td class="text-right">{{ $item['km_total_mes'] ?? '' }}</td>
            <td>{{ $item['param_km_total_mes'] ?? $item['param_mes_total'] ?? '' }}</td>
            <td class="text-right">{{ $item['km_promedio_mes'] ?? '' }}</td>
            <!--OJO <td>{{ $item['param_km_promedio_mes'] ?? $item['param_mes_promedio'] ?? '' }}</td> -->
            <td>{{ $item['conclusion_mes'] ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection