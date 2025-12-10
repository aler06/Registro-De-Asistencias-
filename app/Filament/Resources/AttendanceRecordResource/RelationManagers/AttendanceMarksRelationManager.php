<?php

namespace App\Filament\Resources\AttendanceRecordResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceMarksRelationManager extends RelationManager
{
    protected static string $relationship = 'attendanceMarks';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('attendance_type_id')
                    ->label('Tipo de Asistencia')
                    ->relationship('attendanceType', 'name')
                    ->required()
                    ->validationMessages([
                        'required' => 'The attendance type is required',
                    ]),
                Forms\Components\Select::make('shift_id')
                    ->label('Turno')
                    ->relationship('shift', 'name')
                    ->required()
                    ->validationMessages([
                        'required' => 'The shift is required',
                    ]),
                Forms\Components\TimePicker::make('marked_time')
                    ->label('Hora Marcada')
                    ->required()
                    ->validationMessages([
                        'required' => 'La hora de marca es requerida',
                    ])
                    ->displayFormat('H:i:s'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('marked_time')
            ->columns([
                Tables\Columns\TextColumn::make('shift.name')
                    ->label('Shift'),
                Tables\Columns\TextColumn::make('attendanceType.name')
                    ->label('Attendance Type'),
                Tables\Columns\TextColumn::make('marked_time')
                    ->label('Marked Time')
                    ->time('H:i:s'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
