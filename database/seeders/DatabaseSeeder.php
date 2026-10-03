<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ShopCategory;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Country;
use App\Models\Currency;


// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        /* User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]); */
        $this->call([
        ShopCategorySeeder::class,
        CategorySeeder::class,
        SettingsSeeder::class,
        CurrenciesSeeder::class,
        CountriesSeeder::class,
        PaymentGatewaysSeeder::class,


    ]);
    }
}
