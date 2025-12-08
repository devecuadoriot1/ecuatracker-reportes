<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use App\Models\Vehiculo;
use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Selector avanzado de vehículos:
 * - Grupos por group_title (incluye "Sin grupo")
 * - Selección individual
 * - Seleccionar todo global
 * - Seleccionar por grupo
 * - Paginación por grupos
 *
 * El estado es siempre un array de IDs de vehiculos.
 */
class VehiculosSelector extends Field
{
    protected string $view = 'filament.forms.components.vehiculos-selector';

    /**
     * Número de grupos por página (paginación básica).
     */
    protected int | Closure | null $groupsPerPage = 2;

    /**
     * Permite modificar la query base para multi-tenant, filtros, etc.
     *
     * @var (Closure(Builder): Builder)|null
     */
    protected ?Closure $queryCallback = null;

    /**
     * Si es true, solo muestra vehículos que tienen un dispositivo asignado.
     */
    protected bool | Closure $onlyWithDevice = true;

    public function groupsPerPage(int | Closure | null $groupsPerPage): static
    {
        $this->groupsPerPage = $groupsPerPage;

        return $this;
    }

    public function getGroupsPerPage(): int
    {
        $value = $this->evaluate($this->groupsPerPage);

        return is_int($value) && $value > 0 ? $value : 2;
    }

    /**
     * Configura una callback para modificar la query base de Vehiculo.
     *
     * @param  (Closure(Builder): Builder)|null  $callback
     */
    public function query(?Closure $callback): static
    {
        $this->queryCallback = $callback;

        return $this;
    }

    /**
     * Indica si se deben mostrar solo vehículos con dispositivo.
     */
    public function onlyWithDevice(bool | Closure $value = true): static
    {
        $this->onlyWithDevice = $value;

        return $this;
    }

    protected function shouldShowOnlyWithDevice(): bool
    {
        return (bool) $this->evaluate($this->onlyWithDevice);
    }

    /**
     * Obtiene los vehículos que se mostrarán en el selector.
     *
     * @return Collection<int,Vehiculo>
     */
    public function getVehiculos(): Collection
    {
        $query = Vehiculo::query()
            ->select(['id', 'group_title', 'nombre_api'])
            ->ordered();

        if ($this->shouldShowOnlyWithDevice()) {
            $query->whereNotNull('device_id');
        }

        if ($this->queryCallback instanceof Closure) {
            $query = ($this->queryCallback)($query) ?? $query;
        }

        return $query->get();
    }

    /**
     * Estructura normalizada para el componente Alpine.
     *
     * @return array<int,array{title:string,items:array<int,array{id:int,label:string}>>>
     */
    public function getGroupsForView(): array
    {
        return $this->getVehiculos()
            ->groupBy(fn(Vehiculo $v) => $v->group_title ?: 'Sin grupo')
            ->sortKeys()
            ->map(function (Collection $vehiculos, string $groupTitle): array {
                return [
                    'title' => $groupTitle,
                    'items' => $vehiculos
                        ->values()
                        ->map(fn(Vehiculo $v): array => [
                            'id'    => $v->id,
                            'label' => $v->select_label,
                        ])
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }
}
