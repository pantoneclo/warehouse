<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DefaultPermissionsSeeder extends Seeder
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
                'name'=>'product.create',
                'display_name'=>'Create Product',
            ],
            [
                'name'=>'product.edit',
                'display_name'=>'Edit Product',
            ],
            [
                'name'=>'product.delete',
                'display_name'=>'Delete Product',
            ],
            [
                'name'=>'product.view',
                'display_name'=>'View Product',
            ],
            [
                'name'=>'package.create',
                'display_name'=>'Create Package',
            ],
            [
                'name'=>'package.edit',
                'display_name'=>'Edit Package',
            ],
            [
                'name'=>'package.delete',
                'display_name'=>'Delete Package',
            ],
            [
                'name'=>'package.view',
                'display_name'=>'View Package',
            ],
            [
                'name'=>'purchase.create',
                'display_name'=>'Create Purchase',
            ],
            [
                'name'=>'purchase.edit',
                'display_name'=>'Edit Purchase',
            ],
            [
                'name'=>'purchase.delete',
                'display_name'=>'Delete Purchase',
            ],
            [
                'name'=>'purchase.view',
                'display_name'=>'View Purchase',
            ],
            [
                'name'=>'sale.create',
                'display_name'=>'Create Sale',
            ],
            [
                'name'=>'sale.edit',
                'display_name'=>'Edit Sale',
            ],
            [
                'name'=>'sale.delete',
                'display_name'=>'Delete Sale',
            ],
            [
                'name'=>'sale.view',
                'display_name'=>'View Sale',
            ],
            [
                'name'=>'return.create',
                'display_name'=>'Create Return',
            ],
            [
                'name'=>'return.edit',
                'display_name'=>'Edit Return',
            ],
            [
                'name'=>'return.delete',
                'display_name'=>'Delete Return',
            ],
            [
                'name'=>'return.view',
                'display_name'=>'View Return',
            ],
            [
                'name'=>'expense.create',
                'display_name'=>'Create Expense',
            ],
            [
                'name'=>'expense.edit',
                'display_name'=>'Edit Expense',
            ],
            [
                'name'=>'expense.delete',
                'display_name'=>'Delete Expense',
            ],
            [
                'name'=>'expense.view',
                'display_name'=>'View Expense',
            ],
            [
                'name'=>'stock.adjustment.create',
                'display_name'=>'Create Stock Adjustment',
            ],
            [
                'name'=>'stock.adjustment.edit',
                'display_name'=>'Edit Stock Adjustment',
            ],
            [
                'name'=>'stock.adjustment.delete',
                'display_name'=>'Delete Stock Adjustment',
            ],
            [
                'name'=>'stock.adjustment.view',
                'display_name'=>'View Stock Adjustment',
            ],
            [
                'name'=>'stock.transfer.create',
                'display_name'=>'Create Stock Transfer',
            ],
            [
                'name'=>'stock.transfer.edit',
                'display_name'=>'Edit Stock Transfer',
            ],
            [
                'name'=>'stock.transfer.delete',
                'display_name'=>'Delete Stock Transfer',
            ],
            [
                'name'=>'stock.transfer.view',
                'display_name'=>'View Stock Transfer',
            ],
            [
                'name'=>'stock.report',
                'display_name'=>'View Stock Report',
            ],
            [
                'name'=>'supplier.view',
                'display_name'=>'View Supplier',

            ],
            [
                'name'=>'supplier.create',
                'display_name'=>'Create Supplier',
            ],
            [
                'name'=>'supplier.edit',
                'display_name'=>'Edit Supplier',
            ],
            [
                'name'=>'supplier.delete',
                'display_name'=>'Delete Supplier',
            ],
            [
                'name'=>'customer.create',
                'display_name'=>'Create Customer',
            ],
            [
                'name'=>'customer.edit',
                'display_name'=>'Edit Customer',
            ],
            [
                'name'=>'customer.delete',
                'display_name'=>'Delete Customer',
            ],
            [
                'name'=>'user.create',
                'display_name'=>'Create User',
            ],
            [
                'name'=>'user.edit',
                'display_name'=>'Edit User',
            ],
            [
                'name'=>'user.delete',
                'display_name'=>'Delete User',
            ],
            [
                'name'=>'warehouse.create',
                'display_name'=>'Create Warehouse',
            ],
            [
                'name'=>'warehouse.edit',
                'display_name'=>'Edit Warehouse',
            ],
            [
                'name'=>'warehouse.delete',
                'display_name'=>'Delete Warehouse',
            ],
            [
                'name'=>'warehouse.view',
                'display_name'=>'View Warehouse',
            ],
          
            [
                'name'=>'product.category.create',
                'display_name'=>'Create Product Category',
            ],
            [
                'name'=>'product.category.edit',
                'display_name'=>'Edit Product Category',
            ],
            [
                'name'=>'product.category.delete',
                'display_name'=>'Delete Product Category',
            ],
            [
                'name'=>'product.category.view',
                'display_name'=>'View Product Category',
            ],
            [
                'name'=>'adjustment.create',
                'display_name'=>'Create Adjustment',
            ],
            [
                'name'=>'adjustment.edit',
                'display_name'=>'Edit Adjustment',
            ],
            [
                'name'=> 'adjustment.delete',
                'display_name'=>'Delete Adjustment',
            ],
            [
                'name'=>'adjustment.view',
                'display_name'=>'View Adjustment',
            ],
            [
                'name'=>'brand.create',
                'display_name'=>'Create Brand',
            ],
            [
                'name'=>'brand.edit',
                'display_name'=>'Edit Brand',
            ],
            [
                'name'=>'brand.delete',
                'display_name'=>'Delete Brand',
            ],
            [
                'name'=>'brand.view',
                'display_name'=>'View Brand',
            ],
            [
                'name'=>'unit.create',
                'display_name'=>'Create Unit',
            ],
            [
                'name'=>'unit.edit',
                'display_name'=>'Edit Unit',
                
            ],
            [
                'name'=>'unit.delete',
                'display_name'=>'Delete Unit',
            ],
            [
                'name'=>'unit.view',
                'display_name'=>'View Unit',
            ],
            [
                'name'=>'quotations.create',
                'display_name'=>'Create Quotations',
            ],
            [
                'name'=>'quotations.edit',
                'display_name'=>'Edit Quotations',
            ],
            [
                'name'=>'quotations.delete',
                'display_name'=>'Delete Quotations',
            ],
            [
                'name'=>'quotations.view',
                'display_name'=>'View Quotations',

            ],           
            [
                'name'=>'manage.packages',
                'display_name'=>'Manage Packages'
            ],
            [
                'name'=>'package.barcode',
                'display_name'=>'Package Barcode Print'
            ],
            [
                'name'=>'manage.print.barcode',
                'display_name'=>'Manage Print Barcode'
            ],
            [
                'name'=>'purchase.return.create',
                'display_name'=>'Create Purchase Return'
            ],
            [
                'name'=>'purchase.return.edit',
                'display_name'=>'Edit Purchase Return'
            ],
            [
                'name'=>'purchase.return.delete',
               'display_name'=>'Delete Purchase Return'
            ],
            [
                'name'=>'purchase.return.view',
                'display_name'=>'View Purchase Return'
            ],
            [
                'name'=>'sale.payment.manage',
                'display_name'=>'Manage Sale Payment'
            ],
            [
                'name'=>'expense.category.create',
                'display_name'=>'Create Expense Category'
            ],
            [
                'name'=>'expense.category.edit',
                'display_name'=>'Edit Expense Category'
            ],
            [
                'name'=>'expense.category.delete',
                'display_name'=>'Delete Expense Category'
            ],
            
            

            

        ];

        foreach ($permissions as $permission) {
            $permissionExist = Permission::whereName($permission['name'])->exists();
            if (! $permissionExist) {
                Permission::create($permission);
            }
        }
    }
}
