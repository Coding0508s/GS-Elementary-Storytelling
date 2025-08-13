<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class ChangeAdminPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:password {username} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '관리자 또는 심사위원의 비밀번호를 변경합니다';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username');
        $password = $this->argument('password');

        $admin = Admin::where('username', $username)->first();

        if (!$admin) {
            $this->error("사용자 '{$username}'을(를) 찾을 수 없습니다.");
            return 1;
        }

        // 직접 해시를 생성하여 저장 (setPasswordAttribute 메서드 우회)
        $admin->update([
            'password' => Hash::make($password)
        ]);

        $this->info("✅ '{$username}' 계정의 비밀번호가 성공적으로 변경되었습니다.");
        $this->info("새 비밀번호: {$password}");
        
        return 0;
    }
}
