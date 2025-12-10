<?php

namespace App\Filament\Resources\AttendanceRecordResource\Pages;

use App\Filament\Resources\AttendanceRecordResource;
use App\Models\AttendanceType;
use App\Models\Shift;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageAttendanceRecords extends ManageRecords
{
    protected static string $resource = AttendanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->mutateFormDataUsing(function (array $data): array {
                    $this->shiftId = $data['shift_id'] ?? null;
                    $this->recordType = $data['record_type'] ?? null;
                    $this->manualStartTime = $data['manual_start_time'] ?? null;
                    $this->manualEndTime = $data['manual_end_time'] ?? null;
                    unset($data['shift_id']);
                    unset($data['record_type']);
                    unset($data['manual_start_time']);
                    unset($data['manual_end_time']);
                    unset($data['shift_info']);
                    return $data;
                })
                ->after(function (Model $record) {
                    if ($this->recordType === 'asistencia' && $this->shiftId && $this->manualStartTime && $this->manualEndTime) {
                        $shift = Shift::find($this->shiftId);
                        
                        if ($shift) {
                            $entradaType = AttendanceType::where('name', 'Entrada')->first();
                            $salidaType = AttendanceType::where('name', 'Salida')->first();
                            
                            $startTime = \Carbon\Carbon::parse($this->manualStartTime)->format('H:i:s');
                            $endTime = \Carbon\Carbon::parse($this->manualEndTime)->format('H:i:s');
                            
                            if ($entradaType) {
                                $record->attendanceMarks()->create([
                                    'shift_id' => $shift->id,
                                    'attendance_type_id' => $entradaType->id,
                                    'marked_time' => $startTime,
                                ]);
                            }
                            
                            if ($salidaType) {
                                $record->attendanceMarks()->create([
                                    'shift_id' => $shift->id,
                                    'attendance_type_id' => $salidaType->id,
                                    'marked_time' => $endTime,
                                ]);
                            }
                            
                            Notification::make()
                                ->title('Asistencia registrada exitosamente')
                                ->body("Se registró la asistencia en el turno {$shift->name}")
                                ->success()
                                ->send();
                        }
                    } elseif ($this->recordType === 'falta') {
                        Notification::make()
                            ->title('Falta registrada')
                            ->body('Se registró la falta/inasistencia')
                            ->info()
                            ->send();
                    }
                }),
        ];
    }
    
    protected $shiftId = null;
    protected $recordType = null;
    protected $manualStartTime = null;
    protected $manualEndTime = null;
}
