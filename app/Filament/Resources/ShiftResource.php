<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftResource\Pages;
use App\Models\Position;
use App\Models\Shift;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Control de Asistencia';
    
    // Ocultar del menú de navegación
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Turno';
    protected static ?string $pluralModelLabel = 'Turnos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informacion del Turno')
                    ->description('Datos basicos sobre el turno')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Turno')
                            ->required()
                            ->minLength(3)
                            ->maxLength(255)
                            ->regex('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/')
                            ->unique(Shift::class, 'name', ignoreRecord: true)
                            ->validationMessages([
                                'regex' => 'El nombre del turno solo puede contener letras y espacios',
                                'minLength' => 'El nombre del turno debe tener al menos 3 caracteres',
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->nullable()
                            ->maxLength(1000),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Horarios')
                    ->description('Horarios de entrada y salida del turno')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Hora de Ingreso')
                            ->required()
                            ->seconds(false)
                            ->rules([
                                function (Forms\Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $endTime = $get('end_time');
                                        if ($value && $endTime) {
                                            $exists = Shift::where('start_time', $value)
                                                ->where('end_time', $endTime)
                                                ->where(function ($query) use ($get) {
                                                    $recordId = $get('id');
                                                    if ($recordId) {
                                                        $query->where('id', '!=', $recordId);
                                                    }
                                                })
                                                ->exists();

                                            if ($exists) {
                                                $fail('El ingreso ya está registrado para otro turno.');
                                            }
                                        }
                                    };
                                }
                            ]),
                        Forms\Components\TimePicker::make('end_time')
                            ->label('Hora de Salida')
                            ->required()
                            ->seconds(false)
                            ->afterOrEqual('start_time')
                            ->validationMessages([
                                'afterOrEqual' => 'La salida debe ser después del horario de ingreso',
                            ]),
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
                    ->label('Nombre del Turno')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Hora de Ingreso')
                    ->time('h:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Hora de Salida')
                    ->time('h:i A')
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
                        ->label('Editar turno')
                        ->color('warning')
                        ->action(function (Position $position): void {
                            Notification::make()
                                ->title('El turno se ha actualizado')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make()
                    ->label('Eliminar turno'),
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
            'index' => Pages\ManageShifts::route('/'),
        ];
    }
}
