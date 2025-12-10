<?php

namespace App\Exports;

use Carbon\Carbon;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class AttendanceExport extends ExcelExport
{
    public function setUp()
    {
        $this->withFilename('Registros_Asistencia_' . now()->format('Y-m-d'))
            ->modifyQueryUsing(function ($query) {
                return $query->with([
                    'attendanceMarks.shift',
                    'attendanceMarks.attendanceType',
                    'employee'
                ]);
            })
            ->withColumns([
                Column::make('employee.dni')->heading('DNI'),
                Column::make('employee.paternal_surname')->heading('Apellido Paterno'),
                Column::make('employee.maternal_surname')->heading('Apellido Materno'),
                Column::make('employee.names')->heading('Nombres'),
                Column::make('date')
                    ->heading('Fecha')
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('d/m/Y')),
                Column::make('shift')->heading('Turno'),
                Column::make('ingreso')->heading('Ingreso'),
                Column::make('salida')->heading('Salida'),
            ]);
    }

    public function map($record): array
    {
        $resultados = [];

        $porTurno = $record->attendanceMarks->groupBy('shift.name');

        foreach ($porTurno as $nombreTurno => $marcasTurno) {
            $fila = [
                'DNI' => $record->employee->dni,
                'Apellido Paterno' => $record->employee->paternal_surname,
                'Apellido Materno' => $record->employee->maternal_surname,
                'Nombres' => $record->employee->names,
                'Fecha' => Carbon::parse($record->date)->format('d/m/Y'),
                'Turno' => $nombreTurno,
                'ingreso' => '--:--:--', // minúscula para coincidir con la columna
                'salida' => '--:--:--'   // minúscula para coincidir con la columna
            ];

            foreach ($marcasTurno as $marca) {
                $tipo = strtolower($marca->attendanceType->name);
                if (array_key_exists($tipo, $fila)) {
                    $fila[$tipo] = Carbon::parse($marca->marked_time)->format('H:i:s');
                }
            }

            $resultados[] = $fila;
        }

        return $resultados;
    }
}
