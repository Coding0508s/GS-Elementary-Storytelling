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
                                       ->orderBy('created_at', 'desc')
                                       ->get();

        // 통계 정보
        $totalAssigned = $assignedVideos->count();
        $completedEvaluations = $assignedVideos->where('status', VideoAssignment::STATUS_COMPLETED)->count();
        $inProgressEvaluations = $assignedVideos->where('status', VideoAssignment::STATUS_IN_PROGRESS)->count();
        $pendingEvaluations = $assignedVideos->where('status', VideoAssignment::STATUS_ASSIGNED)->count();

        // 최근 배정된 영상들 (최대 5개)
        $recentAssignments = $assignedVideos->take(5);

        return view('judge.dashboard', compact(
            'judge',
            'assignedVideos',
            'totalAssigned',
            'completedEvaluations',
            'inProgressEvaluations',
            'pendingEvaluations',
            'recentAssignments'
        ));
    }

    /**
     * 심사할 영상 목록
     */
    public function videoList()
    {
        $judge = Auth::guard('admin')->user();
        
        $assignments = VideoAssignment::where('admin_id', $judge->id)
                                    ->with(['videoSubmission', 'evaluation'])
                                    ->orderBy('created_at', 'desc')
                                    ->get();

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
                                   ->with(['videoSubmission', 'evaluation'])
                                   ->firstOrFail();

        $submission = $assignment->videoSubmission;

        return view('judge.evaluation-form', compact('assignment', 'submission', 'judge'));
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

        return redirect()->route('judge.video.list')
                        ->with('success', '심사가 완료되었습니다.');
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
                                   ->with(['videoSubmission', 'evaluation'])
                                   ->firstOrFail();

        $submission = $assignment->videoSubmission;

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

        return redirect()->route('judge.video.list')
                        ->with('success', '심사 결과가 수정되었습니다.');
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

        // S3 다운로드 URL 생성
        $downloadUrl = $submission->getS3DownloadUrl(1); // 1시간 유효

        if (!$downloadUrl) {
            return back()->with('error', '영상 다운로드 링크를 생성할 수 없습니다.');
        }

        return redirect($downloadUrl);
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

        // S3 스트리밍 URL 생성 (24시간 유효)
        $streamUrl = $submission->getS3TemporaryUrl(24);

        if (!$streamUrl) {
            return response()->json(['error' => '영상 URL을 생성할 수 없습니다.'], 500);
        }

        return response()->json([
            'success' => true,
            'url' => $streamUrl,
            'filename' => $submission->video_file_name,
            'size' => $submission->getFormattedFileSizeAttribute()
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