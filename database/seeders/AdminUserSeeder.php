<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            ['email' => 'avesani@spaggiari.eu', 'first_name' => 'Riccardo', 'last_name' => 'Avesani'],
            ['email' => 'pippa@spaggiari.eu', 'first_name' => 'Nicola', 'last_name' => 'Pippa'],
            ['email' => 'bouchachia@spaggiari.eu', 'first_name' => 'Hicham', 'last_name' => 'Bouchachia'],
            ['email' => 'sansone@spaggiari.eu', 'first_name' => 'Robert', 'last_name' => 'Sansone'],
            ['email' => 'luciani@spaggiari.eu', 'first_name' => 'Camilla', 'last_name' => 'Luciani'],
            ['email' => 'toschi@spaggiari.eu', 'first_name' => 'Fabio', 'last_name' => 'Toschi'],
            ['email' => 'grazioli@spaggiari.eu', 'first_name' => 'Alessandro', 'last_name' => 'Grazioli'],
        ];

        $defaultPassword = Hash::make('Admin2026!');

        foreach ($admins as $admin) {
            User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'first_name' => $admin['first_name'],
                    'last_name' => $admin['last_name'],
                    'company' => 'Gruppo Spaggiari Parma',
                    'password' => $defaultPassword,
                    'role' => 'admin|admin',
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'force_renew_password' => true,
                ]
            );
        }

        // Utenti di test
        User::updateOrCreate(
            ['email' => 'redattore@test.it'],
            [
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'company' => 'La Fabbrica',
                'password' => $defaultPassword,
                'role' => 'internal|redattore',
                'status' => 'active',
                'email_verified_at' => now(),
                'force_renew_password' => false,
            ]
        );

        User::updateOrCreate(
            ['email' => 'grafico@test.it'],
            [
                'first_name' => 'Giuseppe',
                'last_name' => 'Verdi',
                'company' => 'La Fabbrica',
                'password' => $defaultPassword,
                'role' => 'internal|grafico',
                'status' => 'active',
                'email_verified_at' => now(),
                'force_renew_password' => false,
            ]
        );
    }
}
