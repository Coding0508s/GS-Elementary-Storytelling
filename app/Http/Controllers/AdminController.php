<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin;
use App\Models\VideoSubmission;
use App\Models\Evaluation;

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
            'password' => 'required|string'
        ], [
            'username.required' => '아이디를 입력해주세요.',
            'password.required' => '비밀번호를 입력해주세요.'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('username', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {
            $admin = Auth::guard('admin')->user();
            $admin->updateLastLogin();
            
            return redirect()->route('admin.dashboard')
                           ->with('success', '관리자 페이지에 오신 것을 환영합니다!');
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
        $totalSubmissions = VideoSubmission::count();
        $evaluatedSubmissions = VideoSubmission::whereHas('evaluation')->count();
        $pendingSubmissions = $totalSubmissions - $evaluatedSubmissions;
        
        // 최근 업로드된 영상들
        $recentSubmissions = VideoSubmission::with('evaluation')
                                          ->orderBy('created_at', 'desc')
                                          ->take(10)
                                          ->get();

        return view('admin.dashboard', compact(
            'totalSubmissions',
            'evaluatedSubmissions', 
            'pendingSubmissions',
            'recentSubmissions'
        ));
    }

    /**
     * 심사 대기 목록
     */
    public function evaluationList(Request $request)
    {
        $query = VideoSubmission::with('evaluation');

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
            if ($request->status === 'evaluated') {
                $query->whereHas('evaluation');
            } elseif ($request->status === 'pending') {
                $query->whereDoesntHave('evaluation');
            }
        }

        $submissions = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.evaluation-list', compact('submissions'));
    }

    /**
     * 개별 심사 페이지
     */
    public function showEvaluation($id)
    {
        $submission = VideoSubmission::with('evaluation')->findOrFail($id);
        $criteriaLabels = Evaluation::getCriteriaLabels();

        return view('admin.evaluation-form', compact('submission', 'criteriaLabels'));
    }

    /**
     * 심사 결과 저장/수정
     */
    public function storeEvaluation(Request $request, $id)
    {
        $submission = VideoSubmission::findOrFail($id);
        
        $validator = Validator::make($request->all(), Evaluation::validationRules(), [
            'pronunciation_score.required' => '발음 점수를 입력해주세요.',
            'pronunciation_score.min' => '발음 점수는 1점 이상이어야 합니다.',
            'pronunciation_score.max' => '발음 점수는 10점 이하여야 합니다.',
            'vocabulary_score.required' => '어휘 점수를 입력해주세요.',
            'vocabulary_score.min' => '어휘 점수는 1점 이상이어야 합니다.',
            'vocabulary_score.max' => '어휘 점수는 10점 이하여야 합니다.',
            'fluency_score.required' => '유창성 점수를 입력해주세요.',
            'fluency_score.min' => '유창성 점수는 1점 이상이어야 합니다.',
            'fluency_score.max' => '유창성 점수는 10점 이하여야 합니다.',
            'confidence_score.required' => '자신감 점수를 입력해주세요.',
            'confidence_score.min' => '자신감 점수는 1점 이상이어야 합니다.',
            'confidence_score.max' => '자신감 점수는 10점 이하여야 합니다.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // 기존 평가가 있으면 업데이트, 없으면 생성
        $evaluationData = $request->only([
            'pronunciation_score',
            'vocabulary_score', 
            'fluency_score',
            'confidence_score',
            'comments'
        ]);
        $evaluationData['admin_id'] = Auth::guard('admin')->id();
        $evaluationData['video_submission_id'] = $submission->id;

        $evaluation = Evaluation::updateOrCreate(
            ['video_submission_id' => $submission->id],
            $evaluationData
        );

        return redirect()->route('admin.evaluation.list')
                        ->with('success', '심사 결과가 성공적으로 저장되었습니다.');
    }

    /**
     * 학생 데이터 엑셀 다운로드
     */
    public function downloadExcel(Request $request)
    {
        $query = VideoSubmission::with(['evaluation']);

        // 필터 적용
        if ($request->filled('status')) {
            if ($request->status === 'evaluated') {
                $query->whereHas('evaluation');
            } elseif ($request->status === 'pending') {
                $query->whereDoesntHave('evaluation');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('student_name_korean', 'like', "%{$search}%")
                  ->orWhere('student_name_english', 'like', "%{$search}%")
                  ->orWhere('institution_name', 'like', "%{$search}%");
            });
        }

        $submissions = $query->orderBy('created_at', 'desc')->get();

        // CSV 헤더 설정
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="speech_contest_' . date('Y-m-d_H-i-s') . '.csv"',
        ];

        // CSV 콜백 함수
        $callback = function() use ($submissions) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM 추가 (엑셀에서 한글 깨짐 방지)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // 헤더 행
            fputcsv($file, [
                '제출 ID',
                '거주 지역',
                '기관명',
                '반 이름',
                '학생 이름 (한글)',
                '학생 이름 (영어)',
                '학년',
                '나이',
                '학부모 성함',
                '학부모 전화번호',
                'Unit 주제',
                '비디오 파일명',
                '파일 크기',
                '업로드 날짜',
                '심사 여부',
                '발음 점수',
                '어휘 점수',
                '유창성 점수',
                '자신감 점수',
                '총점',
                '심사 코멘트'
            ]);

            // 데이터 행
            foreach ($submissions as $submission) {
                $evaluation = $submission->evaluation;
                
                fputcsv($file, [
                    $submission->id,
                    $submission->region,
                    $submission->institution_name,
                    $submission->class_name,
                    $submission->student_name_korean,
                    $submission->student_name_english,
                    $submission->grade,
                    $submission->age,
                    $submission->parent_name,
                    $submission->parent_phone,
                    $submission->unit_topic ?: '-',
                    $submission->video_file_name,
                    $submission->getFormattedFileSizeAttribute(),
                    $submission->created_at->format('Y-m-d H:i:s'),
                    $evaluation ? '완료' : '대기',
                    $evaluation ? $evaluation->pronunciation_score : '-',
                    $evaluation ? $evaluation->vocabulary_score : '-',
                    $evaluation ? $evaluation->fluency_score : '-',
                    $evaluation ? $evaluation->confidence_score : '-',
                    $evaluation ? $evaluation->total_score : '-',
                    $evaluation ? $evaluation->comments : '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 심사 결과 통계
     */
    public function statistics()
    {
        $totalSubmissions = VideoSubmission::count();
        $evaluatedSubmissions = VideoSubmission::whereHas('evaluation')->count();
        
        // 점수별 분포
        $scoreDistribution = Evaluation::selectRaw('
            CASE 
                WHEN total_score >= 36 THEN "우수 (36-40점)"
                WHEN total_score >= 31 THEN "양호 (31-35점)"
                WHEN total_score >= 26 THEN "보통 (26-30점)"
                WHEN total_score >= 21 THEN "미흡 (21-25점)"
                ELSE "매우 미흡 (20점 이하)"
            END as grade,
            COUNT(*) as count
        ')
        ->groupBy('grade')
        ->get();

        // 평균 점수
        $averageScores = Evaluation::selectRaw('
            AVG(pronunciation_score) as avg_pronunciation,
            AVG(vocabulary_score) as avg_vocabulary,
            AVG(fluency_score) as avg_fluency,
            AVG(confidence_score) as avg_confidence,
            AVG(total_score) as avg_total
        ')->first();

        // 기관별 통계
        $institutionStats = VideoSubmission::whereHas('evaluation')
            ->selectRaw('
                institution_name,
                COUNT(*) as submission_count,
                AVG(evaluations.total_score) as avg_score
            ')
            ->join('evaluations', 'video_submissions.id', '=', 'evaluations.video_submission_id')
            ->groupBy('institution_name')
            ->orderBy('avg_score', 'desc')
            ->get();

        return view('admin.statistics', compact(
            'totalSubmissions',
            'evaluatedSubmissions',
            'scoreDistribution',
            'averageScores',
            'institutionStats'
        ));
    }
}
