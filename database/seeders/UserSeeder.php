<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@morowali.go.id'],
            [
                'name' => 'Administrator',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'operator@morowali.go.id'],
            [
                'name' => 'Operator',
                'password' => 'password',
                'role' => User::ROLE_OPERATOR,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'viewer@morowali.go.id'],
            [
                'name' => 'Viewer',
                'password' => 'password',
                'role' => User::ROLE_VIEWER,
                'email_verified_at' => now(),
            ]
        );

        $this->seedDemoUsers();
    }

    private function seedDemoUsers(): void
    {
        $demo = [
            ['name' => 'Budi Santoso', 'email' => 'budi@morowali.go.id', 'role' => User::ROLE_OPERATOR],
            ['name' => 'Siti Rahayu', 'email' => 'siti@morowali.go.id', 'role' => User::ROLE_OPERATOR],
            ['name' => 'Agus Wijaya', 'email' => 'agus@morowali.go.id', 'role' => User::ROLE_OPERATOR],
            ['name' => 'Dewi Lestari', 'email' => 'dewi@morowali.go.id', 'role' => User::ROLE_VIEWER],
            ['name' => 'Rudi Hartono', 'email' => 'rudi@morowali.go.id', 'role' => User::ROLE_VIEWER],
            ['name' => 'Nur Aini', 'email' => 'nur@morowali.go.id', 'role' => User::ROLE_ADMIN],
        ];

        foreach ($demo as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => 'password',
                    'role' => $user['role'],
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
