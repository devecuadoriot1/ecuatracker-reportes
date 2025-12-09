@php
/**
* @var array<int,array{
    * title: string,
    * items: array<int,array{id:int,label:string}>
    * }> $groups
    */
    $groups = $getGroupsForView();
    $statePath = $getStatePath();
    @endphp

    <x-dynamic-component
        :component="$getFieldWrapperView()"
        :field="$field">
        <div
            x-data="vehiculosSelectorPopup({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            groups: {{ json_encode($groups, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }},
            groupsPerPage: {{ $getGroupsPerPage() }},
        })"
            x-init="init()"
            x-cloak
            class="relative">
            {{-- Caja principal (simula un select) --}}
            <x-filament::input.wrapper>
                <button
                    type="button"
                    class="fi-fo-select-input flex w-full items-center justify-between gap-2 rounded-lg border bg-white px-3 py-2 text-sm text-gray-700 shadow-sm transition hover:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                    @click="togglePopup">
                    <span class="truncate" x-text="summaryLabel"></span>

                    <x-filament::icon
                        icon="heroicon-m-chevron-up-down"
                        class="h-4 w-4 text-gray-400" />
                </button>
            </x-filament::input.wrapper>

            {{-- Popup de selección --}}
            <div
                x-show="open"
                x-transition
                @click.outside="closePopup"
                class="absolute z-50 mt-1 w-full">
                <div
                    class="rounded-2xl border bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900 flex flex-col"
                    style="max-height: 700px;">
                    {{-- Barra de búsqueda --}}
                    <div class="border-b border-gray-200 px-3 py-2 dark:border-gray-800">
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="text"
                                placeholder="Buscar vehículo..."
                                x-model.trim.debounce.300ms="search"
                                class="w-full" />
                        </x-filament::input.wrapper>
                    </div>

                    {{-- Contenido scrollable (alto fijo) --}}
                    <div class="flex-1 space-y-2 overflow-y-auto p-2">
                        <template x-if="paginatedFilteredGroups().length === 0">
                            <div class="p-3 text-sm text-gray-500 dark:text-gray-400">
                                No se encontraron vehículos.
                            </div>
                        </template>

                        <template x-for="group in paginatedFilteredGroups()" :key="group.title">
                            <div class="rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-950/40">
                                {{-- Cabecera del grupo --}}
                                <div class="flex items-center justify-between px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        {{-- Flecha expandir/colapsar --}}
                                        <button
                                            type="button"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-transparent hover:bg-gray-100 dark:hover:bg-gray-800"
                                            @click="toggleGroupPanel(group.title)">
                                            <svg
                                                x-show="isGroupOpen(group.title)"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                                class="h-4 w-4">
                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.25 8.27a.75.75 0 01-.02-1.06z" clip-rule="evenodd" />
                                            </svg>
                                            <svg
                                                x-show="!isGroupOpen(group.title)"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                                class="h-4 w-4">
                                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01-.02-1.06L10.94 10 7.19 6.29A.75.75 0 018.25 5.23l4.25 4.24a.75.75 0 010 1.06l-4.25 4.24a.75.75 0 01-1.06 0z" clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        {{-- Checkbox del grupo --}}
                                        <label class="flex cursor-pointer select-none items-center gap-2">
                                            <input
                                                type="checkbox"
                                                class="fi-fo-checkbox-input rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900"
                                                :checked="isGroupSelected(group.title)"
                                                @click.stop
                                                @change="toggleGroupSelection(group.title)">
                                            <span
                                                class="text-sm font-medium text-gray-900 dark:text-gray-100"
                                                x-text="group.title"></span>
                                        </label>
                                    </div>
                                </div>

                                {{-- Vehículos del grupo (grid 2 columnas) --}}
                                <div
                                    x-show="isGroupOpen(group.title)"
                                    x-transition
                                    class="px-3 pb-3 pt-1">
                                    <div class="grid grid-cols-1 gap-1 sm:grid-cols-2">
                                        <template x-for="item in group.items" :key="item.id">
                                            <div class="col-span-1">
                                                <label class="flex cursor-pointer select-none items-center gap-2 text-sm">
                                                    <input
                                                        type="checkbox"
                                                        class="fi-fo-checkbox-input rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900"
                                                        :value="item.id"
                                                        :checked="isItemSelected(item.id)"
                                                        @change="toggleItem(item.id)">
                                                    <span class="text-gray-700 dark:text-gray-200" x-text="item.label"></span>
                                                </label>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Paginación (solo cuando NO hay búsqueda) --}}
                    <div
                        class="flex items-center justify-between border-t border-gray-200 px-3 py-2 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400"
                        x-show="search.trim() === '' && totalPages() > 1">
                        <div class="inline-flex items-center gap-1">
                            <button
                                type="button"
                                class="rounded-full border px-2 py-1 disabled:opacity-40"
                                @click="prevPage"
                                :disabled="page <= 1">
                                ‹
                            </button>

                            <template x-for="p in pages()" :key="p">
                                <button
                                    type="button"
                                    class="rounded-full border px-2 py-1"
                                    :class="p === page ? 'bg-primary-600 text-white border-primary-600' : 'bg-white dark:bg-gray-900'"
                                    @click="goToPage(p)"
                                    x-text="p"></button>
                            </template>

                            <button
                                type="button"
                                class="rounded-full border px-2 py-1 disabled:opacity-40"
                                @click="nextPage"
                                :disabled="page >= totalPages()">
                                ›
                            </button>
                        </div>

                        <div class="text-right">
                            <span x-text="`${normalizedState().length} seleccionados`"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Errores de validación --}}
            @if ($errors->has($statePath))
            <p class="mt-1 text-sm text-danger-600 dark:text-danger-500">
                {{ $errors->first($statePath) }}
            </p>
            @endif
        </div>
    </x-dynamic-component>

    @once
    <script>
        function vehiculosSelectorPopup(config) {
            return {
                state: config.state ?? [],
                groups: config.groups ?? [],
                groupsPerPage: config.groupsPerPage ?? 2,

                open: false,
                search: '',
                page: 1,
                openGroups: {},
                summaryLabel: 'Seleccionar vehículos',
                itemIndex: {},

                init() {
                    // Índice id -> label para el resumen y grupos abiertos por defecto
                    this.groups.forEach(group => {
                        this.openGroups[group.title] = true;

                        group.items.forEach(item => {
                            this.itemIndex[item.id] = item.label;
                        });
                    });

                    this.updateSummary();

                    // Si Livewire cambia state desde PHP (reset, fill, etc.), actualizamos resumen
                    this.$watch('state', () => this.updateSummary());
                },

                /* ---------- Popup ---------- */

                togglePopup() {
                    this.open = !this.open;
                },

                closePopup() {
                    this.open = false;
                },

                /* ---------- Estado normalizado ---------- */

                normalizedState() {
                    if (Array.isArray(this.state)) {
                        return this.state;
                    }

                    if (this.state === null || this.state === undefined || this.state === '') {
                        return [];
                    }

                    return [this.state];
                },

                /* ---------- Helpers: grupos / items ---------- */

                groupIds(title) {
                    const group = this.groups.find(g => g.title === title);
                    return group ? group.items.map(item => item.id) : [];
                },

                isItemSelected(id) {
                    return this.normalizedState().includes(id);
                },

                isGroupSelected(title) {
                    const ids = this.groupIds(title);
                    const current = this.normalizedState();

                    return ids.length > 0 && ids.every(id => current.includes(id));
                },

                isGroupOpen(title) {
                    return this.openGroups[title] ?? false;
                },

                toggleGroupPanel(title) {
                    this.openGroups[title] = !this.isGroupOpen(title);
                },

                /* ---------- Selección ---------- */

                toggleItem(id) {
                    const current = this.normalizedState();

                    if (current.includes(id)) {
                        this.state = current.filter(v => v !== id);
                    } else {
                        this.state = [...current, id];
                    }

                    this.updateSummary();
                },

                toggleGroupSelection(title) {
                    const ids = this.groupIds(title);
                    if (!ids.length) return;

                    const current = this.normalizedState();
                    const allSelected = ids.every(id => current.includes(id));

                    if (allSelected) {
                        this.state = current.filter(id => !ids.includes(id));
                    } else {
                        const set = new Set(current);
                        ids.forEach(id => set.add(id));
                        this.state = Array.from(set);
                    }

                    this.updateSummary();
                },

                /* ---------- Búsqueda + paginación ---------- */

                filteredGroups() {
                    const term = this.search.trim().toLowerCase();
                    if (!term) {
                        return this.groups;
                    }

                    return this.groups
                        .map(group => {
                            const items = group.items.filter(item =>
                                String(item.label).toLowerCase().includes(term)
                            );

                            return {
                                ...group,
                                items
                            };
                        })
                        .filter(group => group.items.length > 0);
                },

                paginatedFilteredGroups() {
                    const groups = this.filteredGroups();

                    // Con búsqueda activa no paginamos
                    if (this.search.trim() !== '') {
                        return groups;
                    }

                    const start = (this.page - 1) * this.groupsPerPage;
                    return groups.slice(start, start + this.groupsPerPage);
                },

                totalPages() {
                    if (this.search.trim() !== '') {
                        return 1;
                    }

                    const count = this.filteredGroups().length;
                    return count > 0 ? Math.ceil(count / this.groupsPerPage) : 1;
                },

                pages() {
                    const total = this.totalPages();
                    return Array.from({
                        length: total
                    }, (_, i) => i + 1);
                },

                goToPage(p) {
                    const total = this.totalPages();
                    if (p < 1 || p > total) return;
                    this.page = p;
                },

                prevPage() {
                    this.goToPage(this.page - 1);
                },

                nextPage() {
                    this.goToPage(this.page + 1);
                },

                /* ---------- Resumen en la caja principal ---------- */

                updateSummary() {
                    const ids = this.normalizedState();
                    const count = ids.length;

                    if (count === 0) {
                        this.summaryLabel = 'Seleccionar vehículos';
                        return;
                    }

                    if (count <= 2) {
                        this.summaryLabel = ids
                            .map(id => this.itemIndex[id] ?? `ID ${id}`)
                            .join(' · ');
                        return;
                    }

                    const firstTwo = ids
                        .slice(0, 2)
                        .map(id => this.itemIndex[id] ?? `ID ${id}`)
                        .join(' · ');

                    this.summaryLabel = `${firstTwo} + ${count - 2} más`;
                },
            };
        }
    </script>
    @endonce