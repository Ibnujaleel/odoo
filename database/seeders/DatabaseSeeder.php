<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndUsersSeeder::class,
            CatalogueSeeder::class,
            SalesSeeder::class,
            FinanceSeeder::class,
            BulkDemoDataSeeder::class,
        ]);
    }
}
