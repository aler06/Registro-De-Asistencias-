<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'dni',
        'paternal_surname',
        'maternal_surname',
        'names',
        'position_id',
        'email',
        'date_of_birth',
        'phone',
    ];

    protected $casts = [
        'dni' => 'integer',
        'phone' => 'integer',
        'date_of_birth' => 'date',
    ];

    protected $dates = [
        'date_of_birth',
        'created_at',
        'updated_at',
    ];

    public function getDateOfBirthAttribute($value)
    {
        if ($value) {
            return \Carbon\Carbon::parseSqlServerDate($value);
        }
        return $value;
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

}
