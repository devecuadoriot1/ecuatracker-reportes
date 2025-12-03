<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $titulo ?? 'Análisis de recorrido - Mensual' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        h1,
        h2,
        h3 {
            margin: 0 0 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
        }

        th {
            background: #f0f0f0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
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
                <th>PARAM PROMEDIO MES</th>
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
                <td>{{ $item['param_km_total_mes'] ?? '' }}</td>
                <td class="text-right">{{ $item['km_promedio_mes'] ?? '' }}</td>
                <td>{{ $item['param_km_promedio_mes'] ?? '' }}</td>
                <td>{{ $item['conclusion_mes'] ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>