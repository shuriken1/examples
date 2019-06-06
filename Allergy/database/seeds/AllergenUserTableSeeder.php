<?php

use Illuminate\Database\Seeder;

class AllergenUserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('allergen_user')->insert([
            'id' => 1,
            'user_id' => 1,
            'allergen_id' => 10,
            'allergy_amount' => '1',
        ]);

        DB::table('allergen_user')->insert([
            'id' => 4,
            'user_id' => 1,
            'allergen_id' => 2,
            'preference_amount' => '0',
        ]);

        DB::table('allergen_user')->insert([
            'id' => 2,
            'user_id' => 1,
            'allergen_id' => 1,
            'allergy_amount' => '2',
        ]);

        DB::table('allergen_user')->insert([
            'id' => 3,
            'user_id' => 2,
            'allergen_id' => 2,
            'allergy_amount' => '1',
        ]);
    }
}
