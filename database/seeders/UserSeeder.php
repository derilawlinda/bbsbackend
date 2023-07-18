<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'name' => 'Deril Alwinda',
                'email' => 'deril@yahoo.com',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Admin')->first()->id,
            ],
            [
                'name' => 'Abang Sales',
                'email' => 'sales@gmail.com',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Sales')->first()->id,
            ],
            [
                'name' => 'Ibu Finance',
                'email' => 'finance@gmail.com',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Finance')->first()->id,
            ],
            [
                'name' => 'Manager',
                'email' => 'manager@gmail.com',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Manager')->first()->id,
            ],
            [
                'name' => 'Pak Direktur',
                'email' => 'direktur@gmail.com',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Director')->first()->id,
            ]
            // Add more users as needed
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}

