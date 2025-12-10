<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Empresa';
    protected static ?string $modelLabel = 'Trabajador';
    protected static ?string $pluralModelLabel = 'Trabajadores';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informacion Personal')
                    ->description('Datos basicos de identificacion del trabajador')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\TextInput::make('dni')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->length(8)
                            ->regex('/^[0-9]+$/')
                            ->validationMessages([
                                'regex' => 'El DNI solo puede contener números',
                                'length' => 'El DNI debe tener exactamente 8 dígitos',
                            ])
                            ->helperText('Ingrese un DNI de 8 dígitos'),
                        Forms\Components\TextInput::make('paternal_surname')
                            ->label('Apellido Paterno')
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->regex('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/')
                            ->validationMessages([
                                'regex' => 'El apellido paterno solo puede contener letras',
                            ]),
                        Forms\Components\TextInput::make('maternal_surname')
                            ->label('Apellido Materno')
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->regex('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/')
                            ->validationMessages([
                                'regex' => 'El apellido materno solo puede contener letras',
                            ]),
                        Forms\Components\TextInput::make('names')
                            ->label('Nombres')
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->regex('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/')
                            ->validationMessages([
                                'regex' => 'El nombre solo puede contener letras',
                            ]),
                        Forms\Components\DatePicker::make('date_of_birth')
                            ->required()
                            ->label('Fecha de Nacimiento')
                            ->maxDate(now()->subYears(18))
                            ->displayFormat('Y-m-d'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Informacion Laboral')
                    ->description('Información relacionada con el puesto de trabajo')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Forms\Components\Select::make('position_id')
                            ->relationship('position', 'name')
                            ->placeholder('Seleccione una posicion')
                            ->required()
                            ->label('Cargo'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Informacion de Contacto')
                    ->description('Datos de contacto del trabajador')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->placeholder('ejemplo@dominio.com')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->nullable()
                            ->regex('/^[a-zA-Z0-9._%+-]+@(gmail|hotmail|outlook|yahoo)\.(com|es)$/')
                            ->validationMessages([
                                'regex' => 'El correo electrónico no es válido',
                            ]),
                        Forms\Components\TextInput::make('phone')
                            ->required()
                            ->placeholder('999999999')
                            ->length(9)
                            ->regex('/^9[0-9]{8}$/')
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'regex' => 'El teléfono debe comenzar con 9 y tener 9 dígitos',
                                'length' => 'El teléfono debe tener exactamente 9 dígitos',
                            ])
                            ->helperText('Ingrese un número de teléfono de 9 dígitos'),
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
                Tables\Columns\TextColumn::make('dni')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paternal_surname')
                    ->label('Apellido Paterno')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('maternal_surname')
                    ->label('Apellido Materno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('names')
                    ->label('Nombres')
                    ->searchable(),
                Tables\Columns\TextColumn::make('position.name')
                    ->label('Cargo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->placeholder('')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->label('Fecha de Nacimiento')
                    ->date('Y-m-d')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefono'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y-m-d H:i:s')
                    ->timezone('America/Lima')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('Y-m-d H:i:s')
                    ->timezone('America/Lima')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
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
                            $indicators['created_from'] = 'Created From ' . $data['created_from'];
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Created Until ' . $data['created_until'];
                        }

                        return $indicators;
                    }),
                Tables\Filters\SelectFilter::make('position_id')
                    ->label('Position')
                    ->relationship('position', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Editar trabajador')
                        ->color('warning')
                        ->action(function (Employee $record): void {
                            Notification::make()
                                ->title('Se ha actualizado el trabajador')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->label('Eliminar trabajador'),
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
            'index' => Pages\ManageEmployees::route('/'),
        ];
    }
}
