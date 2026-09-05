<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SecuritySeeder::class,
            MasterReferenceSeeder::class,
            ReportingSeeder::class,
            ReportDatasourceSeeder::class,
            HumanCapitalSeeder::class,
        ]);
    }
}
