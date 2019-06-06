<?php

use Illuminate\Database\Seeder;

class AllergenFoodTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('allergen_food')->insert([
            'id' => 1,
            'food_id' => 1,
            'allergen_id' => 10,
            'food_amount' => 1,
        ]);

        DB::table('allergen_food')->insert([
            'id' => 2,
            'food_id' => 2,
            'allergen_id' => 5,
            'food_amount' => 1,
        ]);

        DB::table('allergen_food')->insert([
            'id' => 3,
            'food_id' => 3,
            'allergen_id' => 2,
            'food_amount' => 1,
        ]);

        DB::table('allergen_food')->insert([
            'id' => 4,
            'food_id' => 3,
            'allergen_id' => 10,
            'food_amount' => 2,
        ]);

        DB::table('allergen_food')->insert([
            'id' => 5,
            'food_id' => 2,
            'allergen_id' => 10,
            'food_amount' => 1,
        ]);

        DB::table('allergen_food')->insert([
            'id' => 6,
            'food_id' => 4,
            'allergen_id' => 10,
            'food_amount' => 2,
        ]);

        DB::table('allergen_food')->insert([
            'id' => 7,
            'food_id' => 1,
            'allergen_id' => 7,
            'food_amount' => 2,
        ]);

        DB::table('allergen_food')->insert([
            'id' => 8,
            'food_id' => 6,
            'allergen_id' => 1,
            'food_amount' => 0,
        ]);
    }
}
