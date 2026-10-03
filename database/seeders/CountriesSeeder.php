<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountriesSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            // UEMOA (priorité 1)
            ['code' => 'ML', 'name' => 'Mali', 'name_en' => 'Mali', 'currency_code' => 'XOF', 'phone_code' => '+223', 'flag' => '🇲🇱', 'region' => 'West Africa'],
            ['code' => 'CI', 'name' => 'Côte d\'Ivoire', 'name_en' => 'Ivory Coast', 'currency_code' => 'XOF', 'phone_code' => '+225', 'flag' => '🇨🇮', 'region' => 'West Africa'],
            ['code' => 'SN', 'name' => 'Sénégal', 'name_en' => 'Senegal', 'currency_code' => 'XOF', 'phone_code' => '+221', 'flag' => '🇸🇳', 'region' => 'West Africa'],
            ['code' => 'BF', 'name' => 'Burkina Faso', 'name_en' => 'Burkina Faso', 'currency_code' => 'XOF', 'phone_code' => '+226', 'flag' => '🇧🇫', 'region' => 'West Africa'],
            ['code' => 'BJ', 'name' => 'Bénin', 'name_en' => 'Benin', 'currency_code' => 'XOF', 'phone_code' => '+229', 'flag' => '🇧🇯', 'region' => 'West Africa'],
            ['code' => 'TG', 'name' => 'Togo', 'name_en' => 'Togo', 'currency_code' => 'XOF', 'phone_code' => '+228', 'flag' => '🇹🇬', 'region' => 'West Africa'],
            ['code' => 'NE', 'name' => 'Niger', 'name_en' => 'Niger', 'currency_code' => 'XOF', 'phone_code' => '+227', 'flag' => '🇳🇪', 'region' => 'West Africa'],
            ['code' => 'GW', 'name' => 'Guinée-Bissau', 'name_en' => 'Guinea-Bissau', 'currency_code' => 'XOF', 'phone_code' => '+245', 'flag' => '🇬🇼', 'region' => 'West Africa'],
            ['code' => 'GN', 'name' => 'Guinée', 'name_en' => 'Guinea', 'currency_code' => 'XOF', 'phone_code' => '+224', 'flag' => '🇬🇳', 'region' => 'West Africa'],
            
            // Afrique (autres)
            ['code' => 'NG', 'name' => 'Nigeria', 'name_en' => 'Nigeria', 'currency_code' => 'NGN', 'phone_code' => '+234', 'flag' => '🇳🇬', 'region' => 'West Africa'],
            ['code' => 'GH', 'name' => 'Ghana', 'name_en' => 'Ghana', 'currency_code' => 'GHS', 'phone_code' => '+233', 'flag' => '🇬🇭', 'region' => 'West Africa'],
            ['code' => 'KE', 'name' => 'Kenya', 'name_en' => 'Kenya', 'currency_code' => 'KES', 'phone_code' => '+254', 'flag' => '🇰🇪', 'region' => 'East Africa'],
            ['code' => 'ZA', 'name' => 'Afrique du Sud', 'name_en' => 'South Africa', 'currency_code' => 'ZAR', 'phone_code' => '+27', 'flag' => '🇿🇦', 'region' => 'Southern Africa'],
            ['code' => 'MA', 'name' => 'Maroc', 'name_en' => 'Morocco', 'currency_code' => 'MAD', 'phone_code' => '+212', 'flag' => '🇲🇦', 'region' => 'North Africa'],
            ['code' => 'EG', 'name' => 'Égypte', 'name_en' => 'Egypt', 'currency_code' => 'EGP', 'phone_code' => '+20', 'flag' => '🇪🇬', 'region' => 'North Africa'],
            
            // Europe & International
            ['code' => 'FR', 'name' => 'France', 'name_en' => 'France', 'currency_code' => 'EUR', 'phone_code' => '+33', 'flag' => '🇫🇷', 'region' => 'Europe'],
            ['code' => 'BE', 'name' => 'Belgique', 'name_en' => 'Belgium', 'currency_code' => 'EUR', 'phone_code' => '+32', 'flag' => '🇧🇪', 'region' => 'Europe'],
            ['code' => 'DE', 'name' => 'Allemagne', 'name_en' => 'Germany', 'currency_code' => 'EUR', 'phone_code' => '+49', 'flag' => '🇩🇪', 'region' => 'Europe'],
            ['code' => 'ES', 'name' => 'Espagne', 'name_en' => 'Spain', 'currency_code' => 'EUR', 'phone_code' => '+34', 'flag' => '🇪🇸', 'region' => 'Europe'],
            ['code' => 'IT', 'name' => 'Italie', 'name_en' => 'Italy', 'currency_code' => 'EUR', 'phone_code' => '+39', 'flag' => '🇮🇹', 'region' => 'Europe'],
            ['code' => 'GB', 'name' => 'Royaume-Uni', 'name_en' => 'United Kingdom', 'currency_code' => 'GBP', 'phone_code' => '+44', 'flag' => '🇬🇧', 'region' => 'Europe'],
            ['code' => 'CH', 'name' => 'Suisse', 'name_en' => 'Switzerland', 'currency_code' => 'CHF', 'phone_code' => '+41', 'flag' => '🇨🇭', 'region' => 'Europe'],
            ['code' => 'US', 'name' => 'États-Unis', 'name_en' => 'United States', 'currency_code' => 'USD', 'phone_code' => '+1', 'flag' => '🇺🇸', 'region' => 'North America'],
            ['code' => 'CA', 'name' => 'Canada', 'name_en' => 'Canada', 'currency_code' => 'CAD', 'phone_code' => '+1', 'flag' => '🇨🇦', 'region' => 'North America'],
            ['code' => 'CN', 'name' => 'Chine', 'name_en' => 'China', 'currency_code' => 'CNY', 'phone_code' => '+86', 'flag' => '🇨🇳', 'region' => 'Asia'],
            ['code' => 'JP', 'name' => 'Japon', 'name_en' => 'Japan', 'currency_code' => 'JPY', 'phone_code' => '+81', 'flag' => '🇯🇵', 'region' => 'Asia'],
            ['code' => 'AE', 'name' => 'Émirats arabes unis', 'name_en' => 'United Arab Emirates', 'currency_code' => 'AED', 'phone_code' => '+971', 'flag' => '🇦🇪', 'region' => 'Middle East'],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(['code' => $country['code']], $country);
        }

        $this->command->info('✅ ' . count($countries) . ' pays créés.');
    }
}
