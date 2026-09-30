<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Role;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('phone')->label(__('Téléphone'))->searchable(),
                TextColumn::make('name')->label(__('Nom'))->searchable()->placeholder('—'),
                TextColumn::make('roles.name')
                    ->label(__('Rôles'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Role::tryFrom($state)?->getLabel() ?? $state),
                IconColumn::make('phone_verified_at')->label(__('Tél. vérifié'))->boolean(),
                IconColumn::make('banned_at')->label(__('Suspendu'))->boolean()->trueColor('danger'),
                TextColumn::make('created_at')->label(__('Inscrit le'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')->label(__('Rôle'))->relationship('roles', 'name'),
                TernaryFilter::make('banned_at')->label(__('Suspendu'))->nullable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('ban')
                    ->label(__('Suspendre'))
                    ->color('danger')
                    ->icon('heroicon-o-no-symbol')
                    ->visible(fn (User $record) => ! $record->isBanned() && $record->isNot(auth()->user()))
                    ->schema([
                        TextInput::make('reason')->label(__('Motif'))->required()->maxLength(255),
                    ])
                    ->requiresConfirmation()
                    ->action(function (User $record, array $data): void {
                        $record->forceFill(['banned_at' => now(), 'ban_reason' => $data['reason']])->save();
                        activity()->performedOn($record)->causedBy(auth()->user())
                            ->withProperties(['reason' => $data['reason']])->log('user.banned');
                    }),
                Action::make('unban')
                    ->label(__('Réactiver'))
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (User $record) => $record->isBanned())
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->forceFill(['banned_at' => null, 'ban_reason' => null])->save();
                        activity()->performedOn($record)->causedBy(auth()->user())->log('user.unbanned');
                    }),
            ]);
    }
}
