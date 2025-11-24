<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use App\Models\Admin;
use App\Models\VideoSubmission;
use App\Models\Evaluation;
use App\Models\VideoAssignment;
use App\Models\Institution;
use App\Models\AiEvaluation;
use App\Models\SiteSetting;
use App\Services\OpenAiService;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AdminController extends Controller
{
    public function __construct()
    {
        // 인증은 routes/web.php에서 middleware 그룹으로 처리됨
    }

    /**
     * 로그인 페이지 표시
     */
    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        
        return view('admin.login');
    }

    /**
     * 로그인 처리
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
            'role' => 'required|in:admin,judge'
        ], [
            'username.required' => '아이디를 입력해주세요.',
            'password.required' => '비밀번호를 입력해주세요.',
            'role.required' => '역할을 선택해주세요.',
            'role.in' => '올바른 역할을 선택해주세요.'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('username', 'password');

        // 사용자 존재 여부 확인
        $admin = Admin::where('username', $credentials['username'])->first();
        
        if (!$admin) {
            return back()->with('error', '존재하지 않는 사용자입니다.')
                        ->withInput();
        }
        
        if (!$admin->is_active) {
            return back()->with('error', '비활성화된 계정입니다.')
                        ->withInput();
        }

        if (Auth::guard('admin')->attempt($credentials)) {
            $admin = Auth::guard('admin')->user();
            $admin->updateLastLogin();
            
            // 선택된 역할에 따라 다른 대시보드로 리다이렉트
            if ($request->role === 'judge') {
                return redirect()->route('judge.dashboard')
                               ->with('success', '심사위원 페이지에 오신 것을 환영합니다!');
            } else {
                return redirect()->route('admin.dashboard')
                               ->with('success', '관리자 페이지에 오신 것을 환영합니다!');
            }
        }

        return back()->with('error', '비밀번호가 올바르지 않습니다.')
                    ->withInput();
    }

    /**
     * 로그아웃
     */
    public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect()->route('admin.login')
                        ->with('success', '성공적으로 로그아웃되었습니다.');
    }

    /**
     * 관리자 대시보드
     */
    public function dashboard()
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $totalSubmissions = VideoSubmission::count();
        $evaluatedSubmissions = VideoSubmission::whereHas('evaluation')->count();
        $assignedSubmissions = VideoSubmission::whereHas('assignment')->count();
        $pendingSubmissions = $totalSubmissions - $assignedSubmissions;
        
        // 검색어 가져오기
        $searchQuery = request()->get('search', '');
        
        // 최근 업로드된 영상들 (검색 기능 포함)
        $recentSubmissionsQuery = VideoSubmission::with(['evaluation', 'assignment.admin']);
        
        // 검색어가 있으면 필터링
        if (!empty($searchQuery)) {
            $recentSubmissionsQuery->where(function($query) use ($searchQuery) {
                $query->where('student_name_korean', 'like', '%' . $searchQuery . '%')
                      ->orWhere('student_name_english', 'like', '%' . $searchQuery . '%')
                      ->orWhere('institution_name', 'like', '%' . $searchQuery . '%')
                      ->orWhere('class_name', 'like', '%' . $searchQuery . '%')
                      ->orWhere('video_file_name', 'like', '%' . $searchQuery . '%');
                
                // 접수번호 검색 (데이터베이스 타입에 따라 다르게 처리)
                $driver = DB::connection()->getDriverName();
                
                // 숫자만 입력한 경우 ID로 직접 검색
                if (is_numeric($searchQuery)) {
                    $query->orWhere('id', $searchQuery);
                }
                
                // 접수번호 형식 검색 (GSK-00001 형식)
                if ($driver === 'sqlite') {
                    // SQLite는 LPAD를 지원하지 않으므로 문자열 연결과 substr 사용
                    // 'GSK-' || substr('00000' || id, -5) 형식으로 5자리 0 패딩
                    $query->orWhereRaw("('GSK-' || substr('00000' || CAST(id AS TEXT), -5)) LIKE ?", ['%' . $searchQuery . '%']);
                } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                    // MySQL/MariaDB는 LPAD 사용
                    $query->orWhereRaw("CONCAT('GSK-', LPAD(id, 5, '0')) LIKE ?", ['%' . $searchQuery . '%']);
                } elseif ($driver === 'pgsql') {
                    // PostgreSQL은 LPAD 사용
                    $query->orWhereRaw("CONCAT('GSK-', LPAD(id::text, 5, '0')) LIKE ?", ['%' . $searchQuery . '%']);
                }
            });
        }
        
        $recentSubmissions = $recentSubmissionsQuery->orderBy('created_at', 'desc')
                                                     ->paginate(10, ['*'], 'recent')
                                                     ->appends(request()->query());

        // 심사위원별 배정 현황
        $adminStats = Admin::withCount(['videoAssignments', 'evaluations'])
                          ->get()
                          ->map(function ($admin) {
                              $admin->in_progress_count = $admin->videoAssignments()
                                  ->where('status', VideoAssignment::STATUS_IN_PROGRESS)
                                  ->count();
                              return $admin;
                          });

        // 심사위원 수 계산
        $judgesCount = Admin::where('role', 'judge')->count();

        // 대회 활성화 상태 확인 (안전한 방식)
        try {
            $contestActive = SiteSetting::isContestActive();
        } catch (\Exception $e) {
            \Log::error('SiteSetting 오류: ' . $e->getMessage());
            $contestActive = true; // 기본값으로 true 설정
        }

        return view('admin.dashboard', compact(
            'totalSubmissions',
            'evaluatedSubmissions',
            'assignedSubmissions',
            'pendingSubmissions',
            'recentSubmissions',
            'adminStats',
            'judgesCount',
            'contestActive',
            'searchQuery'
        ));
    }

    /**
     * CSRF 토큰 가져오기
     */
    public function getCsrfToken(Request $request)
    {
        return response()->json([
            'csrf_token' => csrf_token()
        ]);
    }

    /**
     * 대회 활성화 상태 토글
     */
    public function toggleContestStatus(Request $request)
    {
        try {
            // SiteSetting 사용 전에 데이터베이스 연결 확인
            if (!Schema::hasTable('site_settings')) {
                throw new \Exception('site_settings 테이블이 존재하지 않습니다.');
            }
            
            $currentStatus = SiteSetting::isContestActive();
            $newValue = $currentStatus ? 'false' : 'true';
            
            // 직접 설정 업데이트
            SiteSetting::set('contest_active', $newValue, '대회 페이지 활성화 상태');
            
            $statusText = $newValue === 'true' ? '활성화' : '비활성화';
            
            return response()->json([
                'success' => true,
                'message' => "대회 페이지가 {$statusText}되었습니다.",
                'contest_active' => $newValue === 'true'
            ]);
        } catch (\Exception $e) {
            Log::error('대회 상태 토글 실패: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '대회 상태 변경에 실패했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 심사 목록
     */
    public function evaluationList(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $query = VideoSubmission::with(['evaluation', 'assignment.admin']);

        // 검색 필터
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('student_name_korean', 'like', "%{$search}%")
                  ->orWhere('student_name_english', 'like', "%{$search}%")
                  ->orWhere('institution_name', 'like', "%{$search}%");
            });
        }

        // 상태 필터
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'evaluated':
                    $query->whereHas('evaluation');
                    break;
                case 'pending':
                    $query->whereDoesntHave('evaluation');
                    break;
                case 'assigned':
                    $query->whereHas('assignment');
                    break;
                case 'unassigned':
                    $query->whereDoesntHave('assignment');
                    break;
            }
        }

        $submissions = $query->orderBy('created_at', 'asc')->paginate(20);

        return view('admin.evaluation-list', compact('submissions'));
    }

    /**
     * 심사 상세 보기
     */
    public function showEvaluation($id)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $submission = VideoSubmission::with(['evaluation', 'assignment.admin'])->findOrFail($id);

        $criteriaLabels = [
            'pronunciation_score' => '정확한 발음과 자연스러운 억양, 전달력',
            'vocabulary_score' => '올바른 어휘 및 표현 사용',
            'fluency_score' => '유창성 수준',
            'confidence_score' => '자신감, 긍정적이고 밝은 태도',
            'topic_connection_score' => '주제와 발표 내용과의 연결성',
            'structure_flow_score' => '자연스러운 구성과 흐름',
            'creativity_score' => '창의적 내용'
        ];

        // AI 평가 결과 가져오기 (완료된 것 중 아무거나)
        $aiEvaluation = AiEvaluation::where('video_submission_id', $submission->id)
                                  ->where('processing_status', AiEvaluation::STATUS_COMPLETED)
                                  ->first();

        return view('admin.evaluation-form', compact('submission', 'criteriaLabels', 'aiEvaluation'));
    }

    /**
     * 심사 결과 저장
     */
    public function storeEvaluation(Request $request, $id)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $validator = Validator::make($request->all(), [
            'pronunciation_score' => 'required|integer|min:0|max:10',
            'vocabulary_score' => 'required|integer|min:0|max:10',
            'fluency_score' => 'required|integer|min:0|max:10',
            'confidence_score' => 'required|integer|min:0|max:10',
            'comments' => 'nullable|string|max:1000'
        ], [
            'pronunciation_score.required' => '발음 점수를 입력해주세요.',
            'vocabulary_score.required' => '어휘 점수를 입력해주세요.',
            'fluency_score.required' => '유창성 점수를 입력해주세요.',
            'confidence_score.required' => '자신감 점수를 입력해주세요.',
            'pronunciation_score.min' => '점수는 0점 이상이어야 합니다.',
            'vocabulary_score.min' => '점수는 0점 이상이어야 합니다.',
            'fluency_score.min' => '점수는 0점 이상이어야 합니다.',
            'confidence_score.min' => '점수는 0점 이상이어야 합니다.',
            'pronunciation_score.max' => '점수는 10점 이하여야 합니다.',
            'vocabulary_score.max' => '점수는 10점 이하여야 합니다.',
            'fluency_score.max' => '점수는 10점 이하여야 합니다.',
            'confidence_score.max' => '점수는 10점 이하여야 합니다.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $submission = VideoSubmission::findOrFail($id);
        
        // 총점 계산
        $totalScore = $request->pronunciation_score + 
                     $request->vocabulary_score + 
                     $request->fluency_score + 
                     $request->confidence_score;

        // 평가 데이터 저장 또는 업데이트
        $evaluation = $submission->evaluation()->updateOrCreate(
            ['video_submission_id' => $submission->id],
            [
                'pronunciation_score' => $request->pronunciation_score,
                'vocabulary_score' => $request->vocabulary_score,
                'fluency_score' => $request->fluency_score,
                'confidence_score' => $request->confidence_score,
                'total_score' => $totalScore,
                'comments' => $request->comments,
                'evaluated_by' => $admin->id,
                'evaluated_at' => now()
            ]
        );

        // 배정 상태 업데이트
        if ($submission->assignment) {
            $submission->assignment->update(['status' => 'completed']);
        }

        return redirect()->route('admin.evaluation.list')
                        ->with('success', '심사 결과가 성공적으로 저장되었습니다.');
    }

    /**
     * Excel 다운로드
     */
    public function downloadExcel(Request $request)
    {
        try {
            Log::info('Excel 다운로드 시작', [
                'admin_id' => Auth::guard('admin')->id(),
                'memory_limit_before' => ini_get('memory_limit'),
                'time_limit_before' => ini_get('max_execution_time')
            ]);

            // 관리자만 접근 가능하도록 체크
            $admin = Auth::guard('admin')->user();
            if (!$admin || !$admin->isAdmin()) {
                Log::warning('Excel 다운로드 권한 없음', ['user_id' => $admin->id ?? 'none']);
                return redirect()->route('judge.dashboard')
                               ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
            }

            // 메모리 및 타임아웃 설정
            ini_set('memory_limit', '512M');
            set_time_limit(300); // 5분
            
            Log::info('PHP 설정 변경 완료', [
                'memory_limit_after' => ini_get('memory_limit'),
                'time_limit_after' => ini_get('max_execution_time')
            ]);
            
            // 출력 버퍼 정리
            if (ob_get_level()) {
                ob_end_clean();
                Log::info('출력 버퍼 정리 완료');
            }

            Log::info('데이터베이스 조회 시작');
            $submissions = VideoSubmission::with(['evaluations', 'assignments.admin'])
                                        ->orderBy('created_at', 'asc')
                                        ->get();
            
            Log::info('데이터베이스 조회 완료', [
                'submissions_count' => $submissions->count(),
                'memory_usage' => memory_get_usage(true) / 1024 / 1024 . 'MB'
            ]);

            // PhpSpreadsheet 사용
            Log::info('PhpSpreadsheet 객체 생성 시작');
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            Log::info('PhpSpreadsheet 객체 생성 완료');

            // 헤더 설정
        $headers = [
            '접수번호', '학생명(한글)', '학생명(영어)', '거주지역', '기관명', '반명', '학년', '나이',
            '학부모명', '연락처', 'Unit주제', '업로드일시', '파일명', '파일크기',
            '배정된심사위원', '심사상태', 
            '정확한 발음과 자연스러운 억양, 전달력', '올바른 어휘 및 표현 사용', '유창성 수준', 
            '자신감, 긍정적이고 밝은 태도', '주제와 발표 내용과의 연결성', '자연스러운 구성과 흐름', '창의적 내용',
            '총점', '순위', '심사코멘트', '심사완료일시'
        ];

        // 헤더 스타일 설정
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        // 헤더 추가
        foreach ($headers as $colIndex => $header) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($column . '1', $header);
            $sheet->getStyle($column . '1')->applyFromArray($headerStyle);
        }

        // 평가가 완료된 영상들의 순위를 계산 (1명의 심사위원)
        $rankedSubmissions = $submissions->filter(function ($submission) {
            return $submission->evaluations->count() >= 1;
        })->map(function ($submission) {
            $evaluation = $submission->evaluations->first();
            $totalScore = $evaluation ? $evaluation->total_score : 0;
            return (object) [
                'submission' => $submission,
                'total_score' => $totalScore,
                'created_at' => $submission->created_at
            ];
        })->sort(function ($a, $b) {
            // 1차 정렬: 총점 내림차순
            if ($a->total_score !== $b->total_score) {
                return $b->total_score <=> $a->total_score;
            }
            // 2차 정렬: 같은 점수일 때 업로드 빠른 순
            return $a->created_at <=> $b->created_at;
        })->values();

        // 순위 매핑 생성
        $rankMap = [];
        foreach ($rankedSubmissions as $index => $item) {
            $rankMap[$item->submission->id] = $index + 1;
        }

        // 데이터 추가
        $rowIndex = 2;
        foreach ($submissions as $submission) {
            $evaluations = $submission->evaluations;
            $assignments = $submission->assignments;
            
            // 심사위원 정보 (1명만)
            $judge = $assignments->count() > 0 ? $assignments[0]->admin->name : '미배정';
            
            // 평가 정보 (1개만)
            $evaluation = $evaluations->count() > 0 ? $evaluations[0] : null;
            
            // 심사 상태
            $status = '';
            if ($evaluations->count() >= 1) {
                $status = '완료';
            } else {
                $status = '미시작';
            }
            
            // 총점수 및 순위
            $totalScore = $evaluation ? $evaluation->total_score : '';
            $rank = isset($rankMap[$submission->id]) ? $rankMap[$submission->id] : '';
            
            // 기본 정보
            $sheet->setCellValue('A' . $rowIndex, $submission->receipt_number);
            $sheet->setCellValue('B' . $rowIndex, $submission->student_name_korean);
            $sheet->setCellValue('C' . $rowIndex, $submission->student_name_english);
            $sheet->setCellValue('D' . $rowIndex, $submission->region);
            $sheet->setCellValue('E' . $rowIndex, $submission->institution_name);
            $sheet->setCellValue('F' . $rowIndex, $submission->class_name);
            $sheet->setCellValue('G' . $rowIndex, $submission->grade);
            $sheet->setCellValue('H' . $rowIndex, $submission->age);
            $sheet->setCellValue('I' . $rowIndex, $submission->parent_name);
            $sheet->setCellValue('J' . $rowIndex, $submission->parent_phone);
            $sheet->setCellValue('K' . $rowIndex, $submission->unit_topic);
            $sheet->setCellValue('L' . $rowIndex, $submission->created_at->format('Y-m-d H:i:s'));
            $sheet->setCellValue('M' . $rowIndex, $submission->video_file_name);
            $sheet->setCellValue('N' . $rowIndex, $submission->getFormattedFileSizeAttribute());
            
            // 심사 정보 (1명의 심사위원)
            $sheet->setCellValue('O' . $rowIndex, $judge);
            $sheet->setCellValue('P' . $rowIndex, $status);
            
            // 점수 정보 (7개 평가 항목)
            $sheet->setCellValue('Q' . $rowIndex, $evaluation ? $evaluation->pronunciation_score : '');
            $sheet->setCellValue('R' . $rowIndex, $evaluation ? $evaluation->vocabulary_score : '');
            $sheet->setCellValue('S' . $rowIndex, $evaluation ? $evaluation->fluency_score : '');
            $sheet->setCellValue('T' . $rowIndex, $evaluation ? $evaluation->confidence_score : '');
            $sheet->setCellValue('U' . $rowIndex, $evaluation ? $evaluation->topic_connection_score : '');
            $sheet->setCellValue('V' . $rowIndex, $evaluation ? $evaluation->structure_flow_score : '');
            $sheet->setCellValue('W' . $rowIndex, $evaluation ? $evaluation->creativity_score : '');
            $sheet->setCellValue('X' . $rowIndex, $evaluation ? $evaluation->total_score : '');
            
            // 순위 및 코멘트
            $sheet->setCellValue('Y' . $rowIndex, $rank ? $rank . '위' : '');
            $sheet->setCellValue('Z' . $rowIndex, $evaluation ? $evaluation->comments : '');
            
            // 완료 일시
            $lastEvaluatedAt = $evaluation ? $evaluation->evaluated_at : null;
            $sheet->setCellValue('AA' . $rowIndex, $lastEvaluatedAt ? $lastEvaluatedAt->format('Y-m-d H:i:s') : '');
            
            $rowIndex++;
        }

        // 열 너비 자동 조정 (AA열까지)
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA'];
        foreach ($columns as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // 파일명 생성
        $filename = 'storytelling_data_' . date('Y-m-d_H-i-s') . '.xlsx';

            // Excel 파일 생성
            Log::info('Excel Writer 생성 시작');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            Log::info('Excel Writer 생성 완료');
            
            // 임시 파일에 저장
            $tempFile = storage_path('app/temp/' . $filename);
            Log::info('임시 파일 경로 설정', ['temp_file' => $tempFile]);
            
            if (!file_exists(dirname($tempFile))) {
                mkdir(dirname($tempFile), 0755, true);
                Log::info('임시 디렉토리 생성 완료');
            }
            
            Log::info('Excel 파일 저장 시작');
            $writer->save($tempFile);
            Log::info('Excel 파일 저장 완료');

            // 파일이 제대로 생성되었는지 확인
            if (!file_exists($tempFile) || filesize($tempFile) == 0) {
                Log::error('Excel 파일 생성 실패', [
                    'file_exists' => file_exists($tempFile),
                    'file_size' => file_exists($tempFile) ? filesize($tempFile) : 0
                ]);
                throw new \Exception('Excel 파일 생성에 실패했습니다.');
            }

            Log::info('Excel 파일 생성 성공', [
                'file_size' => filesize($tempFile),
                'memory_usage' => memory_get_usage(true) / 1024 / 1024 . 'MB'
            ]);

            // 파일 다운로드
            Log::info('파일 다운로드 응답 생성 시작');
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
                'Pragma' => 'public',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Excel 다운로드 오류', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Excel 파일 다운로드 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 통계 페이지
     */
    public function statistics()
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        // 전체 통계
        $totalSubmissions = VideoSubmission::count();
        // 재평가는 제외하고 일반 평가만 카운트
        $evaluatedSubmissions = VideoSubmission::whereHas('evaluations', function($q) {
            $q->where(function($query) {
                $query->where('is_reevaluation', false)
                      ->orWhereNull('is_reevaluation');
            });
        })->count();
        $assignedSubmissions = VideoSubmission::whereHas('assignment')->count();
        $pendingSubmissions = $totalSubmissions - $assignedSubmissions;

        // 기관별 통계는 아래에서 한 번만 계산

        // 심사위원별 통계
        $judgeStats = Admin::where('role', 'judge')
                          ->withCount(['videoAssignments', 'evaluations'])
                          ->get();

        // 일별 업로드 통계 (최근 30일)
        $dailyStats = VideoSubmission::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                                    ->where('created_at', '>=', now()->subDays(30))
                                    ->groupBy('date')
                                    ->orderBy('date')
                                    ->get();

        // 평균 점수 통계 (재평가 제외)
        $averageScores = Evaluation::where(function($q) {
                $q->where('is_reevaluation', false)
                  ->orWhereNull('is_reevaluation');
            })
            ->selectRaw('
                AVG(pronunciation_score) as avg_pronunciation,
                AVG(vocabulary_score) as avg_vocabulary,
                AVG(fluency_score) as avg_fluency,
                AVG(confidence_score) as avg_confidence,
                AVG(topic_connection_score) as avg_topic_connection,
                AVG(structure_flow_score) as avg_structure_flow,
                AVG(creativity_score) as avg_creativity,
                AVG(total_score) as avg_total
            ')->first();

        // 점수 분포 통계 (재평가 제외)
        $scoreDistribution = collect([]);
        if ($evaluatedSubmissions > 0) {
            $scoreDistribution = Evaluation::where(function($q) {
                    $q->where('is_reevaluation', false)
                      ->orWhereNull('is_reevaluation');
                })
                ->selectRaw('
                    CASE 
                        WHEN total_score >= 63 THEN "우수 (63-70점)"
                        WHEN total_score >= 56 THEN "양호 (56-62점)"
                        WHEN total_score >= 49 THEN "보통 (49-55점)"
                        WHEN total_score >= 42 THEN "미흡 (42-48점)"
                        ELSE "매우 미흡 (41점 이하)"
                    END as grade,
                    COUNT(*) as count
                ')
                ->groupBy('grade')
                ->orderByRaw('MIN(total_score) DESC')
                ->get()
                ->map(function ($item) {
                    return (object) [
                        'grade' => $item->grade,
                        'count' => $item->count
                    ];
                });
        }

        // 기관별 통계 (제출수 기준 정렬) - 영상별 고유 카운트 (재평가 제외)
        $institutionStats = VideoSubmission::with(['evaluations' => function($q) {
                $q->where(function($query) {
                    $query->where('is_reevaluation', false)
                          ->orWhereNull('is_reevaluation');
                });
            }])
        ->get()
        ->groupBy('institution_name')
        ->map(function ($submissions, $institutionName) {
            $submissionCount = $submissions->count(); // 실제 제출 수
            
            // 한 심사위원의 평균 점수 계산 (완전히 평가된 영상만, 재평가 제외)
            $completedEvaluations = $submissions->filter(function ($submission) {
                $generalEvaluations = $submission->evaluations->filter(function($eval) {
                    return $eval->is_reevaluation === false || $eval->is_reevaluation === null;
                });
                return $generalEvaluations->count() >= 1;
            });
            
            $avgScore = null;
            if ($completedEvaluations->count() > 0) {
                $totalScores = $completedEvaluations->map(function ($submission) {
                    $generalEval = $submission->evaluations->filter(function($eval) {
                        return $eval->is_reevaluation === false || $eval->is_reevaluation === null;
                    })->first();
                    return $generalEval ? $generalEval->total_score : 0;
                })->filter(function($score) {
                    return $score > 0;
                });
                $avgScore = $totalScores->count() > 0 ? $totalScores->avg() : null;
            }
            
            return (object) [
                'institution_name' => $institutionName,
                'submission_count' => $submissionCount,
                'avg_score' => $avgScore,
                'completed_evaluations' => $completedEvaluations->count()
            ];
        })
        ->sortByDesc('submission_count')
        ->sortByDesc('avg_score')
        ->values();

        // 학생 순위 (한 심사위원 점수 기준, 상위 20명, 재평가 제외)
        $studentRankings = VideoSubmission::select(
            'video_submissions.id',
            'video_submissions.student_name_korean as student_name',
            'video_submissions.institution_name',
            'video_submissions.class_name',
            'video_submissions.grade',
            'video_submissions.created_at'
        )
        ->with(['evaluations' => function($q) {
            $q->where(function($query) {
                $query->where('is_reevaluation', false)
                      ->orWhereNull('is_reevaluation');
            });
        }])
        ->whereHas('evaluations', function($q) {
            $q->where(function($query) {
                $query->where('is_reevaluation', false)
                      ->orWhereNull('is_reevaluation');
            });
        })
        ->get()
        ->map(function ($submission) {
            // 일반 평가만 필터링 (재평가 제외)
            $generalEvaluations = $submission->evaluations->filter(function($eval) {
                return $eval->is_reevaluation === false || $eval->is_reevaluation === null;
            });
            
            $evaluationCount = $generalEvaluations->count();
            if ($evaluationCount < 1) {
                return null; // 평가가 없는 경우 제외
            }
            
            $evaluation = $generalEvaluations->first();
            
            return (object) [
                'student_name' => $submission->student_name_korean,
                'institution_name' => $submission->institution_name,
                'class_name' => $submission->class_name,
                'grade' => $submission->grade,
                'grade_class' => $submission->grade . ' ' . $submission->class_name,
                'pronunciation_score' => $evaluation->pronunciation_score,
                'vocabulary_score' => $evaluation->vocabulary_score,
                'fluency_score' => $evaluation->fluency_score,
                'confidence_score' => $evaluation->confidence_score,
                'topic_connection_score' => $evaluation->topic_connection_score,
                'structure_flow_score' => $evaluation->structure_flow_score,
                'creativity_score' => $evaluation->creativity_score,
                'total_score' => $evaluation->total_score,
                'evaluation_count' => $evaluationCount,
                'created_at' => $submission->created_at
            ];
        })
        ->filter() // null 값 제거
        ->sort(function ($a, $b) {
            // 1차 정렬: 총합 점수 내림차순 (높은 점수가 먼저)
            if ($a->total_score !== $b->total_score) {
                return $b->total_score <=> $a->total_score;
            }
            // 2차 정렬: 같은 점수일 때 업로드 빠른 순 (먼저 제출한 사람이 앞)
            return $a->created_at <=> $b->created_at;
        })
        ->take(20) // 상위 20명
        ->values()
        ->map(function ($item, $index) {
            $item->rank = $index + 1;
            return $item;
        });

        return view('admin.statistics', compact(
            'totalSubmissions', 'evaluatedSubmissions', 'assignedSubmissions', 'pendingSubmissions',
            'institutionStats', 'judgeStats', 'dailyStats', 'averageScores', 'scoreDistribution', 'studentRankings'
        ));
    }

    /**
     * 영상 배정 목록
     */
    public function assignmentList()
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        // 배정된 영상들 (1명의 심사위원에게 배정된 영상)
        $assignedVideos = VideoSubmission::with(['assignments.admin', 'evaluation'])
                                        ->whereHas('assignment')
                                        ->orderBy('created_at', 'asc')
                                        ->paginate(10, ['*'], 'assigned');

        // 미배정 영상들 (배정이 없는 영상)
        $unassignedVideos = VideoSubmission::whereDoesntHave('assignment')
                                          ->orderBy('created_at', 'asc')
                                          ->paginate(10, ['*'], 'unassigned');

        $admins = Admin::where('is_active', true)
                      ->where('role', 'judge') // 심사위원만 표시
                      ->get();

        return view('admin.assignment-list', compact('assignedVideos', 'unassignedVideos', 'admins'));
    }

        /**
     * 영상 배정 (한 영상당 1명의 심사위원에게 배정)
     */
public function assignVideo(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $validator = Validator::make($request->all(), [
            'video_submission_id' => 'required|exists:video_submissions,id',
            'admin_id' => 'required|exists:admins,id'
        ]);

        if ($validator->fails()) {
            return back()->with('error', '잘못된 요청입니다.');
        }

        // 해당 영상에 대한 기존 배정 확인 (최대 1명)
        $existingAssignments = VideoAssignment::where('video_submission_id', $request->video_submission_id)->get();
        
        // 이미 1명에게 배정되었는지 확인
        if ($existingAssignments->count() >= 1) {
            return back()->with('error', '이 영상은 이미 심사위원에게 배정되었습니다.');
        }

        VideoAssignment::create([
            'video_submission_id' => $request->video_submission_id,
            'admin_id' => $request->admin_id,
            'status' => 'assigned'
        ]);
        
        $judge = Admin::find($request->admin_id);

        return back()->with('success', "영상이 {$judge->name} 심사위원에게 성공적으로 배정되었습니다.");
    }

    /**
     * 배정 취소
     */
    public function cancelAssignment($id)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $assignment = VideoAssignment::findOrFail($id);
        
        // 배정 취소 전에 해당 심사위원의 평가 기록(Evaluation)만 삭제
        // AI 채점 기록(AiEvaluation)은 유지
        Evaluation::where('video_submission_id', $assignment->video_submission_id)
                  ->where('admin_id', $assignment->admin_id)
                  ->delete();
        
        // 배정 상태를 초기화 (completed 상태를 assigned로 변경)
        // 이렇게 하면 삭제 전에 상태가 초기화됨
        if ($assignment->status === VideoAssignment::STATUS_COMPLETED) {
            $assignment->update([
                'status' => VideoAssignment::STATUS_ASSIGNED,
                'completed_at' => null,
                'started_at' => null
            ]);
        }
        
        // 배정 삭제
        $assignment->delete();

        Log::info('배정 취소 완료', [
            'admin_id' => $admin->id,
            'assignment_id' => $id,
            'video_submission_id' => $assignment->video_submission_id,
            'judge_id' => $assignment->admin_id,
            'previous_status' => $assignment->getOriginal('status')
        ]);

        return back()->with('success', '배정이 취소되었습니다.');
    }

    /**
     * 자동 배정 (각 영상당 1명의 심사위원에게 균등하게 배정)
     */
    public function autoAssign()
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $activeAdmins = Admin::where('is_active', true)
                            ->where('role', 'judge')
                            ->get();

        if ($activeAdmins->count() < 1) {
            return back()->with('error', '자동 배정을 위해서는 최소 1명의 활성화된 심사위원이 필요합니다.');
        }

        // 미배정 영상들 찾기
        $videosNeedingAssignment = VideoSubmission::whereDoesntHave('assignment')
                                                 ->orderBy('created_at', 'asc') // 업로드 순서대로 배정
                                                 ->get();

        if ($videosNeedingAssignment->isEmpty()) {
            return back()->with('error', '배정이 필요한 영상이 없습니다.');
        }

        // 각 심사위원의 현재 배정 수 계산
        $adminAssignmentCounts = [];
        foreach ($activeAdmins as $adminUser) {
            $adminAssignmentCounts[$adminUser->id] = VideoAssignment::where('admin_id', $adminUser->id)->count();
        }

        $assignedCount = 0;

        foreach ($videosNeedingAssignment as $video) {
            // 배정 수가 적은 순서대로 정렬하여 가장 적은 심사위원 선택
            $sortedAdmins = $activeAdmins->sortBy(function($adminUser) use ($adminAssignmentCounts) {
                return $adminAssignmentCounts[$adminUser->id];
            })->values();
            
            if ($sortedAdmins->isNotEmpty()) {
                $selectedAdmin = $sortedAdmins->first();
                
                VideoAssignment::create([
                    'video_submission_id' => $video->id,
                    'admin_id' => $selectedAdmin->id,
                    'status' => 'assigned'
                ]);
                
                // 배정 카운트 업데이트
                $adminAssignmentCounts[$selectedAdmin->id]++;
                $assignedCount++;
            }
        }

        // 최종 분배 결과 보고
        $finalCounts = [];
        foreach ($activeAdmins as $adminUser) {
            $finalCounts[] = $adminUser->name . ': ' . $adminAssignmentCounts[$adminUser->id] . '개';
        }
        $distributionInfo = implode(', ', $finalCounts);

        return back()->with('success', "{$assignedCount}개의 배정이 자동으로 완료되었습니다. 분배 현황: {$distributionInfo}");
    }

    /**
     * 전체 영상 재배정 (기존 배정 삭제 후 새로 배정)
     */
    public function reassignAll()
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $resetReevaluationCount = 0;
        
        try {
            \DB::beginTransaction();

            // 1. 기존 배정 삭제
            $deletedAssignments = VideoAssignment::count();
            VideoAssignment::query()->delete();

            // 2. 기존 평가 삭제
            $deletedEvaluations = Evaluation::count();
            Evaluation::query()->delete();

            // 3. 재평가 대상 영상 초기화
            $resetReevaluationCount = VideoSubmission::where('is_reevaluation_target', true)->count();
            VideoSubmission::where('is_reevaluation_target', true)->update(['is_reevaluation_target' => false]);

            // 4. 모든 영상 가져오기 (랜덤 순서로)
            $allVideos = VideoSubmission::inRandomOrder()->get();

            // 5. 활성 심사위원 가져오기 (랜덤 순서로)
            $activeAdmins = Admin::where('is_active', true)
                                ->where('role', 'judge')
                                ->inRandomOrder()
                                ->get();

            if ($activeAdmins->isEmpty()) {
                \DB::rollback();
                return back()->with('error', '활성화된 심사위원이 없습니다.');
            }

            $assignedCount = 0;
            $adminCount = $activeAdmins->count();

            // 6. 균등 배정 방식으로 모든 영상 재배정 (각 영상당 1명의 심사위원)
            
            // 각 심사위원의 배정 카운트 초기화
            $adminAssignmentCounts = [];
            foreach ($activeAdmins as $adminUser) {
                $adminAssignmentCounts[$adminUser->id] = 0;
            }
            
            foreach ($allVideos as $video) {
                // 각 영상에 1명의 심사위원 배정
                // 배정 수가 가장 적은 심사위원 선택
                $sortedAdmins = $activeAdmins->sortBy(function($adminUser) use ($adminAssignmentCounts) {
                    return $adminAssignmentCounts[$adminUser->id];
                });
                
                if ($sortedAdmins->isNotEmpty()) {
                    $selectedAdmin = $sortedAdmins->first();
                    
                    VideoAssignment::create([
                        'video_submission_id' => $video->id,
                        'admin_id' => $selectedAdmin->id,
                        'status' => 'assigned'
                    ]);
                    
                    // 배정 카운트 업데이트
                    $adminAssignmentCounts[$selectedAdmin->id]++;
                    $assignedCount++;
                }
            }
            
            // 최종 배정 현황 계산
            $finalCounts = [];
            foreach ($activeAdmins as $adminUser) {
                $finalCounts[] = $adminUser->name . ': ' . $adminAssignmentCounts[$adminUser->id] . '개';
            }
            $distributionInfo = implode(', ', $finalCounts);

            \DB::commit();

        } catch (\Exception $e) {
            \DB::rollback();
            Log::error('전체 영상 재배정 오류: ' . $e->getMessage());
            
            return back()->with('error', '전체 영상 재배정 중 오류가 발생했습니다: ' . $e->getMessage());
        }

        // 트랜잭션 완료 후 로그 및 리다이렉트
        Log::info('전체 영상 재배정 완료', [
            'admin_id' => $admin->id,
            'deleted_assignments' => $deletedAssignments,
            'deleted_evaluations' => $deletedEvaluations,
            'reset_reevaluation_targets' => $resetReevaluationCount,
            'reassigned_videos' => $assignedCount,
            'judges_count' => $adminCount,
            'timestamp' => now()
        ]);

        return back()->with('success', 
                          "전체 영상 재배정이 완료되었습니다.\n" .
                          "삭제된 기존 배정: {$deletedAssignments}개\n" .
                          "삭제된 기존 평가: {$deletedEvaluations}개\n" .
                          "재평가 대상 초기화: {$resetReevaluationCount}개\n" .
                          "새로 배정된 영상: {$assignedCount}개 (각 영상당 1명씩)\n" .
                          "분배 현황: {$distributionInfo}");
    }

    /**
     * 데이터 초기화 확인 페이지
     */
    public function showResetConfirmation()
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 기능입니다.');
        }

        // 현재 데이터 통계
        $stats = [
            'total_submissions' => VideoSubmission::count(),
            'total_evaluations' => Evaluation::count(),
            'total_assignments' => VideoAssignment::count(),
            's3_files' => 0
        ];

        // S3 파일 수 계산
        try {
            $s3Files = Storage::disk('s3')->files('videos');
            $stats['s3_files'] = count($s3Files);
        } catch (\Exception $e) {
            $stats['s3_files'] = 0;
        }

        return view('admin.reset-confirmation', compact('stats'));
    }

    /**
     * 데이터 초기화 실행
     */
    public function executeReset(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 기능입니다.');
        }

        // 확인 절차 검증
        $validator = Validator::make($request->all(), [
            'confirmation_text' => 'required|in:모든 데이터를 영구적으로 삭제합니다',
            'admin_password' => 'required'
        ], [
            'confirmation_text.required' => '확인 문구를 정확히 입력해주세요.',
            'confirmation_text.in' => '확인 문구가 일치하지 않습니다.',
            'admin_password.required' => '관리자 비밀번호를 입력해주세요.'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // 관리자 비밀번호 확인
        if (!Auth::guard('admin')->validate([
            'username' => $admin->username,
            'password' => $request->admin_password
        ])) {
            return back()->with('error', '관리자 비밀번호가 일치하지 않습니다.');
        }

        try {
            \DB::beginTransaction();

            // 초기화 통계 수집
            $stats = [
                'submissions_deleted' => VideoSubmission::count(),
                'evaluations_deleted' => Evaluation::count(),
                'assignments_deleted' => VideoAssignment::count(),
                's3_files_deleted' => 0
            ];

            // 1. 심사 결과 삭제
            Evaluation::query()->delete();

            // 2. 영상 배정 삭제
            VideoAssignment::query()->delete();

            // 3. S3 파일 삭제
            try {
                $s3Files = Storage::disk('s3')->files('videos');
                foreach ($s3Files as $file) {
                    Storage::disk('s3')->delete($file);
                }
                $stats['s3_files_deleted'] = count($s3Files);
            } catch (\Exception $e) {
                Log::error('S3 파일 삭제 오류: ' . $e->getMessage());
            }

            // 4. 영상 제출 데이터 삭제
            VideoSubmission::query()->delete();

            // 5. 로컬 storage 파일 정리
            try {
                $localFiles = Storage::disk('local')->files('videos');
                foreach ($localFiles as $file) {
                    Storage::disk('local')->delete($file);
                }
                
                $publicFiles = Storage::disk('public')->files('videos');
                foreach ($publicFiles as $file) {
                    Storage::disk('public')->delete($file);
                }
            } catch (\Exception $e) {
                Log::error('로컬 파일 삭제 오류: ' . $e->getMessage());
            }

            // 6. ID 시퀀스 초기화 (테이블 ID 재시작)
            try {
                $driver = \DB::getDriverName();
                if ($driver === 'mysql') {
                    \DB::statement('ALTER TABLE evaluations AUTO_INCREMENT = 1');
                    \DB::statement('ALTER TABLE video_assignments AUTO_INCREMENT = 1');
                    \DB::statement('ALTER TABLE video_submissions AUTO_INCREMENT = 1');
                } elseif ($driver === 'sqlite') {
                    // sqlite은 시퀀스가 sqlite_sequence 테이블에 저장됨
                    \DB::statement("DELETE FROM sqlite_sequence WHERE name IN ('evaluations','video_assignments','video_submissions')");
                } elseif ($driver === 'pgsql') {
                    // PostgreSQL 시퀀스 이름은 기본 규칙을 따름: {table}_{column}_seq
                    \DB::statement('ALTER SEQUENCE evaluations_id_seq RESTART WITH 1');
                    \DB::statement('ALTER SEQUENCE video_assignments_id_seq RESTART WITH 1');
                    \DB::statement('ALTER SEQUENCE video_submissions_id_seq RESTART WITH 1');
                }
            } catch (\Exception $e) {
                Log::warning('ID 시퀀스 초기화 경고: ' . $e->getMessage());
            }

            // 7. 로그 기록
            Log::warning('데이터 초기화 실행', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'timestamp' => now(),
                'stats' => $stats
            ]);

            \DB::commit();

        } catch (\Exception $e) {
            \DB::rollback();
            Log::error('데이터 초기화 오류: ' . $e->getMessage());
            
            return back()->with('error', '데이터 초기화 중 오류가 발생했습니다: ' . $e->getMessage());
        }

        // 트랜잭션 완료 후 리다이렉트
        return redirect()->route('admin.dashboard')
                       ->with('success', 
                              "데이터 초기화가 완료되었습니다.\n" .
                              "삭제된 항목: " .
                              "영상 {$stats['submissions_deleted']}개, " .
                              "심사 {$stats['evaluations_deleted']}개, " .
                              "배정 {$stats['assignments_deleted']}개, " .
                              "S3 파일 {$stats['s3_files_deleted']}개");
    }

    /**
     * 2차 예선 진출자 선정 (각 심사위원별 상위 10명)
     * 2차 예선진출 기능이 필요 없어서 주석처리
     */
    /*
    public function qualifySecondRound()
    {
        try {
            \DB::beginTransaction();

            // 모든 심사위원의 평가를 초기화
            Evaluation::query()->update([
                'qualification_status' => Evaluation::QUALIFICATION_NOT_QUALIFIED,
                'rank_by_judge' => null,
                'qualified_at' => null
            ]);

            // 각 심사위원별로 상위 10명 선정
            $judges = Admin::where('role', 'judge')->get();
            $totalQualified = 0;

            foreach ($judges as $judge) {
                // 해당 심사위원이 완료한 배정들을 통해 평가 가져오기
                $completedAssignments = VideoAssignment::where('admin_id', $judge->id)
                    ->where('status', VideoAssignment::STATUS_COMPLETED)
                    ->with(['evaluation', 'videoSubmission'])
                    ->get();
                
                // 평가가 있는 배정들만 필터링하고 총점 순으로 정렬 (동점 시 제출일시 빠른 순)
                $evaluations = $completedAssignments
                    ->filter(function($assignment) {
                        return $assignment->evaluation !== null;
                    })
                    ->sort(function($a, $b) {
                        $scoreA = $a->evaluation ? $a->evaluation->total_score : 0;
                        $scoreB = $b->evaluation ? $b->evaluation->total_score : 0;
                        
                        // 총점이 동일한 경우 제출일시(업로드 시간) 빠른 순으로 정렬
                        if ($scoreA === $scoreB) {
                            $timeA = $a->videoSubmission ? $a->videoSubmission->created_at : now();
                            $timeB = $b->videoSubmission ? $b->videoSubmission->created_at : now();
                            return $timeA <=> $timeB; // 제출일시 빠른 순
                        }
                        return $scoreB <=> $scoreA; // 총점 높은 순
                    });

                // 상위 10명에 순위 부여 및 자격 부여
                foreach ($evaluations->take(10) as $index => $assignment) {
                    $rank = $index + 1;
                    
                    $assignment->evaluation->update([
                        'qualification_status' => Evaluation::QUALIFICATION_QUALIFIED,
                        'rank_by_judge' => $rank,
                        'qualified_at' => now()
                    ]);
                    
                    $totalQualified++;
                }

                // 나머지는 탈락 처리
                foreach ($evaluations->skip(10) as $assignment) {
                    $assignment->evaluation->update([
                        'qualification_status' => Evaluation::QUALIFICATION_NOT_QUALIFIED,
                        'rank_by_judge' => null,
                        'qualified_at' => null
                    ]);
                }
            }

            \DB::commit();

            Log::info('2차 예선 진출자 선정 완료', [
                'admin_id' => auth()->guard('admin')->user()->id,
                'judges_count' => $judges->count(),
                'total_qualified' => $totalQualified,
                'timestamp' => now()
            ]);

            return back()->with('success', "2차 예선 진출자 선정이 완료되었습니다. " .
                                          "심사위원 {$judges->count()}명, " .
                                          "총 진출자 {$totalQualified}명");

        } catch (\Exception $e) {
            \DB::rollback();
            Log::error('2차 예선 진출자 선정 오류: ' . $e->getMessage());
            
            return back()->with('error', '2차 예선 진출자 선정 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }
    */

    /**
     * 2차 예선 진출자 목록 조회
     * 2차 예선진출 기능이 필요 없어서 주석처리
     */
    /*
    public function secondRoundQualifiers()
    {
        // 2차 예선 진출자들을 심사위원별로 그룹화 후 각 그룹 내에서 순위별 정렬
        $qualifiedEvaluations = Evaluation::where('qualification_status', Evaluation::QUALIFICATION_QUALIFIED)
            ->with(['videoSubmission', 'admin'])
            ->get();

        // 심사위원별로 그룹화하고 각 그룹 내에서 1~10위 순서로 정렬 보장
        $qualifiedByJudge = $qualifiedEvaluations
            ->groupBy('admin_id')
            ->map(function ($evaluations) {
                // rank_by_judge 기준으로 정렬하되, null 값은 제외
                return $evaluations
                    ->filter(function($evaluation) {
                        return $evaluation->rank_by_judge !== null;
                    })
                    ->sortBy('rank_by_judge')
                    ->values(); // 키를 0, 1, 2... 순서로 재인덱싱
            })
            ->filter(function($evaluations) {
                return $evaluations->count() > 0; // 빈 그룹 제거
            });

        // 전체 진출자 통계
        $totalQualified = $qualifiedEvaluations->count();
        $judgesCount = Admin::where('role', 'judge')->count();

        return view('admin.second-round-qualifiers', compact('qualifiedByJudge', 'totalQualified', 'judgesCount'));
    }
    */

    /**
     * 2차 예선 진출자 엑셀 다운로드
     * 2차 예선진출 기능이 필요 없어서 주석처리
     */
    /*
    public function downloadSecondRoundQualifiers()
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        // 2차 예선 진출자들 가져오기 (심사위원별 → 순위별 정렬)
        $qualifiedEvaluations = Evaluation::where('qualification_status', Evaluation::QUALIFICATION_QUALIFIED)
            ->with(['videoSubmission', 'admin'])
            ->whereHas('videoSubmission')
            ->whereHas('admin')
            ->orderBy('admin_id')
            ->orderBy('rank_by_judge')
            ->get();

        if ($qualifiedEvaluations->isEmpty()) {
            return back()->with('error', '2차 예선 진출자가 없습니다.');
        }

        // PhpSpreadsheet 사용
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 헤더 설정
        $headers = [
            '접수번호', '심사위원', '순위', '학생명(한글)', '학생명(영어)', '거주지역', '기관명', '학년', '반명', '나이',
            'Unit주제', '총점', '등급', '제출일시', '진출확정일시'
        ];

        // 헤더 스타일 설정
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        // 헤더 추가
        foreach ($headers as $colIndex => $header) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($column . '1', $header);
            $sheet->getStyle($column . '1')->applyFromArray($headerStyle);
        }

        // 데이터 추가
        $rowIndex = 2;
        foreach ($qualifiedEvaluations as $evaluation) {
            // videoSubmission과 admin이 없는 경우 건너뛰기
            if (!$evaluation->videoSubmission || !$evaluation->admin) {
                Log::warning('Excel 다운로드: videoSubmission 또는 admin이 없는 평가 건너뜀', [
                    'evaluation_id' => $evaluation->id
                ]);
                continue;
            }
            
            $submission = $evaluation->videoSubmission;
            
            // 등급 계산 (70점 만점 기준)
            $grade = '';
            if ($evaluation->total_score >= 63) {
                $grade = 'A+';
            } elseif ($evaluation->total_score >= 56) {
                $grade = 'A';
            } elseif ($evaluation->total_score >= 49) {
                $grade = 'B+';
            } elseif ($evaluation->total_score >= 42) {
                $grade = 'B';
            } elseif ($evaluation->total_score >= 35) {
                $grade = 'C+';
            } elseif ($evaluation->total_score >= 28) {
                $grade = 'C';
            } else {
                $grade = 'D';
            }

            // 접수번호
            $sheet->setCellValue('A' . $rowIndex, $submission->receipt_number);
            $sheet->setCellValue('B' . $rowIndex, $evaluation->admin->name);
            $sheet->setCellValue('C' . $rowIndex, $evaluation->rank_by_judge . '위');
            $sheet->setCellValue('D' . $rowIndex, $submission->student_name_korean);
            $sheet->setCellValue('E' . $rowIndex, $submission->student_name_english);
            $sheet->setCellValue('F' . $rowIndex, $submission->region); // 거주지역 추가
            $sheet->setCellValue('G' . $rowIndex, $submission->institution_name);
            $sheet->setCellValue('H' . $rowIndex, $submission->grade);
            $sheet->setCellValue('I' . $rowIndex, $submission->class_name);
            $sheet->setCellValue('J' . $rowIndex, $submission->age);
            $sheet->setCellValue('K' . $rowIndex, $submission->unit_topic ?? '-');
            $sheet->setCellValue('L' . $rowIndex, $evaluation->total_score . '/40');
            $sheet->setCellValue('M' . $rowIndex, $grade);
            $sheet->setCellValue('N' . $rowIndex, $submission->created_at->format('Y-m-d H:i:s'));
            $sheet->setCellValue('O' . $rowIndex, $evaluation->qualified_at->format('Y-m-d H:i:s'));
            
            $rowIndex++;
        }

        // 열 너비 자동 조정
        foreach (range('A', 'O') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // 파일명 생성
        $filename = '2차_예선_진출자_목록_' . date('Y-m-d_H-i-s') . '.xlsx';

        // Excel 파일 생성
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // 임시 파일에 저장
        $tempFile = storage_path('app/temp/' . $filename);
        if (!file_exists(dirname($tempFile))) {
            mkdir(dirname($tempFile), 0755, true);
        }
        
        $writer->save($tempFile);

        // 파일 다운로드
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
    */

    /**
     * 2차 예선 진출 상태 초기화
     * 2차 예선진출 기능이 필요 없어서 주석처리
     */
    /*
    public function resetQualificationStatus()
    {
        try {
            Evaluation::query()->update([
                'qualification_status' => Evaluation::QUALIFICATION_PENDING,
                'rank_by_judge' => null,
                'qualified_at' => null
            ]);

            Log::info('2차 예선 자격 상태 초기화', [
                'admin_id' => auth()->guard('admin')->user()->id,
                'timestamp' => now()
            ]);

            return back()->with('success', '2차 예선 자격 상태가 초기화되었습니다.');

        } catch (\Exception $e) {
            Log::error('2차 예선 자격 상태 초기화 오류: ' . $e->getMessage());
            
            return back()->with('error', '자격 상태 초기화 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }
    */

    /**
     * 평가 완료 영상 순위 페이지
     */
    public function evaluationRanking(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        try {
            // Evaluation 테이블을 기준으로 조회 (더 안전함)
            // 재평가는 제외하고 일반 평가만 조회 (is_reevaluation = false 또는 null)
            $evaluationQuery = Evaluation::with(['videoSubmission', 'admin'])
                ->where(function($q) {
                    $q->where('is_reevaluation', false)
                      ->orWhereNull('is_reevaluation');
                })
                ->whereHas('videoSubmission'); // soft-deleted되지 않은 videoSubmission만

            // 검색 필터
            if ($request->filled('search')) {
                $search = $request->search;
                $evaluationQuery->whereHas('videoSubmission', function($q) use ($search) {
                    $q->where('student_name_korean', 'like', "%{$search}%")
                      ->orWhere('student_name_english', 'like', "%{$search}%")
                      ->orWhere('institution_name', 'like', "%{$search}%");
                });
            }

            // 심사위원 필터
            if ($request->filled('judge_id')) {
                $evaluationQuery->where('admin_id', $request->judge_id);
            }

            // 시상 필터
            if ($request->filled('award')) {
                $award = $request->award;
                if (in_array($award, ['Jenny', 'Cookie', 'Marvin'])) {
                    $evaluationQuery->where('award', $award);
                }
            }

            // 모든 평가 가져오기
            $evaluations = $evaluationQuery->get();

            // Evaluation을 VideoAssignment 형태로 변환
            $assignments = $evaluations->map(function($evaluation) {
                // 해당 평가에 맞는 VideoAssignment 찾기
                $assignment = VideoAssignment::where('video_submission_id', $evaluation->video_submission_id)
                    ->where('admin_id', $evaluation->admin_id)
                    ->with(['videoSubmission', 'admin'])
                    ->first();
                
                // VideoAssignment가 없어도 Evaluation이 있으면 순위에 포함
                // VideoAssignment가 없는 경우를 위해 가상 객체 생성
                if (!$assignment && $evaluation->videoSubmission) {
                    // 가상 VideoAssignment 객체 생성
                    $assignment = new VideoAssignment();
                    $assignment->id = 0; // 임시 ID
                    $assignment->video_submission_id = $evaluation->video_submission_id;
                    $assignment->admin_id = $evaluation->admin_id;
                    $assignment->status = VideoAssignment::STATUS_COMPLETED;
                    $assignment->setRelation('videoSubmission', $evaluation->videoSubmission);
                    $assignment->setRelation('admin', $evaluation->admin);
                }
                
                if ($assignment) {
                    // evaluation 관계를 수동으로 설정
                    $assignment->setRelation('evaluation', $evaluation);
                    return $assignment;
                }
                return null;
            })->filter(); // null 값 제거

            // 순위 계산: 점수 내림차순, 동점일 경우 접수순(created_at 오름차순)
            $rankedAssignments = $assignments->sort(function($a, $b) {
                $scoreA = $a->evaluation ? $a->evaluation->total_score : 0;
                $scoreB = $b->evaluation ? $b->evaluation->total_score : 0;
                
                // 1차 정렬: 총점 내림차순
                if ($scoreA !== $scoreB) {
                    return $scoreB <=> $scoreA;
                }
                
                // 2차 정렬: 동점일 경우 접수순(created_at 오름차순)
                $timeA = $a->videoSubmission ? $a->videoSubmission->created_at : now();
                $timeB = $b->videoSubmission ? $b->videoSubmission->created_at : now();
                return $timeA <=> $timeB;
            })->values();

            // 순위 부여
            $rankedAssignments = $rankedAssignments->map(function($assignment, $index) {
                $assignment->rank = $index + 1;
                return $assignment;
            });

            // 페이지네이션
            $perPage = $request->get('per_page', 50);
            if (!in_array($perPage, [20, 50, 100, 200])) {
                $perPage = 50;
            }

            $currentPage = $request->get('page', 1);
            $total = $rankedAssignments->count();
            $items = $rankedAssignments->forPage($currentPage, $perPage);
            
            // 페이지네이션 객체 생성
            $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            // 심사위원 목록 (필터용)
            $judges = Admin::where('role', 'judge')
                ->orderBy('name')
                ->get();

            // 통계 정보
            $totalCompleted = $rankedAssignments->count();
            $totalJudges = $judges->count();
            
            // 시상별 통계 계산
            $awardStats = [
                'jenny' => $evaluations->where('award', Evaluation::AWARD_JONNY)->count(),
                'cookie' => $evaluations->where('award', Evaluation::AWARD_JENNY)->count(),
                'marvin' => $evaluations->where('award', Evaluation::AWARD_COOKIE)->count(),
            ];

            return view('admin.evaluation-ranking', compact(
                'paginated',
                'judges',
                'totalCompleted',
                'totalJudges',
                'awardStats'
            ));

        } catch (\Exception $e) {
            Log::error('평가 순위 페이지 오류: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', '순위 페이지 로드 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 시상 업데이트
     */
    public function updateAward(Request $request, $evaluationId)
    {
        try {
            // 관리자만 접근 가능하도록 체크
            $admin = Auth::guard('admin')->user();
            if (!$admin || !$admin->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => '관리자만 접근할 수 있습니다.'
                ], 403);
            }

            // 빈 문자열을 null로 변환
            $award = $request->input('award');
            if ($award === '' || $award === null) {
                $award = null;
            }
            
            $request->merge(['award' => $award]);
            
            $request->validate([
                'award' => 'nullable|in:Jenny,Cookie,Marvin'
            ], [
                'award.in' => '올바른 시상을 선택해주세요.'
            ]);

            $evaluation = Evaluation::findOrFail($evaluationId);
            
            $evaluation->update([
                'award' => $award
            ]);

            // 시상 통계 재계산 (재평가 제외)
            $evaluations = Evaluation::whereHas('videoSubmission')
                ->where(function($q) {
                    $q->where('is_reevaluation', false)
                      ->orWhereNull('is_reevaluation');
                })
                ->get();
            $awardStats = [
                'jenny' => $evaluations->where('award', Evaluation::AWARD_JONNY)->count(),
                'cookie' => $evaluations->where('award', Evaluation::AWARD_JENNY)->count(),
                'marvin' => $evaluations->where('award', Evaluation::AWARD_COOKIE)->count(),
            ];

            Log::info('시상 업데이트 완료', [
                'admin_id' => $admin->id,
                'evaluation_id' => $evaluationId,
                'award' => $request->award
            ]);

            return response()->json([
                'success' => true,
                'message' => '시상이 업데이트되었습니다.',
                'award' => $evaluation->award,
                'award_name' => $evaluation->award_name,
                'award_stats' => $awardStats
            ]);

        } catch (ValidationException $e) {
            Log::warning('시상 업데이트 유효성 검증 실패', [
                'admin_id' => $admin->id ?? null,
                'evaluation_id' => $evaluationId,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage() ?: '올바른 시상을 선택해주세요.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('시상 업데이트 오류: ' . $e->getMessage(), [
                'admin_id' => $admin->id ?? null,
                'evaluation_id' => $evaluationId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => '시상 업데이트 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 선택된 영상들을 모든 활성 심사위원에게 배정
     */
    public function assignSelectedVideos(Request $request)
    {
        try {
            // 관리자만 접근 가능하도록 체크
            $admin = Auth::guard('admin')->user();
            if (!$admin || !$admin->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => '관리자만 접근할 수 있는 기능입니다.'
                ], 403);
            }

            // 유효성 검증
            $validator = Validator::make($request->all(), [
                'video_ids' => 'required|array',
                'video_ids.*' => 'required|exists:video_submissions,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => '잘못된 요청입니다.'
                ], 422);
            }

            $videoIds = $request->input('video_ids');
            
            // 활성 심사위원 가져오기
            $activeJudges = Admin::where('is_active', true)
                                ->where('role', 'judge')
                                ->get();

            if ($activeJudges->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => '활성화된 심사위원이 없습니다.'
                ], 400);
            }

            $newAssignmentsCount = 0;
            $existingAssignmentsCount = 0;
            $errors = [];

            DB::beginTransaction();

            try {
                foreach ($videoIds as $videoId) {
                    // 영상 존재 확인
                    $video = VideoSubmission::find($videoId);
                    if (!$video) {
                        $errors[] = "영상 ID {$videoId}를 찾을 수 없습니다.";
                        continue;
                    }

                    // 재평가 대상으로 표시 (이미 배정된 영상도 포함)
                    $video->update(['is_reevaluation_target' => true]);

                    // 각 활성 심사위원에게 배정
                    foreach ($activeJudges as $judge) {
                        // 이미 배정되어 있는지 확인
                        $existingAssignment = VideoAssignment::where('video_submission_id', $videoId)
                                                            ->where('admin_id', $judge->id)
                                                            ->first();

                        if (!$existingAssignment) {
                            // 새로운 배정 생성
                            VideoAssignment::create([
                                'video_submission_id' => $videoId,
                                'admin_id' => $judge->id,
                                'status' => VideoAssignment::STATUS_ASSIGNED,
                                'assigned_at' => now()
                            ]);
                            $newAssignmentsCount++;
                        } else {
                            // 이미 배정된 경우에도 재평가 대상으로 포함
                            $existingAssignmentsCount++;
                        }
                    }
                }

                DB::commit();

                $totalAssignments = $newAssignmentsCount + $existingAssignmentsCount;
                $message = "총 {$totalAssignments}개의 배정이 완료되었습니다.";
                if ($existingAssignmentsCount > 0) {
                    $message .= " (새로 생성된 배정: {$newAssignmentsCount}개, 기존 배정 포함: {$existingAssignmentsCount}개)";
                }

                return response()->json([
                    'success' => true,
                    'assigned_count' => $totalAssignments,
                    'new_assignments' => $newAssignmentsCount,
                    'existing_assignments' => $existingAssignmentsCount,
                    'message' => $message
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('선택된 영상 배정 오류: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => '영상 배정 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 재평가 결과 페이지
     */
    public function reevaluationResults(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        try {
            // 재평가된 평가만 가져오기 (is_reevaluation = true)
            $reevaluationQuery = Evaluation::with(['videoSubmission', 'admin'])
                ->where('is_reevaluation', true)
                ->whereHas('videoSubmission');

            // 검색 필터
            if ($request->filled('search')) {
                $search = $request->search;
                $reevaluationQuery->whereHas('videoSubmission', function($q) use ($search) {
                    $q->where('student_name_korean', 'like', "%{$search}%")
                      ->orWhere('student_name_english', 'like', "%{$search}%")
                      ->orWhere('institution_name', 'like', "%{$search}%");
                });
            }

            // 심사위원 필터가 있는 경우 해당 심사위원이 재평가한 영상 ID만 가져오기
            $filteredVideoIds = null;
            if ($request->filled('judge_id')) {
                $filteredVideoIds = $reevaluationQuery->where('admin_id', $request->judge_id)
                    ->pluck('video_submission_id')
                    ->unique()
                    ->toArray();
                
                // 해당 영상들의 모든 재평가 가져오기 (다른 심사위원 포함)
                $reevaluationQuery = Evaluation::with(['videoSubmission', 'admin'])
                    ->where('is_reevaluation', true)
                    ->whereIn('video_submission_id', $filteredVideoIds)
                    ->whereHas('videoSubmission');
                
                // 검색 필터 재적용
                if ($request->filled('search')) {
                    $search = $request->search;
                    $reevaluationQuery->whereHas('videoSubmission', function($q) use ($search) {
                        $q->where('student_name_korean', 'like', "%{$search}%")
                          ->orWhere('student_name_english', 'like', "%{$search}%")
                          ->orWhere('institution_name', 'like', "%{$search}%");
                    });
                }
            }

            // 재평가된 평가 가져오기
            $reevaluations = $reevaluationQuery->orderBy('created_at', 'desc')->get();

            // 영상별로 그룹화
            $groupedByVideo = $reevaluations->groupBy('video_submission_id');

            // 각 영상별로 재평가 데이터 구성
            $videoReevaluationData = $groupedByVideo->map(function($videoReevaluations, $videoSubmissionId) {
                $videoSubmission = $videoReevaluations->first()->videoSubmission;
                
                // 각 심사위원의 재평가 정보 수집
                $judgeReevaluations = $videoReevaluations->map(function($reevaluation) {
                    // 원본 평가 가져오기 (is_reevaluation = false, 같은 영상, 같은 심사위원)
                    $originalEvaluation = Evaluation::where('video_submission_id', $reevaluation->video_submission_id)
                                                   ->where('admin_id', $reevaluation->admin_id)
                                                   ->where('is_reevaluation', false)
                                                   ->orderBy('created_at', 'asc')
                                                   ->first();

                    return (object) [
                        'reevaluation' => $reevaluation,
                        'original_evaluation' => $originalEvaluation,
                        'judge' => $reevaluation->admin,
                        'score_difference' => $originalEvaluation 
                            ? $reevaluation->total_score - $originalEvaluation->total_score 
                            : null
                    ];
                });

                // 모든 심사위원의 재평가 점수 합산
                $totalReevaluationScore = $judgeReevaluations->sum(function($item) {
                    return $item->reevaluation->total_score ?? 0;
                });

                return (object) [
                    'video_submission' => $videoSubmission,
                    'judge_reevaluations' => $judgeReevaluations,
                    'total_reevaluation_score' => $totalReevaluationScore,
                    'judge_count' => $judgeReevaluations->count()
                ];
            });

            // 합산 점수 순으로 정렬 (내림차순)
            $sortedVideoData = $videoReevaluationData->sortByDesc('total_reevaluation_score')->values();

            // 페이지네이션
            $perPage = $request->get('per_page', 50);
            if (!in_array($perPage, [20, 50, 100, 200])) {
                $perPage = 50;
            }

            $currentPage = $request->get('page', 1);
            $total = $sortedVideoData->count();
            $items = $sortedVideoData->forPage($currentPage, $perPage);
            
            $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            // 심사위원 목록 (필터용)
            $judges = Admin::where('role', 'judge')
                ->orderBy('name')
                ->get();

            // 통계 정보 (개별 재평가 기준)
            $totalReevaluations = $reevaluations->count();
            $withOriginalCount = $reevaluations->filter(function($reevaluation) {
                $original = Evaluation::where('video_submission_id', $reevaluation->video_submission_id)
                                     ->where('admin_id', $reevaluation->admin_id)
                                     ->where('is_reevaluation', false)
                                     ->first();
                return $original !== null;
            })->count();
            $scoreIncreasedCount = $reevaluations->filter(function($reevaluation) {
                $original = Evaluation::where('video_submission_id', $reevaluation->video_submission_id)
                                     ->where('admin_id', $reevaluation->admin_id)
                                     ->where('is_reevaluation', false)
                                     ->first();
                if ($original) {
                    return $reevaluation->total_score > $original->total_score;
                }
                return false;
            })->count();
            $scoreDecreasedCount = $reevaluations->filter(function($reevaluation) {
                $original = Evaluation::where('video_submission_id', $reevaluation->video_submission_id)
                                     ->where('admin_id', $reevaluation->admin_id)
                                     ->where('is_reevaluation', false)
                                     ->first();
                if ($original) {
                    return $reevaluation->total_score < $original->total_score;
                }
                return false;
            })->count();

            return view('admin.reevaluation-results', compact(
                'paginated',
                'judges',
                'totalReevaluations',
                'withOriginalCount',
                'scoreIncreasedCount',
                'scoreDecreasedCount'
            ));

        } catch (\Exception $e) {
            Log::error('재평가 결과 조회 오류: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', '재평가 결과를 불러오는 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 재평가 영상 배정 초기화
     */
    public function resetReevaluationAssignments()
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        try {
            \DB::beginTransaction();

            // 재평가 대상 영상 ID 가져오기
            $reevaluationVideoIds = VideoSubmission::where('is_reevaluation_target', true)
                ->pluck('id')
                ->toArray();

            // 재평가 대상 영상들의 배정 삭제
            $deletedAssignments = VideoAssignment::whereIn('video_submission_id', $reevaluationVideoIds)
                ->count();
            
            VideoAssignment::whereIn('video_submission_id', $reevaluationVideoIds)
                ->delete();

            // 재평가 결과 삭제 (is_reevaluation = true인 평가)
            $deletedReevaluations = Evaluation::whereIn('video_submission_id', $reevaluationVideoIds)
                ->where('is_reevaluation', true)
                ->count();
            
            Evaluation::whereIn('video_submission_id', $reevaluationVideoIds)
                ->where('is_reevaluation', true)
                ->delete();

            // 재평가 대상 플래그 초기화
            $resetCount = VideoSubmission::where('is_reevaluation_target', true)
                ->update(['is_reevaluation_target' => false]);

            \DB::commit();

            Log::info('재평가 영상 배정 및 결과 초기화 완료', [
                'admin_id' => $admin->id,
                'deleted_assignments' => $deletedAssignments,
                'deleted_reevaluations' => $deletedReevaluations,
                'reset_videos' => $resetCount,
                'timestamp' => now()
            ]);

            return back()->with('success', 
                "재평가 영상 배정 및 결과가 초기화되었습니다.\n" .
                "삭제된 배정: {$deletedAssignments}개\n" .
                "삭제된 재평가 결과: {$deletedReevaluations}개\n" .
                "초기화된 영상: {$resetCount}개");

        } catch (\Exception $e) {
            \DB::rollback();
            Log::error('재평가 영상 배정 초기화 오류: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', '재평가 영상 배정 초기화 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 평가 순위 Excel 다운로드
     */
    public function downloadEvaluationRankingExcel(Request $request)
    {
        try {
            // 관리자만 접근 가능하도록 체크
            $admin = Auth::guard('admin')->user();
            if (!$admin || !$admin->isAdmin()) {
                return redirect()->route('judge.dashboard')
                               ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
            }

            // 메모리 및 타임아웃 설정
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            // Evaluation 테이블을 기준으로 조회 (순위 페이지와 동일한 로직)
            // 재평가는 제외하고 일반 평가만 조회 (is_reevaluation = false 또는 null)
            $evaluationQuery = Evaluation::with(['videoSubmission', 'admin'])
                ->where(function($q) {
                    $q->where('is_reevaluation', false)
                      ->orWhereNull('is_reevaluation');
                })
                ->whereHas('videoSubmission');

            // 검색 필터
            if ($request->filled('search')) {
                $search = $request->search;
                $evaluationQuery->whereHas('videoSubmission', function($q) use ($search) {
                    $q->where('student_name_korean', 'like', "%{$search}%")
                      ->orWhere('student_name_english', 'like', "%{$search}%")
                      ->orWhere('institution_name', 'like', "%{$search}%");
                });
            }

            // 심사위원 필터
            if ($request->filled('judge_id')) {
                $evaluationQuery->where('admin_id', $request->judge_id);
            }

            // 모든 평가 가져오기
            $evaluations = $evaluationQuery->get();

            // Evaluation을 VideoAssignment 형태로 변환
            $assignments = $evaluations->map(function($evaluation) {
                $assignment = VideoAssignment::where('video_submission_id', $evaluation->video_submission_id)
                    ->where('admin_id', $evaluation->admin_id)
                    ->with(['videoSubmission', 'admin'])
                    ->first();
                
                if (!$assignment && $evaluation->videoSubmission) {
                    $assignment = new VideoAssignment();
                    $assignment->id = 0;
                    $assignment->video_submission_id = $evaluation->video_submission_id;
                    $assignment->admin_id = $evaluation->admin_id;
                    $assignment->status = VideoAssignment::STATUS_COMPLETED;
                    $assignment->setRelation('videoSubmission', $evaluation->videoSubmission);
                    $assignment->setRelation('admin', $evaluation->admin);
                }
                
                if ($assignment) {
                    $assignment->setRelation('evaluation', $evaluation);
                    return $assignment;
                }
                return null;
            })->filter();

            // 순위 계산: 점수 내림차순, 동점일 경우 접수순
            $rankedAssignments = $assignments->sort(function($a, $b) {
                $scoreA = $a->evaluation ? $a->evaluation->total_score : 0;
                $scoreB = $b->evaluation ? $b->evaluation->total_score : 0;
                
                if ($scoreA !== $scoreB) {
                    return $scoreB <=> $scoreA;
                }
                
                $timeA = $a->videoSubmission ? $a->videoSubmission->created_at : now();
                $timeB = $b->videoSubmission ? $b->videoSubmission->created_at : now();
                return $timeA <=> $timeB;
            })->values();

            // 순위 부여
            $rankedAssignments = $rankedAssignments->map(function($assignment, $index) {
                $assignment->rank = $index + 1;
                return $assignment;
            });

            if ($rankedAssignments->isEmpty()) {
                return back()->with('error', '다운로드할 평가 결과가 없습니다.');
            }

            // PhpSpreadsheet 사용
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('평가 순위');

            // 헤더 설정
            $headers = [
                '순위', '접수번호', '학생명(한글)', '학생명(영어)', '기관명', '반명', '학년', '나이', '거주지역',
                '심사위원', '총점', '발음', '어휘', '유창성', '자신감', '주제연결', '구성흐름', '창의성', '심사코멘트', '시상', '접수일시'
            ];

            // 헤더 스타일 설정
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];

            // 헤더 추가
            foreach ($headers as $colIndex => $header) {
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                $sheet->setCellValue($column . '1', $header);
                $sheet->getStyle($column . '1')->applyFromArray($headerStyle);
            }

            // 데이터 추가
            $rowIndex = 2;
            foreach ($rankedAssignments as $assignment) {
                $submission = $assignment->videoSubmission;
                $evaluation = $assignment->evaluation;
                
                if (!$submission || !$evaluation) {
                    continue;
                }

                $colIndex = 0;
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $assignment->rank);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $submission->receipt_number);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $submission->student_name_korean);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $submission->student_name_english);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $submission->institution_name);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $submission->class_name);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $submission->grade);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $submission->age);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $submission->region);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $assignment->admin->name ?? '알 수 없음');
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $evaluation->total_score);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $evaluation->pronunciation_score);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $evaluation->vocabulary_score);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $evaluation->fluency_score);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $evaluation->confidence_score);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $evaluation->topic_connection_score);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $evaluation->structure_flow_score);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $evaluation->creativity_score);
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $evaluation->comments ?? '');
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $evaluation->award_name ?? '미선택');
                
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(++$colIndex);
                $sheet->setCellValue($column . $rowIndex, $submission->created_at->format('Y-m-d H:i:s'));
                
                $rowIndex++;
            }

            // 열 너비 자동 조정
            foreach (range('A', \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers))) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // 파일명 생성
            $filename = 'evaluation_ranking_' . date('Y-m-d_H-i-s') . '.xlsx';

            // Excel 파일 생성
            $writer = new Xlsx($spreadsheet);
            
            // 임시 파일에 저장
            $tempFile = storage_path('app/temp/' . $filename);
            if (!file_exists(dirname($tempFile))) {
                mkdir(dirname($tempFile), 0755, true);
            }
            
            $writer->save($tempFile);

            // 파일 다운로드
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('평가 순위 Excel 다운로드 오류: ' . $e->getMessage(), [
                'admin_id' => Auth::guard('admin')->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Excel 다운로드 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 기관명 목록 페이지
     */
    public function institutionList(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        // 검색 및 필터링 파라미터
        $search = $request->get('search');
        $type = $request->get('type');
        $perPage = $request->get('per_page', 20);
        
        // 페이지당 항목 수 검증 (10, 20, 50, 100만 허용)
        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 20;
        }

        // 쿼리 빌더 시작
        $query = Institution::withCount('videoSubmissions');
        
        // 검색어 필터링
        if (!empty($search)) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }
        
        // 유형 필터링
        if (!empty($type)) {
            $query->where('type', $type);
        }
        
        // 정렬
        $query->orderBy('sort_order')
              ->orderBy('name');
        
        // 페이지네이션
        $institutions = $query->paginate($perPage);
        
        // 검색 파라미터를 페이지네이션 링크에 유지
        $institutions->appends($request->query());

        return view('admin.institution-list', compact('institutions'));
    }

    /**
     * 기관명 추가
     */
    public function addInstitution(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:institutions,name',
            'type' => 'required|string|in:' . implode(',', array_keys(Institution::TYPES)),
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0'
        ]);

        try {
            Institution::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? '',
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => true
            ]);

            return back()->with('success', "기관명 '{$validated['name']}'이(가) 성공적으로 추가되었습니다.");

        } catch (\Exception $e) {
            \Log::error('기관명 추가 오류: ' . $e->getMessage());
            return back()->with('error', '기관명 추가 중 오류가 발생했습니다.');
        }
    }

    /**
     * 기관명 수정
     */
    public function updateInstitution(Request $request, $id)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $institution = Institution::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:institutions,name,' . $id,
            'type' => 'required|string|in:' . implode(',', array_keys(Institution::TYPES)),
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0'
        ]);

        try {
            $institution->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? '',
                'sort_order' => $validated['sort_order'] ?? 0
            ]);

            return back()->with('success', "기관명 '{$validated['name']}'이(가) 성공적으로 수정되었습니다.");

        } catch (\Exception $e) {
            \Log::error('기관명 수정 오류: ' . $e->getMessage());
            return back()->with('error', '기관명 수정 중 오류가 발생했습니다.');
        }
    }

    /**
     * 기관명 삭제
     */
    public function deleteInstitution($id)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $institution = Institution::findOrFail($id);

        // 해당 기관에 영상 제출이 있는지 확인
        $submissionCount = $institution->video_submissions_count ?? $institution->videoSubmissions()->count();
        
        if ($submissionCount > 0) {
            return back()->with('error', "기관명 '{$institution->name}'은(는) {$submissionCount}개의 영상 제출이 있어 삭제할 수 없습니다. 비활성화를 권장합니다.");
        }

        try {
            $institutionName = $institution->name;
            $institution->delete();

            return back()->with('success', "기관명 '{$institutionName}'이(가) 성공적으로 삭제되었습니다.");

        } catch (\Exception $e) {
            \Log::error('기관명 삭제 오류: ' . $e->getMessage());
            return back()->with('error', '기관명 삭제 중 오류가 발생했습니다.');
        }
    }

    /**
     * 기관명 활성화/비활성화 토글
     */
    public function toggleInstitution($id)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $institution = Institution::findOrFail($id);

        try {
            $institution->update([
                'is_active' => !$institution->is_active
            ]);

            $status = $institution->is_active ? '활성화' : '비활성화';
            return back()->with('success', "기관명 '{$institution->name}'이(가) {$status}되었습니다.");

        } catch (\Exception $e) {
            \Log::error('기관명 상태 변경 오류: ' . $e->getMessage());
            return back()->with('error', '기관명 상태 변경 중 오류가 발생했습니다.');
        }
    }

    /**
     * 비밀번호 재설정 페이지 표시
     */
    public function showPasswordReset()
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        // 모든 관리자와 심사위원 목록 가져오기
        $allAdmins = Admin::orderBy('role', 'asc')->orderBy('name', 'asc')->get();

        return view('admin.password-reset', compact('allAdmins'));
    }

    /**
     * 비밀번호 재설정 실행
     */
    public function resetPassword(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $request->validate([
            'admin_id' => 'required|exists:admins,id',
            'new_password' => 'required|min:6|confirmed',
        ], [
            'admin_id.required' => '계정을 선택해주세요.',
            'admin_id.exists' => '존재하지 않는 계정입니다.',
            'new_password.required' => '새 비밀번호를 입력해주세요.',
            'new_password.min' => '비밀번호는 최소 6자 이상이어야 합니다.',
            'new_password.confirmed' => '비밀번호 확인이 일치하지 않습니다.',
        ]);

        try {
            $targetAdmin = Admin::findOrFail($request->admin_id);
            
            // 비밀번호 업데이트
            $targetAdmin->update([
                'password' => $request->new_password // Admin 모델의 setPasswordAttribute에서 자동 해싱
            ]);

            \Log::info('비밀번호 재설정', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'target_admin_id' => $targetAdmin->id,
                'target_admin_name' => $targetAdmin->name,
                'timestamp' => now()
            ]);

            return back()->with('success', "계정 '{$targetAdmin->name}'의 비밀번호가 성공적으로 변경되었습니다.");

        } catch (\Exception $e) {
            \Log::error('비밀번호 재설정 오류: ' . $e->getMessage());
            return back()->with('error', '비밀번호 재설정 중 오류가 발생했습니다.');
        }
    }

    /**
     * 심사위원 관리 페이지 표시
     */
    public function showJudgeManagement()
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        // 모든 심사위원 목록 가져오기 (배정 수와 평가 수 포함)
        $judges = Admin::where('role', 'judge')
                     ->withCount(['videoAssignments', 'evaluations'])
                     ->orderBy('created_at', 'desc')
                     ->get();

        // 통계 정보
        $stats = [
            'total_judges' => $judges->count(),
            'active_judges' => $judges->where('is_active', true)->count(),
            'inactive_judges' => $judges->where('is_active', false)->count(),
            'judges_with_assignments' => $judges->where('video_assignments_count', '>', 0)->count(),
        ];

        return view('admin.judge-management', compact('judges', 'stats'));
    }

    /**
     * 심사위원 추가
     */
    public function createJudge(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:admins,username',
            'email' => 'required|email|max:255|unique:admins,email',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required' => '이름을 입력해주세요.',
            'username.required' => '사용자명을 입력해주세요.',
            'username.unique' => '이미 사용 중인 사용자명입니다.',
            'email.required' => '이메일을 입력해주세요.',
            'email.email' => '유효한 이메일 주소를 입력해주세요.',
            'email.unique' => '이미 등록된 이메일입니다.',
            'password.required' => '비밀번호를 입력해주세요.',
            'password.min' => '비밀번호는 최소 6자 이상이어야 합니다.',
            'password.confirmed' => '비밀번호 확인이 일치하지 않습니다.',
        ]);

        try {
            $judge = Admin::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => $request->password, // Admin 모델에서 자동 해싱
                'role' => 'judge',
                'is_active' => true,
            ]);

            \Log::info('심사위원 계정 생성', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'new_judge_id' => $judge->id,
                'new_judge_name' => $judge->name,
                'new_judge_username' => $judge->username,
                'timestamp' => now()
            ]);

            return back()->with('success', "심사위원 '{$judge->name}' 계정이 성공적으로 생성되었습니다.");

        } catch (\Exception $e) {
            \Log::error('심사위원 생성 오류: ' . $e->getMessage());
            return back()->with('error', '심사위원 생성 중 오류가 발생했습니다.');
        }
    }

    /**
     * 심사위원 삭제
     */
    public function deleteJudge($id)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        try {
            $judge = Admin::where('role', 'judge')->findOrFail($id);
            
            // 배정된 영상이 있는지 확인
            $assignmentCount = $judge->videoAssignments()->count();
            $evaluationCount = $judge->evaluations()->count();
            
            if ($assignmentCount > 0 || $evaluationCount > 0) {
                return back()->with('error', 
                    "심사위원 '{$judge->name}'은(는) 배정된 영상({$assignmentCount}건) 또는 평가 기록({$evaluationCount}건)이 있어 삭제할 수 없습니다. 먼저 배정과 평가를 삭제해주세요.");
            }
            
            $judgeName = $judge->name;
            $judgeUsername = $judge->username;
            
            // 심사위원 삭제
            $judge->delete();

            \Log::info('심사위원 계정 삭제', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'deleted_judge_id' => $id,
                'deleted_judge_name' => $judgeName,
                'deleted_judge_username' => $judgeUsername,
                'timestamp' => now()
            ]);

            return back()->with('success', "심사위원 '{$judgeName}' 계정이 성공적으로 삭제되었습니다.");

        } catch (ModelNotFoundException $e) {
            return back()->with('error', '존재하지 않는 심사위원입니다.');
        } catch (\Exception $e) {
            \Log::error('심사위원 삭제 오류: ' . $e->getMessage());
            return back()->with('error', '심사위원 삭제 중 오류가 발생했습니다.');
        }
    }

    /**
     * 심사위원 활성/비활성 상태 전환
     */
    public function toggleJudgeStatus($id)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        try {
            $judge = Admin::where('role', 'judge')->findOrFail($id);
            $previousStatus = $judge->is_active;
            
            // 상태 전환
            $judge->update([
                'is_active' => !$judge->is_active
            ]);

            $status = $judge->is_active ? '활성화' : '비활성화';
            
            // 비활성화할 때만 평가 데이터 초기화
            if (!$judge->is_active && $previousStatus) {
                // 해당 심사위원의 평가 기록(Evaluation) 삭제
                // AI 채점 기록(AiEvaluation)은 유지
                $evaluationCount = Evaluation::where('admin_id', $judge->id)->count();
                Evaluation::where('admin_id', $judge->id)->delete();
                
                // 해당 심사위원의 배정(VideoAssignment) 상태 초기화 및 삭제
                $assignments = VideoAssignment::where('admin_id', $judge->id)->get();
                $assignmentCount = $assignments->count();
                
                foreach ($assignments as $assignment) {
                    // completed 상태인 경우 상태를 assigned로 초기화
                    if ($assignment->status === VideoAssignment::STATUS_COMPLETED) {
                        $assignment->update([
                            'status' => VideoAssignment::STATUS_ASSIGNED,
                            'completed_at' => null,
                            'started_at' => null
                        ]);
                    }
                    // 배정 삭제
                    $assignment->delete();
                }
                
                Log::info('심사위원 비활성화 및 평가 데이터 초기화', [
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'judge_id' => $judge->id,
                    'judge_name' => $judge->name,
                    'evaluations_deleted' => $evaluationCount,
                    'assignments_deleted' => $assignmentCount,
                    'timestamp' => now()
                ]);
            }
            
            \Log::info('심사위원 상태 변경', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'judge_id' => $judge->id,
                'judge_name' => $judge->name,
                'new_status' => $judge->is_active ? 'active' : 'inactive',
                'previous_status' => $previousStatus ? 'active' : 'inactive',
                'timestamp' => now()
            ]);

            $message = "심사위원 '{$judge->name}'이(가) {$status}되었습니다.";
            if (!$judge->is_active && $previousStatus) {
                $message .= " (평가 데이터가 초기화되었습니다.)";
            }

            return back()->with('success', $message);

        } catch (ModelNotFoundException $e) {
            return back()->with('error', '존재하지 않는 심사위원입니다.');
        } catch (\Exception $e) {
            \Log::error('심사위원 상태 변경 오류: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
                'judge_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', '심사위원 상태 변경 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * AI 채점 결과 목록 페이지
     */
    public function aiEvaluationList(Request $request)
    {
        try {
            Log::info('AI 평가 목록 페이지 로드', [
                'admin_id' => Auth::guard('admin')->id(),
                'request_params' => $request->all()
            ]);

            // soft-deleted되지 않은 video_submission과 admin이 있는 평가만 조회
            $query = AiEvaluation::with(['videoSubmission', 'admin'])
                ->whereHas('videoSubmission', function($q) {
                    // soft-deleted되지 않은 video_submission만
                })
                ->whereHas('admin', function($q) {
                    // admin이 존재하는 것만
                });

            // 검색 필터
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('videoSubmission', function($q) use ($search) {
                    $q->where('student_name_korean', 'like', "%{$search}%")
                      ->orWhere('student_name_english', 'like', "%{$search}%")
                      ->orWhere('institution_name', 'like', "%{$search}%");
                });
            }

            // 상태 필터
            if ($request->filled('status')) {
                $query->where('ai_evaluations.processing_status', $request->status);
            }

            // 정렬
            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');
            $query->orderBy('ai_evaluations.' . $sortField, $sortDirection);

            $aiEvaluations = $query->paginate(20)->appends($request->query());

            // 통계 정보 계산
            $totalEvaluations = AiEvaluation::count();
            $completedEvaluations = AiEvaluation::where('processing_status', AiEvaluation::STATUS_COMPLETED)->count();
            $processingEvaluations = AiEvaluation::where('processing_status', AiEvaluation::STATUS_PROCESSING)->count();
            $failedEvaluations = AiEvaluation::where('processing_status', AiEvaluation::STATUS_FAILED)->count();
            $averageScore = AiEvaluation::where('processing_status', AiEvaluation::STATUS_COMPLETED)
                ->avg('total_score') ?? 0;

            return view('admin.ai-evaluation-list', compact(
                'aiEvaluations',
                'totalEvaluations',
                'completedEvaluations',
                'processingEvaluations',
                'failedEvaluations',
                'averageScore'
            ));

        } catch (\Exception $e) {
            Log::error('AI 평가 목록 조회 오류: ' . $e->getMessage());
            return back()->with('error', 'AI 평가 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    /**
     * AI 채점 결과 상세 보기
     */
    public function showAiEvaluation($id)
    {
        try {
            $aiEvaluation = AiEvaluation::with(['videoSubmission', 'admin'])
                ->whereHas('videoSubmission')
                ->whereHas('admin')
                ->findOrFail($id);
            
            // null 체크
            if (!$aiEvaluation->videoSubmission || !$aiEvaluation->admin) {
                return back()->with('error', 'AI 평가 결과의 관련 데이터를 찾을 수 없습니다.');
            }
            
            return view('admin.ai-evaluation-detail', compact('aiEvaluation'));

        } catch (ModelNotFoundException $e) {
            return back()->with('error', '존재하지 않는 AI 평가 결과입니다.');
        } catch (\Exception $e) {
            Log::error('AI 평가 상세 조회 오류: ' . $e->getMessage());
            return back()->with('error', 'AI 평가 상세 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    /**
     * AI 평가 결과 전체 초기화
     */
    public function resetAiEvaluations()
    {
        try {
            // 관리자만 접근 가능
            $admin = Auth::guard('admin')->user();
            if (!$admin || !$admin->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => '관리자만 접근할 수 있습니다.'
                ], 403);
            }

            // 모든 AI 평가 결과 삭제
            $deletedCount = AiEvaluation::count();
            AiEvaluation::truncate();

            Log::info('AI 평가 결과 전체 초기화', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'deleted_count' => $deletedCount,
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'AI 평가 결과가 성공적으로 초기화되었습니다.',
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            Log::error('AI 평가 결과 초기화 오류: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'AI 평가 결과 초기화 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * AI 평가 상세 정보 반환 (Ajax용)
     */
    public function getAiEvaluationDetail($id)
    {
        try {
            Log::info('AI 평가 상세 조회 요청', ['id' => $id]);
            $aiEvaluation = AiEvaluation::with(['videoSubmission', 'admin'])
                ->whereHas('videoSubmission')
                ->whereHas('admin')
                ->findOrFail($id);
            Log::info('AI 평가 상세 조회 성공', ['id' => $id, 'status' => $aiEvaluation->processing_status]);
            
            // null 체크
            if (!$aiEvaluation->videoSubmission || !$aiEvaluation->admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI 평가 결과의 관련 데이터를 찾을 수 없습니다.'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $aiEvaluation->id,
                    'student_name' => $aiEvaluation->videoSubmission->student_name_korean ?? '',
                    'student_name_english' => $aiEvaluation->videoSubmission->student_name_english ?? '',
                    'institution' => $aiEvaluation->videoSubmission->institution_name ?? '',
                    'class_name' => $aiEvaluation->videoSubmission->class_name ?? '',
                    'pronunciation_score' => $aiEvaluation->pronunciation_score,
                    'vocabulary_score' => $aiEvaluation->vocabulary_score,
                    'fluency_score' => $aiEvaluation->fluency_score,
                    'total_score' => $aiEvaluation->total_score,
                    'transcription' => $aiEvaluation->transcription,
                    'ai_feedback' => $aiEvaluation->ai_feedback,
                    'processing_status' => $aiEvaluation->processing_status,
                    'processed_at' => $aiEvaluation->processed_at ? $aiEvaluation->processed_at->format('Y-m-d H:i:s') : null,
                    'admin_name' => $aiEvaluation->admin->name ?? '시스템'
                ]
            ]);

        } catch (ModelNotFoundException $e) {
            Log::warning('AI 평가를 찾을 수 없음', ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'AI 평가 결과를 찾을 수 없습니다.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('AI 평가 상세 조회 오류: ' . $e->getMessage(), ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'AI 평가 상세 정보를 불러올 수 없습니다.'
            ], 500);
        }
    }

    /**
     * 영상 보기
     */
    public function viewVideo($id)
    {
        try {
            $submission = VideoSubmission::with(['assignments'])->findOrFail($id);
            
            // 영상 URL 생성 (S3 또는 로컬)
            $videoUrl = null;
            try {
                if ($submission->isStoredOnS3()) {
                    $videoUrl = $submission->getS3TemporaryUrl(24); // 24시간 유효
                } else {
                    $videoUrl = $submission->getLocalVideoUrl();
                }
            } catch (\Exception $e) {
                Log::warning('영상 URL 생성 실패: ' . $e->getMessage());
                $videoUrl = null;
            }

            return view('admin.video-view', compact('submission', 'videoUrl'));

        } catch (ModelNotFoundException $e) {
            return back()->with('error', '존재하지 않는 영상입니다.');
        } catch (\Exception $e) {
            Log::error('영상 보기 오류: ' . $e->getMessage());
            return back()->with('error', '영상을 불러오는 중 오류가 발생했습니다.');
        }
    }

    /**
     * 영상 다운로드 (관리자용)
     */
    public function downloadVideo($id)
    {
        try {
            $submission = VideoSubmission::findOrFail($id);
            
            // S3 또는 로컬 스토리지에 따라 다른 다운로드 방법 사용
            if ($submission->isStoredOnS3()) {
                // S3 다운로드 URL 생성 (1시간 유효)
                $downloadUrl = $submission->getS3DownloadUrl(1);
                
                // 한글 파일명으로 인한 오류 발생 시 안전한 방법 시도
                if (!$downloadUrl) {
                    $downloadUrl = $submission->getSafeS3DownloadUrl(1);
                }

                if (!$downloadUrl) {
                    return back()->with('error', '영상 다운로드 링크를 생성할 수 없습니다.');
                }

                return redirect($downloadUrl);
            } else {
                // 로컬 파일 직접 다운로드
                $filePath = storage_path('app/public/' . $submission->video_file_path);
                
                if (!file_exists($filePath)) {
                    return back()->with('error', '영상 파일을 찾을 수 없습니다.');
                }

                return response()->download($filePath, $submission->video_file_name);
            }

        } catch (ModelNotFoundException $e) {
            return back()->with('error', '존재하지 않는 영상입니다.');
        } catch (\Exception $e) {
            Log::error('영상 다운로드 오류: ' . $e->getMessage());
            return back()->with('error', '영상 다운로드 중 오류가 발생했습니다.');
        }
    }

    /**
     * 영상 스트리밍 URL 가져오기 (AJAX용)
     */
    public function getVideoStreamUrl($id)
    {
        try {
            $submission = VideoSubmission::findOrFail($id);
            
            // 영상 URL 생성 (S3 또는 로컬)
            $videoUrl = null;
            try {
                if ($submission->isStoredOnS3()) {
                    $videoUrl = $submission->getS3TemporaryUrl(24); // 24시간 유효
                } else {
                    $videoUrl = $submission->getLocalVideoUrl();
                }
            } catch (\Exception $e) {
                Log::warning('영상 URL 생성 실패: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'error' => '영상 URL을 생성할 수 없습니다.'
                ], 500);
            }

            if (!$videoUrl) {
                return response()->json([
                    'success' => false,
                    'error' => '영상 URL을 생성할 수 없습니다.'
                ], 500);
            }

            // 비디오 타입 확인 (파일명에서 추출하거나 기본값 사용)
            $videoType = $submission->video_file_type;
            if (!$videoType && $submission->video_file_name) {
                $extension = pathinfo($submission->video_file_name, PATHINFO_EXTENSION);
                $videoType = strtolower($extension) ?: 'mp4';
            }
            $videoType = $videoType ?: 'mp4';
            
            Log::info('영상 URL 생성 성공', [
                'video_id' => $id,
                'video_url' => $videoUrl,
                'video_type' => $videoType,
                'is_s3' => $submission->isStoredOnS3()
            ]);
            
            return response()->json([
                'success' => true,
                'video_url' => $videoUrl,
                'video_type' => $videoType,
                'file_name' => $submission->video_file_name
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => '존재하지 않는 영상입니다.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('영상 URL 조회 오류: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => '영상 URL을 불러오는 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 접수 정보 수정을 위한 데이터 가져오기
     */
    public function getSubmissionForEdit($id)
    {
        try {
            $admin = Auth::guard('admin')->user();
            if (!$admin || !$admin->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => '관리자만 접근할 수 있습니다.'
                ], 403);
            }

            $submission = VideoSubmission::findOrFail($id);

            return response()->json([
                'success' => true,
                'submission' => [
                    'id' => $submission->id,
                    'region' => $submission->region,
                    'institution_name' => $submission->institution_name,
                    'class_name' => $submission->class_name,
                    'student_name_korean' => $submission->student_name_korean,
                    'student_name_english' => $submission->student_name_english,
                    'grade' => $submission->grade,
                    'age' => $submission->age,
                    'parent_name' => $submission->parent_name,
                    'parent_phone' => $submission->parent_phone,
                    'unit_topic' => $submission->unit_topic,
                ]
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => '존재하지 않는 접수 정보입니다.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('접수 정보 조회 오류: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => '접수 정보를 불러오는 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 접수 정보 수정
     */
    public function updateSubmission(Request $request, $id)
    {
        try {
            $admin = Auth::guard('admin')->user();
            if (!$admin || !$admin->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'error' => '관리자만 접근할 수 있습니다.'
                ], 403);
            }

            $submission = VideoSubmission::findOrFail($id);

            // 디버깅: 요청 데이터 로깅
            Log::info('접수 정보 수정 요청', [
                'submission_id' => $id,
                'request_all' => $request->all(),
                'region' => $request->input('region'),
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type')
            ]);

            // 유효성 검사
            $validator = Validator::make($request->all(), [
                'region' => ['required', 'string', function ($attribute, $value, $fail) {
                    $parts = explode(' ', $value, 2);
                    if (count($parts) < 2) {
                        $fail('올바른 지역 형식을 선택해주세요.');
                        return;
                    }
                    
                    $province = $parts[0];
                    $city = $parts[1];
                    
                    if (!array_key_exists($province, VideoSubmission::REGIONS)) {
                        $fail('올바른 시/도를 선택해주세요.');
                        return;
                    }
                    
                    if (!in_array($city, VideoSubmission::REGIONS[$province])) {
                        $fail('올바른 시/군/구를 선택해주세요.');
                        return;
                    }
                }],
                'institution_name' => 'required|string|max:255',
                'class_name' => 'required|string|max:255',
                'student_name_korean' => 'required|string|max:255',
                'student_name_english' => 'required|string|max:255',
                'grade' => 'required|string|max:50',
                'age' => 'required|integer|min:1|max:100',
                'parent_name' => 'required|string|max:255',
                'parent_phone' => 'required|string|max:20',
                'unit_topic' => 'nullable|string|max:255',
            ], [
                'region.required' => '거주 지역을 선택해주세요.',
                'institution_name.required' => '기관명을 입력해주세요.',
                'class_name.required' => '반 이름을 입력해주세요.',
                'student_name_korean.required' => '학생 한글 이름을 입력해주세요.',
                'student_name_english.required' => '학생 영어 이름을 입력해주세요.',
                'grade.required' => '학년을 입력해주세요.',
                'age.required' => '나이를 선택해주세요.',
                'age.integer' => '올바른 나이를 선택해주세요.',
                'age.min' => '나이는 1세 이상이어야 합니다.',
                'age.max' => '나이는 100세 이하여야 합니다.',
                'parent_name.required' => '학부모 성함을 입력해주세요.',
                'parent_phone.required' => '학부모 전화번호를 입력해주세요.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }

            // 기관명이 변경되었는지 확인
            $institutionChanged = $submission->institution_name !== $request->institution_name;
            $studentNameChanged = $submission->student_name_korean !== $request->student_name_korean;
            $gradeChanged = $submission->grade !== $request->grade;
            
            // 파일명 업데이트가 필요한 경우 (기관명, 학생명, 학년 중 하나라도 변경된 경우)
            $updateFileName = $institutionChanged || $studentNameChanged || $gradeChanged;
            $newFileName = null;
            
            if ($updateFileName && $submission->video_file_name) {
                $newFileName = $this->updateVideoFileName(
                    $submission->video_file_name,
                    $request->institution_name,
                    $request->student_name_korean,
                    $request->grade
                );
            }

            // 업데이트할 데이터 준비
            $updateData = [
                'region' => $request->region,
                'institution_name' => $request->institution_name,
                'class_name' => $request->class_name,
                'student_name_korean' => $request->student_name_korean,
                'student_name_english' => $request->student_name_english,
                'grade' => $request->grade,
                'age' => $request->age,
                'parent_name' => $request->parent_name,
                'parent_phone' => $request->parent_phone,
                'unit_topic' => $request->unit_topic,
            ];
            
            // 파일명이 업데이트된 경우 추가
            if ($newFileName) {
                $updateData['video_file_name'] = $newFileName;
            }

            // 데이터 업데이트
            $submission->update($updateData);

            Log::info('접수 정보 수정 완료', [
                'admin_id' => $admin->id,
                'submission_id' => $submission->id,
                'student_name' => $submission->student_name_korean,
                'institution_changed' => $institutionChanged,
                'file_name_updated' => $updateFileName
            ]);

            return response()->json([
                'success' => true,
                'message' => '접수 정보가 성공적으로 수정되었습니다.',
                'submission' => $submission,
                'file_name_updated' => $updateFileName
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => '존재하지 않는 접수 정보입니다.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('접수 정보 수정 오류: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => '접수 정보 수정 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 비디오 파일명 업데이트 (기관명, 학생명, 학년 변경 시)
     * 형식: 기관명_이름_학년_원본파일명_타임스탬프.확장자
     */
    private function updateVideoFileName($currentFileName, $newInstitutionName, $newStudentName, $newGrade)
    {
        try {
            // 파일명에서 확장자 추출
            $extension = pathinfo($currentFileName, PATHINFO_EXTENSION);
            $fileNameWithoutExt = pathinfo($currentFileName, PATHINFO_FILENAME);
            
            // 파일명을 언더스코어로 분리
            $parts = explode('_', $fileNameWithoutExt);
            
            // 파일명 형식이 예상과 다른 경우 (타임스탬프가 여러 부분으로 나뉠 수 있음)
            // 마지막 부분이 타임스탬프일 가능성이 높음 (날짜_시간_마이크로초 형식)
            // 따라서 처음 3개 부분이 기관명_이름_학년일 가능성이 높음
            
            // 안전한 파일명 생성
            $safeInstitution = $this->sanitizeFilename($newInstitutionName ?? 'Unknown');
            $safeStudentName = $this->sanitizeFilename($newStudentName ?? 'Unknown');
            $safeGrade = $this->sanitizeFilename($newGrade ?? 'Unknown');
            
            // 기존 파일명에서 원본파일명과 타임스탬프 부분 추출 시도
            // 형식: 기관명_이름_학년_원본파일명_타임스탬프
            // 최소 5개 부분이 있어야 함
            if (count($parts) >= 5) {
                // 처음 3개는 기관명_이름_학년, 나머지는 원본파일명과 타임스탬프
                $originalAndTimestamp = implode('_', array_slice($parts, 3));
                $newFileName = sprintf(
                    '%s_%s_%s_%s.%s',
                    $safeInstitution,
                    $safeStudentName,
                    $safeGrade,
                    $originalAndTimestamp,
                    $extension
                );
            } else {
                // 형식이 예상과 다른 경우, 타임스탬프를 새로 생성
                $timestamp = date('Ymd_His') . '_' . substr(microtime(), 2, 6);
                $baseOriginalName = count($parts) > 3 ? implode('_', array_slice($parts, 3)) : 'video';
                $safeOriginalName = $this->sanitizeFilename($baseOriginalName);
                
                $newFileName = sprintf(
                    '%s_%s_%s_%s_%s.%s',
                    $safeInstitution,
                    $safeStudentName,
                    $safeGrade,
                    $safeOriginalName,
                    $timestamp,
                    $extension ?: 'mp4'
                );
            }
            
            // 파일명 길이 제한
            if (strlen($newFileName) > 200) {
                $newFileName = substr($newFileName, 0, 200) . '.' . ($extension ?: 'mp4');
            }
            
            return $newFileName;
        } catch (\Exception $e) {
            Log::error('파일명 업데이트 오류: ' . $e->getMessage(), [
                'current_file_name' => $currentFileName,
                'new_institution' => $newInstitutionName,
                'new_student' => $newStudentName,
                'new_grade' => $newGrade
            ]);
            // 오류 발생 시 원본 파일명 반환
            return $currentFileName;
        }
    }

    /**
     * 파일명 안전화 (특수문자 제거, 공백 보존)
     */
    private function sanitizeFilename($filename)
    {
        // 한글, 영문, 숫자, 공백, 언더스코어, 하이픈만 허용
        $filename = preg_replace('/[^가-힣a-zA-Z0-9 _-]/', '_', $filename);
        
        // 연속된 공백을 하나로 변경
        $filename = preg_replace('/\s+/', ' ', $filename);
        
        // 연속된 언더스코어를 하나로 변경
        $filename = preg_replace('/_+/', '_', $filename);
        
        // 앞뒤 공백 제거
        $filename = trim($filename);
        
        // 공백은 그대로 유지 (언더바로 변경하지 않음)
        
        return $filename;
    }

    /**
     * AI 설정 페이지
     */
    public function aiSettings()
    {
        // 관리자만 접근 가능
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $currentApiKey = config('services.openai.api_key');
        $apiKeySet = !empty($currentApiKey) && $currentApiKey !== 'your-openai-api-key-here';
        
        // FFmpeg 설치 상태 확인
        $ffmpegInstalled = $this->checkFFmpegInstallation();
        
        // AI 평가 통계
        $aiStats = [
            'total_evaluations' => AiEvaluation::count(),
            'completed_evaluations' => AiEvaluation::where('processing_status', 'completed')->count(),
            'failed_evaluations' => AiEvaluation::where('processing_status', 'failed')->count(),
        ];

        return view('admin.ai-settings', compact('apiKeySet', 'ffmpegInstalled', 'aiStats'));
    }

    /**
     * AI 설정 업데이트
     */
    public function updateAiSettings(Request $request)
    {
        // 관리자만 접근 가능
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $request->validate([
            'openai_api_key' => 'required|string|min:20'
        ]);

        try {
            // config/services.php 파일 업데이트
            $configPath = config_path('services.php');
            $configContent = file_get_contents($configPath);
            
            // OpenAI API 키 부분 찾기 및 교체
            $pattern = "/'openai'\s*=>\s*\[\s*'api_key'\s*=>\s*env\('OPENAI_API_KEY'(?:,\s*'[^']*')?\),?\s*\]/";
            $replacement = "'openai' => [\n        'api_key' => env('OPENAI_API_KEY', '" . $request->openai_api_key . "'),\n    ]";
            
            $newContent = preg_replace($pattern, $replacement, $configContent);
            
            if ($newContent && $newContent !== $configContent) {
                file_put_contents($configPath, $newContent);
                
                // 설정 캐시 지우기
                \Artisan::call('config:clear');
                
                Log::info('OpenAI API 키 업데이트', [
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'timestamp' => now()
                ]);

                return back()->with('success', 'OpenAI API 키가 성공적으로 업데이트되었습니다.');
            } else {
                return back()->with('error', 'config/services.php 파일 업데이트에 실패했습니다.');
            }

        } catch (\Exception $e) {
            Log::error('AI 설정 업데이트 오류: ' . $e->getMessage());
            return back()->with('error', 'AI 설정 업데이트 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * FFmpeg 설치 상태 확인
     */
    private function checkFFmpegInstallation()
    {
        $possiblePaths = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/opt/homebrew/bin/ffmpeg',
            'ffmpeg',
        ];

        foreach ($possiblePaths as $path) {
            if (shell_exec("which $path 2>/dev/null")) {
                return [
                    'installed' => true,
                    'path' => $path,
                    'version' => trim(shell_exec("$path -version 2>&1 | head -n 1"))
                ];
            }
        }

        return [
            'installed' => false,
            'path' => null,
            'version' => null
        ];
    }

    /**
     * AI 채점 결과 엑셀 다운로드
     */
    public function downloadAiEvaluationExcel(Request $request)
    {
        try {
            Log::info('AI 평가 엑셀 다운로드 시작', [
                'admin_id' => Auth::guard('admin')->id(),
                'request_params' => $request->all()
            ]);

            // soft-deleted되지 않은 video_submission과 admin이 있는 평가만 조회
            $query = AiEvaluation::with(['videoSubmission', 'admin'])
                ->whereHas('videoSubmission', function($q) {
                    // soft-deleted되지 않은 video_submission만
                })
                ->whereHas('admin', function($q) {
                    // admin이 존재하는 것만
                });

            // 완료된 평가만 다운로드
            $query->where('ai_evaluations.processing_status', AiEvaluation::STATUS_COMPLETED);
            
            Log::info('쿼리 조건 설정 완료', [
                'status_filter' => AiEvaluation::STATUS_COMPLETED
            ]);

            // 검색 필터 적용
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('videoSubmission', function($q) use ($search) {
                    $q->where('student_name_korean', 'like', "%{$search}%")
                      ->orWhere('student_name_english', 'like', "%{$search}%")
                      ->orWhere('institution_name', 'like', "%{$search}%");
                });
            }

            $aiEvaluations = $query->orderBy('ai_evaluations.created_at', 'desc')->get();

            Log::info('AI 평가 데이터 조회 완료', [
                'count' => $aiEvaluations->count(),
                'first_evaluation_id' => $aiEvaluations->first()->id ?? 'none'
            ]);

            // AI 평가 결과가 없는 경우 처리
            if ($aiEvaluations->isEmpty()) {
                Log::warning('다운로드할 AI 평가 결과가 없음');
                return back()->with('error', '[Excel Download] 다운로드할 완료된 AI 평가 결과가 없습니다.');
            }

            // Excel 파일 생성
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // 시트 이름 설정
            $sheet->setTitle('AI 평가 결과');
            
            // 헤더 설정 (AI가 평가하는 3개 항목만)
            $headers = [
                'ID', '학생명(한글)', '학생명(영문)', '기관명', '학년', '나이',
                '정확한 발음과 자연스러운 억양, 전달력', '올바른 어휘 및 표현 사용', '유창성 수준',
                'AI 총점', 'AI 심사평', '음성인식 텍스트', '평가일시', '평가자'
            ];
            
            // 헤더 행 추가
            $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'];
            foreach ($headers as $index => $header) {
                $sheet->setCellValue($columns[$index] . '1', $header);
            }
            
            // 헤더 스타일 설정
            $headerRange = 'A1:' . $columns[count($headers) - 1] . '1';
            $sheet->getStyle($headerRange)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ]);
            
            // 데이터 행 추가 (AI가 평가하는 3개 항목만)
            $row = 2;
            foreach ($aiEvaluations as $evaluation) {
                // videoSubmission과 admin이 없는 경우 건너뛰기
                if (!$evaluation->videoSubmission || !$evaluation->admin) {
                    Log::warning('Excel 다운로드: videoSubmission 또는 admin이 없는 평가 건너뜀', [
                        'evaluation_id' => $evaluation->id
                    ]);
                    continue;
                }
                
                $data = [
                    $evaluation->videoSubmission->id ?? '',
                    $evaluation->videoSubmission->student_name_korean ?? '',
                    $evaluation->videoSubmission->student_name_english ?? '',
                    $evaluation->videoSubmission->institution_name ?? '',
                    $evaluation->videoSubmission->grade ?? '',
                    $evaluation->videoSubmission->age ?? '',
                    $evaluation->pronunciation_score ?? 0,
                    $evaluation->vocabulary_score ?? 0,
                    $evaluation->fluency_score ?? 0,
                    $evaluation->total_score ?? 0,
                    $evaluation->ai_feedback ?? '',
                    $evaluation->transcription ?? '',
                    $evaluation->processed_at ? $evaluation->processed_at->format('Y-m-d H:i:s') : '',
                    $evaluation->admin->name ?? ''
                ];
                
                foreach ($data as $index => $value) {
                    $sheet->setCellValue($columns[$index] . $row, $value);
                }
                $row++;
            }
            
            // 컬럼 너비 자동 조정
            foreach ($columns as $index => $col) {
                if ($index >= count($headers)) break;
                
                if (in_array($col, ['K', 'L'])) { // AI 심사평, 음성인식 텍스트 컬럼
                    $sheet->getColumnDimension($col)->setWidth(50);
                } else {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
            
            // 데이터 영역 스타일 설정
            $dataRange = 'A2:' . $columns[count($headers) - 1] . ($row - 1);
            $sheet->getStyle($dataRange)->applyFromArray([
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true // 텍스트 자동 줄바꿈
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ]);
            
            // 점수 컬럼 가운데 정렬
            $scoreColumns = ['G', 'H', 'I', 'J']; // 발음, 어휘, 유창성, AI 총점
            foreach ($scoreColumns as $col) {
                $sheet->getStyle($col . '2:' . $col . ($row - 1))->getAlignment()
                     ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            
            // 파일명 생성
            $filename = 'AI평가결과_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // Excel 파일을 메모리에 저장
            $writer = new Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'excel');
            $writer->save($tempFile);
            
            // 응답 생성
            $response = response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ])->deleteFileAfterSend(true);

            Log::info('AI 평가 결과 Excel 다운로드', [
                'admin_id' => Auth::guard('admin')->id(),
                'count' => count($aiEvaluations),
                'filename' => $filename
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('AI 평가 결과 엑셀 다운로드 오류: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', '[Excel Download Error] AI 평가 결과 Excel 파일 생성 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 일괄 AI 채점 시작
     */
    public function startBatchAiEvaluation(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '관리자만 접근할 수 있습니다.'
            ], 403);
        }

        try {
            // 처리할 영상들 가져오기 (AI 평가가 완료되지 않은 것들)
            $submissions = VideoSubmission::whereDoesntHave('aiEvaluations', function($query) {
                $query->where('processing_status', AiEvaluation::STATUS_COMPLETED);
            })->get();

            if ($submissions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => '처리할 영상이 없습니다. 모든 영상이 이미 AI 채점이 완료되었습니다.'
                ]);
            }

            $queuedCount = 0;
            $alreadyProcessingCount = 0;
            $noFileCount = 0;

            foreach ($submissions as $submission) {
                // 기존 AI 평가가 처리 중인지 확인
                $existingEvaluation = AiEvaluation::where('video_submission_id', $submission->id)
                    ->where('processing_status', AiEvaluation::STATUS_PROCESSING)
                    ->first();

                if ($existingEvaluation) {
                    $alreadyProcessingCount++;
                    continue;
                }

                // 영상 파일 존재 여부 확인
                if (!$this->checkVideoFileExists($submission)) {
                    Log::warning('영상 파일이 존재하지 않아 건너뜀', [
                        'submission_id' => $submission->id,
                        'video_path' => $submission->video_file_path
                    ]);
                    
                    // AI 평가 레코드를 실패 상태로 생성
                    $aiEvaluation = AiEvaluation::where('video_submission_id', $submission->id)->first() ?? new AiEvaluation();
                    $aiEvaluation->video_submission_id = $submission->id;
                    $aiEvaluation->admin_id = $admin->id;
                    $aiEvaluation->processing_status = AiEvaluation::STATUS_FAILED;
                    $aiEvaluation->error_message = '영상 파일이 존재하지 않습니다.';
                    $aiEvaluation->save();
                    
                    $noFileCount++;
                    continue;
                }

                // Job을 큐에 추가 (비동기 처리)
                \App\Jobs\BatchAiEvaluationJob::dispatch($submission->id, $admin->id);
                $queuedCount++;
            }

            Log::info('일괄 AI 채점 시작', [
                'admin_id' => $admin->id,
                'total_submissions' => $submissions->count(),
                'queued_jobs' => $queuedCount,
                'already_processing' => $alreadyProcessingCount,
                'no_file' => $noFileCount
            ]);

            $message = "일괄 AI 채점을 시작했습니다. {$queuedCount}개의 영상이 처리 대기열에 추가되었습니다.";
            if ($alreadyProcessingCount > 0) {
                $message .= " (이미 처리 중인 {$alreadyProcessingCount}개 영상은 건너뛰었습니다.)";
            }
            if ($noFileCount > 0) {
                $message .= " (영상 파일이 없는 {$noFileCount}개 영상은 제외되었습니다.)";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'total_submissions' => $submissions->count(),
                    'queued_jobs' => $queuedCount,
                    'already_processing' => $alreadyProcessingCount,
                    'no_file' => $noFileCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('일괄 AI 채점 시작 오류: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '일괄 AI 채점 시작 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 일괄 AI 채점 진행상황 조회
     */
    public function getBatchAiEvaluationProgress(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '관리자만 접근할 수 있습니다.'
            ], 403);
        }

        try {
            // 전체 영상 수
            $totalSubmissions = VideoSubmission::count();

            // AI 평가 완료된 영상 수 (제출 영상 기준)
            $completedEvaluations = VideoSubmission::whereHas('aiEvaluations', function($query) {
                $query->where('processing_status', AiEvaluation::STATUS_COMPLETED);
            })->count();

            // 처리 중인 영상 수 (제출 영상 기준)
            $processingEvaluations = VideoSubmission::whereHas('aiEvaluations', function($query) {
                $query->where('processing_status', AiEvaluation::STATUS_PROCESSING);
            })->count();

            // 파일없음 영상 수 (제출 영상 기준)
            $noFileEvaluations = VideoSubmission::whereHas('aiEvaluations', function($query) {
                $query->where('processing_status', AiEvaluation::STATUS_FAILED)
                      ->where('error_message', '영상 파일이 존재하지 않습니다.');
            })->count();
            
            // 실패한 영상 수 (파일없음 제외, 제출 영상 기준)
            $failedEvaluations = VideoSubmission::whereHas('aiEvaluations', function($query) {
                $query->where('processing_status', AiEvaluation::STATUS_FAILED)
                      ->where(function($q) {
                          $q->where('error_message', '!=', '영상 파일이 존재하지 않습니다.')
                            ->orWhereNull('error_message');
                      });
            })->count();

            // 대기 중인 영상 수 (AI 평가가 없는 영상)
            $pendingSubmissions = VideoSubmission::whereDoesntHave('aiEvaluations')->count();

            // 진행률 계산
            $progressPercentage = $totalSubmissions > 0 ? round(($completedEvaluations / $totalSubmissions) * 100, 1) : 0;
            
            Log::info('진행률 계산', [
                'completed_evaluations' => $completedEvaluations,
                'total_submissions' => $totalSubmissions,
                'progress_percentage' => $progressPercentage
            ]);

            // 최근 처리된 평가들 (최근 10개)
            $recentEvaluations = AiEvaluation::with(['videoSubmission', 'admin'])
                ->whereHas('videoSubmission')
                ->whereHas('admin')
                ->where('processing_status', AiEvaluation::STATUS_COMPLETED)
                ->orderBy('processed_at', 'desc')
                ->limit(10)
                ->get()
                ->filter(function($evaluation) {
                    return $evaluation->videoSubmission && $evaluation->admin;
                })
                ->map(function($evaluation) {
                    return [
                        'id' => $evaluation->id,
                        'student_name' => $evaluation->videoSubmission->student_name_korean ?? '',
                        'institution' => $evaluation->videoSubmission->institution_name ?? '',
                        'total_score' => $evaluation->total_score ?? 0,
                        'processed_at' => $evaluation->processed_at ? $evaluation->processed_at->format('Y-m-d H:i:s') : null,
                        'admin_name' => $evaluation->admin->name ?? '시스템'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_submissions' => $totalSubmissions,
                    'completed_evaluations' => $completedEvaluations,
                    'processing_evaluations' => $processingEvaluations,
                    'failed_evaluations' => $failedEvaluations,
                    'no_file_evaluations' => $noFileEvaluations,
                    'pending_submissions' => $pendingSubmissions,
                    'progress_percentage' => $progressPercentage,
                    'recent_evaluations' => $recentEvaluations
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('일괄 AI 채점 진행상황 조회 오류: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '진행상황 조회 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 일괄 AI 채점 취소
     */
    public function cancelBatchAiEvaluation(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '관리자만 접근할 수 있습니다.'
            ], 403);
        }

        try {
            // 처리 중인 AI 평가들을 실패로 변경
            $processingEvaluations = AiEvaluation::where('processing_status', AiEvaluation::STATUS_PROCESSING)->get();
            
            $cancelledCount = 0;
            foreach ($processingEvaluations as $evaluation) {
                $evaluation->update([
                    'processing_status' => AiEvaluation::STATUS_FAILED,
                    'error_message' => '관리자에 의해 취소되었습니다.'
                ]);
                $cancelledCount++;
            }

            // 큐에 있는 대기 중인 작업들 제거 (Laravel 8+ 방식)
            // 주의: 이 방법은 모든 큐 작업을 제거하므로 신중하게 사용
            \Illuminate\Support\Facades\Artisan::call('queue:clear', ['--queue' => 'default']);

            Log::info('일괄 AI 채점 취소', [
                'admin_id' => $admin->id,
                'cancelled_evaluations' => $cancelledCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "일괄 AI 채점을 취소했습니다. {$cancelledCount}개의 처리 중인 평가가 중단되었습니다.",
                'data' => [
                    'cancelled_count' => $cancelledCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('일괄 AI 채점 취소 오류: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '일괄 AI 채점 취소 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 실패한 AI 평가 재시도
     */
    public function retryFailedAiEvaluations(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '관리자만 접근할 수 있습니다.'
            ], 403);
        }

        try {
            // 실패한 AI 평가들 가져오기
            $failedEvaluations = AiEvaluation::where('processing_status', AiEvaluation::STATUS_FAILED)->get();

            if ($failedEvaluations->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => '재시도할 실패한 평가가 없습니다.'
                ]);
            }

            $retryCount = 0;

            foreach ($failedEvaluations as $evaluation) {
                // Job을 큐에 추가
                \App\Jobs\BatchAiEvaluationJob::dispatch($evaluation->video_submission_id, $admin->id);
                $retryCount++;
            }

            Log::info('실패한 AI 평가 재시도', [
                'admin_id' => $admin->id,
                'retry_count' => $retryCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$retryCount}개의 실패한 평가를 재시도 대기열에 추가했습니다.",
                'data' => [
                    'retry_count' => $retryCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('실패한 AI 평가 재시도 오류: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '재시도 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 영상 일괄 채점 페이지
     */
    public function batchEvaluationList(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        try {
            // 검색 및 필터링 파라미터
            $search = $request->get('search', '');
            $status = $request->get('status', 'all');
            $institution = $request->get('institution', '');
            $sortBy = $request->get('sort', 'created_at');
            $sortOrder = $request->get('order', 'desc');

            // 기본 쿼리
            $query = VideoSubmission::with(['aiEvaluations', 'evaluations']);

            // 검색 조건
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('student_name_korean', 'like', "%{$search}%")
                      ->orWhere('student_name_english', 'like', "%{$search}%")
                      ->orWhere('institution_name', 'like', "%{$search}%")
                      ->orWhere('class_name', 'like', "%{$search}%");
                });
            }

            // 기관 필터
            if (!empty($institution)) {
                $query->where('institution_name', $institution);
            }

            // AI 평가 상태 필터
            if ($status !== 'all') {
                switch ($status) {
                    case 'completed':
                        $query->whereHas('aiEvaluations', function($q) {
                            $q->where('processing_status', AiEvaluation::STATUS_COMPLETED);
                        });
                        break;
                    case 'processing':
                        $query->whereHas('aiEvaluations', function($q) {
                            $q->where('processing_status', AiEvaluation::STATUS_PROCESSING);
                        });
                        break;
                    case 'failed':
                        $query->whereHas('aiEvaluations', function($q) {
                            $q->where('processing_status', AiEvaluation::STATUS_FAILED)
                              ->where('error_message', '!=', '영상 파일이 존재하지 않습니다.');
                        });
                        break;
                    case 'no_file':
                        $query->whereHas('aiEvaluations', function($q) {
                            $q->where('processing_status', AiEvaluation::STATUS_FAILED)
                              ->where('error_message', '영상 파일이 존재하지 않습니다.');
                        });
                        break;
                    case 'pending':
                        $query->whereDoesntHave('aiEvaluations');
                        break;
                }
            }

            // 정렬
            $allowedSortFields = ['created_at', 'student_name_korean', 'institution_name', 'video_file_name'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // 페이지네이션
            $submissions = $query->paginate(20)->appends($request->query());

            // 통계 데이터 (제출 영상 기준으로 카운트)
            $totalSubmissions = VideoSubmission::count();
            
            // AI 평가 완료된 영상 수
            $completedEvaluations = VideoSubmission::whereHas('aiEvaluations', function($query) {
                $query->where('processing_status', AiEvaluation::STATUS_COMPLETED);
            })->count();
            
            // 처리 중인 영상 수
            $processingEvaluations = VideoSubmission::whereHas('aiEvaluations', function($query) {
                $query->where('processing_status', AiEvaluation::STATUS_PROCESSING);
            })->count();
            
            // 파일없음 영상 수
            $noFileEvaluations = VideoSubmission::whereHas('aiEvaluations', function($query) {
                $query->where('processing_status', AiEvaluation::STATUS_FAILED)
                      ->where('error_message', '영상 파일이 존재하지 않습니다.');
            })->count();
            
            // 실패한 영상 수 (파일없음 제외)
            $failedEvaluations = VideoSubmission::whereHas('aiEvaluations', function($query) {
                $query->where('processing_status', AiEvaluation::STATUS_FAILED)
                      ->where(function($q) {
                          $q->where('error_message', '!=', '영상 파일이 존재하지 않습니다.')
                            ->orWhereNull('error_message');
                      });
            })->count();
            
            // 대기 중인 영상 수 (AI 평가가 없는 영상)
            $pendingSubmissions = VideoSubmission::whereDoesntHave('aiEvaluations')->count();

            // 기관 목록 (필터용)
            $institutions = VideoSubmission::select('institution_name')
                ->distinct()
                ->orderBy('institution_name')
                ->pluck('institution_name');

            // 진행률 계산
            $progressPercentage = $totalSubmissions > 0 ? round(($completedEvaluations / $totalSubmissions) * 100, 1) : 0;

            return view('admin.batch-evaluation', compact(
                'submissions',
                'totalSubmissions',
                'completedEvaluations',
                'processingEvaluations',
                'failedEvaluations',
                'noFileEvaluations',
                'pendingSubmissions',
                'progressPercentage',
                'institutions',
                'search',
                'status',
                'institution',
                'sortBy',
                'sortOrder'
            ));

        } catch (\Exception $e) {
            Log::error('영상 일괄 채점 페이지 오류: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', '페이지 로드 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 개별 영상 AI 채점 시작
     */
    public function startSingleAiEvaluation(Request $request, $submissionId)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '관리자만 접근할 수 있습니다.'
            ], 403);
        }

        try {
            // 영상 제출 정보 확인
            $submission = VideoSubmission::find($submissionId);
            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => '영상을 찾을 수 없습니다.'
                ], 404);
            }

            // 영상 파일 존재 여부 확인
            if (!$this->checkVideoFileExists($submission)) {
                return response()->json([
                    'success' => false,
                    'message' => '영상 파일이 존재하지 않습니다.'
                ], 400);
            }

            // 기존 AI 평가가 처리 중인지 확인
            $existingEvaluation = AiEvaluation::where('video_submission_id', $submissionId)
                ->where('processing_status', AiEvaluation::STATUS_PROCESSING)
                ->first();

            if ($existingEvaluation) {
                return response()->json([
                    'success' => false,
                    'message' => '이미 처리 중인 영상입니다.'
                ]);
            }

            // AI 평가 레코드 생성 또는 업데이트
            $aiEvaluation = AiEvaluation::updateOrCreate(
                [
                    'video_submission_id' => $submissionId,
                    'admin_id' => $admin->id
                ],
                [
                    'processing_status' => AiEvaluation::STATUS_PROCESSING,
                    'error_message' => null,
                    'ai_feedback' => '대용량 파일 처리 중입니다. 영상 길이에 따라 5-15분 소요될 수 있습니다.'
                ]
            );

            // OpenAI API를 사용한 실제 AI 평가 처리 (동기)
            try {
                $openAiService = new OpenAiService();
                $result = $openAiService->evaluateVideo($submission->video_file_path);

                // 결과 저장
                $aiEvaluation->update([
                    'pronunciation_score' => $result['pronunciation_score'],
                    'vocabulary_score' => $result['vocabulary_score'],
                    'fluency_score' => $result['fluency_score'],
                    'transcription' => $result['transcription'],
                    'ai_feedback' => $result['ai_feedback'],
                    'processing_status' => AiEvaluation::STATUS_COMPLETED,
                    'processed_at' => now()
                ]);

                // 총점 계산
                $aiEvaluation->calculateTotalScore();
                $aiEvaluation->save();

                Log::info('개별 AI 채점 완료', [
                    'admin_id' => $admin->id,
                    'submission_id' => $submissionId,
                    'total_score' => $aiEvaluation->total_score
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'AI 채점이 성공적으로 완료되었습니다.',
                    'ai_evaluation_id' => $aiEvaluation->id
                ]);

            } catch (\Exception $e) {
                Log::error('개별 AI 채점 실패', [
                    'admin_id' => $admin->id,
                    'submission_id' => $submissionId,
                    'error' => $e->getMessage()
                ]);

                $aiEvaluation->update([
                    'processing_status' => AiEvaluation::STATUS_FAILED,
                    'error_message' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'AI 채점 중 오류가 발생했습니다: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('개별 AI 채점 시작 오류: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
                'submission_id' => $submissionId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'AI 채점 시작 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AI 평가 재채점
     */
    public function reevaluateAiEvaluation(Request $request, $aiEvaluationId)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '관리자만 접근할 수 있습니다.'
            ], 403);
        }

        try {
            // AI 평가 정보 확인
            $aiEvaluation = AiEvaluation::find($aiEvaluationId);
            if (!$aiEvaluation) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI 평가를 찾을 수 없습니다.'
                ], 404);
            }

            // 영상 제출 정보 확인
            $submission = $aiEvaluation->videoSubmission;
            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => '영상 정보를 찾을 수 없습니다.'
                ], 404);
            }

            // 영상 파일 존재 여부 확인
            if (!$this->checkVideoFileExists($submission)) {
                return response()->json([
                    'success' => false,
                    'message' => '영상 파일이 존재하지 않습니다.'
                ], 400);
            }

            // 이미 처리 중인지 확인
            if ($aiEvaluation->processing_status === AiEvaluation::STATUS_PROCESSING) {
                return response()->json([
                    'success' => false,
                    'message' => '이미 처리 중인 평가입니다.'
                ], 400);
            }

            // AI 평가 상태를 처리 중으로 변경
            $aiEvaluation->update([
                'processing_status' => AiEvaluation::STATUS_PROCESSING,
                'error_message' => null,
                'ai_feedback' => '재채점 처리 중입니다. 영상 길이에 따라 5-15분 소요될 수 있습니다.'
            ]);

            // OpenAI API를 사용한 실제 AI 평가 처리
            try {
                $openAiService = new OpenAiService();
                $result = $openAiService->evaluateVideo($submission->video_file_path);

                // 결과 저장
                $aiEvaluation->update([
                    'pronunciation_score' => $result['pronunciation_score'],
                    'vocabulary_score' => $result['vocabulary_score'],
                    'fluency_score' => $result['fluency_score'],
                    'transcription' => $result['transcription'],
                    'ai_feedback' => $result['ai_feedback'],
                    'processing_status' => AiEvaluation::STATUS_COMPLETED,
                    'processed_at' => now()
                ]);

                // 총점 계산
                $aiEvaluation->calculateTotalScore();
                $aiEvaluation->save();

                Log::info('AI 재채점 완료', [
                    'admin_id' => $admin->id,
                    'ai_evaluation_id' => $aiEvaluationId,
                    'submission_id' => $submission->id,
                    'total_score' => $aiEvaluation->total_score
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'AI 재채점이 성공적으로 완료되었습니다.',
                    'ai_evaluation_id' => $aiEvaluation->id
                ]);

            } catch (\Exception $e) {
                Log::error('AI 재채점 실패', [
                    'admin_id' => $admin->id,
                    'ai_evaluation_id' => $aiEvaluationId,
                    'submission_id' => $submission->id,
                    'error' => $e->getMessage()
                ]);

                $aiEvaluation->update([
                    'processing_status' => AiEvaluation::STATUS_FAILED,
                    'error_message' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'AI 재채점 중 오류가 발생했습니다: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('AI 재채점 시작 오류: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
                'ai_evaluation_id' => $aiEvaluationId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'AI 재채점 시작 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 영상 파일 존재 여부 확인
     */
    private function checkVideoFileExists($submission)
    {
        try {
            // S3에 저장된 경우
            if ($submission->isStoredOnS3()) {
                return \Illuminate\Support\Facades\Storage::disk('s3')->exists($submission->video_file_path);
            }
            
            // 로컬에 저장된 경우 - public 디스크 사용
            if ($submission->isStoredLocally()) {
                return \Illuminate\Support\Facades\Storage::disk('public')->exists($submission->video_file_path);
            }
            
            // 기본 스토리지에서 확인 (public 디스크)
            return \Illuminate\Support\Facades\Storage::disk('public')->exists($submission->video_file_path);
            
        } catch (\Exception $e) {
            Log::error('영상 파일 존재 여부 확인 중 오류', [
                'submission_id' => $submission->id,
                'video_path' => $submission->video_file_path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 선택된 영상들 삭제
     */
    public function deleteSelectedVideos(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '관리자만 접근할 수 있습니다.'
            ], 403);
        }

        try {
            $ids = $request->input('ids', []);
            
            if (empty($ids) || !is_array($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => '삭제할 영상을 선택해주세요.'
                ]);
            }

            // 영상 제출 데이터 조회
            $submissions = VideoSubmission::whereIn('id', $ids)->get();
            
            if ($submissions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => '삭제할 영상을 찾을 수 없습니다.'
                ]);
            }

            $deletedCount = 0;
            $deletedFiles = [];

            foreach ($submissions as $submission) {
                try {
                    // Soft Delete로 변경 (파일은 삭제하지 않고 휴지통으로 이동)
                    // 관련 데이터는 그대로 유지 (복원 시 필요)
                    
                    // 영상 제출 데이터를 Soft Delete (휴지통으로 이동)
                    $submission->delete(); // SoftDeletes 트레이트로 인해 soft delete 됨
                    $deletedCount++;
                    
                } catch (\Exception $e) {
                    Log::error('영상 삭제 중 오류', [
                        'submission_id' => $submission->id,
                        'student_name' => $submission->student_name_korean,
                        'error' => $e->getMessage()
                    ]);
                    // 개별 영상 삭제 실패해도 계속 진행
                }
            }

            Log::info('영상 일괄 삭제 완료', [
                'admin_id' => $admin->id,
                'requested_count' => count($ids),
                'deleted_count' => $deletedCount,
                'deleted_files' => $deletedFiles
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount}개의 영상이 휴지통으로 이동되었습니다.",
                'data' => [
                    'deleted_count' => $deletedCount,
                    'requested_count' => count($ids)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('영상 일괄 삭제 오류: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
                'requested_ids' => $ids,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '영상 삭제 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 휴지통 목록
     */
    public function trashList(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        // 검색어 가져오기
        $searchQuery = $request->get('search', '');
        
        // 삭제된 영상들 조회 (Soft Deleted)
        $trashedQuery = VideoSubmission::onlyTrashed()
                                      ->with(['evaluation', 'assignment.admin']);
        
        // 검색어가 있으면 필터링
        if (!empty($searchQuery)) {
            $trashedQuery->where(function($query) use ($searchQuery) {
                $query->where('student_name_korean', 'like', '%' . $searchQuery . '%')
                      ->orWhere('student_name_english', 'like', '%' . $searchQuery . '%')
                      ->orWhere('institution_name', 'like', '%' . $searchQuery . '%')
                      ->orWhere('class_name', 'like', '%' . $searchQuery . '%')
                      ->orWhere('video_file_name', 'like', '%' . $searchQuery . '%');
                
                // 접수번호 검색
                $driver = DB::connection()->getDriverName();
                
                if (is_numeric($searchQuery)) {
                    $query->orWhere('id', $searchQuery);
                }
                
                if ($driver === 'sqlite') {
                    $query->orWhereRaw("('GSK-' || substr('00000' || CAST(id AS TEXT), -5)) LIKE ?", ['%' . $searchQuery . '%']);
                } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                    $query->orWhereRaw("CONCAT('GSK-', LPAD(id, 5, '0')) LIKE ?", ['%' . $searchQuery . '%']);
                } elseif ($driver === 'pgsql') {
                    $query->orWhereRaw("CONCAT('GSK-', LPAD(id::text, 5, '0')) LIKE ?", ['%' . $searchQuery . '%']);
                }
            });
        }
        
        $trashedSubmissions = $trashedQuery->orderBy('deleted_at', 'desc')
                                          ->paginate(20)
                                          ->appends($request->query());

        return view('admin.trash-list', compact('trashedSubmissions', 'searchQuery'));
    }

    /**
     * 영상 복원
     */
    public function restoreVideo($id)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '관리자만 접근할 수 있습니다.'
            ], 403);
        }

        try {
            $submission = VideoSubmission::onlyTrashed()->findOrFail($id);
            $submission->restore();

            Log::info('영상 복원 완료', [
                'admin_id' => $admin->id,
                'submission_id' => $id,
                'student_name' => $submission->student_name_korean
            ]);

            return response()->json([
                'success' => true,
                'message' => '영상이 복원되었습니다.'
            ]);

        } catch (\Exception $e) {
            Log::error('영상 복원 오류: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
                'submission_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => '영상 복원 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 영상 완전 삭제 (휴지통에서 영구 삭제)
     */
    public function forceDeleteVideo($id)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '관리자만 접근할 수 있습니다.'
            ], 403);
        }

        try {
            $submission = VideoSubmission::onlyTrashed()->findOrFail($id);
            
            // 관련 데이터 삭제
            $submission->aiEvaluations()->delete();
            $submission->evaluations()->delete();
            $submission->assignments()->delete();
            
            // 영상 파일 삭제 (S3 또는 로컬)
            if ($submission->video_file_path) {
                if ($submission->isStoredOnS3()) {
                    Storage::disk('s3')->delete($submission->video_file_path);
                } else {
                    Storage::disk('public')->delete($submission->video_file_path);
                }
            }
            
            // 영구 삭제
            $submission->forceDelete();

            Log::info('영상 영구 삭제 완료', [
                'admin_id' => $admin->id,
                'submission_id' => $id,
                'student_name' => $submission->student_name_korean
            ]);

            return response()->json([
                'success' => true,
                'message' => '영상이 영구적으로 삭제되었습니다.'
            ]);

        } catch (\Exception $e) {
            Log::error('영상 영구 삭제 오류: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
                'submission_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => '영상 삭제 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 선택된 영상 복원
     */
    public function restoreSelectedVideos(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '관리자만 접근할 수 있습니다.'
            ], 403);
        }

        try {
            $ids = $request->input('ids', []);
            
            if (empty($ids) || !is_array($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => '복원할 영상을 선택해주세요.'
                ]);
            }

            $restoredCount = VideoSubmission::onlyTrashed()
                                          ->whereIn('id', $ids)
                                          ->restore();

            Log::info('영상 일괄 복원 완료', [
                'admin_id' => $admin->id,
                'restored_count' => $restoredCount,
                'requested_ids' => $ids
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$restoredCount}개의 영상이 복원되었습니다.",
                'data' => [
                    'restored_count' => $restoredCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('영상 일괄 복원 오류: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '영상 복원 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 선택된 영상 영구 삭제
     */
    public function forceDeleteSelectedVideos(Request $request)
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '관리자만 접근할 수 있습니다.'
            ], 403);
        }

        try {
            $ids = $request->input('ids', []);
            
            if (empty($ids) || !is_array($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => '삭제할 영상을 선택해주세요.'
                ]);
            }

            $submissions = VideoSubmission::onlyTrashed()->whereIn('id', $ids)->get();
            $deletedCount = 0;

            foreach ($submissions as $submission) {
                try {
                    // 관련 데이터 삭제
                    $submission->aiEvaluations()->delete();
                    $submission->evaluations()->delete();
                    $submission->assignments()->delete();
                    
                    // 영상 파일 삭제
                    if ($submission->video_file_path) {
                        if ($submission->isStoredOnS3()) {
                            Storage::disk('s3')->delete($submission->video_file_path);
                        } else {
                            Storage::disk('public')->delete($submission->video_file_path);
                        }
                    }
                    
                    // 영구 삭제
                    $submission->forceDelete();
                    $deletedCount++;
                } catch (\Exception $e) {
                    Log::error('영상 영구 삭제 중 오류', [
                        'submission_id' => $submission->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('영상 일괄 영구 삭제 완료', [
                'admin_id' => $admin->id,
                'deleted_count' => $deletedCount,
                'requested_ids' => $ids
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount}개의 영상이 영구적으로 삭제되었습니다.",
                'data' => [
                    'deleted_count' => $deletedCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('영상 일괄 영구 삭제 오류: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '영상 삭제 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
}
