@extends('reportes.layouts.pdf_base')

@section('title', $titulo ?? 'Análisis de recorrido - Semanal')

@section('content')
<h1>{{ $titulo ?? 'ANÁLISIS DE RECORRIDO DE KILOMETRAJE' }}</h1>
<p>
    Período:
    {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}<br>
    Modo: SEMANAL + RESUMEN MENSUAL
</p>

<table class="compact">
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
            {{-- Semanas --}}
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
        @php
        $semanas = $item['semanas'] ?? [];
        @endphp

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

            {{-- SEMANA 1 --}}
            <td class="text-right">{{ $semanas[0]['km'] ?? '' }}</td>
            <td>{{ $semanas[0]['parametrizacion'] ?? '' }}</td>

            {{-- SEMANA 2 --}}
            <td class="text-right">{{ $semanas[1]['km'] ?? '' }}</td>
            <td>{{ $semanas[1]['parametrizacion'] ?? '' }}</td>

            {{-- SEMANA 3 --}}
            <td class="text-right">{{ $semanas[2]['km'] ?? '' }}</td>
            <td>{{ $semanas[2]['parametrizacion'] ?? '' }}</td>

            {{-- SEMANA 4 --}}
            <td class="text-right">{{ $semanas[3]['km'] ?? '' }}</td>
            <td>{{ $semanas[3]['parametrizacion'] ?? '' }}</td>

            <td class="text-right">{{ $item['km_total_mes'] ?? '' }}</td>
            <td>{{ $item['param_mes_total'] ?? '' }}</td>
            <td class="text-right">{{ $item['km_promedio_mes'] ?? '' }}</td>
            <td>{{ $item['conclusion_mes'] ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection