<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan db:seed            -> conteúdo + dados de demonstração
     * php artisan db:seed --class=ContentSeeder  -> só o conteúdo pedagógico
     */
    public function run(): void
    {
        $this->call([
            ContentSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
