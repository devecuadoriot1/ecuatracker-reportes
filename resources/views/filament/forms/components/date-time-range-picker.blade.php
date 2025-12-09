@php
$statePath = $getStatePath();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field">
    <div
        x-data="dateTimeRangePicker({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            placeholder: @js($getPlaceholder() ?? 'Selecciona rango de fechas y horas'),
        })"
        x-init="init()"
        class="w-full">
        <div wire:ignore>
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    x-ref="input"
                    x-model="displayValue"
                    x-on:keydown.prevent
                    readonly
                    :placeholder="$getPlaceholder() ?? 'Selecciona rango de fechas y horas'"
                    class="w-full cursor-pointer"
                    aria-haspopup="true"
                    aria-expanded="false" />
            </x-filament::input.wrapper>
        </div>

        @if ($errors->has($statePath))
        <p class="mt-1 text-sm text-danger-600 dark:text-danger-500">
            {{ $errors->first($statePath) }}
        </p>
        @endif
    </div>
</x-dynamic-component>

@once
<style>
    /* ===========================
           Flatpickr – integración con Filament
           =========================== */

    /* Popup base */
    .flatpickr-calendar {
        border-radius: 0.75rem;
        box-shadow: 0 10px 40px rgba(15, 23, 42, 0.45);
        font-size: 0.875rem;
        border: 1px solid rgb(229 231 235);
        /* gray-200 */
    }

    /* Modo claro */
    html:not(.dark) .flatpickr-calendar {
        background-color: #ffffff;
        color: rgb(15 23 42);
        /* slate-900 */
    }

    /* Días seleccionados / rango - amber para combinar con Color::Amber */
    html:not(.dark) .flatpickr-day.selected,
    html:not(.dark) .flatpickr-day.startRange,
    html:not(.dark) .flatpickr-day.endRange,
    html:not(.dark) .flatpickr-day.inRange,
    html:not(.dark) .flatpickr-day.selected:hover,
    html:not(.dark) .flatpickr-day.startRange:hover,
    html:not(.dark) .flatpickr-day.endRange:hover,
    html:not(.dark) .flatpickr-day.inRange:hover {
        background-color: #f59e0b;
        /* amber-500 */
        border-color: #f59e0b;
        color: #ffffff;
    }

    /* Modo oscuro: fondo y textos */
    html.dark .flatpickr-calendar {
        background-color: #000000;
        /* slate-900 */
        color: rgb(226 232 240);
        /* slate-200 */
        border-color: rgb(51 65 85);
        /* slate-700 */
    }

    html.dark .flatpickr-day {
        color: rgb(226 232 240);
    }

    html.dark .flatpickr-weekday,
    html.dark .flatpickr-current-month,
    html.dark .flatpickr-months {
        color: rgb(148 163 184);
        /* slate-400 */
    }

    html.dark .flatpickr-prev-month svg,
    html.dark .flatpickr-next-month svg {
        fill: rgb(148 163 184);
    }

    /* Días seleccionados / rango en modo oscuro */
    html.dark .flatpickr-day.selected,
    html.dark .flatpickr-day.startRange,
    html.dark .flatpickr-day.endRange,
    html.dark .flatpickr-day.inRange,
    html.dark .flatpickr-day.selected:hover,
    html.dark .flatpickr-day.startRange:hover,
    html.dark .flatpickr-day.endRange:hover,
    html.dark .flatpickr-day.inRange:hover {
        background-color: #f59e0b;
        /* amber-500 */
        border-color: #f59e0b;
        color: rgb(15 23 42);
        /* texto oscuro sobre amber */
    }

    /* ===========================
           Footer de horas personalizado
           =========================== */

    .flatpickr-calendar .custom-time-range {
        border-top: 1px solid rgba(148, 163, 184, 0.4);
        /* slate-400/40 */
    }

    .flatpickr-calendar .custom-time-range span {
        font-size: 0.7rem;
        color: rgb(148 163 184);
        /* slate-400 */
    }

    html.dark .flatpickr-calendar .custom-time-range span {
        color: rgb(148 163 184);
    }

    .flatpickr-calendar .custom-time-range input[type="time"] {
        background-color: rgb(248 250 252);
        /* gray-50 */
        color: rgb(15 23 42);
        /* slate-900 */
        border-radius: 0.375rem;
        border: 1px solid rgb(209 213 219);
        /* gray-300 */
        padding: 0.1rem 0.25rem;
        font-size: 0.75rem;
        outline: none;
    }

    html.dark .flatpickr-calendar .custom-time-range input[type="time"] {
        background-color: rgb(15 23 42);
        /* slate-900 */
        color: rgb(248 250 252);
        /* gray-50 */
        border-color: rgb(75 85 99);
        /* gray-600 */
    }

    .flatpickr-calendar .custom-time-range input[type="time"]:focus {
        border-color: #f59e0b;
        /* amber-500 */
        box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.5);
    }
</style>
<script>
    function dateTimeRangePicker(config) {
        return {
            // Entangle directo con Livewire
            state: config.state ?? {
                from: null,
                to: null
            },

            placeholder: config.placeholder ?? '',
            displayValue: '',
            fp: null,

            // horas por defecto
            fromTime: '00:00',
            toTime: '23:59',

            async init() {
                this.normalizeState();
                this.initTimesFromState();

                try {
                    await this.ensureFlatpickr();
                } catch (error) {
                    console.error(error);
                    return;
                }

                if (typeof flatpickr === 'undefined') {
                    console.error('flatpickr no está disponible.');
                    return;
                }

                const defaultDates = this.getDefaultDatesForCalendar();
                const self = this;

                this.fp = flatpickr(this.$refs.input, {
                    locale: flatpickr.l10ns.es,
                    mode: 'range',
                    enableTime: false,
                    dateFormat: 'Y-m-d',
                    allowInput: false,
                    defaultDate: defaultDates,
                    onReady(selectedDates, dateStr, instance) {
                        self.injectTimeInputs(instance);
                        self.syncDisplay();
                    },
                    onChange(selectedDates, dateStr, instance) {
                        self.handleDateChange(selectedDates, instance);
                    },
                });

                this.syncDisplay();

                // Si el servidor cambia el estado (reset de formulario, etc.),
                // nos re-sincronizamos.
                this.$watch('state', () => {
                    self.normalizeState();
                    self.initTimesFromState();
                    if (self.fp) {
                        self.fp.setDate(self.getDefaultDatesForCalendar(), false);
                    }
                    self.syncDisplay();
                });
            },

            initTimesFromState() {
                this.normalizeState();

                if (this.state.from) {
                    const parts = String(this.state.from).split(' ');
                    if (parts[1]) {
                        this.fromTime = parts[1].slice(0, 5);
                    }
                }

                if (this.state.to) {
                    const parts = String(this.state.to).split(' ');
                    if (parts[1]) {
                        this.toTime = parts[1].slice(0, 5);
                    }
                }
            },

            injectTimeInputs(instance) {
                const container = instance.calendarContainer;
                if (!container || container.querySelector('.custom-time-range')) {
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.className =
                    'custom-time-range flex items-center justify-between gap-2 px-2 pb-2 mt-1 text-xs';

                // Hora desde
                const fromWrapper = document.createElement('div');
                fromWrapper.className = 'flex items-center gap-1';

                const fromLabel = document.createElement('span');
                fromLabel.textContent = 'Hora desde:';

                const fromInput = document.createElement('input');
                fromInput.type = 'time';
                fromInput.value = this.fromTime;
                fromInput.className = 'border rounded px-1 py-0.5 text-xs';

                fromWrapper.appendChild(fromLabel);
                fromWrapper.appendChild(fromInput);

                // Hora hasta
                const toWrapper = document.createElement('div');
                toWrapper.className = 'flex items-center gap-1';

                const toLabel = document.createElement('span');
                toLabel.textContent = 'Hora hasta:';

                const toInput = document.createElement('input');
                toInput.type = 'time';
                toInput.value = this.toTime;
                toInput.className = 'border rounded px-1 py-0.5 text-xs';

                toWrapper.appendChild(toLabel);
                toWrapper.appendChild(toInput);

                wrapper.appendChild(fromWrapper);
                wrapper.appendChild(toWrapper);

                container.appendChild(wrapper);

                const self = this;

                fromInput.addEventListener('input', (e) => {
                    self.fromTime = e.target.value || '00:00';
                    self.updateStateFromParts();
                });

                toInput.addEventListener('input', (e) => {
                    self.toTime = e.target.value || '23:59';
                    self.updateStateFromParts();
                });
            },

            handleDateChange(selectedDates, instance) {
                let fromDate = null;
                let toDate = null;

                if (selectedDates.length >= 1) {
                    fromDate = instance.formatDate(selectedDates[0], 'Y-m-d');
                }

                if (selectedDates.length >= 2) {
                    toDate = instance.formatDate(selectedDates[1], 'Y-m-d');
                }

                this.updateStateDates(fromDate, toDate);
            },

            updateStateDates(fromDate, toDate) {
                this.normalizeState();

                const from = fromDate ? `${fromDate} ${this.fromTime || '00:00'}` : null;
                const to = toDate ? `${toDate} ${this.toTime || '23:59'}` : null;

                // Importante: no reasignar this.state para no romper el entangle
                this.state.from = from;
                this.state.to = to;

                this.syncDisplay();
            },

            updateStateFromParts() {
                this.normalizeState();

                let fromDate = null;
                let toDate = null;

                if (this.state.from) {
                    fromDate = String(this.state.from).split(' ')[0];
                }

                if (this.state.to) {
                    toDate = String(this.state.to).split(' ')[0];
                }

                this.updateStateDates(fromDate, toDate);
            },

            async ensureFlatpickr() {
                if (window.flatpickr) {
                    return;
                }

                await Promise.all([
                    this.loadStyleOnce(
                        'flatpickr-style',
                        'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css',
                    ),
                    this.loadScriptOnce(
                        'flatpickr-script',
                        'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
                    ),
                    this.loadScriptOnce(
                        'flatpickr-l10n-es',
                        'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js',
                    ),
                ]);
            },

            loadScriptOnce(id, src) {
                return new Promise((resolve, reject) => {
                    if (document.getElementById(id)) return resolve();

                    const script = document.createElement('script');
                    script.id = id;
                    script.src = src;
                    script.async = true;
                    script.onload = () => resolve();
                    script.onerror = () => reject(new Error('No se pudo cargar ' + src));
                    document.head.appendChild(script);
                });
            },

            loadStyleOnce(id, href) {
                return new Promise((resolve, reject) => {
                    if (document.getElementById(id)) return resolve();

                    const link = document.createElement('link');
                    link.id = id;
                    link.rel = 'stylesheet';
                    link.href = href;
                    link.onload = () => resolve();
                    link.onerror = () => reject(new Error('No se pudo cargar ' + href));
                    document.head.appendChild(link);
                });
            },

            normalizeState() {
                if (!this.state || typeof this.state !== 'object') {
                    this.state = {
                        from: null,
                        to: null
                    };
                }

                if (!('from' in this.state)) {
                    this.state.from = null;
                }
                if (!('to' in this.state)) {
                    this.state.to = null;
                }
            },

            syncDisplay() {
                this.normalizeState();

                const from = this.state.from;
                const to = this.state.to;

                if (!from && !to) {
                    this.displayValue = '';
                    return;
                }

                // Formato un poco más amigable para el usuario
                const formatLabel = (value) => {
                    const [date, time] = String(value).split(' ');
                    return `${date} ${time ?? ''}`.trim();
                };

                if (from && to) {
                    this.displayValue = `${formatLabel(from)} → ${formatLabel(to)}`;
                } else if (from) {
                    this.displayValue = `${formatLabel(from)} →`;
                } else {
                    this.displayValue = '';
                }
            },

            getDefaultDatesForCalendar() {
                const dates = [];

                if (this.state.from) {
                    dates.push(String(this.state.from).split(' ')[0]);
                }

                if (this.state.to) {
                    dates.push(String(this.state.to).split(' ')[0]);
                }

                return dates;
            },
        };
    }
</script>
@endonce