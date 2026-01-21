<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin King Rental',
            'email' => 'admin@kingrental.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'phone' => '081234567890',
            'address' => 'Kantor Pusat Bali',
        ]);

        User::create([
            'name' => 'Wilson (Customer)',
            'email' => 'wilson@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'phone' => '08987654321',
            'address' => 'Jakarta Selatan',
        ]);

        $bali = Branch::create([
            'name' => 'King Rental Bali (Kuta)',
            'address' => 'Jl. Raya Kuta No. 88, Badung, Bali',
            'latitude' => -8.723796,
            'longitude' => 115.174623,
            'contact_number' => '0361-123456',
        ]);

        $ubud = Branch::create([
            'name' => 'King Rental Ubud',
            'address' => 'Jl. Monkey Forest, Ubud, Gianyar',
            'latitude' => -8.513222, 
            'longitude' => 115.263222,
            'contact_number' => '0361-654321',
        ]);

        Vehicle::create([
            'branch_id' => $bali->id,
            'name' => 'Toyota Avanza 2023',
            'type' => 'car',
            'license_plate' => 'DK 1234 AB',
            'transmission' => 'automatic',
            'capacity' => 7,
            'price_per_day' => 350000,
            'is_available' => true,
            'description' => 'Mobil keluarga nyaman, AC dingin, hemat bensin.',
            'image_url' => null,
        ]);

        Vehicle::create([
            'branch_id' => $bali->id,
            'name' => 'Honda Vario 160',
            'type' => 'bike',
            'license_plate' => 'DK 5678 CD',
            'transmission' => 'automatic',
            'capacity' => 2,
            'price_per_day' => 85000,
            'is_available' => true,
            'description' => 'Motor matic bertenaga untuk keliling Bali.',
            'image_url' => null,
        ]);
        
        Vehicle::create([
            'branch_id' => $ubud->id,
            'name' => 'Toyota Innova Reborn',
            'type' => 'car',
            'license_plate' => 'DK 9999 XR',
            'transmission' => 'manual',
            'capacity' => 7,
            'price_per_day' => 500000,
            'is_available' => true,
            'description' => 'Premium MPV, cocok untuk perjalanan jauh.',
            'image_url' => null,
        ]);
    }
}