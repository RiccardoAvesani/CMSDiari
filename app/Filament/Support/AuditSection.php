<?php

namespace App\Filament\Support;

use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;

class AuditSection
{
    /**
     * @param  array<string, string>  $statusLabels value => label (legacy, opzionale)
     * @param  array<string, string>  $statusColors value => color (legacy, opzionale)
     */
    public static function make(
        string $statusLabel = 'Stato',
        array $statusLabels = ['active' => 'Attivo', 'deleted' => 'Eliminato'],
        array $statusColors = [],
    ): Section {
        return Section::make('Audit')
            ->columnSpanFull()
            ->columns(2)
            ->visible(fn(?Model $record): bool => (bool) ($record?->exists))
            ->schema([
                TextEntry::make('id')
                    ->label('ID')
                    ->placeholder('-'),

                TextEntry::make('status')
                    ->label($statusLabel)
                    ->badge()
                    ->formatStateUsing(function (?string $state, ?Model $record) use ($statusLabels): string {
                        $state = self::normalizeStatus($state);

                        if ($state === null) {
                            return '-';
                        }

                        if ($record) {
                            $modelClass = $record::class;

                            if (method_exists($modelClass, 'statusLabel')) {
                                return $modelClass::statusLabel($state);
                            }
                        }

                        return $statusLabels[$state] ?? 'Sconosciuto';
                    })
                    ->color(function (?string $state, ?Model $record) use ($statusColors): string {
                        $state = self::normalizeStatus($state);

                        if ($state === null) {
                            return 'gray';
                        }

                        if ($record) {
                            $modelClass = $record::class;

                            if (method_exists($modelClass, 'statusColor')) {
                                return $modelClass::statusColor($state);
                            }
                        }

                        if ($statusColors !== []) {
                            return $statusColors[$state] ?? 'gray';
                        }

                        return match ($state) {
                            'active' => 'success',
                            'blocked' => 'warning',
                            'deleted' => 'danger',
                            default => 'gray',
                        };
                    }),

                TextEntry::make('created_by')
                    ->label('Creato da')
                    ->state(fn(?Model $record): string => $record ? self::formatUser($record->createdBy ?? null) : '-')
                    ->placeholder('-'),

                TextEntry::make('created_at')
                    ->label('Creato il')
                    ->state(fn(?Model $record) => $record?->getAttribute('created_at') ?? $record?->getAttribute('createdat'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),

                TextEntry::make('updated_by')
                    ->label('Modificato da')
                    ->state(fn(?Model $record): string => $record ? self::formatUser($record->updatedBy ?? null) : '-')
                    ->placeholder('-'),

                TextEntry::make('updated_at')
                    ->label('Modificato il')
                    ->state(fn(?Model $record) => $record?->getAttribute('updated_at') ?? $record?->getAttribute('updatedat'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
            ]);
    }

    private static function normalizeStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $status = trim($status);

        return $status !== '' ? $status : null;
    }

    private static function formatUser(?User $user): string
    {
        if (! $user) {
            return '-';
        }

        $fullName = trim((string) ($user->fullname ?? ''));

        if ($fullName !== '') {
            return $fullName;
        }

        $fullName = trim((string) ($user->firstname ?? '') . ' ' . (string) ($user->lastname ?? ''));

        if ($fullName !== '') {
            return $fullName;
        }

        return (string) ($user->email ?? '-');
    }
}
