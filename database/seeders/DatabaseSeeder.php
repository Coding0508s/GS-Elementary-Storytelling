<?php

namespace Database\Seeders;

use App\Models\User;
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

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // 관리자 및 심사위원 계정 생성
        $this->call([
            AdminSeeder::class,
            JudgeSeeder::class,
            UpdateAdminRolesSeeder::class,
        ]);
    }
}
