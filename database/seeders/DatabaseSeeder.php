<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\FormDefinitionsTableSeeder;
use Database\Seeders\WorflowDefinitionsTableSeeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(FormDefinitionsTableSeeder::class);
        $this->call(WorflowDefinitionsTableSeeder::class);
    }
}
