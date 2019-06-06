<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UsersTableSeeder::class);
        $this->call(AllergensTableSeeder::class);
        $this->call(FoodsTableSeeder::class);
        $this->call(AllergenFoodTableSeeder::class);
        //$this->call(RetailersTableSeeder::class);
        $this->call(OrganisationsTableSeeder::class);
        $this->call(MenusTableSeeder::class);
        $this->call(FoodMenuTableSeeder::class);
        //$this->call(AllergiesTableSeeder::class);
        $this->call(AllergenUserTableSeeder::class);
        $this->call(IngredientsTableSeeder::class);
        $this->call(LinksTableSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);
    }
}
