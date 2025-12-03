@extends('reportes.layouts.pdf_base')

@section('title', $titulo ?? 'Análisis de recorrido - Semanal')

@section('content')
<h1>{{ $titulo ?? 'ANÁLISIS DE RECORRIDO DE KILOMETRAJE' }}</h1>
<p>
    Período:
    {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}<br>
    Modo: SEMANAL + RESUMEN MENSUAL
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
            <th>SEM 1 (Km)</th>
            <th>PARAM SEM 1</th>
            <th>SEM 2 (Km)</th>
            <th>PARAM SEM 2</th>
            <th>SEM 3 (Km)</th>
            <th>PARAM SEM 3</th>
            <th>SEM 4 (Km)</th>
            <th>PARAM SEM 4</th>
            <th>TOTAL MES (Km)</th>
            <th>PARAM MES</th>
            <th>PROMEDIO MES</th>
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

            {{-- Ajusta estos índices a la estructura real que devuelva tu servicio/export --}}
            <td class="text-right">{{ $item['semanas'][0]['km'] ?? '' }}</td>
            <td>{{ $item['semanas'][0]['param'] ?? '' }}</td>
            <td class="text-right">{{ $item['semanas'][1]['km'] ?? '' }}</td>
            <td>{{ $item['semanas'][1]['param'] ?? '' }}</td>
            <td class="text-right">{{ $item['semanas'][2]['km'] ?? '' }}</td>
            <td>{{ $item['semanas'][2]['param'] ?? '' }}</td>
            <td class="text-right">{{ $item['semanas'][3]['km'] ?? '' }}</td>
            <td>{{ $item['semanas'][3]['param'] ?? '' }}</td>

            <td class="text-right">{{ $item['total_mes_km'] ?? '' }}</td>
            <td>{{ $item['param_total_mes'] ?? '' }}</td>
            <td class="text-right">{{ $item['promedio_mes_km'] ?? '' }}</td>
            <td>{{ $item['conclusion_mes'] ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection