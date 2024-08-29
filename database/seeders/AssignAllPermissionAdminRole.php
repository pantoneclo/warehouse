<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AssignAllPermissionAdminRole extends Seeder
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
                'name' => 'manage.roles',
                'display_name' => 'Manage Roles',
            ],
            [
                'name' => 'manage.brands',
                'display_name' => 'Manage Brands',
            ],
            [
                'name' => 'manage.currency',
                'display_name' => 'Manage Currency',
            ],
            [
                'name' => 'manage.warehouses',
                'display_name' => 'Manage Warehouses',
            ],
            [
                'name' => 'manage.units',
                'display_name' => 'Manage Units',
            ],
            [
                'name' => 'manage.product.categories',
                'display_name' => 'Manage Product Categories',
            ],
            [
                'name' => 'manage.products',
                'display_name' => 'Manage Products ',
            ],
            [
                'name' => 'manage.suppliers',
                'display_name' => 'Manage Suppliers',
            ],
            [
                'name' => 'manage.customers',
                'display_name' => 'Manage Customers',
            ],
            [
                'name' => 'manage.users',
                'display_name' => 'Manage Users',
            ],
            [
                'name' => 'manage.expense.categories',
                'display_name' => 'Manage Expense Categories',
            ],
            [
                'name' => 'manage.expenses',
                'display_name' => 'Manage Expenses',
            ],
            [
                'name' => 'manage.adjustments',
                'display_name' => 'Manage Adjustments',
            ],
            [
                'name' => 'manage.transfers',
                'display_name' => 'Manage Transfers',
            ], [
                'name' => 'manage.setting',
                'display_name' => 'Manage Setting',
            ],
            [
                'name' => 'manage.dashboard',
                'display_name' => 'Manage Dashboard',
            ],
            [
                'name' => 'manage.pos.screen',
                'display_name' => 'Manage Pos Screen',
            ],
            [
                'name' => 'manage.purchase',
                'display_name' => 'Manage Purchase',
            ],
            [
                'name' => 'manage.sale',
                'display_name' => 'Manage Sale',
            ],
            [
                'name' => 'manage.purchase.return',
                'display_name' => 'Manage Purchase Return',
            ],
            [
                'name' => 'manage.sale.return',
                'display_name' => 'Manage Sale Return',
            ],

        ];

        /** @var Role $adminRole */
        $adminRole = Role::whereName(Role::ADMIN)->first();

        if (empty($adminRole)) {
            $adminRole = Role::create([
                'name' => 'admin',
                'display_name' => ' Admin',
            ]);
        }

        foreach ($permissions as $permission) {
            $permissionExist = Permission::where('name', $permission['name'])->exists();
            if (! $permissionExist) {
                Permission::create($permission);
            }
        }

        $allPermissions = Permission::pluck('name', 'id');
        $adminRole->syncPermissions($allPermissions);
    }
}
