<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $merchantRole = Role::create(['name' => 'merchant']);
        $customerRole = Role::create(['name' => 'customer']);

        // Create permissions
        // User permissions
        Permission::create(['name' => 'user.view']);
        Permission::create(['name' => 'user.create']);
        Permission::create(['name' => 'user.edit']);
        Permission::create(['name' => 'user.delete']);

        // Category permissions
        Permission::create(['name' => 'category.view']);
        Permission::create(['name' => 'category.create']);
        Permission::create(['name' => 'category.edit']);
        Permission::create(['name' => 'category.delete']);

        // Product permissions
        Permission::create(['name' => 'product.view']);
        Permission::create(['name' => 'product.create']);
        Permission::create(['name' => 'product.edit']);
        Permission::create(['name' => 'product.delete']);

        // Order permissions
        Permission::create(['name' => 'order.view']);
        Permission::create(['name' => 'order.create']);
        Permission::create(['name' => 'order.edit']);
        Permission::create(['name' => 'order.delete']);
        Permission::create(['name' => 'order.pay']);

        // Cart permissions
        Permission::create(['name' => 'cart.view']);
        Permission::create(['name' => 'cart.create']);
        Permission::create(['name' => 'cart.edit']);
        Permission::create(['name' => 'cart.delete']);

        // Transaction permissions
        Permission::create(['name' => 'transaction.view']);
        Permission::create(['name' => 'transaction.create']);
        Permission::create(['name' => 'transaction.payment']);
        Permission::create(['name' => 'transaction.refund']);
        Permission::create(['name' => 'transaction.report']);

        // Assign permissions to roles
        // Admin has all permissions
        $adminRole->givePermissionTo(Permission::all());

        // Merchant permissions
        $merchantRole->givePermissionTo([
            'category.view',
            'category.create',
            'category.edit',
            'category.delete',
            'product.view',
            'product.create',
            'product.edit',
            'product.delete',
            'order.view',
            'transaction.view',
            'transaction.report',
        ]);

        // Customer permissions
        $customerRole->givePermissionTo([
            'product.view',
            'order.view',
            'order.create',
            'order.pay',
            'cart.view',
            'cart.create',
            'cart.edit',
            'cart.delete',
            'transaction.view',
            'transaction.payment',
            'transaction.refund',
        ]);
    }
}
