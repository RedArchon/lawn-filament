<?php

namespace App\Filament\Widgets;

use App\Models\ServiceAppointment;
use Carbon\Carbon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class DailyAppointmentsTable extends TableWidget
{
    public ?string $selectedDate = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Scheduled Appointments';

    #[On('dateChanged')]
    public function updateDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ServiceAppointment::query()
                    ->when($this->selectedDate, fn (Builder $query) => $query->forDate(Carbon::parse($this->selectedDate)))
                    ->with(['property.customer', 'serviceType'])
                    ->orderBy('scheduled_time')
                    ->orderBy('id')
            )
            ->columns([
                IconColumn::make('geocoding_status')
                    ->label('')
                    ->icon(fn (ServiceAppointment $record): string => $record->property->latitude && $record->property->longitude && ! $record->property->geocoding_failed
                        ? 'heroicon-o-check-circle'
                        : 'heroicon-o-exclamation-triangle')
                    ->color(fn (ServiceAppointment $record): string => $record->property->latitude && $record->property->longitude && ! $record->property->geocoding_failed
                        ? 'success'
                        : 'warning')
                    ->tooltip(fn (ServiceAppointment $record): string => $record->property->latitude && $record->property->longitude && ! $record->property->geocoding_failed
                        ? 'Geocoded'
                        : 'Needs Geocoding'),

                TextColumn::make('property.full_address')
                    ->label('Address')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('property.customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('serviceType.name')
                    ->label('Service')
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'skipped' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                TextColumn::make('scheduled_time')
                    ->label('Time')
                    ->dateTime('g:i A')
                    ->sortable(),

                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->alignEnd(),
            ])
            ->defaultSort('scheduled_time')
            ->striped()
            ->paginated(false)
            ->emptyStateHeading('No appointments scheduled')
            ->emptyStateDescription('There are no service appointments for this date.')
            ->emptyStateIcon('heroicon-o-calendar');
    }
}
