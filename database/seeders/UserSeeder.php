<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $manager = User::firstOrCreate(
            ['email' => 'manager@crm.test'],
            ['name' => 'Manager', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $manager->assignRole('Manager');

        $admin = User::firstOrCreate(
            ['email' => 'admin@crm.test'],
            ['name' => 'Admin', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $admin->assignRole('Admin');

        foreach (['sales1', 'sales2'] as $name) {
            $user = User::firstOrCreate(
                ['email' => "{$name}@crm.test"],
                ['name' => ucfirst($name), 'password' => Hash::make('password'), 'email_verified_at' => now(), 'manager_id' => $manager->id]
            );
            $user->assignRole('Sales');
        }

        $sales3 = User::firstOrCreate(
            ['email' => 'sales3@crm.test'],
            ['name' => 'Sales3', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $sales3->assignRole('Sales');
    }
}
