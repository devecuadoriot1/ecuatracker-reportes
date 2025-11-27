@extends('layouts.app')

@section('title', 'Análisis de recorrido de Km')

@section('content')
<div class="card">
    <div class="card-header">
        <h1>Análisis de recorrido de Km</h1>
        <p>
            Genera un reporte <strong>semanal</strong> o <strong>mensual</strong> en formato
            <span class="badge">Excel / PDF</span>
            a partir de los vehículos registrados.
        </p>
    </div>

    {{-- Alertas de error / éxito --}}
    @include('components.alerts')

    <form
        action="{{ route('reportes.analisis_recorrido.store') }}"
        method="POST"
        data-loading-button>
        @csrf

        <div class="form-grid">
            {{-- Título --}}
            <div class="form-group">
                <label for="titulo" class="form-label">Título del reporte</label>
                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    class="form-control @error('titulo') is-invalid @enderror"
                    value="{{ old('titulo', 'Análisis de recorrido') }}"
                    required>
                <p class="form-hint">
                    Ejemplo: "Análisis junio 2025", "Recorrido semanal obras públicas".
                </p>
                @error('titulo')
                <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            {{-- Modo: semanal / mensual --}}
            <div class="form-group">
                <label class="form-label" for="modo">Tipo de reporte</label>
                <select
                    id="modo"
                    name="modo"
                    class="form-select @error('modo') is-invalid @enderror"
                    required>
                    <option value="semanal" {{ old('modo') === 'mensual' ? '' : 'selected' }}>
                        Semanal + resumen mensual
                    </option>
                    <option value="mensual" {{ old('modo') === 'mensual' ? 'selected' : '' }}>
                        Mensual general
                    </option>
                </select>
                <p class="form-hint">
                    Semanal: divide el rango en semanas lógicas (máx. 4) y arma el resumen del mes.<br>
                    Mensual: calcula solo el total y promedio del mes.
                </p>
                @error('modo')
                <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            {{-- Fecha desde --}}
            <div class="form-group">
                <label for="fecha_desde" class="form-label">Fecha desde</label>
                <input
                    type="date"
                    id="fecha_desde"
                    name="fecha_desde"
                    class="form-control @error('fecha_desde') is-invalid @enderror"
                    value="{{ old('fecha_desde') }}"
                    required>
                @error('fecha_desde')
                <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            {{-- Fecha hasta --}}
            <div class="form-group">
                <label for="fecha_hasta" class="form-label">Fecha hasta</label>
                <input
                    type="date"
                    id="fecha_hasta"
                    name="fecha_hasta"
                    class="form-control @error('fecha_hasta') is-invalid @enderror"
                    value="{{ old('fecha_hasta') }}"
                    required>
                @error('fecha_hasta')
                <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            {{-- Hora desde --}}
            <div class="form-group">
                <label for="hora_desde" class="form-label">Hora desde (opcional)</label>
                <input
                    type="time"
                    id="hora_desde"
                    name="hora_desde"
                    class="form-control @error('hora_desde') is-invalid @enderror"
                    value="{{ old('hora_desde') }}">
                <p class="form-hint">
                    Si se deja vacío, se usa 00:00.
                </p>
                @error('hora_desde')
                <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            {{-- Hora hasta --}}
            <div class="form-group">
                <label for="hora_hasta" class="form-label">Hora hasta (opcional)</label>
                <input
                    type="time"
                    id="hora_hasta"
                    name="hora_hasta"
                    class="form-control @error('hora_hasta') is-invalid @enderror"
                    value="{{ old('hora_hasta') }}">
                <p class="form-hint">
                    Si se deja vacío, se usa 23:59.
                </p>
                @error('hora_hasta')
                <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            {{-- Formato: Excel / PDF --}}
            <div class="form-group">
                <label class="form-label" for="formato">Formato de salida</label>
                <select
                    id="formato"
                    name="formato"
                    class="form-select @error('formato') is-invalid @enderror"
                    required>
                    <option value="excel" {{ old('formato') === 'pdf' ? '' : 'selected' }}>
                        Excel (.xlsx)
                    </option>
                    <option value="pdf" {{ old('formato') === 'pdf' ? 'selected' : '' }}>
                        PDF
                    </option>
                </select>
                <p class="form-hint">
                    Excel para análisis detallado; PDF para enviar como informe.
                </p>
                @error('formato')
                <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            {{-- Selección de vehículos --}}
            <div class="form-group" style="grid-column: 1 / -1;">
                <label for="vehiculo_ids" class="form-label">
                    Vehículos a incluir en el reporte
                </label>

                <select
                    id="vehiculo_ids"
                    name="vehiculo_ids[]"
                    class="form-select @error('vehiculo_ids') is-invalid @enderror"
                    multiple
                    size="6"
                    required>
                    @foreach($vehiculos as $vehiculo)
                    <option
                        value="{{ $vehiculo->id }}"
                        @if(collect(old('vehiculo_ids', []))->contains($vehiculo->id)) selected @endif
                        >
                        {{-- Label: nombre_api (si existe) + placas + marca/modelo --}}
                        @php
                        $labelParts = [];
                        if ($vehiculo->nombre_api) {
                        $labelParts[] = $vehiculo->nombre_api;
                        }
                        if ($vehiculo->placas) {
                        $labelParts[] = 'Placa: ' . $vehiculo->placas;
                        }
                        if ($vehiculo->marca || $vehiculo->modelo) {
                        $labelParts[] = trim(($vehiculo->marca ?? '') . ' ' . ($vehiculo->modelo ?? ''));
                        }
                        if ($vehiculo->area_asignada) {
                        $labelParts[] = 'Área: ' . $vehiculo->area_asignada;
                        }
                        @endphp

                        {{ implode(' • ', $labelParts) }}
                    </option>
                    @endforeach
                </select>

                <p class="form-hint">
                    Mantén presionada la tecla <strong>Ctrl</strong> (Windows) o <strong>Cmd</strong> (Mac)
                    para seleccionar varios vehículos.
                </p>

                @error('vehiculo_ids')
                <div class="error-text">{{ $message }}</div>
                @enderror

                {{-- Errores de cada elemento del array --}}
                @if($errors->has('vehiculo_ids.*'))
                <div class="error-text">
                    Algunos vehículos seleccionados no son válidos.
                </div>
                @endif
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-primary">
                <span class="btn-icon">📊</span>
                Generar reporte
            </button>

            <button
                type="reset"
                class="btn btn-secondary">
                Limpiar
            </button>
        </div>
    </form>
</div>
@endsection