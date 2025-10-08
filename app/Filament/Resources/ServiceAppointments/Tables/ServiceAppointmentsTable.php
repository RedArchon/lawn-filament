<?php

namespace App\Filament\Resources\ServiceAppointments\Tables;

use App\Models\Team;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ServiceAppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('scheduled_date')
                    ->date()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('team.name')
                    ->label('Team')
                    ->badge()
                    ->color(fn ($record) => $record->team?->color ?: 'gray')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Unassigned'),
                TextColumn::make('property.address')
                    ->label('Property')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('property.customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('serviceType.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('scheduled_time')
                    ->time()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('duration_minutes')
                    ->label('Duration (min)')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('team_id')
                    ->label('Team')
                    ->relationship('team', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Filter::make('unassigned')
                    ->query(fn (Builder $query): Builder => $query->whereNull('team_id'))
                    ->label('Unassigned Only')
                    ->toggle(),
                SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'skipped' => 'Skipped',
                    ])
                    ->multiple(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    Action::make('assignToTeam')
                        ->label('Assign to Team')
                        ->icon('heroicon-o-user-group')
                        ->form([
                            Select::make('team_id')
                                ->label('Team')
                                ->options(Team::active()->pluck('name', 'id'))
                                ->required()
                                ->searchable(),
                        ])
                        ->accessSelectedRecords()
                        ->action(function (Collection $records, array $data): void {
                            $records->each->update(['team_id' => $data['team_id']]);

                            Notification::make()
                                ->success()
                                ->title('Team Assigned')
                                ->body("Assigned {$records->count()} appointment(s) to team.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('scheduled_date', 'desc');
    }
}
