<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctor;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create sample doctors
        Doctor::create([
            'name' => 'Dr. Sarah Johnson',
            'specialization' => 'Cardiology',
        ]);

        Doctor::create([
            'name' => 'Dr. Michael Chen',
            'specialization' => 'Pediatrics',
        ]);

        Doctor::create([
            'name' => 'Dr. Emily Rodriguez',
            'specialization' => 'Dermatology',
        ]);

        Doctor::create([
            'name' => 'Dr. James Wilson',
            'specialization' => 'Orthopedics',
        ]);
    }
}


