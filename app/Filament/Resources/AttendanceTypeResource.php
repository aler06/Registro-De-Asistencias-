<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceTypeResource\Pages;
use App\Models\AttendanceType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceTypeResource extends Resource
{
    protected static ?string $model = AttendanceType::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Control de Asistencia';
    
    // Ocultar del menú de navegación
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Tipo de Marcación';
    protected static ?string $pluralModelLabel = 'Tipos de Marcación';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Marcación')
                    ->description('Datos básicos sobre la marcación')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Marcación')
                            ->required()
                            ->minLength(3)
                            ->maxLength(255)
                            ->regex('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/')
                            ->unique(AttendanceType::class, 'name', ignoreRecord: true)
                            ->validationMessages([
                                'regex' => 'El nombre de la marcación solo puede contener letras y espacios',
                                'minLength' => 'El nombre de la marcación debe tener al menos 3 caracteres',
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull()
                            ->nullable()
                            ->maxLength(1000),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre de la Marcación')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i:s')
                    ->timezone('America/Lima')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i:s')
                    ->timezone('America/Lima')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Created from ' . $data['created_from'];
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Created until ' . $data['created_until'];
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Editar tipo de marcación')
                        ->color('warning')
                        ->action(function (AttendanceType $record): void {
                            Notification::make()
                                ->title('El tipo de marcación ha sido actualizado')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->label('Eliminar tipo de marcación'),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAttendanceTypes::route('/'),
        ];
    }
}
