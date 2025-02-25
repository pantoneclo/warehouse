<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DefaultPermissionEmailReportQuotationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            [
                'name' => 'manage.email.templates',
                'display_name' => 'Manage Email Templates',
            ],
            [
                'name' => 'manage.reports',
                'display_name' => 'Manage Reports',
            ],
            [
                'name' => 'manage.quotations',
                'display_name' => 'Manage Quotations',
            ],
        ];
        foreach ($permissions as $permission) {
            $permissionExist = Permission::whereName($permission['name'])->exists();
            if (! $permissionExist) {
                Permission::create($permission);
            }
        }

        /** @var Role $adminRole */
        $adminRole = Role::whereName(Role::ADMIN)->first();

        if (empty($adminRole)) {
            $adminRole = Role::create([
                'name' => 'admin',
                'display_name' => ' Admin',
            ]);
        }

        $allPermissions = Permission::pluck('name', 'id');
        $adminRole->syncPermissions($allPermissions);
    }
}
