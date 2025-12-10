<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class AttendanceRecord extends Model
{
    use HasFactory;

    use HasTranslations;

    protected $fillable = [
        'employee_id',
        'date',
        'observations',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected $dates = [
        'date',
        'created_at',
        'updated_at',
    ];

    public function getDateAttribute($value)
    {
        if ($value) {
            return \Carbon\Carbon::parseSqlServerDate($value);
        }
        return $value;
    }


    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendanceMarks(): HasMany
    {
        return $this->hasMany(AttendanceMark::class);
    }

}
