<?php

namespace Database\Seeders;

use Hamcrest\Core\AllOf;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (glob(database_path('seeders/*Seeder.php')) as $file) {

            $class = 'Database\\Seeders\\' . basename($file, '.php');

            if ($class !== self::class) {
                $this->call($class);
            }
        }
    }
}
