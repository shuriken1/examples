<?php

use Illuminate\Database\Seeder;
use App\Allergen;

class AllergensTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $allergens = [
            ['id' => 1, 'name' => 'Celery'],
            ['id' => 2, 'name' => 'Cereals containing gluten'],
            ['id' => 3, 'name' => 'Crustaceans'],
            ['id' => 4, 'name' => 'Eggs'],
            ['id' => 5, 'name' => 'Fish'],
            ['id' => 6, 'name' => 'Lupin'],
            ['id' => 7, 'name' => 'Milk'],
            ['id' => 8, 'name' => 'Molluscs'],
            ['id' => 9, 'name' => 'Mustard'],
            ['id' => 10, 'name' => 'Tree nuts'],
            ['id' => 11, 'name' => 'Peanuts'],
            ['id' => 12, 'name' => 'Sesame seeds'],
            ['id' => 13, 'name' => 'Soybeans'],
            ['id' => 14, 'name' => 'Sulphur dioxide and sulphites'],
        ];

        Allergen::insert($allergens);
    }
}
