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
                'name' => 'Jane Smith',
                'email' => 'jane@gmail.com',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Sales')->first()->id,
            ],
            [
                'name' => 'John Smith',
                'email' => 'john@gmail.com',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Finance')->first()->id,
            ]
            // Add more users as needed
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}

