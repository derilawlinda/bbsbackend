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
            ],
            [
                'name' => 'SL1',
                'email' => 'sl1',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Director')->first()->id,
            ],
            [
                'name' => 'SL2',
                'email' => 'sl2',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Director')->first()->id,
            ],
            [
                'name' => 'SL3',
                'email' => 'sl3',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Director')->first()->id,
            ],
            [
                'name' => 'SL4',
                'email' => 'sl4',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Director')->first()->id,
            ],
            [
                'name' => 'SL5',
                'email' => 'sl5',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Director')->first()->id,
            ],
            [
                'name' => 'SL6',
                'email' => 'sl6',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Director')->first()->id,
            ],
            [
                'name' => 'SL7',
                'email' => 'sl7',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Director')->first()->id,
            ],
            [
                'name' => 'SL8',
                'email' => 'sl8',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Director')->first()->id,
            ],
            [
                'name' => 'SL9',
                'email' => 'sl9',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Director')->first()->id,
            ],
            [
                'name' => 'SL10',
                'email' => 'sl10',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Director')->first()->id,
            ],
            [
                'name' => 'SL11',
                'email' => 'sl11',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'Director')->first()->id,
            ],
            [
                'name' => 'SL12',
                'email' => 'sl12',
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

