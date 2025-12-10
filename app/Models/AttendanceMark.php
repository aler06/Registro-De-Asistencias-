<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_type_id',
        'shift_id',
        'attendance_record_id',
        'marked_time'
    ];

    protected $casts = [
        'marked_time' => 'datetime:H:i:s',
    ];

    protected $dates = [
        'marked_time',
        'created_at',
        'updated_at',
    ];

    public function getMarkedTimeAttribute($value)
    {
        if ($value) {
            return \Carbon\Carbon::parseSqlServerDate($value);
        }
        return $value;
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function attendanceType(): BelongsTo
    {
        return $this->belongsTo(AttendanceType::class);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

}
