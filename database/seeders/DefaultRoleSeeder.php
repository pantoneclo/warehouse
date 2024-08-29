<?php

namespace Database\Seeders;

//use App\Models\Permission;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

use App\Models\User;

class DefaultRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
            ],
            [
                'name' => 'admin',
                'display_name' => 'Admin',
            ],
        ];

        foreach ($roles as $role) {
            $role_c = Role::whereName($role['name'])->first();
            if (empty($role_c)) {
                $role = Role::create($role);
            }
        }

        $adminRole = Role::whereName('super_admin')->first();
        $allPermissions = Permission::all();
        $adminRole->syncPermissions($allPermissions);

        $user = User::where('email','superadmin@tc.com')->first();

        $user->assignRole($adminRole);
    }
}
