<?php

namespace App\Filament\Resources;

use App\Exports\AttendanceExport;
use App\Filament\Resources\AttendanceRecordResource\Pages;
use App\Filament\Resources\AttendanceRecordResource\RelationManagers;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class AttendanceRecordResource extends Resource
{
    protected static ?string $model = AttendanceRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Control de Asistencia';

    protected static ?string $modelLabel = 'Registro de Asistencia';
    protected static ?string $pluralModelLabel = 'Registros de Asistencia';

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttendanceMarksRelationManager::class,
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Registro de Asistencia')
                    ->description('Seleccione el empleado y el turno de trabajo')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Empleado (DNI)')
                            ->placeholder('Buscar por DNI')
                            ->relationship('employee', 'dni')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->dni} - {$record->names} {$record->paternal_surname}")
                            ->searchable(['dni', 'names', 'paternal_surname', 'maternal_surname'])
                            ->required()
                            ->unique(modifyRuleUsing: function (Unique $rule) {
                                return $rule->where('date', Carbon::today()->toDateString());
                            })
                            ->validationMessages([
                                'unique' => 'Este empleado ya tiene un registro de asistencia para el día de hoy.',
                            ])
                            ->columnSpanFull(),
                        
                        Forms\Components\Select::make('shift_id')
                            ->label('Turno')
                            ->placeholder('Seleccione el turno de trabajo')
                            ->options(function () {
                                return \App\Models\Shift::all()->mapWithKeys(function ($shift) {
                                    $startTime = \Carbon\Carbon::parse($shift->start_time)->format('h:i A');
                                    $endTime = \Carbon\Carbon::parse($shift->end_time)->format('h:i A');
                                    return [$shift->id => "{$shift->name} ({$startTime} - {$endTime})"];
                                });
                            })
                            ->required()
                            ->columnSpanFull(),
                        
                        Forms\Components\DatePicker::make('date')
                            ->label('Fecha')
                            ->required()
                            ->default(now())
                            ->timezone(config('app.timezone'))
                            ->format('Y-m-d')
                            ->columnSpanFull(),
                        
                        Forms\Components\Select::make('record_type')
                            ->label('Tipo de Registro')
                            ->options([
                                'asistencia' => 'Asistencia',
                                'falta' => 'Falta/Inasistencia',
                            ])
                            ->required()
                            ->default('asistencia')
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('show_hours', $state === 'asistencia'))
                            ->columnSpanFull(),
                        
                        // Campos de horas solo visibles si es asistencia
                        Forms\Components\Section::make('Horarios')
                            ->schema([
                                Forms\Components\TimePicker::make('manual_start_time')
                                    ->label('Hora de Entrada')
                                    ->required()
                                    ->seconds(false)
                                    ->displayFormat('H:i:s')
                                    ->columnSpanFull(),
                                
                                Forms\Components\TimePicker::make('manual_end_time')
                                    ->label('Hora de Salida')
                                    ->required()
                                    ->seconds(false)
                                    ->displayFormat('H:i:s')
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn (callable $get) => $get('record_type') === 'asistencia')
                            ->columns(1)
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('observations')
                            ->label('Observaciones')
                            ->placeholder('Agregar observaciones adicionales (opcional)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->label('ID'),
                Tables\Columns\TextColumn::make('employee.dni')
                    ->searchable()
                    ->label('DNI')
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.paternal_surname')
                    ->label('Apellido Paterno')
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.maternal_surname')
                    ->label('Apellido Materno')
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.names')
                    ->label('Nombres')
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendanceMarks.shift.name')
                    ->label('Turno')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Mañana' => 'warning',
                        'Tarde' => 'info',
                        'Noche' => 'gray',
                        default => 'primary',
                    })
                    ->formatStateUsing(function ($record) {
                        $shift = $record->attendanceMarks->first()?->shift;
                        if ($shift) {
                            return $shift->name;
                        }
                        return 'N/A';
                    }),
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Desde')
                            ->default(now()->startOfDay())
                            ->timezone(config('app.timezone'))
                            ->native(false),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('Hasta')
                            ->default(now()->endOfDay())
                            ->timezone(config('app.timezone'))
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
                Tables\Filters\SelectFilter::make('employee')
                    ->relationship('employee', 'dni')
                    ->searchable()
                    ->preload()
                    ->label('Empleado'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Editar Asistencia')
                        ->color('warning')
                        ->form([
                            Forms\Components\Section::make('Informacion del Registro')
                                ->description('Datos básicos actualizados')
                                ->icon('heroicon-o-information-circle')
                                ->schema([
                                    Forms\Components\Select::make('employee_id')
                                        ->label('DNI')
                                        ->relationship('employee', 'dni')
                                        ->disabled(),

                                    Forms\Components\DatePicker::make('date')
                                        ->label('Fecha')
                                        ->disabled(),

                                    Forms\Components\Select::make('shift_id')
                                        ->label('Turno')
                                        ->placeholder('Seleccione el turno de trabajo')
                                        ->options(function () {
                                            return \App\Models\Shift::all()->mapWithKeys(function ($shift) {
                                                $startTime = \Carbon\Carbon::parse($shift->start_time)->format('h:i A');
                                                $endTime = \Carbon\Carbon::parse($shift->end_time)->format('h:i A');
                                                return [$shift->id => "{$shift->name} ({$startTime} - {$endTime})"];
                                            });
                                        })
                                        ->required()
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('record_type')
                                        ->label('Tipo de Registro')
                                        ->options([
                                            'asistencia' => 'Asistencia',
                                            'falta' => 'Falta/Inasistencia',
                                        ])
                                        ->required()
                                        ->default('asistencia')
                                        ->reactive()
                                        ->afterStateUpdated(fn ($state, callable $set) => $set('show_hours', $state === 'asistencia'))
                                        ->columnSpanFull(),

                                    Forms\Components\Section::make('Horarios')
                                        ->schema([
                                            Forms\Components\TimePicker::make('manual_start_time')
                                                ->label('Hora de Entrada')
                                                ->required()
                                                ->seconds(false)
                                                ->displayFormat('H:i:s')
                                                ->columnSpanFull(),
                                            Forms\Components\TimePicker::make('manual_end_time')
                                                ->label('Hora de Salida')
                                                ->required()
                                                ->seconds(false)
                                                ->displayFormat('H:i:s')
                                                ->columnSpanFull(),
                                        ])
                                        ->visible(fn (callable $get) => $get('record_type') === 'asistencia')
                                        ->columns(1)
                                        ->columnSpanFull(),

                                    Forms\Components\Textarea::make('observations')
                                        ->label('Observaciones')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ])
                        ->mutateFormDataUsing(function (array $data, $record): array {
                            $this->editShiftId = $data['shift_id'] ?? null;
                            $this->editRecordType = $data['record_type'] ?? null;
                            $this->editManualStartTime = $data['manual_start_time'] ?? null;
                            $this->editManualEndTime = $data['manual_end_time'] ?? null;
                            unset($data['shift_id']);
                            unset($data['record_type']);
                            unset($data['manual_start_time']);
                            unset($data['manual_end_time']);
                            return $data;
                        })
                        ->after(function (Model $record) {
                            if ($this->editRecordType === 'asistencia' && $this->editShiftId && $this->editManualStartTime && $this->editManualEndTime) {
                                $shift = \App\Models\Shift::find($this->editShiftId);
                                
                                if ($shift) {
                                    $entradaType = \App\Models\AttendanceType::where('name', 'Entrada')->first();
                                    $salidaType = \App\Models\AttendanceType::where('name', 'Salida')->first();
                                    
                                    $startTime = \Carbon\Carbon::parse($this->editManualStartTime)->format('H:i:s');
                                    $endTime = \Carbon\Carbon::parse($this->editManualEndTime)->format('H:i:s');
                                    
                                    // Actualizar o crear marcación de entrada
                                    if ($entradaType) {
                                        $existingEntrada = $record->attendanceMarks()
                                            ->where('attendance_type_id', $entradaType->id)
                                            ->first();
                                        
                                        if ($existingEntrada) {
                                            $existingEntrada->update([
                                                'shift_id' => $shift->id,
                                                'marked_time' => $startTime,
                                            ]);
                                        
                                        } else {
                                            $record->attendanceMarks()->create([
                                                'shift_id' => $shift->id,
                                                'attendance_type_id' => $entradaType->id,
                                                'marked_time' => $startTime,
                                            ]);
                                        }
                                    }
                                    
                                    // Actualizar o crear marcación de salida
                                    if ($salidaType) {
                                        $existingSalida = $record->attendanceMarks()
                                            ->where('attendance_type_id', $salidaType->id)
                                            ->first();
                                        
                                        if ($existingSalida) {
                                            $existingSalida->update([
                                                'shift_id' => $shift->id,
                                                'marked_time' => $endTime,
                                            ]);
                                        } else {
                                            $record->attendanceMarks()->create([
                                                'shift_id' => $shift->id,
                                                'attendance_type_id' => $salidaType->id,
                                                'marked_time' => $endTime,
                                            ]);
                                        }
                                    }
                                    
                                    \Filament\Notifications\Notification::make()
                                        ->title('Asistencia actualizada exitosamente')
                                        ->body("Se actualizaron las marcaciones en el turno {$shift->name}")
                                        ->success()
                                        ->send();
                                }
                            } elseif ($this->editRecordType === 'falta') {
                                // Eliminar marcaciones existentes si se cambia a falta
                                $record->attendanceMarks()->delete();
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Falta registrada')
                                    ->body('Se registró la falta/inasistencia y se eliminaron las marcaciones existentes')
                                    ->info()
                                    ->send();
                            }
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->label('Borrar Asistencia'),
                    Tables\Actions\ViewAction::make()
                        ->label('Ver Detalles de Asistencia')
                        ->color('success')
                        ->form([
                            Forms\Components\Section::make('Informacion del Registro de asistencia')
                                ->description('Datos basicos sobre el registro de asistencia')
                                ->icon('heroicon-o-information-circle')
                                ->schema([
                                    Forms\Components\Select::make('employee_id')
                                        ->label('DNI')
                                        ->relationship('employee', 'dni'),
                                    Forms\Components\Select::make('employee_id')
                                        ->label('Apellido Paterno')
                                        ->relationship('employee', 'paternal_surname'),
                                    Forms\Components\Select::make('employee_id')
                                        ->label('Apellido Materno')
                                        ->relationship('employee', 'maternal_surname'),
                                    Forms\Components\Select::make('employee_id')
                                        ->label('Nombres')
                                        ->relationship('employee', 'names'),
                                    Forms\Components\DatePicker::make('date')
                                        ->label('Fecha')
                                        ->disabled()
                                        ->required()
                                        ->default(now())
                                        ->timezone(config('app.timezone'))
                                        ->format('Y-m-d'),
                                    Forms\Components\Textarea::make('observations')
                                        ->label('Observaciones')
                                        ->disabled()
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                            Forms\Components\Section::make('Informacion de las marcaciones')
                                ->description('Datos de las marcaciones del día')
                                ->icon('heroicon-o-list-bullet')
                                ->schema([
                                    Forms\Components\Repeater::make('attendanceMarks')
                                        ->relationship('attendanceMarks')
                                        ->schema([
                                            Forms\Components\Select::make('shift_id')
                                                ->label('Turno')
                                                ->relationship('shift', 'name')
                                                ->required(),
                                            Forms\Components\Select::make('attendance_type_id')
                                                ->label('Tipo de Marcación')
                                                ->relationship('attendanceType', 'name')
                                                ->required(),
                                            Forms\Components\TimePicker::make('marked_time')
                                                ->label('Hora Marcada')
                                                ->required()
                                                ->default(now())
                                                ->timezone(config('app.timezone'))
                                                ->displayFormat('H:i:s'),
                                        ])
                                        ->columns(2)
                                        ->columnSpanFull()
                                ]),
                        ]),
                    Tables\Actions\Action::make('create_attendance_mark')
                        ->label('Crear Marcación')
                        ->icon('heroicon-o-clock')
                        ->color('primary')
                        ->form([
                            Forms\Components\Section::make('Informacion de la nueva marcación')
                                ->description('Datos de la nueva marcación')
                                ->icon('heroicon-o-clipboard-document')
                                ->schema([
                                    Forms\Components\Select::make('shift_id')
                                        ->label('Turno')
                                        ->options(\App\Models\Shift::pluck('name', 'id'))
                                        ->required(),
                                    Forms\Components\Select::make('attendance_type_id')
                                        ->label('Tipo de Marcación')
                                        ->options(\App\Models\AttendanceType::pluck('name', 'id'))
                                        ->required(),
                                    Forms\Components\TimePicker::make('marked_time')
                                        ->required()
                                        ->label('Hora de Marca')
                                        ->default(now())
                                        ->timezone(config('app.timezone'))
                                        ->displayFormat('H:i:s'),
                                ])
                                ->columns(2)
                                ->columnSpanFull()
                        ])
                        ->action(function (AttendanceRecord $record, array $data): void {
                            $attendanceType = \App\Models\AttendanceType::find($data['attendance_type_id']);
                            $shift = \App\Models\Shift::find($data['shift_id']);
                            $today = date('Y-m-d');

                            $existingMarks = $record->attendanceMarks()
                                ->where('shift_id', $data['shift_id'])
                                ->where('attendance_type_id', $data['attendance_type_id'])
                                ->get();

                            $existingMark = false;
                            foreach ($existingMarks as $mark) {
                                $markDate = date('Y-m-d', strtotime($mark->marked_time));

                                if ($markDate === $today) {
                                    $existingMark = true;
                                    break;
                                }
                            }

                            if ($existingMark) {
                                Notification::make()
                                    ->title('Error de marcación')
                                    ->body('Ya existe una marcación de ' . strtolower($attendanceType->name) .
                                        ' para el turno ' . strtolower($shift->name) . ' en la fecha actual.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $newMark = $record->attendanceMarks()->create([
                                'shift_id' => $data['shift_id'],
                                'attendance_type_id' => $data['attendance_type_id'],
                                'marked_time' => $data['marked_time'],
                            ]);

                            Notification::make()
                                ->title('Marcación creada')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\EditAction::make('edit_attendance_mark')
                        ->label('Editar Marcaciones')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->form([
                            Forms\Components\Section::make('Informacion de las marcaciones')
                                ->description('Datos de las marcaciones del día')
                                ->icon('heroicon-o-list-bullet')
                                ->schema([
                                    Forms\Components\Repeater::make('attendanceMarks')
                                        ->relationship('attendanceMarks')
                                        ->addable(false)
                                        ->schema([
                                            Forms\Components\Select::make('shift_id')
                                                ->label('Turno')
                                                ->relationship('shift', 'name')
                                                ->required(),
                                            Forms\Components\Select::make('attendance_type_id')
                                                ->label('Tipo de Marcación')
                                                ->relationship('attendanceType', 'name')
                                                ->required(),
                                            Forms\Components\TimePicker::make('marked_time')
                                                ->label('Hora Marcada')
                                                ->required()
                                                ->default(now())
                                                ->timezone(config('app.timezone'))
                                                ->displayFormat('H:i:s'),
                                        ])
                                        ->columns(2)
                                        ->columnSpanFull()
                                ])
                        ])
                        ->action(function (AttendanceRecord $record): void {
                            Notification::make()
                                ->title('Marcaciones actualizadas correctamente')
                                ->success()
                                ->send();
                        })

                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()->exports([
                        AttendanceExport::make()
                    ])
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAttendanceRecords::route('/'),
        ];
    }
}
