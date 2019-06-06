<?php

use Illuminate\Database\Seeder;

class LinksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('links')->insert([
            'id' => 1,
            'user_id' => 1,
            'linkable_type' => 'App\Food',
            'linkable_id' => 1,
            'code' => '4de213',
            'expires_at' => date("Y-m-d H:i:s", 1597839633)
        ]);

        DB::table('links')->insert([
            'id' => 2,
            'user_id' => 1,
            'linkable_type' => 'App\Food',
            'linkable_id' => 1,
            'code' => '3ea12b',
            'expires_at' => date("Y-m-d H:i:s", 1187839633)
        ]);

        DB::table('links')->insert([
            'id' => 3,
            'user_id' => 1,
            'linkable_type' => 'App\Food',
            'linkable_id' => 1,
            'code' => 'fa32b4',
            'expires_at' => date("Y-m-d H:i:s", 1387839633)
        ]);

        DB::table('links')->insert([
            'id' => 4,
            'user_id' => 1,
            'linkable_type' => 'App\Food',
            'linkable_id' => 1,
            'code' => 'dc2a34',
            'expires_at' => null
        ]);

        DB::table('links')->insert([
            'id' => 5,
            'user_id' => 1,
            'linkable_type' => 'App\Food',
            'linkable_id' => 1,
            'code' => 'a344cd',
            'expires_at' => date("Y-m-d H:i:s", 1427839633)
        ]);
    }
}
