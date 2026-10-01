<nav aria-label="신청 진행 단계">
    <ol class="application-progress">
        @foreach([1 => '동의', 2 => '정보 입력', 3 => '접수 완료'] as $step => $label)
            <li class="{{ $step == $currentStep ? 'is-current' : ($step < $currentStep ? 'is-complete' : '') }}" @if($step == $currentStep) aria-current="step" @endif>
                <span class="step-number" aria-hidden="true">{{ $step }}</span>
                <span>{{ $label }}</span>
            </li>
        @endforeach
    </ol>
</nav>
