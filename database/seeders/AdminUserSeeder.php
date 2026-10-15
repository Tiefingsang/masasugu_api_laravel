<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * 🎯 Créer le compte administrateur principal
     */
    public function run(): void
    {
        $email = 'tiefingsangare86@gmail.com';

        // Vérifier si l'admin existe déjà
        $existing = User::where('email', $email)->first();

        if ($existing) {
            $this->command->warn('⚠️  Admin existe déjà : ' . $existing->email);
            $this->command->info('   Rôle actuel : ' . $existing->role);

            // Forcer le rôle admin si ce n'est pas le cas
            if ($existing->role !== 'admin') {
                $existing->update([
                    'role' => 'admin',
                    'status' => 'active',
                    'is_verified' => 1,
                ]);
                $this->command->info('   ✅ Rôle corrigé en "admin"');
            }

            return;
        }

        // Créer l'admin
        $admin = User::create([
            'name'              => 'Tiefing Sangare',
            'email'             => $email,
            'password'          => 'Tiefing6689-',   // ⚠️ Auto-hashé par le cast 'hashed'
            'role'              => 'admin',
            'status'            => 'active',
            'is_verified'       => 1,
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Admin créé avec succès !');
        $this->command->info('   Email : ' . $admin->email);
        $this->command->info('   Rôle  : ' . $admin->role);
        $this->command->info('   ID    : ' . $admin->id);
        $this->command->info('   Slug  : ' . $admin->slug);
    }
}
