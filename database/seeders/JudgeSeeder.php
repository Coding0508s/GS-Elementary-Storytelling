<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;

class JudgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $judges = [
            [
                'username' => 'judge1',
                'password' => 'judge123',
                'name' => '김심사위원',
                'email' => 'judge1@gs-education.com',
                'is_active' => true
            ],
            [
                'username' => 'judge2',
                'password' => 'judge123',
                'name' => '이심사위원',
                'email' => 'judge2@gs-education.com',
                'is_active' => true
            ],
            [
                'username' => 'judge3',
                'password' => 'judge123',
                'name' => '박심사위원',
                'email' => 'judge3@gs-education.com',
                'is_active' => true
            ],
            [
                'username' => 'judge4',
                'password' => 'judge123',
                'name' => '최심사위원',
                'email' => 'judge4@gs-education.com',
                'is_active' => true
            ],
            [
                'username' => 'judge5',
                'password' => 'judge123',
                'name' => '정심사위원',
                'email' => 'judge5@gs-education.com',
                'is_active' => true
            ]
        ];

        foreach ($judges as $judge) {
            // 이미 존재하는지 확인
            if (!Admin::where('username', $judge['username'])->exists()) {
                Admin::create($judge);
                $this->command->info("심사위원 계정 생성: {$judge['username']} - {$judge['name']}");
            } else {
                $this->command->info("심사위원 계정이 이미 존재합니다: {$judge['username']}");
            }
        }

        $this->command->info('심사위원 계정 생성이 완료되었습니다.');
        $this->command->info('모든 심사위원의 비밀번호는: judge123');
        $this->command->warn('보안을 위해 첫 로그인 후 비밀번호를 변경해주세요.');
    }
} 