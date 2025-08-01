<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 기존 관리자가 있는지 확인
        if (Admin::where('username', 'admin')->exists()) {
            $this->command->info('관리자 계정이 이미 존재합니다.');
            return;
        }

        // 초기 관리자 계정 생성
        Admin::create([
            'username' => 'admin',
            'password' => 'admin123', // Admin 모델에서 자동으로 해시됨
            'name' => '관리자',
            'email' => 'admin@gs-education.com',
            'is_active' => true
        ]);

        $this->command->info('초기 관리자 계정이 생성되었습니다.');
        $this->command->info('아이디: admin');
        $this->command->info('비밀번호: admin123');
        $this->command->warn('보안을 위해 첫 로그인 후 비밀번호를 변경해주세요.');
    }
}
