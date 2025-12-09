<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;

/**
 * Campo de rango fecha/hora.
 *
 * Estado: ['from' => 'YYYY-MM-DD HH:MM', 'to' => 'YYYY-MM-DD HH:MM'].
 */
class DateTimeRangePicker extends Field
{
    use HasPlaceholder;

    protected string $view = 'filament.forms.components.date-time-range-picker';

    protected function setUp(): void
    {
        parent::setUp();

        // Estado inicial predecible
        $this->default(static fn(): array => [
            'from' => null,
            'to'   => null,
        ]);

        $this->placeholder('Selecciona rango de fechas y horas');
    }
}
