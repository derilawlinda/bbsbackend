<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $roles = [
            [
                'name' => "Admin",
                'permissions' => json_encode([
                    'User' => ['view', 'create', 'update', 'delete','approve'],
                    'Budgeting' => ['view'],
                    'AE' => ['view'],
                    'MR' => ['view'],
                ]),
            ],
            [
                'name' => "Finance",
                'permissions' => json_encode([
                    'User' => ['view', 'create', 'update', 'delete','approve'],
                    'Budgeting' => ['view','approve'],
                    'AE' => ['view','approve'],
                    'MR' => ['view','approve'],
                ]),
            ],
            [
                'name' => "Sales",
                'permissions' => json_encode([
                    'User' => ['view'],
                    'Budgeting' => ['view','create', 'update'],
                    'AE' => ['view','create', 'update'],
                    'MR' => ['view','create', 'update'],
                ]),
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
