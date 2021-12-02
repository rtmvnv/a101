<?php

namespace Database\Seeders;

use App\Models\Accrual;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Accrual::truncate();
        Accrual::factory(5)->create();
    }
}
