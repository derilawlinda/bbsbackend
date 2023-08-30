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
            [
                'name' => 'Aristide Goinfaith Salomo Sondakh',
                'email' => 'aristide.goinfaith@unitedcreative.id',
                'password' => Hash::make('BaliUnited1234'),
                'role_id' => Role::where('name', 'Finance')->first()->id,
            ],
            [
                'name' => 'Robin Suparto Finance',
                'email' => 'rbsrobin2021@gmail.com',
                'password' => Hash::make('BaliUnited1234'),
                'role_id' => Role::where('name', 'Finance')->first()->id,
            ]
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}

