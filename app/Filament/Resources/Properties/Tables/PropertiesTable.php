<?php

namespace App\Filament\Resources\Properties\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PropertiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_address')
                    ->label('Address')
                    ->searchable(['address', 'city', 'state', 'zip'])
                    ->sortable(['address'])
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user'),

                TextColumn::make('city')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('state')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('service_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'seasonal' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                IconColumn::make('geocoded')
                    ->label('Geocoded')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->latitude && $record->longitude && ! $record->geocoding_failed)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('lot_size')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('N/A'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('service_status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'seasonal' => 'Seasonal',
                    ]),
                SelectFilter::make('geocoded')
                    ->label('Geocoding Status')
                    ->options([
                        'yes' => 'Geocoded',
                        'no' => 'Not Geocoded',
                        'failed' => 'Failed',
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when($data['value'] === 'yes', fn ($q) => $q->whereNotNull('latitude')->whereNotNull('longitude')->where('geocoding_failed', false))
                            ->when($data['value'] === 'no', fn ($q) => $q->where(function ($sq) {
                                $sq->whereNull('latitude')->orWhereNull('longitude');
                            })->where('geocoding_failed', false))
                            ->when($data['value'] === 'failed', fn ($q) => $q->where('geocoding_failed', true));
                    }),
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
