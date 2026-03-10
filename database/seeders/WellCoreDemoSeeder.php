<?php

namespace Database\Seeders;

use App\Models\ClientXp;
use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WellCoreDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Superadmin — equivalente a daniel.esparza en producción
        User::firstOrCreate(
            ['email' => 'daniel.esparza@wellcorefitness.com'],
            [
                'name'        => 'Daniel Esparza',
                'password'    => Hash::make('RISE2026Admin!SuperPower'),
                'role'        => 'superadmin',
                'status'      => 'activo',
                'plan'        => null,
                'client_code' => null,
            ]
        );

        // Coach principal — equivalente a coachsilvia en producción
        $coach = User::firstOrCreate(
            ['email' => 'coachsilvia@wellcorefitness.com'],
            [
                'name'        => 'Silvia Carvajal',
                'password'    => Hash::make('Coach2026!'),
                'role'        => 'coach',
                'status'      => 'activo',
                'plan'        => null,
                'client_code' => null,
            ]
        );

        // Clientes demo — equivalentes a los clientes de producción
        $clientsData = [
            [
                'name'   => 'Carlos Rodriguez',
                'email'  => 'carlos.rodriguez@email.com',
                'plan'   => 'elite',
                'xp'     => 850,
                'level'  => 4,
                'streak' => 12,
            ],
            [
                'name'   => 'María García',
                'email'  => 'maria.garcia@email.com',
                'plan'   => 'metodo',
                'xp'     => 420,
                'level'  => 3,
                'streak' => 5,
            ],
            [
                'name'   => 'Juan Pérez',
                'email'  => 'juan.perez@email.com',
                'plan'   => 'esencial',
                'xp'     => 120,
                'level'  => 1,
                'streak' => 2,
            ],
        ];

        foreach ($clientsData as $data) {
            $client = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => Hash::make('Client2026!'),
                    'role'     => 'client',
                    'plan'     => $data['plan'],
                    'coach_id' => $coach->id,
                    'status'   => 'activo',
                ]
            );

            if ($client->wasRecentlyCreated) {
                ClientXp::create([
                    'user_id'            => $client->id,
                    'xp_total'           => $data['xp'],
                    'level'              => $data['level'],
                    'streak_days'        => $data['streak'],
                    'last_activity_date' => now()->subDays(rand(0, 1)),
                ]);

                ClientProfile::firstOrCreate(['user_id' => $client->id]);
            }
        }

        // Cliente real — Daniel Esparza (analyticfitness)
        $realClient = User::firstOrCreate(
            ['email' => 'analyticfitness@gmail.com'],
            [
                'name'     => 'Daniel Esparza',
                'password' => Hash::make('KingLord6962'),
                'role'     => 'client',
                'plan'     => 'elite',
                'coach_id' => $coach->id,
                'status'   => 'activo',
            ]
        );
        if ($realClient->wasRecentlyCreated) {
            ClientXp::create([
                'user_id'            => $realClient->id,
                'xp_total'           => 0,
                'level'              => 1,
                'streak_days'        => 0,
                'last_activity_date' => now(),
            ]);
            ClientProfile::firstOrCreate(['user_id' => $realClient->id]);
        }

        $this->command->info('WellCore demo data seeded: 1 admin, 1 coach, 3 clients + 1 real client');
    }
}
