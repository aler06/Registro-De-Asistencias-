<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AttendanceType;

class AttendanceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Entrada',
                'description' => 'Marcación de entrada',
            ],
            [
                'name' => 'Salida',
                'description' => 'Marcación de salida',
            ],
        ];

        foreach ($types as $type) {
            AttendanceType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}

