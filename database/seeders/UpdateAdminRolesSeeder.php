<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;

class UpdateAdminRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 기존 admin 계정을 관리자로 설정
        Admin::where('username', 'admin')->update(['role' => 'admin']);
        
        // judge1~judge5 계정을 심사위원으로 설정
        for ($i = 1; $i <= 5; $i++) {
            Admin::where('username', "judge{$i}")->update(['role' => 'judge']);
        }
        
        // role이 설정되지 않은 계정들을 기본적으로 심사위원으로 설정
        Admin::whereNull('role')->update(['role' => 'judge']);
    }
}
