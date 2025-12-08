@php
/** @var array<int,array{title:string,items:array<int,array{id:int,label:string}>> $groups */
    $groups = $getGroupsForView();
    @endphp

    <x-dynamic-component
        :component="$getFieldWrapperView()"
        :field="$field">
        <div
            x-data="vehiculosSelector({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            groups: @js($groups),
            groupsPerPage: {{ $getGroupsPerPage() }},
        })"
            x-init="init()"
            x-cloak
            class="space-y-3">
            {{-- Contenedor tipo “dropdown”: borde y fondo --}}
            <div class="border rounded-2xl bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                {{-- Búsqueda --}}
                <div class="p-3 border-b border-gray-200 dark:border-gray-800">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            placeholder="Buscar vehículo..."
                            x-model.trim.debounce.300ms="search"
                            class="w-full" />
                    </x-filament::input.wrapper>
                </div>

                {{-- Grupos + vehículos --}}
                <div class="max-h-80 overflow-y-auto p-2 space-y-2">
                    <template x-if="paginatedFilteredGroups().length === 0">
                        <div class="p-3 text-sm text-gray-500 dark:text-gray-400">
                            No se encontraron vehículos.
                        </div>
                    </template>

                    <template x-for="group in paginatedFilteredGroups()" :key="group.title">
                        <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-gray-50 dark:bg-gray-950/40">
                            {{-- Cabecera del grupo --}}
                            <div class="flex items-center justify-between px-3 py-2">
                                <div class="flex items-center gap-2">
                                    {{-- Flecha desplegable --}}
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-full border border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 h-6 w-6"
                                        @click="toggleGroupPanel(group.title)">
                                        <svg x-show="isGroupOpen(group.title)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.25 8.27a.75.75 0 01-.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                        <svg x-show="!isGroupOpen(group.title)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01-.02-1.06L10.94 10 7.19 6.29A.75.75 0 018.25 5.23l4.25 4.24a.75.75 0 010 1.06l-4.25 4.24a.75.75 0 01-1.06 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>

                                    {{-- Checkbox de grupo --}}
                                    <label class="flex items-center gap-2 cursor-pointer select-none">
                                        <input
                                            type="checkbox"
                                            class="fi-fo-checkbox-input rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900"
                                            :checked="isGroupSelected(group.title)"
                                            @click.stop
                                            @change="toggleGroupSelection(group.title)">
                                        <span class="font-medium text-sm text-gray-900 dark:text-gray-100" x-text="group.title"></span>
                                    </label>
                                </div>
                            </div>

                            {{-- Vehículos del grupo (desplegable) --}}
                            <div
                                x-show="isGroupOpen(group.title)"
                                x-transition
                                class="px-3 pb-3 pt-1">
                                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-1">
                                    <template x-for="item in group.items" :key="item.id">
                                        <div class="col-span-1">
                                            <label class="flex items-center gap-2 text-sm cursor-pointer select-none">
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

                {{-- Paginación (solo sin búsqueda) --}}
                <div
                    class="flex items-center justify-center px-3 py-2 border-t border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400"
                    x-show="search.trim() === '' && totalPages() > 1">
                    <div class="inline-flex items-center gap-1">
                        <button
                            type="button"
                            class="px-2 py-1 border rounded-full disabled:opacity-40"
                            @click="prevPage"
                            :disabled="page <= 1">
                            ‹
                        </button>

                        <template x-for="p in pages()" :key="p">
                            <button
                                type="button"
                                class="px-2 py-1 border rounded-full"
                                :class="p === page ? 'bg-primary-600 text-white border-primary-600' : 'bg-white dark:bg-gray-900'"
                                @click="goToPage(p)"
                                x-text="p"></button>
                        </template>

                        <button
                            type="button"
                            class="px-2 py-1 border rounded-full disabled:opacity-40"
                            @click="nextPage"
                            :disabled="page >= totalPages()">
                            ›
                        </button>
                    </div>
                </div>
            </div>

            {{-- Barra inferior: seleccionar todo --}}
            <div class="flex items-center justify-between pt-1">
                <button
                    type="button"
                    class="fi-btn fi-btn-size-sm fi-btn-color-primary fi-btn-outline inline-flex items-center gap-1"
                    @click="toggleAll">
                    <x-filament::icon
                        icon="heroicon-m-check-circle"
                        class="fi-btn-icon h-4 w-4" />
                    <span class="fi-btn-label text-sm">
                        Seleccionar todo
                    </span>
                </button>
            </div>
        </div>
    </x-dynamic-component>

    @once
    <script>
        function vehiculosSelector(config) {
            return {
                state: config.state ?? [],
                groups: config.groups ?? [],
                groupsPerPage: config.groupsPerPage ?? 2,
                search: '',
                page: 1,
                openGroups: {},

                init() {
                    // Abrimos todos los grupos por defecto
                    this.groups.forEach(group => {
                        this.openGroups[group.title] = true;
                    });

                    // Aseguramos que el estado inicial esté normalizado
                    this.state = this.normalizedState();
                },

                normalizedState() {
                    let raw = this.state ?? [];

                    if (!Array.isArray(raw)) {
                        raw = [raw];
                    }

                    // Convertimos todo a enteros y filtramos NaN
                    const normalized = raw
                        .map(value => Number(value))
                        .filter(value => !Number.isNaN(value));

                    return normalized;
                },

                isItemSelected(id) {
                    return this.normalizedState().includes(id);
                },

                // --- Helpers de grupos / items ---

                allIds() {
                    return this.groups.flatMap(group => group.items.map(item => item.id));
                },

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

                // --- Selección ---
                toggleItem(id) {
                    id = Number(id);
                    const current = this.normalizedState();

                    if (current.includes(id)) {
                        this.state = current.filter(v => v !== id);
                    } else {
                        this.state = [...current, id];
                    }
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
                },

                toggleAll() {
                    const ids = this.allIds();
                    const current = this.normalizedState();
                    const allSelected = ids.length > 0 && ids.every(id => current.includes(id));

                    this.state = allSelected ? [] : Array.from(new Set([...current, ...ids]));
                },

                // --- Búsqueda + paginación ---

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

                    // Con búsqueda activa, se desactiva la paginación
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
            };
        }
    </script>
    @endonce