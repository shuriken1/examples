<?php

use Illuminate\Database\Seeder;

class IngredientsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('ingredients')->insert([
            'id' => 1,
            'food_id' => 1,
            'ingredient_id' => 4,
        ]);

        DB::table('ingredients')->insert([
            'id' => 2,
            'food_id' => 4,
            'ingredient_id' => 2,
        ]);
    }
}
