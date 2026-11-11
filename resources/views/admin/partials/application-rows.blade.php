                    @forelse($applications as $application)
                        <tr data-application-id="{{ $application->id }}">
                            <td class="is-check">
                                <input type="checkbox"
                                       class="recipient-checkbox"
                                       value="{{ $application->id }}"
                                       aria-label="{{ $application->receipt_number }} {{ $application->student_name_korean }} 선택">
                            </td>
                            <td><strong class="receipt-code">{{ $application->receipt_number }}</strong></td>
                            <td><small>{{ $application->created_at?->format('Y-m-d H:i') }}</small></td>
                            <td>{{ $application->student_name_korean }}</td>
                            <td>{{ $application->grade }}</td>
                            <td>
                                {{ $application->institution_name }}
                                <span class="cell-sub">{{ $application->region }}</span>
                            </td>
                            <td>
                                {{ $application->parent_name }}
                                <span class="cell-sub">{{ $application->parent_phone }}</span>
                            </td>
                            <td class="is-question">
                                @php $day1Question = $application->instructorQuestion('day1'); @endphp
                                @if($day1Question !== '')
                                    <span class="question-clamp" title="{{ $day1Question }}">{{ $day1Question }}</span>
                                @else
                                    <span class="text-muted">없음</span>
                                @endif
                            </td>
                            <td class="is-question">
                                @php $day2Question = $application->instructorQuestion('day2'); @endphp
                                @if($day2Question !== '')
                                    <span class="question-clamp" title="{{ $day2Question }}">{{ $day2Question }}</span>
                                @else
                                    <span class="text-muted">없음</span>
                                @endif
                            </td>
                            <td>{{ $application->marketing_consent ? '동의' : '미동의' }}</td>
                            <td class="is-send">
                                @foreach(['morning' => '오전 안내', 'entry' => '입장 링크'] as $sendKind => $sendLabel)
                                    @php $sendResult = $applicationResults[$application->id][$sendKind]; @endphp
                                    <div class="send-line" data-result-id="{{ $application->id }}" data-result-kind="{{ $sendKind }}">
                                        <span class="send-label">{{ $sendLabel }}</span>
                                        <div>
                                            <span class="{{ $sendResult['class'] }}" title="{{ $sendResult['title'] }}" data-result-badge>{{ $sendResult['label'] }}</span>
                                            <div class="small text-muted" data-result-reason @if(!$sendResult['reason']) hidden @endif>{{ $sendResult['reason'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </td>
                            <td class="is-action">
                                <form method="POST"
                                      action="{{ route('admin.applications.destroy', $application) }}"
                                      onsubmit="return confirm(@json($application->receipt_number.' '.$application->student_name_korean.' 접수를 휴지통으로 옮길까요? 휴지통에서 다시 복원할 수 있습니다.'))">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="search" value="{{ $searchQuery }}">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">삭제</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-5">
                                @if($searchQuery !== '' || $resultStatus !== '')
                                    검색 결과가 없습니다.
                                @else
                                    아직 접수된 참가자가 없습니다.
                                @endif
                            </td>
                        </tr>
                    @endforelse
