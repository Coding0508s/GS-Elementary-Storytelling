<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin;
use App\Models\VideoSubmission;
use App\Models\Evaluation;
use App\Models\VideoAssignment;

class JudgeController extends Controller
{

    /**
     * 심사위원 대시보드
     */
    public function dashboard()
    {
        $judge = Auth::guard('admin')->user();
        
        // 이 심사위원에게 배정된 영상들
        $assignedVideos = VideoAssignment::where('admin_id', $judge->id)
                                       ->with(['videoSubmission', 'evaluation'])
                                       ->orderBy('created_at', 'asc')
                                       ->get();

        // 통계 정보
        $totalAssigned = $assignedVideos->count();
        $completedEvaluations = $assignedVideos->where('status', VideoAssignment::STATUS_COMPLETED)->count();
        $inProgressEvaluations = $assignedVideos->where('status', VideoAssignment::STATUS_IN_PROGRESS)->count();
        $pendingEvaluations = $assignedVideos->where('status', VideoAssignment::STATUS_ASSIGNED)->count();

        // 최근 배정된 영상들 (최대 5개)
        $recentAssignments = $assignedVideos->take(5);

        // 두 심사위원 총합 점수 기준 상위 10명 순위 계산
        $allEvaluatedVideos = VideoSubmission::with(['evaluations'])
            ->whereHas('evaluations')
            ->get()
            ->map(function ($submission) use ($judge) {
                // 두 심사위원의 평가 합계 계산
                $totalScore = $submission->evaluations->sum('total_score');
                $evaluationCount = $submission->evaluations->count();
                
                // 평가가 2개 있는 경우만 포함 (완전히 평가된 영상)
                if ($evaluationCount < 2) {
                    return null;
                }
                
                // 이 심사위원이 평가한 영상인지 확인
                $myEvaluation = $submission->evaluations->where('admin_id', $judge->id)->first();
                
                return (object) [
                    'submission' => $submission,
                    'total_score' => $totalScore,
                    'evaluation_count' => $evaluationCount,
                    'my_evaluation' => $myEvaluation,
                    'evaluated_by_me' => $myEvaluation !== null
                ];
            })
            ->filter() // null 값 제거
            ->sort(function ($a, $b) {
                // 1차 정렬: 총합 점수 내림차순 (높은 점수가 먼저)
                if ($a->total_score !== $b->total_score) {
                    return $b->total_score <=> $a->total_score;
                }
                // 2차 정렬: 같은 점수일 때 업로드 빠른 순 (먼저 제출한 사람이 앞)
                return $a->submission->created_at <=> $b->submission->created_at;
            })
            ->values()
            ->take(10); // 상위 10명

        // 순위 계산 (두 심사위원 총합 점수 기준)
        $myEvaluatedRankings = collect();

        foreach ($allEvaluatedVideos as $index => $videoData) {
            $submission = $videoData->submission;
            $myEvaluation = $videoData->my_evaluation;
            
            // 내가 평가한 영상만 표시를 위해 체크 (필요시 주석 해제하여 전체 순위 표시 가능)
            if (!$videoData->evaluated_by_me) {
                continue;
            }
            
            // 현재 심사위원의 배정 ID 찾기
            $myAssignment = VideoAssignment::where('video_submission_id', $submission->id)
                                         ->where('admin_id', $judge->id)
                                         ->first();
            
            $myEvaluatedRankings->push([
                'rank' => $index + 1, // 두 심사위원 총합 점수 기준 순위
                'student_name' => $submission->student_name_korean,
                'institution' => $submission->institution_name,
                'grade' => $submission->grade,
                'total_score' => $videoData->total_score, // 두 심사위원 총합 점수
                'my_score' => $myEvaluation ? $myEvaluation->total_score : 0, // 내 평가 점수
                'evaluation_grade' => $this->calculateGrade($videoData->total_score),
                'submission_id' => $submission->id,
                'assignment_id' => $myAssignment ? $myAssignment->id : null,
                'upload_time' => $submission->created_at->format('m/d H:i')
            ]);
        }

        return view('judge.dashboard', compact(
            'judge',
            'assignedVideos',
            'totalAssigned',
            'completedEvaluations',
            'inProgressEvaluations',
            'pendingEvaluations',
            'recentAssignments',
            'myEvaluatedRankings'
        ));
    }

    /**
     * 점수에 따른 등급 계산
     */
    private function calculateGrade($totalScore)
    {
        if ($totalScore >= 36) {
            return '우수';
        } elseif ($totalScore >= 31) {
            return '양호';
        } elseif ($totalScore >= 26) {
            return '보통';
        } elseif ($totalScore >= 21) {
            return '미흡';
        } else {
            return '매우 미흡';
        }
    }

    /**
     * 심사할 영상 목록
     */
    public function videoList()
    {
        $judge = Auth::guard('admin')->user();
        
        $assignments = VideoAssignment::where('admin_id', $judge->id)
                                    ->with(['videoSubmission'])
                                    ->orderBy('created_at', 'asc')
                                    ->paginate(10);

        // 각 assignment에 현재 심사위원의 evaluation만 추가
        $assignments->getCollection()->transform(function ($assignment) use ($judge) {
            $currentEvaluation = Evaluation::where('video_submission_id', $assignment->video_submission_id)
                                         ->where('admin_id', $judge->id)
                                         ->first();
            $assignment->setRelation('evaluation', $currentEvaluation);
            return $assignment;
        });

        return view('judge.video-list', compact('assignments', 'judge'));
    }

    /**
     * 영상 심사 페이지
     */
    public function showEvaluation($assignmentId)
    {
        $judge = Auth::guard('admin')->user();
        
        // 이 심사위원에게 배정된 영상인지 확인
        $assignment = VideoAssignment::where('id', $assignmentId)
                                   ->where('admin_id', $judge->id)
                                   ->with(['videoSubmission'])
                                   ->firstOrFail();

        $submission = $assignment->videoSubmission;

        // 현재 심사위원의 기존 평가만 가져오기 (다른 심사위원 점수 숨김)
        $currentEvaluation = Evaluation::where('video_submission_id', $submission->id)
                                     ->where('admin_id', $judge->id)
                                     ->first();
        
        // assignment에 현재 심사위원의 evaluation만 설정
        $assignment->setRelation('evaluation', $currentEvaluation);

        // 다음 배정된 영상 정보 가져오기
        $nextAssignment = VideoAssignment::where('admin_id', $judge->id)
                                        ->where('status', VideoAssignment::STATUS_ASSIGNED)
                                        ->where('id', '!=', $assignmentId)
                                        ->with('videoSubmission')
                                        ->orderBy('created_at', 'asc')
                                        ->first();

        return view('judge.evaluation-form', compact('assignment', 'submission', 'judge', 'nextAssignment'));
    }

    /**
     * 심사 결과 저장
     */
    public function storeEvaluation(Request $request, $assignmentId)
    {
        $judge = Auth::guard('admin')->user();
        
        // 이 심사위원에게 배정된 영상인지 확인
        $assignment = VideoAssignment::where('id', $assignmentId)
                                   ->where('admin_id', $judge->id)
                                   ->firstOrFail();

        $submission = $assignment->videoSubmission;

        // 검증 규칙
        $validator = Validator::make($request->all(), [
            'pronunciation_score' => 'required|integer|min:0|max:10',
            'vocabulary_score' => 'required|integer|min:0|max:10',
            'fluency_score' => 'required|integer|min:0|max:10',
            'confidence_score' => 'required|integer|min:0|max:10',
            'comments' => 'nullable|string|max:1000'
        ], [
            'pronunciation_score.required' => '발음 점수를 입력해주세요.',
            'pronunciation_score.min' => '발음 점수는 0점 이상이어야 합니다.',
            'pronunciation_score.max' => '발음 점수는 10점 이하여야 합니다.',
            'vocabulary_score.required' => '어휘 점수를 입력해주세요.',
            'vocabulary_score.min' => '어휘 점수는 0점 이상이어야 합니다.',
            'vocabulary_score.max' => '어휘 점수는 10점 이하여야 합니다.',
            'fluency_score.required' => '유창성 점수를 입력해주세요.',
            'fluency_score.min' => '유창성 점수는 0점 이상이어야 합니다.',
            'fluency_score.max' => '유창성 점수는 10점 이하여야 합니다.',
            'confidence_score.required' => '자신감 점수를 입력해주세요.',
            'confidence_score.min' => '자신감 점수는 0점 이상이어야 합니다.',
            'confidence_score.max' => '자신감 점수는 10점 이하여야 합니다.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // 기존 심사 결과가 있는지 확인
        $existingEvaluation = Evaluation::where('video_submission_id', $submission->id)
                                       ->where('admin_id', $judge->id)
                                       ->first();

        if ($existingEvaluation) {
            // 기존 심사 결과 업데이트
            $existingEvaluation->update([
                'pronunciation_score' => $request->pronunciation_score,
                'vocabulary_score' => $request->vocabulary_score,
                'fluency_score' => $request->fluency_score,
                'confidence_score' => $request->confidence_score,
                'comments' => $request->comments
            ]);
            
            $evaluation = $existingEvaluation;
        } else {
            // 새로운 심사 결과 생성
            $evaluation = Evaluation::create([
                'video_submission_id' => $submission->id,
                'admin_id' => $judge->id,
                'pronunciation_score' => $request->pronunciation_score,
                'vocabulary_score' => $request->vocabulary_score,
                'fluency_score' => $request->fluency_score,
                'confidence_score' => $request->confidence_score,
                'comments' => $request->comments
            ]);
        }

        // 배정 상태를 완료로 변경
        $assignment->completeEvaluation();

        // 다음 배정된 영상 확인
        $nextAssignment = VideoAssignment::where('admin_id', $judge->id)
                                        ->where('status', VideoAssignment::STATUS_ASSIGNED)
                                        ->where('id', '!=', $assignmentId)
                                        ->with('videoSubmission')
                                        ->orderBy('created_at', 'asc')
                                        ->first();

        if ($nextAssignment) {
            // 다음 영상이 있으면 바로 심사 페이지로 이동
            return redirect()->route('judge.evaluation.show', $nextAssignment->id)
                            ->with('success', '심사가 완료되었습니다. 다음 영상을 심사해주세요.');
        } else {
            // 더 이상 배정된 영상이 없으면 목록 페이지로 이동
            return redirect()->route('judge.video.list')
                            ->with('success', '모든 배정된 영상의 심사가 완료되었습니다.');
        }
    }

    /**
     * 영상 심사 시작
     */
    public function startEvaluation($assignmentId)
    {
        $judge = Auth::guard('admin')->user();
        
        $assignment = VideoAssignment::where('id', $assignmentId)
                                   ->where('admin_id', $judge->id)
                                   ->firstOrFail();

        // 배정 상태를 심사 중으로 변경
        $assignment->startEvaluation();

        return redirect()->route('judge.evaluation.show', $assignmentId)
                        ->with('success', '심사를 시작합니다.');
    }

    /**
     * 심사 결과 수정
     */
    public function editEvaluation($assignmentId)
    {
        $judge = Auth::guard('admin')->user();
        
        $assignment = VideoAssignment::where('id', $assignmentId)
                                   ->where('admin_id', $judge->id)
                                   ->with(['videoSubmission'])
                                   ->firstOrFail();

        $submission = $assignment->videoSubmission;

        // 현재 심사위원의 기존 평가만 가져오기 (다른 심사위원 점수 숨김)
        $currentEvaluation = Evaluation::where('video_submission_id', $submission->id)
                                     ->where('admin_id', $judge->id)
                                     ->first();
        
        // assignment에 현재 심사위원의 evaluation만 설정
        $assignment->setRelation('evaluation', $currentEvaluation);

        return view('judge.evaluation-edit', compact('assignment', 'submission', 'judge'));
    }

    /**
     * 심사 결과 수정 저장
     */
    public function updateEvaluation(Request $request, $assignmentId)
    {
        $judge = Auth::guard('admin')->user();
        
        $assignment = VideoAssignment::where('id', $assignmentId)
                                   ->where('admin_id', $judge->id)
                                   ->firstOrFail();

        $submission = $assignment->videoSubmission;

        // 검증 규칙
        $validator = Validator::make($request->all(), [
            'pronunciation_score' => 'required|integer|min:0|max:10',
            'vocabulary_score' => 'required|integer|min:0|max:10',
            'fluency_score' => 'required|integer|min:0|max:10',
            'confidence_score' => 'required|integer|min:0|max:10',
            'comments' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // 기존 심사 결과 업데이트
        $evaluation = Evaluation::where('video_submission_id', $submission->id)
                               ->where('admin_id', $judge->id)
                               ->firstOrFail();

        $evaluation->update([
            'pronunciation_score' => $request->pronunciation_score,
            'vocabulary_score' => $request->vocabulary_score,
            'fluency_score' => $request->fluency_score,
            'confidence_score' => $request->confidence_score,
            'comments' => $request->comments
        ]);

        // 다음 배정된 영상 확인
        $nextAssignment = VideoAssignment::where('admin_id', $judge->id)
                                        ->where('status', VideoAssignment::STATUS_ASSIGNED)
                                        ->where('id', '!=', $assignmentId)
                                        ->with('videoSubmission')
                                        ->orderBy('created_at', 'asc')
                                        ->first();

        if ($nextAssignment) {
            // 다음 영상이 있으면 바로 심사 페이지로 이동
            return redirect()->route('judge.evaluation.show', $nextAssignment->id)
                            ->with('success', '심사 결과가 수정되었습니다. 다음 영상을 심사해주세요.');
        } else {
            // 더 이상 배정된 영상이 없으면 목록 페이지로 이동
            return redirect()->route('judge.video.list')
                            ->with('success', '심사 결과가 수정되었습니다.');
        }
    }

    /**
     * 영상 다운로드
     */
    public function downloadVideo($assignmentId)
    {
        $judge = Auth::guard('admin')->user();
        
        // 이 심사위원에게 배정된 영상인지 확인
        $assignment = VideoAssignment::where('id', $assignmentId)
                                   ->where('admin_id', $judge->id)
                                   ->with('videoSubmission')
                                   ->firstOrFail();

        $submission = $assignment->videoSubmission;

        // S3 또는 로컬 스토리지에 따라 다른 다운로드 방법 사용
        if ($submission->isStoredOnS3()) {
            // S3 다운로드 URL 생성 (한글 파일명 안전 처리)
            $downloadUrl = $submission->getS3DownloadUrl(1); // 1시간 유효
            
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
    }

    /**
     * 영상 스트리밍 URL 가져오기 (AJAX)
     */
    public function getVideoStreamUrl($assignmentId)
    {
        $judge = Auth::guard('admin')->user();
        
        // 이 심사위원에게 배정된 영상인지 확인
        $assignment = VideoAssignment::where('id', $assignmentId)
                                   ->where('admin_id', $judge->id)
                                   ->with('videoSubmission')
                                   ->firstOrFail();

        $submission = $assignment->videoSubmission;

        // S3 또는 로컬 스토리지에 따라 다른 URL 생성
        if ($submission->isStoredOnS3()) {
            // S3 스트리밍 URL 생성 (24시간 유효)
            $streamUrl = $submission->getS3TemporaryUrl(24);
        } else {
            // 로컬 스토리지 URL 생성
            $streamUrl = $submission->getLocalVideoUrl();
        }

        if (!$streamUrl) {
            return response()->json(['error' => '영상 URL을 생성할 수 없습니다.'], 500);
        }

        return response()->json([
            'success' => true,
            'url' => $streamUrl,
            'filename' => $submission->video_file_name,
            'size' => $submission->getFormattedFileSizeAttribute(),
            'storage_type' => $submission->isStoredOnS3() ? 's3' : 'local'
        ]);
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
} 