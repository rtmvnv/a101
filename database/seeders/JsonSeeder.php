<?php

namespace Database\Seeders;

use \App\Models\Accrual;
use Illuminate\Database\Seeder;

class JsonSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        Accrual::truncate();
        Accrual::factory(5)->create();
    }
}
