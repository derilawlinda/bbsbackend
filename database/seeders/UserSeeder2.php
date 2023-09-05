<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class UserSeeder2 extends Seeder
{
    public function run()
    {
        $users = [
            // [
            //     'name' => 'Jasmine Finance',
            //     'email' => 'Jasmine.jasmine@unitedcreative.id',
            //     'password' => Hash::make('BaliUnited1234'),
            //     'role_id' => Role::where('name', 'Finance')->first()->id,
            // ],
            [
                'name' => 'Heri Gunawan',
                'email' => 'Heriigun25@gmail.com',
                'password' => Hash::make('BaliUnited1234'),
                'role_id' => Role::where('name', 'Manager')->first()->id,
            ],
            [
                'name' => 'Viky Rumaniogi',
                'email' => 'Viky.rumaniogi@unitedcreative.co.id',
                'password' => Hash::make('BaliUnited1234'),
                'role_id' => Role::where('name', 'Staff')->first()->id,
            ]
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}

