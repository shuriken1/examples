<?php

use Illuminate\Database\Seeder;
use App\Food;

class FoodsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $foods = [
            ['id' => 1, 'owner_type' => 'App\Organisation', 'owner_id' => 1, 'public' => false, 'name' => 'Chocolate brownie', 'description' => 'Nom nom nom'],
            ['id' => 2, 'owner_type' => 'App\Organisation', 'owner_id' => 1, 'public' => false, 'name' => 'Tuna sandwich', 'description' => 'Fishy.'],
            ['id' => 3, 'owner_type' => 'App\Organisation', 'owner_id' => 1, 'public' => false, 'name' => 'Beans on toast', 'description' => 'Yum.']
        ];

        Food::insert($foods);

        DB::table('foods')->insert([
            'id' => 4,
            'owner_type' => 'App\Organisation',
            'owner_id' => 1, 
            'public' => false,
            'barcode' => '1122334455',
            'name' => 'Peanut Butter',
        ]);

        DB::table('foods')->insert([
            'id' => 5,
            'owner_type' => 'App\Organisation',
            'owner_id' => 1, 
            'public' => false,
            'barcode' => '5060023979498',
            'name' => 'Gü Puds Güzillionaires',
        ]);

        DB::table('foods')->insert([
            'id' => 6,
            'owner_type' => 'App\Organisation',
            'owner_id' => 1, 
            'public' => false,
            'name' => 'White Chocolate & Raspberry Blondie',
        ]);
    }
}
