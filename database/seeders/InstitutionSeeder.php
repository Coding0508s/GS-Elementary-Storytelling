<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Institution;
use App\Models\VideoSubmission;
use Illuminate\Support\Facades\DB;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('기존 영상 제출 데이터에서 기관명 추출 중...');
        
        // 기존 video_submissions 테이블에서 고유한 기관명 추출
        $institutions = VideoSubmission::select('institution_name')
            ->groupBy('institution_name')
            ->orderBy('institution_name')
            ->get();

        $this->command->info("발견된 기관 수: " . $institutions->count());

        // 각 기관명을 institutions 테이블에 삽입
        foreach ($institutions as $index => $submission) {
            $institutionName = trim($submission->institution_name);
            
            if (empty($institutionName)) {
                continue;
            }

            // 기관 유형 자동 추정
            $type = $this->guessInstitutionType($institutionName);
            
            try {
                Institution::updateOrCreate(
                    ['name' => $institutionName],
                    [
                        'type' => $type,
                        'is_active' => true,
                        'sort_order' => $index,
                        'description' => "기존 영상 제출 데이터에서 자동 추가됨"
                    ]
                );
                
                $this->command->info("✓ {$institutionName} ({$type})");
                
            } catch (\Exception $e) {
                $this->command->error("✗ {$institutionName} 추가 실패: " . $e->getMessage());
            }
        }

        $this->command->info('기관명 데이터 마이그레이션 완료!');
    }

    /**
     * 기관명으로부터 기관 유형 추정
     */
    private function guessInstitutionType($name)
    {
        $name = strtolower($name);
        
        if (str_contains($name, '초등학교') || str_contains($name, '초등')) {
            return 'elementary';
        }
        
        if (str_contains($name, '중학교') || str_contains($name, '중등')) {
            return 'middle';
        }
        
        if (str_contains($name, '고등학교') || str_contains($name, '고등')) {
            return 'high';
        }
        
        if (str_contains($name, '유치원')) {
            return 'kindergarten';
        }
        
        if (str_contains($name, '대학교') || str_contains($name, '대학')) {
            return 'university';
        }
        
        if (str_contains($name, '학원') || str_contains($name, '어학원')) {
            return 'academy';
        }
        
        return 'other';
    }
}
