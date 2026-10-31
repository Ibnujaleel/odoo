<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin', 'staff', 'customer'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $password = Hash::make('Brightloop@123');

        $admin = User::firstOrCreate(
            ['email' => 'admin@brightloop.test'],
            ['name' => 'Brightloop Admin', 'password' => $password, 'email_verified_at' => now()],
        );
        $admin->syncRoles(['admin']);

        $staff = User::firstOrCreate(
            ['email' => 'staff@brightloop.test'],
            ['name' => 'Brightloop Staff', 'password' => $password, 'email_verified_at' => now()],
        );
        $staff->syncRoles(['staff']);

        $customerUser = User::firstOrCreate(
            ['email' => 'customer@brightloop.test'],
            ['name' => 'Brightloop Customer', 'password' => $password, 'email_verified_at' => now()],
        );
        $customerUser->syncRoles(['customer']);

        Customer::firstOrCreate(
            ['email' => 'customer@brightloop.test'],
            [
                'user_id' => $customerUser->id,
                'name' => $customerUser->name,
                'company' => 'Brightloop Demo Co.',
                'phone' => '+1 555 0100',
                'address' => '1 Loop Street, Suite 100',
                'assigned_to' => $staff->id,
            ],
        );
    }
}
