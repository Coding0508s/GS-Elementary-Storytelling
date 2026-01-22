<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Admin;
use App\Models\VideoSubmission;
use App\Models\Evaluation;
use App\Models\VideoAssignment;
use App\Models\AiEvaluation;
use App\Services\OpenAiService;

class JudgeController extends Controller
{

    /**
     * 심사위원 대시보드
     */
    public function dashboard()
    {
        $judge = Auth::guard('admin')->user();
        
        // 이 심사위원에게 배정된 영상들 (soft delete되지 않은 videoSubmission만)
        // 재평가 대상 영상도 포함하여 표시
        $assignedVideos = VideoAssignment::where('admin_id', $judge->id)
                                       ->whereHas('videoSubmission')
                                       ->with(['videoSubmission', 'evaluation'])
                                       ->orderBy('created_at', 'asc')
                                       ->get();

        // 통계 정보
        $totalAssigned = $assignedVideos->count();
        $completedEvaluations = $assignedVideos->where('status', VideoAssignment::STATUS_COMPLETED)->count();
        $inProgressEvaluations = $assignedVideos->where('status', VideoAssignment::STATUS_IN_PROGRESS)->count();
        $pendingEvaluations = $assignedVideos->where('status', VideoAssignment::STATUS_ASSIGNED)->count();

        // 최근 배정된 영상들 (최대 5개, videoSubmission이 존재하는 것만)
        $recentAssignments = $assignedVideos->filter(function ($assignment) {
            return $assignment->videoSubmission !== null;
        })->take(5);

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
                'evaluation_grade' => null, // 등급 시스템 제거됨
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
     * 심사할 영상 목록
     */
    public function videoList()
    {
        $judge = Auth::guard('admin')->user();
        
        // 전체 배정된 영상 개수 및 상태별 개수 계산 (재평가 대상 포함)
        $allAssignments = VideoAssignment::where('admin_id', $judge->id)
                                        ->whereHas('videoSubmission')
                                        ->get();
        $totalAssigned = $allAssignments->count();
        $pendingCount = $allAssignments->where('status', VideoAssignment::STATUS_ASSIGNED)->count();
        $inProgressCount = $allAssignments->where('status', VideoAssignment::STATUS_IN_PROGRESS)->count();
        $completedCount = $allAssignments->where('status', VideoAssignment::STATUS_COMPLETED)->count();
        
        // 페이지네이션을 위한 assignments (soft delete되지 않은 videoSubmission만, 재평가 대상 포함)
        $assignments = VideoAssignment::where('admin_id', $judge->id)
                                    ->whereHas('videoSubmission')
                                    ->with(['videoSubmission'])
                                    ->orderBy('created_at', 'asc')
                                    ->paginate(10);

        // 각 assignment에 현재 심사위원의 evaluation과 관리자 AI 평가 정보 추가
        $assignments->getCollection()->transform(function ($assignment) use ($judge) {
            // videoSubmission이 null인 경우 스킵 (방어적 코딩)
            if (!$assignment->videoSubmission) {
                return null;
            }
            
            $currentEvaluation = Evaluation::where('video_submission_id', $assignment->video_submission_id)
                                         ->where('admin_id', $judge->id)
                                         ->first();
            $assignment->setRelation('evaluation', $currentEvaluation);
            
            // 관리자가 일괄 채점한 AI 평가 결과 확인
            $adminAiEvaluation = AiEvaluation::where('video_submission_id', $assignment->video_submission_id)
                                            ->where('admin_id', '!=', $judge->id) // 관리자의 AI 평가
                                            ->where('processing_status', AiEvaluation::STATUS_COMPLETED)
                                            ->first();
            $assignment->admin_ai_evaluation = $adminAiEvaluation;
            
            return $assignment;
        })->filter(); // null 값 제거

        return view('judge.video-list', compact(
            'assignments', 
            'judge',
            'totalAssigned',
            'pendingCount',
            'inProgressCount', 
            'completedCount'
        ));
    }

    /**
     * 재평가 대상 영상 목록
     */
    public function reevaluationList()
    {
        $judge = Auth::guard('admin')->user();
        
        // 재평가 대상 영상만 가져오기 (is_reevaluation_target이 명시적으로 true인 경우만)
        // SQLite에서 boolean 비교를 위해 명시적으로 1 또는 true로 비교
        $assignments = VideoAssignment::where('admin_id', $judge->id)
                                    ->whereHas('videoSubmission', function($query) {
                                        $query->where(function($q) {
                                            $q->where('is_reevaluation_target', '=', 1)
                                              ->orWhere('is_reevaluation_target', '=', true)
                                              ->orWhereRaw('is_reevaluation_target = 1');
                                        });
                                    })
                                    ->with(['videoSubmission'])
                                    ->orderBy('created_at', 'asc')
                                    ->get();
        
        // 추가 필터링: videoSubmission이 null이거나 is_reevaluation_target이 true가 아닌 경우 제거
        $filteredAssignments = $assignments->filter(function ($assignment) {
            return $assignment->videoSubmission && 
                   $assignment->videoSubmission->is_reevaluation_target === true;
        });
        
        // 페이지네이션을 위해 수동으로 페이지네이션 처리
        $perPage = 10;
        $currentPage = request()->get('page', 1);
        $items = $filteredAssignments->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $total = $filteredAssignments->count();
        
        $assignments = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // 각 assignment에 현재 심사위원의 evaluation과 관리자 AI 평가 정보 추가
        $assignments->getCollection()->transform(function ($assignment) use ($judge) {
            // videoSubmission이 null인 경우 스킵 (방어적 코딩)
            if (!$assignment->videoSubmission) {
                return null;
            }
            
            // 현재 심사위원의 기존 평가 (원본 평가 - is_reevaluation = false)
            $originalEvaluation = Evaluation::where('video_submission_id', $assignment->video_submission_id)
                                         ->where('admin_id', $judge->id)
                                         ->where('is_reevaluation', false)
                                         ->orderBy('created_at', 'asc')
                                         ->first();
            
            // 재평가 (is_reevaluation = true인 가장 최근 평가)
            $reevaluation = Evaluation::where('video_submission_id', $assignment->video_submission_id)
                                     ->where('admin_id', $judge->id)
                                     ->where('is_reevaluation', true)
                                     ->orderBy('created_at', 'desc')
                                     ->first();
            
            // 표시용: 재평가가 있으면 재평가, 없으면 원본 평가
            $currentEvaluation = $reevaluation ?? $originalEvaluation;
            
            $assignment->setRelation('evaluation', $currentEvaluation);
            $assignment->original_evaluation = $originalEvaluation;
            $assignment->reevaluation = $reevaluation;
            
            // 관리자가 일괄 채점한 AI 평가 결과 확인
            $adminAiEvaluation = AiEvaluation::where('video_submission_id', $assignment->video_submission_id)
                                            ->where('admin_id', '!=', $judge->id) // 관리자의 AI 평가
                                            ->where('processing_status', AiEvaluation::STATUS_COMPLETED)
                                            ->first();
            $assignment->admin_ai_evaluation = $adminAiEvaluation;
            
            return $assignment;
        })->filter(); // null 값 제거

        // 통계 정보
        $totalReevaluation = $assignments->total();
        $completedReevaluation = $assignments->getCollection()->filter(function($assignment) {
            return $assignment->reevaluation !== null;
        })->count();
        $pendingReevaluation = $totalReevaluation - $completedReevaluation;

        return view('judge.reevaluation-list', compact(
            'assignments', 
            'judge',
            'totalReevaluation',
            'completedReevaluation',
            'pendingReevaluation'
        ));
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
        
        // videoSubmission이 null인 경우 처리 (soft delete된 경우)
        if (!$submission) {
            Log::error('VideoSubmission이 존재하지 않음', [
                'assignment_id' => $assignmentId,
                'judge_id' => $judge->id,
                'video_submission_id' => $assignment->video_submission_id
            ]);
            return redirect()->route('judge.video.list')
                            ->with('error', '해당 영상 정보를 찾을 수 없습니다. 영상이 삭제되었을 수 있습니다.');
        }

        // 재평가 대상인지 확인
        $isReevaluation = $submission->is_reevaluation_target ?? false;
        
        // 현재 심사위원의 기존 평가 확인 (is_reevaluation = false)
        $originalEvaluation = Evaluation::where('video_submission_id', $submission->id)
                                       ->where('admin_id', $judge->id)
                                       ->where('is_reevaluation', false)
                                       ->first();
        
        // 재평가 대상이고 기존 평가가 있는 경우 재평가 차단
        if ($isReevaluation && $originalEvaluation) {
            return redirect()->route('judge.reevaluation.list')
                            ->with('error', '기존에 평가한 영상은 재평가할 수 없습니다.');
        }
        
        // 현재 심사위원의 평가 가져오기
        if ($isReevaluation) {
            // 재평가인 경우: 평가를 null로 설정하여 초기화 상태로 표시
            // AI 평가 항목만 표시하고 나머지는 빈 값으로 표시
            $currentEvaluation = null;
        } else {
            // 일반 평가인 경우: 기존 평가만 가져오기 (다른 심사위원 점수 숨김)
            $currentEvaluation = $originalEvaluation;
        }
        
        // assignment에 현재 심사위원의 evaluation만 설정
        $assignment->setRelation('evaluation', $currentEvaluation);
        $assignment->is_reevaluation = $isReevaluation;

        // 다음 배정된 영상 정보 가져오기 (videoSubmission이 존재하는 것만)
        $nextAssignment = VideoAssignment::where('admin_id', $judge->id)
                                        ->where('status', VideoAssignment::STATUS_ASSIGNED)
                                        ->where('id', '!=', $assignmentId)
                                        ->whereHas('videoSubmission') // soft delete되지 않은 videoSubmission만
                                        ->with('videoSubmission')
                                        ->orderBy('created_at', 'asc')
                                        ->first();

        // AI 평가 결과 가져오기 (관리자가 일괄 채점한 AI 평가 우선, 없으면 해당 심사위원의 AI 평가)
        $aiEvaluation = AiEvaluation::where('video_submission_id', $submission->id)
                                  ->where('processing_status', AiEvaluation::STATUS_COMPLETED)
                                  ->orderBy('admin_id', 'asc') // 관리자(admin)의 AI 평가를 우선적으로 가져옴
                                  ->first();

        // 다른 심사위원이 이미 채점했는지 확인 (재평가인 경우는 제외)
        $otherEvaluation = null;
        if (!$isReevaluation) {
            $otherEvaluation = Evaluation::where('video_submission_id', $submission->id)
                                       ->where('admin_id', '!=', $judge->id)
                                       ->where('is_reevaluation', false)
                                       ->first();
        }

        return view('judge.evaluation-form', compact('assignment', 'submission', 'judge', 'nextAssignment', 'aiEvaluation', 'otherEvaluation', 'isReevaluation'));
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

        // 재평가 대상인지 확인
        $isReevaluation = $submission->is_reevaluation_target ?? false;

        // 재평가인 경우 AI 평가 값을 가져와서 자동으로 채워주기
        if ($isReevaluation) {
            // AI 평가 가져오기 (관리자 일괄 채점 우선, 없으면 현재 심사위원의 AI 평가)
            $aiEvaluation = $submission->aiEvaluations()
                ->where('processing_status', 'completed')
                ->orderBy('admin_id', 'asc') // 관리자(admin_id=1) 일괄 채점 우선
                ->first();
            
            // AI 평가 값이 있으면 자동으로 채워주기
            if ($aiEvaluation) {
                $request->merge([
                    'pronunciation_score' => $request->pronunciation_score ?: $aiEvaluation->pronunciation_score ?? 0,
                    'vocabulary_score' => $request->vocabulary_score ?: $aiEvaluation->vocabulary_score ?? 0,
                    'fluency_score' => $request->fluency_score ?: $aiEvaluation->fluency_score ?? 0,
                ]);
            }
        }

        // 일반 평가인 경우에만 다른 심사위원 채점 여부 확인
        if (!$isReevaluation) {
            $existingOtherEvaluation = Evaluation::where('video_submission_id', $submission->id)
                                               ->where('admin_id', '!=', $judge->id)
                                               ->where('is_reevaluation', false)
                                               ->first();

            if ($existingOtherEvaluation) {
                return back()->with('error', '이 영상은 이미 다른 심사위원에 의해 채점되었습니다.');
            }
        }

        // 검증 규칙
        $validator = Validator::make($request->all(), [
            'pronunciation_score' => 'required|integer|min:0|max:10',
            'vocabulary_score' => 'required|integer|min:0|max:10',
            'fluency_score' => 'required|integer|min:0|max:10',
            'confidence_score' => 'required|integer|min:0|max:10',
            'topic_connection_score' => 'required|integer|min:0|max:10',
            'structure_flow_score' => 'required|integer|min:0|max:10',
            'creativity_score' => 'required|integer|min:0|max:10',
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
            'topic_connection_score.required' => '주제연결성 점수를 입력해주세요.',
            'topic_connection_score.min' => '주제연결성 점수는 0점 이상이어야 합니다.',
            'topic_connection_score.max' => '주제연결성 점수는 10점 이하여야 합니다.',
            'structure_flow_score.required' => '구성·흐름 점수를 입력해주세요.',
            'structure_flow_score.min' => '구성·흐름 점수는 0점 이상이어야 합니다.',
            'structure_flow_score.max' => '구성·흐름 점수는 10점 이하여야 합니다.',
            'creativity_score.required' => '창의성 점수를 입력해주세요.',
            'creativity_score.min' => '창의성 점수는 0점 이상이어야 합니다.',
            'creativity_score.max' => '창의성 점수는 10점 이하여야 합니다.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // 재평가인 경우 항상 새로운 평가 생성, 일반 평가인 경우 기존 평가 업데이트 또는 생성
        if ($isReevaluation) {
            // 재평가인 경우: 항상 새로운 평가 생성 (기존 평가는 유지)
            $evaluation = Evaluation::create([
                'video_submission_id' => $submission->id,
                'admin_id' => $judge->id,
                'pronunciation_score' => $request->pronunciation_score,
                'vocabulary_score' => $request->vocabulary_score,
                'fluency_score' => $request->fluency_score,
                'confidence_score' => $request->confidence_score,
                'topic_connection_score' => $request->topic_connection_score,
                'structure_flow_score' => $request->structure_flow_score,
                'creativity_score' => $request->creativity_score,
                'comments' => $request->comments,
                'is_reevaluation' => true
            ]);
        } else {
            // 일반 평가인 경우: 기존 평가가 있으면 업데이트, 없으면 생성
            $existingEvaluation = Evaluation::where('video_submission_id', $submission->id)
                                           ->where('admin_id', $judge->id)
                                           ->where('is_reevaluation', false)
                                           ->first();

            if ($existingEvaluation) {
                // 기존 심사 결과 업데이트
                $existingEvaluation->update([
                    'pronunciation_score' => $request->pronunciation_score,
                    'vocabulary_score' => $request->vocabulary_score,
                    'fluency_score' => $request->fluency_score,
                    'confidence_score' => $request->confidence_score,
                    'topic_connection_score' => $request->topic_connection_score,
                    'structure_flow_score' => $request->structure_flow_score,
                    'creativity_score' => $request->creativity_score,
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
                    'topic_connection_score' => $request->topic_connection_score,
                    'structure_flow_score' => $request->structure_flow_score,
                    'creativity_score' => $request->creativity_score,
                    'comments' => $request->comments,
                    'is_reevaluation' => false
                ]);
            }
        }

        // 배정 상태를 완료로 변경
        $assignment->completeEvaluation();

        // 다음 배정된 영상 확인 (videoSubmission이 존재하는 것만)
        if ($isReevaluation) {
            // 재평가인 경우: 기존 평가가 없는 영상만 찾기
            $nextAssignment = null;
            $allAssignments = VideoAssignment::where('admin_id', $judge->id)
                                            ->where('status', VideoAssignment::STATUS_ASSIGNED)
                                            ->where('id', '!=', $assignmentId)
                                            ->whereHas('videoSubmission', function($query) {
                                                $query->where('is_reevaluation_target', true);
                                            })
                                            ->with('videoSubmission')
                                            ->orderBy('created_at', 'asc')
                                            ->get();
            
            // 기존 평가가 없는 첫 번째 영상 찾기
            foreach ($allAssignments as $assignment) {
                if (!$assignment->videoSubmission) {
                    continue;
                }
                
                // 기존 평가 확인 (is_reevaluation = false)
                $originalEvaluation = Evaluation::where('video_submission_id', $assignment->video_submission_id)
                                               ->where('admin_id', $judge->id)
                                               ->where('is_reevaluation', false)
                                               ->first();
                
                // 기존 평가가 없으면 이 영상을 다음 영상으로 선택
                if (!$originalEvaluation) {
                    $nextAssignment = $assignment;
                    break;
                }
            }
        } else {
            // 일반 평가인 경우: 기존 로직 유지
            $nextAssignment = VideoAssignment::where('admin_id', $judge->id)
                                            ->where('status', VideoAssignment::STATUS_ASSIGNED)
                                            ->where('id', '!=', $assignmentId)
                                            ->whereHas('videoSubmission') // soft delete되지 않은 videoSubmission만
                                            ->with('videoSubmission')
                                            ->orderBy('created_at', 'asc')
                                            ->first();
        }

        if ($nextAssignment && $nextAssignment->videoSubmission) {
            // 다음 영상이 있으면 바로 심사 페이지로 이동
            $message = $isReevaluation 
                ? '재평가가 완료되었습니다. 다음 영상을 재평가해주세요.' 
                : '심사가 완료되었습니다. 다음 영상을 심사해주세요.';
            return redirect()->route('judge.evaluation.show', $nextAssignment->id)
                            ->with('success', $message);
        } else {
            // 더 이상 배정된 영상이 없으면 목록 페이지로 이동
            $message = $isReevaluation
                ? '모든 재평가 대상 영상의 심사가 완료되었습니다.'
                : '모든 배정된 영상의 심사가 완료되었습니다.';
            $redirectRoute = $isReevaluation 
                ? 'judge.reevaluation.list' 
                : 'judge.video.list';
            return redirect()->route($redirectRoute)
                            ->with('success', $message);
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

        return redirect()->route('judge.evaluation.show', $assignmentId);
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
        
        // videoSubmission이 null인 경우 처리 (soft delete된 경우)
        if (!$submission) {
            Log::error('VideoSubmission이 존재하지 않음 (수정)', [
                'assignment_id' => $assignmentId,
                'judge_id' => $judge->id,
                'video_submission_id' => $assignment->video_submission_id
            ]);
            return redirect()->route('judge.video.list')
                            ->with('error', '해당 영상 정보를 찾을 수 없습니다. 영상이 삭제되었을 수 있습니다.');
        }

        // 재평가 대상인지 확인
        $isReevaluation = $submission->is_reevaluation_target ?? false;
        
        // 현재 심사위원의 평가 가져오기
        if ($isReevaluation) {
            // 재평가인 경우: 가장 최근 재평가
            $currentEvaluation = Evaluation::where('video_submission_id', $submission->id)
                                         ->where('admin_id', $judge->id)
                                         ->where('is_reevaluation', true)
                                         ->orderBy('created_at', 'desc')
                                         ->first();
        } else {
            // 일반 평가인 경우: 기존 평가만 가져오기
            $currentEvaluation = Evaluation::where('video_submission_id', $submission->id)
                                         ->where('admin_id', $judge->id)
                                         ->where('is_reevaluation', false)
                                         ->first();
        }
        
        // assignment에 현재 심사위원의 evaluation만 설정
        $assignment->setRelation('evaluation', $currentEvaluation);
        $assignment->is_reevaluation = $isReevaluation;

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

        // 재평가 대상인지 확인
        $isReevaluation = $submission->is_reevaluation_target ?? false;

        // 일반 평가인 경우에만 다른 심사위원 채점 여부 확인
        if (!$isReevaluation) {
            $existingOtherEvaluation = Evaluation::where('video_submission_id', $submission->id)
                                               ->where('admin_id', '!=', $judge->id)
                                               ->where('is_reevaluation', false)
                                               ->first();

            if ($existingOtherEvaluation) {
                return back()->with('error', '이 영상은 이미 다른 심사위원에 의해 채점되었기 때문에 수정할 수 없습니다.');
            }
        }

        // 검증 규칙
        $validator = Validator::make($request->all(), [
            'pronunciation_score' => 'required|integer|min:0|max:10',
            'vocabulary_score' => 'required|integer|min:0|max:10',
            'fluency_score' => 'required|integer|min:0|max:10',
            'confidence_score' => 'required|integer|min:0|max:10',
            'topic_connection_score' => 'required|integer|min:0|max:10',
            'structure_flow_score' => 'required|integer|min:0|max:10',
            'creativity_score' => 'required|integer|min:0|max:10',
            'comments' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // 재평가인 경우 가장 최근 재평가를 수정, 일반 평가인 경우 기존 평가 수정
        if ($isReevaluation) {
            $evaluation = Evaluation::where('video_submission_id', $submission->id)
                                   ->where('admin_id', $judge->id)
                                   ->where('is_reevaluation', true)
                                   ->orderBy('created_at', 'desc')
                                   ->firstOrFail();
        } else {
            $evaluation = Evaluation::where('video_submission_id', $submission->id)
                                   ->where('admin_id', $judge->id)
                                   ->where('is_reevaluation', false)
                                   ->firstOrFail();
        }

        $evaluation->update([
            'pronunciation_score' => $request->pronunciation_score,
            'vocabulary_score' => $request->vocabulary_score,
            'fluency_score' => $request->fluency_score,
            'confidence_score' => $request->confidence_score,
            'topic_connection_score' => $request->topic_connection_score,
            'structure_flow_score' => $request->structure_flow_score,
            'creativity_score' => $request->creativity_score,
            'comments' => $request->comments
        ]);
        
        // 총점이 자동으로 계산되도록 refresh
        $evaluation->refresh();

        // 재평가 대상인지 확인
        $isReevaluation = $submission->is_reevaluation_target ?? false;

        // 다음 배정된 영상 확인 (videoSubmission이 존재하는 것만)
        if ($isReevaluation) {
            // 재평가인 경우: 기존 평가가 없는 영상만 찾기
            $nextAssignment = null;
            $allAssignments = VideoAssignment::where('admin_id', $judge->id)
                                            ->where('status', VideoAssignment::STATUS_ASSIGNED)
                                            ->where('id', '!=', $assignmentId)
                                            ->whereHas('videoSubmission', function($query) {
                                                $query->where('is_reevaluation_target', true);
                                            })
                                            ->with('videoSubmission')
                                            ->orderBy('created_at', 'asc')
                                            ->get();
            
            // 기존 평가가 없는 첫 번째 영상 찾기
            foreach ($allAssignments as $assignment) {
                if (!$assignment->videoSubmission) {
                    continue;
                }
                
                // 기존 평가 확인 (is_reevaluation = false)
                $originalEvaluation = Evaluation::where('video_submission_id', $assignment->video_submission_id)
                                               ->where('admin_id', $judge->id)
                                               ->where('is_reevaluation', false)
                                               ->first();
                
                // 기존 평가가 없으면 이 영상을 다음 영상으로 선택
                if (!$originalEvaluation) {
                    $nextAssignment = $assignment;
                    break;
                }
            }
        } else {
            // 일반 평가인 경우: 기존 로직 유지
            $nextAssignment = VideoAssignment::where('admin_id', $judge->id)
                                            ->where('status', VideoAssignment::STATUS_ASSIGNED)
                                            ->where('id', '!=', $assignmentId)
                                            ->whereHas('videoSubmission') // soft delete되지 않은 videoSubmission만
                                            ->with('videoSubmission')
                                            ->orderBy('created_at', 'asc')
                                            ->first();
        }

        if ($nextAssignment && $nextAssignment->videoSubmission) {
            // 다음 영상이 있으면 바로 심사 페이지로 이동
            $message = $isReevaluation 
                ? '재평가 결과가 수정되었습니다. 다음 영상을 재평가해주세요.' 
                : '심사 결과가 수정되었습니다. 다음 영상을 심사해주세요.';
            return redirect()->route('judge.evaluation.show', $nextAssignment->id)
                            ->with('success', $message);
        } else {
            // 더 이상 배정된 영상이 없으면 목록 페이지로 이동
            $message = $isReevaluation
                ? '재평가 결과가 수정되었습니다.'
                : '심사 결과가 수정되었습니다.';
            $redirectRoute = $isReevaluation 
                ? 'judge.reevaluation.list' 
                : 'judge.video.list';
            return redirect()->route($redirectRoute)
                            ->with('success', $message);
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
        
        // videoSubmission이 null인 경우 처리 (soft delete된 경우)
        if (!$submission) {
            Log::error('VideoSubmission이 존재하지 않음 (다운로드)', [
                'assignment_id' => $assignmentId,
                'judge_id' => $judge->id,
                'video_submission_id' => $assignment->video_submission_id
            ]);
            return back()->with('error', '해당 영상 정보를 찾을 수 없습니다. 영상이 삭제되었을 수 있습니다.');
        }

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
        
        // videoSubmission이 null인 경우 처리 (soft delete된 경우)
        if (!$submission) {
            Log::error('VideoSubmission이 존재하지 않음 (스트리밍)', [
                'assignment_id' => $assignmentId,
                'judge_id' => $judge->id,
                'video_submission_id' => $assignment->video_submission_id
            ]);
            return response()->json(['error' => '해당 영상 정보를 찾을 수 없습니다. 영상이 삭제되었을 수 있습니다.'], 404);
        }

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
     * AI 평가 실행 (Ajax 요청용)
     */
    public function performAiEvaluation(Request $request, $id)
    {
        Log::info('AI 평가 요청 시작', [
            'assignment_id' => $id,
            'user_id' => Auth::guard('admin')->id(),
            'request_data' => $request->all()
        ]);

        try {
            $judge = Auth::guard('admin')->user();
            
            if (!$judge) {
                Log::error('AI 평가 요청 - 인증되지 않은 사용자');
                return response()->json([
                    'success' => false,
                    'message' => '인증이 필요합니다.'
                ], 401);
            }

            Log::info('AI 평가 요청 - 심사위원 정보', [
                'judge_id' => $judge->id,
                'judge_name' => $judge->name
            ]);
            
            // VideoAssignment에서 해당 assignment 찾기
            $assignment = VideoAssignment::where('id', $id)
                ->where('admin_id', $judge->id)
                ->with('videoSubmission')
                ->first();

            Log::info('AI 평가 요청 - Assignment 조회 결과', [
                'assignment_found' => $assignment ? true : false,
                'assignment_id' => $id,
                'judge_id' => $judge->id
            ]);

            if (!$assignment) {
                Log::error('AI 평가 요청 - Assignment를 찾을 수 없음', [
                    'assignment_id' => $id,
                    'judge_id' => $judge->id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => '해당 배정을 찾을 수 없습니다.'
                ], 404);
            }

            $submission = $assignment->videoSubmission;

            // 이미 AI 평가가 진행 중이거나 완료된 경우 체크
            $existingAiEvaluation = AiEvaluation::where('video_submission_id', $submission->id)
                ->where('admin_id', $judge->id)
                ->first();

            if ($existingAiEvaluation) {
                if ($existingAiEvaluation->processing_status === AiEvaluation::STATUS_COMPLETED) {
                    return response()->json([
                        'success' => true,
                        'message' => '이미 AI 평가가 완료되었습니다.',
                        'ai_evaluation_id' => $existingAiEvaluation->id
                    ]);
                } elseif ($existingAiEvaluation->processing_status === AiEvaluation::STATUS_PROCESSING) {
                    return response()->json([
                        'success' => false,
                        'message' => 'AI 평가가 현재 진행 중입니다.'
                    ]);
                }
            }

            // 영상 파일이 S3에 있는지 확인
            if (!$submission->isStoredOnS3()) {
                return response()->json([
                    'success' => false,
                    'message' => 'S3에 저장된 영상만 AI 평가가 가능합니다.'
                ]);
            }

            // AI 평가 레코드 생성 또는 업데이트
            $aiEvaluation = AiEvaluation::updateOrCreate(
                [
                    'video_submission_id' => $submission->id,
                    'admin_id' => $judge->id
                ],
                [
                    'processing_status' => AiEvaluation::STATUS_PROCESSING,
                    'error_message' => null,
                    'ai_feedback' => '대용량 파일 처리 중입니다. 영상 길이에 따라 5-15분 소요될 수 있습니다.'
                ]
            );

            // OpenAI API를 사용한 실제 AI 평가 처리
            try {
                // API 키가 설정되어 있는지 확인
                $apiKey = config('services.openai.api_key');
                
                if (empty($apiKey) || $apiKey === 'your-openai-api-key-here') {
                    Log::warning('OpenAI API 키가 설정되지 않음. 더미 데이터 사용.');
                    
                    // API 키가 없으면 더미 데이터 사용
                    $aiEvaluation->update([
                        'pronunciation_score' => rand(7, 10),
                        'vocabulary_score' => rand(7, 10),
                        'fluency_score' => rand(7, 10),
                        'transcription' => 'Hello, my name is John. I am a student presenting about my favorite hobby. I really enjoy reading books because they help me learn new things and improve my vocabulary. Thank you for listening to my presentation.',
                        'ai_feedback' => '[Demo Mode] This is a simulated AI evaluation. The student demonstrates good basic English skills with clear pronunciation and appropriate vocabulary for their level. Areas for improvement include expanding sentence complexity and using more varied expressions.',
                        'processing_status' => AiEvaluation::STATUS_COMPLETED,
                        'processed_at' => now()
                    ]);
                } else {
                    // 실제 OpenAI API 사용
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
                }

                // 총점 계산
                $aiEvaluation->calculateTotalScore();
                $aiEvaluation->save();

                Log::info('AI 평가 완료', [
                    'assignment_id' => $id,
                    'submission_id' => $submission->id,
                    'judge_id' => $judge->id,
                    'total_score' => $aiEvaluation->total_score
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'AI 평가가 성공적으로 완료되었습니다.',
                    'ai_evaluation_id' => $aiEvaluation->id
                ]);

            } catch (\Exception $e) {
                // 오류 발생 시 상태 업데이트
                $aiEvaluation->update([
                    'processing_status' => AiEvaluation::STATUS_FAILED,
                    'error_message' => $e->getMessage()
                ]);

                Log::error('AI 평가 실패', [
                    'assignment_id' => $id,
                    'submission_id' => $submission->id,
                    'judge_id' => $judge->id,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'AI 평가 중 오류가 발생했습니다: ' . $e->getMessage()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('AI 평가 실행 오류: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'AI 평가를 실행할 수 없습니다.'
            ]);
        }
    }

    /**
     * AI 평가 결과 보기
     */
    public function showAiEvaluation($id)
    {
        try {
            $judge = Auth::guard('admin')->user();
            $submission = VideoSubmission::findOrFail($id);
            
            $aiEvaluation = AiEvaluation::where('video_submission_id', $id)
                ->where('admin_id', $judge->id)
                ->first();

            if (!$aiEvaluation) {
                return back()->with('error', 'AI 평가 결과를 찾을 수 없습니다.');
            }

            return view('judge.ai-evaluation-result', compact('submission', 'aiEvaluation'));

        } catch (\Exception $e) {
            Log::error('AI 평가 결과 조회 오류: ' . $e->getMessage());
            return back()->with('error', 'AI 평가 결과를 불러올 수 없습니다.');
        }
    }

    /**
     * AI 평가 결과 조회 (Ajax용)
     */
    public function showAiResult($aiEvaluationId)
    {
        try {
            $judge = Auth::guard('admin')->user();
            
            $aiEvaluation = AiEvaluation::where('id', $aiEvaluationId)
                ->where('admin_id', $judge->id)
                ->first();

            if (!$aiEvaluation) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI 평가 결과를 찾을 수 없습니다.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'aiEvaluation' => [
                    'id' => $aiEvaluation->id,
                    'pronunciation_score' => $aiEvaluation->pronunciation_score,
                    'vocabulary_score' => $aiEvaluation->vocabulary_score,
                    'fluency_score' => $aiEvaluation->fluency_score,
                    'total_score' => $aiEvaluation->total_score,
                    'ai_feedback' => $aiEvaluation->ai_feedback,
                    'transcription' => $aiEvaluation->transcription,
                    'processed_at' => $aiEvaluation->processed_at ? $aiEvaluation->processed_at->format('Y-m-d H:i:s') : null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('AI 평가 결과 Ajax 조회 오류: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'AI 평가 결과를 불러올 수 없습니다.'
            ], 500);
        }
    }

    /**
     * AI 평가 결과 조회 (Ajax용)
     */
    public function getAiResult($id)
    {
        try {
            $judge = Auth::guard('admin')->user();
            if (!$judge || !$judge->isJudge()) {
                return response()->json([
                    'success' => false,
                    'message' => '심사위원만 접근할 수 있습니다.'
                ], 403);
            }

            $aiEvaluation = AiEvaluation::with(['videoSubmission', 'admin'])->findOrFail($id);
            
            // 해당 심사위원이 접근할 수 있는 영상인지 확인
            $assignment = VideoAssignment::where('admin_id', $judge->id)
                                      ->where('video_submission_id', $aiEvaluation->video_submission_id)
                                      ->first();
            
            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => '접근 권한이 없습니다.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'aiEvaluation' => [
                    'id' => $aiEvaluation->id,
                    'student_name' => $aiEvaluation->videoSubmission->student_name_korean,
                    'student_name_english' => $aiEvaluation->videoSubmission->student_name_english,
                    'institution' => $aiEvaluation->videoSubmission->institution_name,
                    'class_name' => $aiEvaluation->videoSubmission->class_name,
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

        } catch (\Exception $e) {
            Log::error('AI 평가 결과 조회 오류: ' . $e->getMessage(), [
                'judge_id' => $judge->id ?? null,
                'ai_evaluation_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'AI 평가 결과를 불러올 수 없습니다.'
            ], 500);
        }
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