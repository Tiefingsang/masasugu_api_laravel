<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrenciesSeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            // Afrique de l'Ouest (UEMOA)
            ['code' => 'XOF', 'name' => 'Franc CFA (UEMOA)', 'name_en' => 'CFA Franc (BCEAO)', 'symbol' => 'FCFA', 'decimals' => 0],
            // Afrique Centrale (CEMAC)
            ['code' => 'XAF', 'name' => 'Franc CFA (CEMAC)', 'name_en' => 'CFA Franc (BEAC)', 'symbol' => 'FCFA', 'decimals' => 0],
            // Afrique (autres)
            ['code' => 'NGN', 'name' => 'Naira nigérian', 'name_en' => 'Nigerian Naira', 'symbol' => '₦', 'decimals' => 2],
            ['code' => 'GHS', 'name' => 'Cedi ghanéen', 'name_en' => 'Ghanaian Cedi', 'symbol' => 'GH₵', 'decimals' => 2],
            ['code' => 'KES', 'name' => 'Shilling kényan', 'name_en' => 'Kenyan Shilling', 'symbol' => 'KSh', 'decimals' => 2],
            ['code' => 'ZAR', 'name' => 'Rand sud-africain', 'name_en' => 'South African Rand', 'symbol' => 'R', 'decimals' => 2],
            ['code' => 'MAD', 'name' => 'Dirham marocain', 'name_en' => 'Moroccan Dirham', 'symbol' => 'DH', 'decimals' => 2],
            ['code' => 'EGP', 'name' => 'Livre égyptienne', 'name_en' => 'Egyptian Pound', 'symbol' => 'E£', 'decimals' => 2],
            // International
            ['code' => 'EUR', 'name' => 'Euro', 'name_en' => 'Euro', 'symbol' => '€', 'decimals' => 2],
            ['code' => 'USD', 'name' => 'Dollar américain', 'name_en' => 'US Dollar', 'symbol' => '$', 'decimals' => 2],
            ['code' => 'GBP', 'name' => 'Livre sterling', 'name_en' => 'British Pound', 'symbol' => '£', 'decimals' => 2],
            ['code' => 'CAD', 'name' => 'Dollar canadien', 'name_en' => 'Canadian Dollar', 'symbol' => 'C$', 'decimals' => 2],
            ['code' => 'CHF', 'name' => 'Franc suisse', 'name_en' => 'Swiss Franc', 'symbol' => 'CHF', 'decimals' => 2],
            ['code' => 'CNY', 'name' => 'Yuan chinois', 'name_en' => 'Chinese Yuan', 'symbol' => '¥', 'decimals' => 2],
            ['code' => 'JPY', 'name' => 'Yen japonais', 'name_en' => 'Japanese Yen', 'symbol' => '¥', 'decimals' => 0],
            ['code' => 'AED', 'name' => 'Dirham des Émirats', 'name_en' => 'UAE Dirham', 'symbol' => 'د.إ', 'decimals' => 2],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(['code' => $currency['code']], $currency);
        }

        $this->command->info('✅ ' . count($currencies) . ' devises créées.');
    }
}
