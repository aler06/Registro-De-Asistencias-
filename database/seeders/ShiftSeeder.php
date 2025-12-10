<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Shift;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Mañana',
                'description' => 'Turno de mañana',
                'start_time' => '06:00:00',
                'end_time' => '12:30:00',
            ],
            [
                'name' => 'Tarde',
                'description' => 'Turno de tarde',
                'start_time' => '14:00:00',
                'end_time' => '16:30:00',
            ],
            [
                'name' => 'Noche',
                'description' => 'Turno de noche',
                'start_time' => '20:00:00',
                'end_time' => '06:00:00',
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::updateOrCreate(
                ['name' => $shift['name']],
                $shift
            );
        }
    }
}

