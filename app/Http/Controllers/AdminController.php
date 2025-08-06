<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin;
use App\Models\VideoSubmission;
use App\Models\Evaluation;
use App\Models\VideoAssignment;

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

        return back()->with('error', '아이디 또는 비밀번호가 올바르지 않습니다.')
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
        
        // 최근 업로드된 영상들
        $recentSubmissions = VideoSubmission::with(['evaluation', 'assignment.admin'])
                                          ->orderBy('created_at', 'desc')
                                          ->take(10)
                                          ->get();

        // 심사위원별 배정 현황
        $adminStats = Admin::withCount(['videoAssignments', 'evaluations'])
                          ->get()
                          ->map(function ($admin) {
                              $admin->in_progress_count = $admin->videoAssignments()
                                  ->where('status', VideoAssignment::STATUS_IN_PROGRESS)
                                  ->count();
                              return $admin;
                          });

        return view('admin.dashboard', compact(
            'totalSubmissions',
            'evaluatedSubmissions',
            'assignedSubmissions',
            'pendingSubmissions',
            'recentSubmissions',
            'adminStats'
        ));
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

        $submissions = $query->orderBy('created_at', 'desc')->paginate(20);

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
            'confidence_score' => '자신감, 긍정적이고 밝은 태도'
        ];

        return view('admin.evaluation-form', compact('submission', 'criteriaLabels'));
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
            'pronunciation_score' => 'required|integer|min:1|max:100',
            'vocabulary_score' => 'required|integer|min:1|max:100',
            'fluency_score' => 'required|integer|min:1|max:100',
            'confidence_score' => 'required|integer|min:1|max:100',
            'comments' => 'nullable|string|max:1000'
        ], [
            'pronunciation_score.required' => '발음 점수를 입력해주세요.',
            'vocabulary_score.required' => '어휘 점수를 입력해주세요.',
            'fluency_score.required' => '유창성 점수를 입력해주세요.',
            'confidence_score.required' => '자신감 점수를 입력해주세요.',
            'pronunciation_score.min' => '점수는 1점 이상이어야 합니다.',
            'vocabulary_score.min' => '점수는 1점 이상이어야 합니다.',
            'fluency_score.min' => '점수는 1점 이상이어야 합니다.',
            'confidence_score.min' => '점수는 1점 이상이어야 합니다.',
            'pronunciation_score.max' => '점수는 100점 이하여야 합니다.',
            'vocabulary_score.max' => '점수는 100점 이하여야 합니다.',
            'fluency_score.max' => '점수는 100점 이하여야 합니다.',
            'confidence_score.max' => '점수는 100점 이하여야 합니다.',
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
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $submissions = VideoSubmission::with(['evaluation', 'assignment.admin'])
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        // PhpSpreadsheet 사용
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 헤더 설정
        $headers = [
            'ID', '학생명(한글)', '학생명(영어)', '기관명', '반명', '학년', '나이',
            '학부모명', '연락처', 'Unit주제', '업로드일시', '파일명', '파일크기',
            '배정된심사위원', '배정일시', '심사상태', '발음점수', '어휘점수', 
            '유창성점수', '자신감점수', '총점', '심사코멘트', '심사완료일시'
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
        foreach ($submissions as $submission) {
            $sheet->setCellValue('A' . $rowIndex, $submission->id);
            $sheet->setCellValue('B' . $rowIndex, $submission->student_name_korean);
            $sheet->setCellValue('C' . $rowIndex, $submission->student_name_english);
            $sheet->setCellValue('D' . $rowIndex, $submission->institution_name);
            $sheet->setCellValue('E' . $rowIndex, $submission->class_name);
            $sheet->setCellValue('F' . $rowIndex, $submission->grade);
            $sheet->setCellValue('G' . $rowIndex, $submission->age);
            $sheet->setCellValue('H' . $rowIndex, $submission->parent_name);
            $sheet->setCellValue('I' . $rowIndex, $submission->parent_phone);
            $sheet->setCellValue('J' . $rowIndex, $submission->unit_topic);
            $sheet->setCellValue('K' . $rowIndex, $submission->created_at->format('Y-m-d H:i:s'));
            $sheet->setCellValue('L' . $rowIndex, $submission->video_file_name);
            $sheet->setCellValue('M' . $rowIndex, $submission->getFormattedFileSizeAttribute());
            $sheet->setCellValue('N' . $rowIndex, $submission->assignment ? $submission->assignment->admin->name : '미배정');
            $sheet->setCellValue('O' . $rowIndex, $submission->assignment ? $submission->assignment->created_at->format('Y-m-d H:i:s') : '');
            $sheet->setCellValue('P' . $rowIndex, $submission->evaluation ? '완료' : '미완료');
            $sheet->setCellValue('Q' . $rowIndex, $submission->evaluation ? $submission->evaluation->pronunciation_score : '');
            $sheet->setCellValue('R' . $rowIndex, $submission->evaluation ? $submission->evaluation->vocabulary_score : '');
            $sheet->setCellValue('S' . $rowIndex, $submission->evaluation ? $submission->evaluation->fluency_score : '');
            $sheet->setCellValue('T' . $rowIndex, $submission->evaluation ? $submission->evaluation->confidence_score : '');
            $sheet->setCellValue('U' . $rowIndex, $submission->evaluation ? $submission->evaluation->total_score : '');
            $sheet->setCellValue('V' . $rowIndex, $submission->evaluation ? $submission->evaluation->comments : '');
            $sheet->setCellValue('W' . $rowIndex, $submission->evaluation && $submission->evaluation->evaluated_at ? $submission->evaluation->evaluated_at->format('Y-m-d H:i:s') : '');
            
            $rowIndex++;
        }

        // 열 너비 자동 조정
        foreach (range('A', 'W') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // 파일명 생성
        $filename = 'speech_contest_data_' . date('Y-m-d_H-i-s') . '.xlsx';

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
        $evaluatedSubmissions = VideoSubmission::whereHas('evaluation')->count();
        $assignedSubmissions = VideoSubmission::whereHas('assignment')->count();
        $pendingSubmissions = $totalSubmissions - $assignedSubmissions;

        // 기관별 통계
        $institutionStats = VideoSubmission::selectRaw('institution_name, COUNT(*) as count')
                                         ->groupBy('institution_name')
                                         ->orderBy('count', 'desc')
                                         ->get();

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

        // 평균 점수 통계
        $averageScores = Evaluation::selectRaw('
            AVG(pronunciation_score) as avg_pronunciation,
            AVG(vocabulary_score) as avg_vocabulary,
            AVG(fluency_score) as avg_fluency,
            AVG(confidence_score) as avg_confidence,
            AVG(total_score) as avg_total
        ')->first();

        return view('admin.statistics', compact(
            'totalSubmissions', 'evaluatedSubmissions', 'assignedSubmissions', 'pendingSubmissions',
            'institutionStats', 'judgeStats', 'dailyStats', 'averageScores'
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

        $assignments = VideoAssignment::with(['videoSubmission', 'admin'])
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        $unassignedVideos = VideoSubmission::whereDoesntHave('assignment')
                                          ->orderBy('created_at', 'desc')
                                          ->get();

        $admins = Admin::where('is_active', true)
                      ->where('role', 'judge') // 심사위원만 표시
                      ->get();

        return view('admin.assignment-list', compact('assignments', 'unassignedVideos', 'admins'));
    }

    /**
     * 영상 배정
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

        // 이미 배정된 영상인지 확인
        $existingAssignment = VideoAssignment::where('video_submission_id', $request->video_submission_id)->first();
        if ($existingAssignment) {
            return back()->with('error', '이미 배정된 영상입니다.');
        }

        VideoAssignment::create([
            'video_submission_id' => $request->video_submission_id,
            'admin_id' => $request->admin_id,
            'status' => 'assigned'
        ]);

        return back()->with('success', '영상이 성공적으로 배정되었습니다.');
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
        $assignment->delete();

        return back()->with('success', '배정이 취소되었습니다.');
    }

    /**
     * 자동 배정
     */
    public function autoAssign()
    {
        // 관리자만 접근 가능하도록 체크
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->isAdmin()) {
            return redirect()->route('judge.dashboard')
                           ->with('error', '관리자만 접근할 수 있는 페이지입니다.');
        }

        $unassignedVideos = VideoSubmission::whereDoesntHave('assignment')
                                          ->orderBy('created_at', 'asc')
                                          ->get();

        $activeAdmins = Admin::where('is_active', true)
                            ->where('role', 'judge') // 심사위원만 배정
                            ->get();

        if ($activeAdmins->isEmpty()) {
            return back()->with('error', '활성화된 심사위원이 없습니다.');
        }

        $assignedCount = 0;
        foreach ($unassignedVideos as $index => $video) {
            $adminIndex = $index % $activeAdmins->count();
            $selectedAdmin = $activeAdmins[$adminIndex];

            VideoAssignment::create([
                'video_submission_id' => $video->id,
                'admin_id' => $selectedAdmin->id,
                'status' => 'assigned'
            ]);

            $assignedCount++;
        }

        return back()->with('success', "{$assignedCount}개의 영상이 자동으로 배정되었습니다.");
    }
}
