<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = User::create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'points' => 0,
        ]);
        $admin->assignRole('admin');

        // Create merchant user
        $merchant = User::create([
            'username' => 'merchant',
            'email' => 'merchant@example.com',
            'password' => Hash::make('password'),
            'points' => 0,
        ]);
        $merchant->assignRole('merchant');

        // Create customer user
        $customer = User::create([
            'username' => 'customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'points' => 0,
        ]);
        $customer->assignRole('customer');
    }
}
