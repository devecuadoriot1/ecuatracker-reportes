<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Reportes Ecuatrack')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Estilos básicos inline (luego puedes mover a un CSS propio) --}}
    <style>
        :root {
            --color-primary: #1f2937;
            --color-secondary: #4b5563;
            --color-accent: #2563eb;
            --color-success: #16a34a;
            --color-danger: #dc2626;
            --color-bg: #f3f4f6;
            --color-card: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: var(--color-bg);
            color: var(--color-primary);
        }

        .app-header {
            background: #111827;
            color: #f9fafb;
            padding: 0.75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .app-header .brand {
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .app-header nav a {
            color: #e5e7eb;
            text-decoration: none;
            margin-left: 1rem;
            font-size: 0.9rem;
        }

        .app-header nav a:hover {
            color: #ffffff;
            text-decoration: underline;
        }

        .app-container {
            max-width: 1100px;
            margin: 1.5rem auto;
            padding: 0 1rem;
        }

        .card {
            background: var(--color-card);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.05);
        }

        .card-header {
            margin-bottom: 1.25rem;
        }

        .card-header h1,
        .card-header h2 {
            margin: 0;
            font-size: 1.3rem;
        }

        .card-header p {
            margin: 0.35rem 0 0;
            color: var(--color-secondary);
            font-size: 0.9rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem 1.5rem;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .form-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--color-secondary);
        }

        .form-control,
        .form-select {
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            padding: 0.55rem 0.7rem;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--color-accent);
            box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.3);
        }

        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: var(--color-danger);
        }

        .form-hint {
            font-size: 0.8rem;
            color: #6b7280;
        }

        .error-text {
            font-size: 0.8rem;
            color: var(--color-danger);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.1rem 0.45rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #e5e7eb;
            color: #374151;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            border-radius: 0.5rem;
            border: none;
            padding: 0.55rem 1.2rem;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background-color 0.15s ease, transform 0.1s ease;
        }

        .btn-primary {
            background: var(--color-accent);
            color: #f9fafb;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #111827;
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .btn[disabled] {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-icon {
            font-size: 1rem;
        }

        .actions {
            margin-top: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .alert {
            border-radius: 0.75rem;
            padding: 0.6rem 0.85rem;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .alert-danger {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #ecfdf3;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .mb-2 {
            margin-bottom: 0.5rem;
        }

        .mb-3 {
            margin-bottom: 0.75rem;
        }

        .mb-4 {
            margin-bottom: 1rem;
        }

        .text-sm {
            font-size: 0.85rem;
        }
    </style>

    @stack('head')
</head>

<body>
    <header class="app-header">
        <div class="brand">
            Ecuatrack • <span class="text-sm">Reportes</span>
        </div>
        <nav>
            <a href="{{ route('reportes.analisis_recorrido.create') }}">Análisis de recorrido</a>
            {{-- Aquí luego puedes añadir más enlaces a otros reportes --}}
        </nav>
    </header>

    <main class="app-container">
        @yield('content')
    </main>

    <script>
        // Pequeño helper para bloquear el botón al enviar el formulario
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form[data-loading-button]');
            forms.forEach(function(form) {
                const button = form.querySelector('button[type="submit"]');
                form.addEventListener('submit', function() {
                    if (!button) return;
                    button.disabled = true;
                    const originalText = button.dataset.originalText || button.innerHTML;
                    button.dataset.originalText = originalText;
                    button.innerHTML = '<span class="btn-icon">⏳</span> Generando...';
                });
            });
        });
    </script>

    @stack('scripts')
</body>

</html>