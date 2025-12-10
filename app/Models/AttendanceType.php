<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class AttendanceType extends Model
{
    use HasFactory;

    use HasTranslations;
    protected $fillable = [
        'name',
        'description',
    ];

    public function attendanceMarks(): HasMany
    {
        return $this->hasMany(AttendanceMark::class);
    }

}
