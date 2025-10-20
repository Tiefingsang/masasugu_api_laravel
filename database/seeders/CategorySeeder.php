<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Électronique',
                'description' => 'Téléphones, ordinateurs, accessoires, etc.',
                'image' => 'uploads/categories/electronique.jpg',
                'icon' => 'devices',
            ],
            [
                'name' => 'Téléphones & Tablettes',
                'description' => 'Smartphones, tablettes et accessoires mobiles.',
                'image' => 'uploads/categories/telephones.jpg',
                'icon' => 'smartphone',
            ],
            [
                'name' => 'Informatique',
                'description' => 'Ordinateurs, imprimantes, accessoires et périphériques.',
                'image' => 'uploads/categories/informatique.jpg',
                'icon' => 'computer',
            ],
            [
                'name' => 'Mode Homme',
                'description' => 'Vêtements, chaussures et accessoires pour homme.',
                'image' => 'uploads/categories/mode-homme.jpg',
                'icon' => 'man',
            ],
            [
                'name' => 'Mode Femme',
                'description' => 'Vêtements, sacs, bijoux et accessoires pour femme.',
                'image' => 'uploads/categories/mode-femme.jpg',
                'icon' => 'woman',
            ],
            [
                'name' => 'Chaussures',
                'description' => 'Chaussures pour hommes, femmes et enfants.',
                'image' => 'uploads/categories/chaussures.jpg',
                'icon' => 'storefront',
            ],
            [
                'name' => 'Beauté & Santé',
                'description' => 'Produits de beauté, maquillage, soins du corps, santé.',
                'image' => 'uploads/categories/beaute.jpg',
                'icon' => 'spa',
            ],
            [
                'name' => 'Maison & Décoration',
                'description' => 'Meubles, décoration, vaisselle et linge de maison.',
                'image' => 'uploads/categories/maison.jpg',
                'icon' => 'home',
            ],
            [
                'name' => 'Électroménager',
                'description' => 'Réfrigérateurs, cuisinières, ventilateurs, climatiseurs.',
                'image' => 'uploads/categories/electromenager.jpg',
                'icon' => 'kitchen',
            ],
            [
                'name' => 'Supermarché',
                'description' => 'Produits alimentaires et ménagers du quotidien.',
                'image' => 'uploads/categories/supermarche.jpg',
                'icon' => 'shopping_cart',
            ],
            [
                'name' => 'Bébés & Enfants',
                'description' => 'Jouets, vêtements, couches et produits pour bébé.',
                'image' => 'uploads/categories/bebe.jpg',
                'icon' => 'child_care',
            ],
            [
                'name' => 'Sport & Loisirs',
                'description' => 'Articles de sport, vélos, équipements et loisirs.',
                'image' => 'uploads/categories/sport.jpg',
                'icon' => 'sports_soccer',
            ],
            [
                'name' => 'Automobile & Moto',
                'description' => 'Pièces détachées, huiles, accessoires et outils.',
                'image' => 'uploads/categories/auto.jpg',
                'icon' => 'directions_car',
            ],
            [
                'name' => 'Énergie & Solaire',
                'description' => 'Panneaux solaires, batteries, lampes et onduleurs.',
                'image' => 'uploads/categories/solaire.jpg',
                'icon' => 'wb_sunny',
            ],
            [
                'name' => 'Bricolage & Outils',
                'description' => 'Matériel de construction, outils et quincaillerie.',
                'image' => 'uploads/categories/bricolage.jpg',
                'icon' => 'build',
            ],
            [
                'name' => 'Livres & Papeterie',
                'description' => 'Livres, cahiers, stylos et fournitures scolaires.',
                'image' => 'uploads/categories/livres.jpg',
                'icon' => 'menu_book',
            ],
            [
                'name' => 'Jeux & Consoles',
                'description' => 'Consoles, jeux vidéo et accessoires gamers.',
                'image' => 'uploads/categories/jeux.jpg',
                'icon' => 'sports_esports',
            ],
            [
                'name' => 'Instruments de musique',
                'description' => 'Guitares, pianos, batteries, instruments africains.',
                'image' => 'uploads/categories/musique.jpg',
                'icon' => 'music_note',
            ],
            [
                'name' => 'Agriculture',
                'description' => 'Équipements agricoles, engrais, semences et outils.',
                'image' => 'uploads/categories/agriculture.jpg',
                'icon' => 'grass',
            ],
            [
                'name' => 'Animaux & Accessoires',
                'description' => 'Nourriture, cages et accessoires pour animaux.',
                'image' => 'uploads/categories/animaux.jpg',
                'icon' => 'pets',
            ],
            [
                'name' => 'Artisanat Africain',
                'description' => 'Objets d’art, tissus, sculptures et bijoux africains.',
                'image' => 'uploads/categories/artisanat.jpg',
                'icon' => 'palette',
            ],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'slug' => Str::slug($cat['name']),
                    'description' => $cat['description'],
                    'image' => $cat['image'],
                    'icon' => $cat['icon'],
                ]
            );
        }
    }
}
