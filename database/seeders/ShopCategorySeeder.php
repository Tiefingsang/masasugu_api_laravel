<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShopCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Mode & Beauté',
                'slug' => Str::slug('Mode & Beauté'),
                'description' => 'Vêtements, accessoires, produits de beauté, et bien plus.',
            ],
            [
                'name' => 'Électronique',
                'slug' => Str::slug('Électronique'),
                'description' => 'Téléphones, ordinateurs, accessoires et appareils électroniques.',
            ],
            [
                'name' => 'Alimentation',
                'slug' => Str::slug('Alimentation'),
                'description' => 'Produits alimentaires, boissons, et articles de cuisine.',
            ],
            [
                'name' => 'Maison & Déco',
                'slug' => Str::slug('Maison & Déco'),
                'description' => 'Articles pour la maison, meubles, décorations et jardin.',
            ],
            [
                'name' => 'Santé & Bien-être',
                'slug' => Str::slug('Santé & Bien-être'),
                'description' => 'Produits de santé, hygiène, et bien-être.',
            ],
            [
                'name' => 'Autre',
                'slug' => Str::slug('Autre'),
                'description' => 'Autres types de commerces ou services divers.',
            ],
        ];

        DB::table('shop_categories')->insert($categories);
    }
}
