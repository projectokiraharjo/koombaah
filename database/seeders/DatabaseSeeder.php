<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $akun = [
            ['name' => 'Owner Koombaah',    'email' => 'owner@koombaah.test',    'role' => Role::Owner],
            ['name' => 'Admin Operasional', 'email' => 'admin@koombaah.test',    'role' => Role::Admin],
            ['name' => 'Admin Keuangan',    'email' => 'keuangan@koombaah.test', 'role' => Role::AdminKeuangan],
        ];

        foreach ($akun as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'role' => $data['role'],
                    'password' => 'password', // otomatis di-hash oleh cast 'hashed'
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
        }
                $this->call(InventorySeeder::class);
                $this->call(BomSeeder::class);
                $this->call(SupplierSeeder::class);
                $this->call(EmployeeSeeder::class);
    }
}