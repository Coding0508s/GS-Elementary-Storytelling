@extends('layouts.app')

@section('title', '심사 결과 수정')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">심사 결과 수정</h1>
        <p class="text-gray-600">{{ $judge->name }} 심사위원님</p>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- 학생 정보 -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">학생 정보</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">학생명</label>
                    <p class="mt-1 text-sm text-gray-900">
                        {{ $submission->student_name_korean }} ({{ $submission->student_name_english }})
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">기관명</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $submission->institution_name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">반</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $submission->class_name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">학년/나이</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $submission->grade }} ({{ $submission->age }}세)</p>
                </div>
                @if($submission->unit_topic)
                <div>
                    <label class="block text-sm font-medium text-gray-700">Unit 주제</label>
                    <p class="mt-1 text-sm text-gray-900 font-semibold text-blue-600">{{ $submission->unit_topic }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- 현재 심사 결과 -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">현재 심사 결과</h2>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">발음 점수</label>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $assignment->evaluation->pronunciation_score }}/100</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">어휘 점수</label>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $assignment->evaluation->vocabulary_score }}/100</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">유창성 점수</label>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $assignment->evaluation->fluency_score }}/100</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">자신감 점수</label>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $assignment->evaluation->confidence_score }}/100</p>
                    </div>
                </div>
                <div class="border-t pt-4">
                    <label class="block text-sm font-medium text-gray-700">총점</label>
                    <p class="mt-1 text-2xl font-bold text-blue-600">{{ $assignment->evaluation->total_score }}/100</p>
                </div>
                @if($assignment->evaluation->comments)
                <div>
                    <label class="block text-sm font-medium text-gray-700">현재 코멘트</label>
                    <p class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded">{{ $assignment->evaluation->comments }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- 수정 폼 -->
    <div class="mt-8 bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">심사 결과 수정</h2>
        
        <form action="{{ route('judge.evaluation.update', $assignment->id) }}" method="POST" id="evaluation-form">
            @csrf
            @method('PUT')
            
            <!-- 평가 기준 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @php
                    $criteria = [
                        'pronunciation_score' => '정확한 발음과 자연스러운 억양, 전달력',
                        'vocabulary_score' => '올바른 어휘 및 표현 사용',
                        'fluency_score' => '유창성 수준',
                        'confidence_score' => '자신감, 긍정적이고 밝은 태도'
                    ];
                @endphp
                
                @foreach($criteria as $field => $description)
                <div class="bg-gray-50 rounded-lg p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $description }}
                    </label>
                    
                    <div class="mb-3">
                        <label for="{{ $field }}" class="form-label">
                            점수 (1-100점)
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="range" 
                                   class="flex-grow-1" 
                                   id="{{ $field }}_range"
                                   min="1" 
                                   max="100" 
                                   step="1"
                                   value="{{ old($field, $assignment->evaluation->$field) }}">
                            <input type="number" 
                                   class="w-20 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                   id="{{ $field }}"
                                   name="{{ $field }}"
                                   min="1" 
                                   max="100" 
                                   value="{{ old($field, $assignment->evaluation->$field) }}"
                                   required>
                        </div>
                    </div>
                    
                    <!-- 점수 가이드 -->
                    <div class="text-xs text-gray-600">
                        <strong>점수 가이드:</strong><br>
                        1-25: 미흡 | 26-50: 보통 | 51-75: 양호 | 76-100: 우수
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- 총점 표시 -->
            <div class="mt-6 bg-blue-50 rounded-lg p-4 text-center">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">수정된 총점</h3>
                <div class="text-3xl font-bold text-blue-600">
                    <span id="total-score">{{ $assignment->evaluation->total_score }}</span> / 100점
                </div>
                <div class="mt-2">
                    <span id="grade-badge" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">등급 계산 중...</span>
                </div>
            </div>
            
            <!-- 심사 코멘트 -->
            <div class="mt-6">
                <label for="comments" class="block text-sm font-medium text-gray-700 mb-2">
                    심사 코멘트 수정
                </label>
                <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                          id="comments" 
                          name="comments" 
                          rows="4"
                          placeholder="학생의 발표에 대한 구체적인 피드백을 입력해주세요...">{{ old('comments', $assignment->evaluation->comments) }}</textarea>
                <p class="mt-1 text-sm text-gray-500">
                    학생과 학부모에게 도움이 될 수 있는 건설적인 피드백을 남겨주세요.
                </p>
            </div>
            
            <!-- 제출 버튼 -->
            <div class="mt-8 flex gap-4 justify-end">
                <a href="{{ route('judge.video.list') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    취소
                </a>
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700">
                    수정 완료
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const scoreInputs = document.querySelectorAll('input[type="number"]');
    const ranges = document.querySelectorAll('input[type="range"]');
    const totalScoreElement = document.getElementById('total-score');
    const gradeBadge = document.getElementById('grade-badge');
    
    // 점수 입력과 슬라이더 동기화
    scoreInputs.forEach((input, index) => {
        const range = ranges[index];
        
        // 숫자 입력 시 슬라이더 업데이트
        input.addEventListener('input', function() {
            const value = Math.max(1, Math.min(100, parseInt(this.value) || 1));
            this.value = value;
            range.value = value;
            calculateTotal();
        });
        
        // 슬라이더 변경 시 숫자 입력 업데이트
        range.addEventListener('input', function() {
            input.value = this.value;
            calculateTotal();
        });
    });
    
    // 총점 계산
    function calculateTotal() {
        let total = 0;
        scoreInputs.forEach(input => {
            const value = parseInt(input.value) || 0;
            total += value;
        });
        
        totalScoreElement.textContent = total;
        updateGrade(total);
    }
    
    // 등급 업데이트
    function updateGrade(total) {
        let grade, className;
        
        if (total >= 76) {
            grade = '우수 (A등급)';
            className = 'bg-green-100 text-green-800';
        } else if (total >= 51) {
            grade = '양호 (B등급)';
            className = 'bg-blue-100 text-blue-800';
        } else if (total >= 26) {
            grade = '보통 (C등급)';
            className = 'bg-yellow-100 text-yellow-800';
        } else if (total >= 1) {
            grade = '미흡 (D등급)';
            className = 'bg-red-100 text-red-800';
        } else {
            grade = '매우 미흡 (F등급)';
            className = 'bg-gray-100 text-gray-800';
        }
        
        gradeBadge.textContent = grade;
        gradeBadge.className = `inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${className}`;
    }
    
    // 폼 제출 시 확인
    document.getElementById('evaluation-form').addEventListener('submit', function(e) {
        const scores = Array.from(scoreInputs).map(input => parseInt(input.value));
        const hasInvalidScore = scores.some(score => score < 1 || score > 100 || isNaN(score));
        
        if (hasInvalidScore) {
            e.preventDefault();
            alert('모든 점수는 1-100점 사이여야 합니다.');
            return;
        }
        
        if (!confirm('심사 결과를 수정하시겠습니까?')) {
            e.preventDefault();
            return;
        }
    });
    
    // 초기 총점 계산
    calculateTotal();
});
</script>
@endsection 