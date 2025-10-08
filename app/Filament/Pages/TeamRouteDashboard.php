<?php

namespace App\Filament\Pages;

use App\Models\ServiceAppointment;
use App\Models\Team;
use BackedEnum;
use Filament\Actions\Action as TableAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class TeamRouteDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Team Routes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.team-route-dashboard';

    public function mount(): void
    {
        // No initialization needed - table handles its own data
    }

    protected function getHeaderActions(): array
    {
        return [
            // Header actions can be added here if needed in the future
        ];
    }

    public function markComplete(int $appointmentId): void
    {
        $appointment = ServiceAppointment::find($appointmentId);

        if ($appointment) {
            $appointment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by' => auth()->id(),
            ]);

            $this->loadAppointments();

            Notification::make()
                ->success()
                ->title('Appointment Completed')
                ->body("Marked appointment for {$appointment->property->customer->name} as completed.")
                ->send();
        }
    }

    public function viewDetails(int $appointmentId): void
    {
        // TODO: Open appointment details modal or navigate to appointment page
        Notification::make()
            ->info()
            ->title('View Details')
            ->body('Appointment details view coming soon.')
            ->send();
    }

    public function reassign(int $appointmentId): void
    {
        // TODO: Open reassignment modal
        Notification::make()
            ->info()
            ->title('Reassign Appointment')
            ->body('Appointment reassignment coming soon.')
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('scheduled_time')
                    ->label('Time')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '-';
                        }

                        return $state->format('g:i A');
                    })
                    ->description(fn ($record) => $record->scheduled_time?->format('M j'))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('property.customer.name')
                    ->label('Customer')
                    ->description(fn ($record) => $record->property->address)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('serviceType.name')
                    ->label('Service')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} min" : '-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state)))
                    ->color(fn ($state) => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'primary',
                        default => 'gray'
                    })
                    ->icon(fn ($state) => match ($state) {
                        'completed' => 'heroicon-o-check',
                        'in_progress' => 'heroicon-o-clock',
                        default => null
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('team_id')
                    ->label('Team')
                    ->relationship('team', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('appointment_date')
                    ->form([
                        DatePicker::make('selected_date')
                            ->label('Appointment Date')
                            ->default('2025-10-09'),
                    ])
                    ->query(function ($query, array $data) {
                        if (isset($data['selected_date']) && $data['selected_date']) {
                            $query->whereDate('scheduled_date', $data['selected_date']);
                        }
                    }),
                SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'skipped' => 'Skipped',
                    ])
                    ->multiple(),
                SelectFilter::make('service_type_id')
                    ->label('Service Type')
                    ->relationship('serviceType', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Filter::make('geocoded')
                    ->query(fn ($query) => $query->whereHas('property', fn ($q) => $q->whereNotNull('latitude')
                        ->whereNotNull('longitude')
                        ->where('geocoding_failed', false)
                    ))
                    ->label('Geocoded Only')
                    ->toggle(),
                Filter::make('not_geocoded')
                    ->query(fn ($query) => $query->whereHas('property', fn ($q) => $q->where(function ($query) {
                        $query->whereNull('latitude')
                            ->orWhereNull('longitude')
                            ->orWhere('geocoding_failed', true);
                    })
                    ))
                    ->label('Not Geocoded')
                    ->toggle(),
            ])
            ->actions([
                ActionGroup::make([
                    TableAction::make('markComplete')
                        ->label('Mark Complete')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn ($record) => $record->status !== 'completed')
                        ->action(function ($record) {
                            $record->update([
                                'status' => 'completed',
                                'completed_at' => now(),
                                'completed_by' => auth()->id(),
                            ]);

                            $this->loadAppointments();

                            Notification::make()
                                ->success()
                                ->title('Appointment Completed')
                                ->body("Marked appointment for {$record->property->customer->name} as completed.")
                                ->send();
                        }),

                    TableAction::make('viewDetails')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->action(function ($record) {
                            Notification::make()
                                ->info()
                                ->title('View Details')
                                ->body('Appointment details view coming soon.')
                                ->send();
                        }),

                    TableAction::make('reassign')
                        ->label('Reassign')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->action(function ($record) {
                            Notification::make()
                                ->info()
                                ->title('Reassign Appointment')
                                ->body('Appointment reassignment coming soon.')
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    TableAction::make('bulkMarkComplete')
                        ->label('Mark Complete')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Mark Appointments as Complete')
                        ->modalDescription('Are you sure you want to mark the selected appointments as completed?')
                        ->accessSelectedRecords()
                        ->action(function (Collection $records) {
                            $records->each(fn ($record) => $record->update([
                                'status' => 'completed',
                                'completed_at' => now(),
                                'completed_by' => auth()->id(),
                            ]));

                            $this->loadAppointments();

                            Notification::make()
                                ->success()
                                ->title('Appointments Completed')
                                ->body("Marked {$records->count()} appointment(s) as completed.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    TableAction::make('bulkReassign')
                        ->label('Reassign to Team')
                        ->icon('heroicon-o-user-group')
                        ->form([
                            Select::make('team_id')
                                ->label('Team')
                                ->options(Team::active()->pluck('name', 'id'))
                                ->required()
                                ->searchable(),
                        ])
                        ->accessSelectedRecords()
                        ->action(function (Collection $records, array $data) {
                            $records->each->update(['team_id' => $data['team_id']]);

                            $this->loadAppointments();

                            Notification::make()
                                ->success()
                                ->title('Team Assigned')
                                ->body("Reassigned {$records->count()} appointment(s) to team.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->selectable()
            ->selectCurrentPageOnly()
            ->defaultSort('route_order')
            ->paginated(false);
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return ServiceAppointment::query()
            ->with(['property.customer', 'serviceType', 'team'])
            ->whereHas('property')
            ->whereDate('scheduled_date', '2025-10-09'); // Default to a date with data
    }
}
