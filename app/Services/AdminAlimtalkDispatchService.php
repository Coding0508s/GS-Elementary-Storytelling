<?php

namespace App\Services;

use App\Jobs\CancelAlimtalkForChange;
use App\Jobs\ManageAdminAlimtalkSchedule;
use App\Jobs\RegisterAdminAlimtalkSchedule;
use App\Jobs\SendAdminAlimtalkBatch;
use App\Models\SiteSetting;
use App\Models\VideoSubmission;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminAlimtalkDispatchService
{
    public const QUEUE = 'storytelling-alimtalk';

    private const SNAPSHOT_COLUMNS = ['id', 'parent_phone', 'student_name_korean', 'parent_name', 'created_at', 'alimtalk_results'];

    private const RESULT_PENDING = ['accepted', 'delivering', 'scheduled', 'unknown'];

    public function __construct(private SolapiAlimtalkService $alimtalk) {}

    /** Use the same effective result (including active dispatches) as the ledger. */
    public function matchingApplicationIds(\Illuminate\Database\Eloquent\Builder $query, string $kind, string $status): array
    {
        $ids = [];
        (clone $query)->reorder()->select(self::SNAPSHOT_COLUMNS)->chunkById(200, function ($rows) use ($kind, $status, &$ids) {
            $results = $this->applicationResults($rows);
            $states = $status === 'failed' ? ['failed', 'delivery_failed'] : ['unknown', 'cancel_unknown'];
            foreach ($rows as $row) {
                foreach ($kind === '' ? ['morning', 'entry'] : [$kind] as $type) {
                    if (in_array($results[$row->id][$type]['status'], $states, true)) {
                        $ids[] = $row->id;
                        break;
                    }
                }
            }
        });

        return $ids;
    }

    public function preview(int $adminId, string $token, string $kind, string $scope, array $ids, ?string $link, ?string $scheduledAt = null): object
    {
        $this->assertQueue();
        $scheduledAt = $this->validateSchedule($scheduledAt);
        $configuration = $this->alimtalk->adminConfigurationHash($kind);
        try {
            $link = $kind === 'entry' ? $this->alimtalk->entryLinkValue((string) $link) : null;
        } catch (\Exception $e) {
            $this->invalid($e->getMessage());
        }

        return DB::transaction(function () use ($adminId, $token, $kind, $scope, $ids, $link, $configuration, $scheduledAt) {
            $lock = DB::table('alimtalk_dispatch_locks')->where('kind', $kind)->lockForUpdate()->first();
            $existing = DB::table('alimtalk_dispatches')->where('request_token', $token)->first();
            if ($existing) {
                abort_unless((int) $existing->admin_id === $adminId, 403);

                return $existing; // A repeated POST never creates another operation.
            }
            if ($lock->dispatch_id) {
                $active = DB::table('alimtalk_dispatches')->find($lock->dispatch_id);
                if ($active && $active->status === 'preview' && now()->gte($active->expires_at)) {
                    DB::table('alimtalk_dispatches')->where('id', $active->id)->update(['status' => 'expired', 'updated_at' => now()]);
                } else {
                    $this->invalid('같은 종류의 안내가 확인 또는 발송 중입니다. 아래 진행 상황을 확인해주세요.');
                }
            }
            if ($scheduledAt && DB::table('alimtalk_dispatches')->where('kind', $kind)->where('scheduled_at', $scheduledAt)
                ->whereIn('status', ['preview', 'queued', 'processing', 'scheduled', 'partial_scheduled', 'needs_review', 'cancelling'])->exists()) {
                $this->invalid('같은 종류와 시간의 예약이 이미 있습니다. 기존 예약을 먼저 확인해주세요.');
            }
            $dispatchId = (string) Str::uuid();
            DB::table('alimtalk_dispatches')->insert([
                'id' => $dispatchId, 'request_token' => $token, 'admin_id' => $adminId,
                'kind' => $kind, 'scope' => $scope, 'status' => 'preview', 'scheduled_at' => $scheduledAt,
                'entry_link' => $link, 'configuration_hash' => $configuration,
                'expires_at' => now()->addMinutes(15), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $query = VideoSubmission::query()->when($scope !== 'all', fn ($q) => $q->whereIn('id', $ids));
            if ($scope === 'failed') {
                $eligible = $this->matchingApplicationIds($query, $kind, 'failed');
                if (count($eligible) !== count(array_unique($ids))) {
                    $this->invalid('실패 상태가 변경된 대상이 있습니다. 실패 목록을 다시 조회하고 선택해주세요.');
                }
                $query->whereIn('id', $eligible);
            }
            $this->snapshot($dispatchId, $query, $kind);
            DB::table('alimtalk_dispatch_locks')->where('kind', $kind)->update(['dispatch_id' => $dispatchId]);

            return DB::table('alimtalk_dispatches')->find($dispatchId);
        });
    }

    public function confirm(string $id, int $adminId, bool $resend): void
    {
        $this->assertQueue();
        $error = DB::transaction(function () use ($id, $adminId, $resend) {
            $dispatch = $this->ownedLocked($id, $adminId);
            abort_if($dispatch->deleted_at, 404);
            if ($dispatch->pending_edit || $dispatch->delete_requested) {
                $this->invalid('수정 또는 삭제 처리 중입니다.');
            }
            if (in_array($dispatch->status, ['queued', 'processing', 'completed', 'needs_review', 'scheduled', 'partial_scheduled', 'cancelling'], true)) {
                return null; // Same confirmation can be safely submitted again.
            }
            if ($dispatch->status !== 'preview' || now()->gte($dispatch->expires_at)) {
                if ($dispatch->status === 'preview') {
                    $this->release($dispatch, 'expired');
                }

                return '확인 시간이 만료되었거나 취소된 작업입니다. 대상을 다시 선택해주세요.';
            }
            if ($dispatch->scheduled_at) {
                $this->validateSchedule($dispatch->scheduled_at);
            }
            if (! DB::table('alimtalk_recipients')->where('dispatch_id', $id)->exists()) {
                $this->invalid('발송 가능한 대상이 없습니다. 대상을 다시 선택해주세요.');
            }
            if (($dispatch->previous_count > 0 || $dispatch->scope === 'failed') && ! $resend) {
                $this->invalid('이전 발송 이력을 확인하고 재발송에 동의해주세요.');
            }
            try {
                $configuration = $this->alimtalk->adminConfigurationHash($dispatch->kind);
            } catch (\Exception $e) {
                $configuration = '';
            }
            if ($dispatch->configuration_hash !== $configuration
                || ! $this->unchanged($dispatch)) {
                $this->release($dispatch, 'cancelled');

                return '대상 또는 발송 설정이 변경되었습니다. 대상을 다시 선택해 확인해주세요.';
            }
            DB::table('alimtalk_dispatches')->where('id', $id)->update([
                'status' => 'queued', 'confirmed_at' => now(), 'updated_at' => now(),
            ]);
            if ($dispatch->kind === 'entry') {
                SiteSetting::set('webinar_zoom_url', 'https://'.$dispatch->entry_link, '웨비나 입장 유튜브 링크');
            }
            // database queue uses this SAME connection/transaction. A rollback removes both
            // the confirmation and every queued job, without a post-commit dispatch gap.
            $jobClass = $dispatch->scheduled_at ? RegisterAdminAlimtalkSchedule::class : SendAdminAlimtalkBatch::class;
            DB::table('alimtalk_recipients')->where('dispatch_id', $id)->select('id')->orderBy('id')
                ->chunkById(100, function ($rows) use ($id, $jobClass) {
                    $jobClass::dispatch($id, $rows->pluck('id')->all())
                        ->onConnection('database')->onQueue(self::QUEUE)->beforeCommit();
                });

            return null;
        });
        if ($error) {
            $this->invalid($error);
        }
    }

    public function cancel(string $id, int $adminId): void
    {
        DB::transaction(function () use ($id, $adminId) {
            $dispatch = $this->ownedLocked($id, $adminId);
            if ($dispatch->status !== 'preview') {
                $this->invalid('발송이 확정된 작업은 취소할 수 없습니다.');
            }
            $this->release($dispatch, 'cancelled');
        });
    }

    public function updateDispatch(string $id, int $adminId, int $revision, array $data): void
    {
        $this->assertQueue();
        DB::transaction(function () use ($id, $adminId, $revision, $data) {
            $dispatch = $this->ownedLocked($id, $adminId);
            $this->assertRevision($dispatch, $revision);
            $counts = $this->recipientCounts($id);
            if (! $this->canEdit($dispatch, $counts)) {
                $this->invalid('처리 중이거나 이미 전송된 작업은 수정할 수 없습니다. 상태를 확인해주세요.');
            }
            $link = null;
            if ($dispatch->kind === 'entry') {
                try {
                    $link = $this->alimtalk->entryLinkValue((string) ($data['zoom_url'] ?? ''));
                } catch (\Exception $e) {
                    $this->invalid($e->getMessage());
                }
            }
            $edit = [
                'entry_link' => $link,
                'scheduled_at' => $data['delivery_mode'] === 'scheduled' ? $this->validateSchedule($data['scheduled_at'] ?? '') : null,
                'configuration_hash' => $this->alimtalk->adminConfigurationHash($dispatch->kind),
            ];
            if ($edit['scheduled_at'] && DB::table('alimtalk_dispatches')->where('kind', $dispatch->kind)
                ->where('scheduled_at', $edit['scheduled_at'])->where('id', '!=', $id)->whereNull('deleted_at')
                ->whereIn('status', ['preview', 'queued', 'processing', 'scheduled', 'partial_scheduled', 'needs_review', 'cancelling'])->exists()) {
                $this->invalid('같은 종류와 시간의 다른 예약이 있습니다. 다른 시간을 선택해주세요.');
            }
            if ($data['delivery_mode'] === 'scheduled' && ! $edit['scheduled_at']) {
                $this->invalid('예약 시간을 입력해주세요.');
            }
            $lock = DB::table('alimtalk_dispatch_locks')->where('kind', $dispatch->kind)->first();
            if ($lock->dispatch_id && $lock->dispatch_id !== $id) {
                $this->invalid('같은 종류의 다른 작업이 진행 중입니다. 해당 작업을 먼저 완료해주세요.');
            }
            DB::table('alimtalk_dispatch_locks')->where('kind', $dispatch->kind)->update(['dispatch_id' => $id]);
            DB::table('alimtalk_dispatches')->where('id', $id)->update([
                'pending_edit' => json_encode($edit, JSON_THROW_ON_ERROR), 'revision' => $revision + 1,
                'change_error' => null, 'updated_at' => now(),
            ]);
            $dispatch->pending_edit = json_encode($edit, JSON_THROW_ON_ERROR);
            if (($counts['scheduled'] ?? 0) > 0) {
                $this->cancelForChange($dispatch);
            } else {
                $this->replaceWithDraft($dispatch, $edit);
            }
        });
    }

    /** Delete from the visible history; retain provider identifiers and audit records. */
    public function deleteDispatch(string $id, int $adminId, int $revision): void
    {
        $this->assertQueue();
        DB::transaction(function () use ($id, $adminId, $revision) {
            $dispatch = $this->ownedLocked($id, $adminId);
            $this->assertRevision($dispatch, $revision);
            $counts = $this->recipientCounts($id);
            if (! $this->canDelete($dispatch, $counts)) {
                $this->invalid('처리 중이거나 결과 확인이 필요한 작업은 아직 삭제할 수 없습니다.');
            }
            DB::table('alimtalk_dispatches')->where('id', $id)->update([
                'delete_requested' => true, 'revision' => $revision + 1, 'change_error' => null, 'updated_at' => now(),
            ]);
            if (($counts['scheduled'] ?? 0) > 0) {
                $this->cancelForChange($dispatch);
            } else {
                $this->archive($dispatch);
            }
        });
    }

    private function cancelForChange(object $dispatch): void
    {
        $groups = DB::table('alimtalk_recipients')->where('dispatch_id', $dispatch->id)
            ->where('status', 'scheduled')->distinct()->pluck('provider_group_id');
        if ($groups->isEmpty() || $groups->contains(null)) {
            $this->invalid('예약 그룹을 확인하지 못했습니다. 제공사 예약 상태를 먼저 확인해주세요.');
        }
        DB::table('alimtalk_dispatches')->where('id', $dispatch->id)->update(['status' => 'cancelling']);
        DB::table('alimtalk_recipients')->where('dispatch_id', $dispatch->id)->where('status', 'scheduled')
            ->update(['status' => 'cancel_pending', 'updated_at' => now()]);
        foreach ($groups as $group) {
            CancelAlimtalkForChange::dispatch($dispatch->id, $group)
                ->onConnection('database')->onQueue(self::QUEUE)->beforeCommit();
        }
    }

    private function finishChange(object $dispatch, array $counts, string $status): bool
    {
        if (! $dispatch->pending_edit && ! $dispatch->delete_requested) {
            return false;
        }
        foreach (['pending', 'processing', 'scheduled', 'cancel_pending', 'unknown', 'cancel_unknown'] as $state) {
            if (($counts[$state] ?? 0) > 0) {
                return true; // Never hide an unresolved external reservation or automatically resend.
            }
        }
        if ($dispatch->delete_requested) {
            $dispatch->status = $status;
            $this->archive($dispatch);
        } elseif (($counts['accepted'] ?? 0) + ($counts['delivering'] ?? 0) + ($counts['delivered'] ?? 0) + ($counts['delivery_failed'] ?? 0) > 0) {
            DB::table('alimtalk_dispatches')->where('id', $dispatch->id)->update([
                'pending_edit' => null, 'change_error' => '이미 발송이 시작되어 수정하지 못했습니다. 기존 발송 결과를 확인해주세요.',
            ]);
            $this->release($dispatch, $status);
        } else {
            $this->replaceWithDraft($dispatch, json_decode($dispatch->pending_edit, true, 512, JSON_THROW_ON_ERROR));
        }

        return true;
    }

    private function replaceWithDraft(object $source, array $edit): void
    {
        // New IDs fence out every old queued batch. Save creates a preview, never a send.
        $newId = (string) Str::uuid();
        DB::table('alimtalk_dispatches')->insert([
            'id' => $newId, 'request_token' => (string) Str::uuid(), 'admin_id' => $source->admin_id,
            'kind' => $source->kind, 'scope' => $source->scope, 'status' => 'preview',
            'scheduled_at' => $edit['scheduled_at'], 'entry_link' => $edit['entry_link'],
            'configuration_hash' => $edit['configuration_hash'],
            'expires_at' => now()->addMinutes(15), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $query = VideoSubmission::query()->when($source->scope !== 'all', fn ($q) => $q->whereIn('id',
            DB::table('alimtalk_dispatch_targets')->where('dispatch_id', $source->id)->select('submission_id')));
        $this->snapshot($newId, $query, $source->kind, true);
        DB::table('alimtalk_recipients')->where('dispatch_id', $source->id)->where('status', 'pending')
            ->update(['status' => 'cancelled', 'reason' => '수정 전 작업을 취소했습니다.', 'updated_at' => now()]);
        DB::table('alimtalk_dispatches')->where('id', $source->id)->update([
            'status' => 'cancelled', 'deleted_at' => now(), 'pending_edit' => null, 'updated_at' => now(),
        ]);
        DB::table('alimtalk_dispatch_locks')->where('kind', $source->kind)->update(['dispatch_id' => $newId]);
    }

    private function archive(object $dispatch): void
    {
        DB::table('alimtalk_recipients')->where('dispatch_id', $dispatch->id)->where('status', 'pending')
            ->update(['status' => 'cancelled', 'reason' => '발송 전 삭제했습니다.', 'updated_at' => now()]);
        $status = in_array($dispatch->status, ['preview', 'queued'], true) ? 'cancelled' : $dispatch->status;
        $this->release($dispatch, $status);
        DB::table('alimtalk_dispatches')->where('id', $dispatch->id)->update([
            'deleted_at' => now(), 'delete_requested' => false, 'pending_edit' => null,
        ]);
    }

    private function recipientCounts(string $id): array
    {
        return DB::table('alimtalk_recipients')->where('dispatch_id', $id)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all();
    }

    private function canEdit(object $dispatch, array $counts): bool
    {
        return $this->canDelete($dispatch, $counts)
            && ($counts['accepted'] ?? 0) + ($counts['delivering'] ?? 0) + ($counts['delivered'] ?? 0) + ($counts['delivery_failed'] ?? 0) === 0;
    }

    private function canDelete(object $dispatch, array $counts): bool
    {
        return ! $dispatch->deleted_at && ! $dispatch->pending_edit && ! $dispatch->delete_requested
            && in_array($dispatch->status, ['preview', 'queued', 'cancelled', 'expired', 'closed', 'completed', 'scheduled', 'partial_scheduled'], true)
            && ($counts['processing'] ?? 0) + ($counts['unknown'] ?? 0) + ($counts['cancel_unknown'] ?? 0) + ($counts['cancel_pending'] ?? 0) === 0;
    }

    private function assertRevision(object $dispatch, int $revision): void
    {
        if ((int) $dispatch->revision !== $revision) {
            $this->invalid('작업 상태가 변경되었습니다. 새로고침한 후 다시 시도해주세요.');
        }
    }

    public function recent(): array
    {
        $active = DB::table('alimtalk_dispatches')->whereNull('deleted_at')->whereIn('status', ['preview', 'queued', 'processing', 'needs_review', 'scheduled', 'partial_scheduled', 'cancelling'])
            ->latest('created_at')->orderByDesc('id')->get();

        return $active->concat(DB::table('alimtalk_dispatches')->whereNull('deleted_at')->whereNotIn('id', $active->pluck('id'))
            ->latest('created_at')->orderByDesc('id')->limit(8)->get())
            ->map(fn ($dispatch) => $this->summary($dispatch))->all();
    }

    /** Read current per-person work in one query; historical projections alone miss queued reservations. */
    public function applicationResults(\Illuminate\Support\Collection $applications): array
    {
        $results = [];
        $current = $applications->keyBy('id');
        foreach ($applications as $application) {
            foreach (['morning', 'entry'] as $kind) {
                $results[$application->id][$kind] = $this->resultBadge($application->alimtalkResult($kind));
            }
        }
        if ($applications->isEmpty() || ! \Illuminate\Support\Facades\Schema::hasColumn('alimtalk_dispatches', 'deleted_at')) {
            return $results;
        }
        $rows = DB::table('alimtalk_dispatch_targets as t')
            ->join('alimtalk_dispatches as d', 'd.id', '=', 't.dispatch_id')
            ->join('alimtalk_recipients as r', function ($join) {
                $join->on('r.dispatch_id', '=', 't.dispatch_id')->on('r.phone', '=', 't.phone');
            })
            ->whereIn('t.submission_id', $current->keys())->whereNull('d.deleted_at')->whereNotNull('d.confirmed_at')
            ->orderByRaw("CASE WHEN r.status IN ('unknown', 'cancel_unknown') THEN 0 WHEN r.status = 'cancel_pending' THEN 1 WHEN r.status IN ('pending', 'processing', 'scheduled') THEN 2 ELSE 3 END")
            ->orderByDesc('r.id')->get(array_merge(['t.submission_id', 't.identity_hash', 'd.kind', 'd.scheduled_at', 'r.status', 'r.reason'],
                \Illuminate\Support\Facades\Schema::hasColumn('alimtalk_recipients', 'result_checked_at')
                    ? ['r.provider_status_code', 'r.delivery_channel', 'r.result_checked_at', 'r.result_error'] : []));
        $seen = [];
        foreach ($rows as $row) {
            $key = $row->submission_id.':'.$row->kind;
            if (isset($seen[$key]) || $this->identity($current[$row->submission_id]) !== $row->identity_hash) {
                continue;
            }
            $seen[$key] = true;
            $status = $row->status;
            $title = $row->reason;
            if ($row->scheduled_at && in_array($status, ['pending', 'processing', 'scheduled'], true)) {
                $phase = match ($status) {
                    'pending' => '예약 등록 대기', 'processing' => '예약 등록 중', default => 'SOLAPI 예약 접수 완료',
                };
                $title = $phase.' · '.Carbon::parse($row->scheduled_at)->format('Y.m.d H:i').' (한국 시간)';
                $status = 'scheduled';
            }
            $results[$row->submission_id][$row->kind] = $this->resultBadge(array_merge((array) $row, ['status' => $status, 'title' => $title]));
        }

        return $results;
    }

    private function resultBadge(?array $result): array
    {
        $status = $result['status'] ?? 'none';
        [$label, $color] = match ($status) {
            'sent', 'accepted' => ['요청 접수', 'success'],
            'delivering' => ['발송 중', 'info'],
            'delivered' => ['전달 완료', 'success'],
            'delivery_failed' => ['전달 실패', 'danger'],
            'scheduled' => ['예약 중', 'info'],
            'pending' => ['발송 대기', 'secondary'],
            'processing' => ['처리 중', 'warning'],
            'cancelled' => ['취소', 'secondary'],
            'cancel_pending' => ['취소 확인 중', 'warning'],
            'unknown', 'cancel_unknown' => ['확인 필요', 'warning'],
            'failed' => ['실패', 'danger'],
            'skipped' => ['제외', 'secondary'],
            default => ['미발송', null],
        };

        if ($status === 'delivered' && in_array($result['delivery_channel'] ?? '', ['SMS', 'LMS', 'MMS'], true)) {
            $label = '문자 전달 완료';
        }
        $title = $result['title'] ?? ($result['reason'] ?? (in_array($status, ['accepted', 'sent'], true) ? '발송 서비스에 요청이 접수된 상태입니다. 실제 수신 완료를 의미하지 않습니다.' : ''));
        if ($status === 'sent' && empty($result['dispatch_id'])) {
            $title .= ' · 이전 발송 기록은 조회 식별자가 없어 최종 결과를 자동 확인할 수 없습니다.';
        }
        if (! empty($result['result_checked_at'])) {
            $title .= ' · 결과 조회 '.$result['result_checked_at'];
        }
        if (! empty($result['result_error'])) {
            $title .= ' · '.$result['result_error'];
        }

        return [
            'status' => $status, 'label' => $label,
            'class' => $color ? 'badge text-bg-'.$color : 'text-muted small',
            'title' => $title,
            'reason' => in_array($status, ['failed', 'delivery_failed', 'skipped', 'unknown', 'cancel_unknown'], true) ? ($result['reason'] ?? '') : ($result['result_error'] ?? ''),
        ];
    }

    /** Read-only provider requests: one group per screen refresh, bounded work for the scheduler. */
    public function syncDeliveryResults(int $limit = 1): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('alimtalk_recipients', 'result_checked_at')) {
            return ['checked' => 0, 'errors' => 0, 'ready' => false];
        }
        $checked = $errors = 0;
        for ($i = 0; $i < min(max($limit, 1), 20); $i++) {
            $candidate = DB::table('alimtalk_recipients as r')->join('alimtalk_dispatches as d', 'd.id', '=', 'r.dispatch_id')
                ->whereIn('r.status', self::RESULT_PENDING)->whereNotNull('r.provider_group_id')
                ->where(fn ($q) => $q->whereNull('d.scheduled_at')->orWhere('d.scheduled_at', '<=', now()))
                ->where(fn ($q) => $q->whereNull('r.result_checked_at')->orWhere('r.result_checked_at', '<=', now()->subSeconds(30)))
                ->orderByRaw('CASE WHEN r.result_checked_at IS NULL THEN 0 ELSE 1 END')->orderBy('r.result_checked_at')->orderBy('r.id')
                ->first(['r.dispatch_id', 'r.provider_group_id']);
            if (! $candidate) {
                break;
            }
            $at = now()->toDateTimeString();
            $rows = DB::transaction(function () use ($candidate, $at) {
                $dispatch = $this->lockedDispatch($candidate->dispatch_id);
                if (! $dispatch || ($dispatch->scheduled_at && Carbon::parse($dispatch->scheduled_at)->isFuture())) {
                    return collect();
                }
                $rows = DB::table('alimtalk_recipients')->where('dispatch_id', $dispatch->id)
                    ->where('provider_group_id', $candidate->provider_group_id)->whereIn('status', self::RESULT_PENDING)
                    ->where(fn ($q) => $q->whereNull('result_checked_at')->orWhere('result_checked_at', '<=', now()->subSeconds(30)))
                    ->lockForUpdate()->get();
                DB::table('alimtalk_recipients')->whereIn('id', $rows->pluck('id'))->update(['result_checked_at' => $at]);

                return $rows;
            });
            if ($rows->isEmpty()) {
                continue;
            }
            $checked++;
            try {
                $results = $this->alimtalk->adminDeliveryResults($candidate->provider_group_id);
            } catch (\Throwable $e) {
                $results = [];
                Log::warning('관리자 알림톡 결과 조회 실패', ['dispatch_id' => $candidate->dispatch_id, 'exception' => get_class($e)]);
            }
            if ($rows->contains(fn ($row) => ! in_array($results[$row->phone]['status'] ?? '', ['accepted', 'delivering', 'delivered', 'delivery_failed', 'cancelled'], true))) {
                $errors++;
            }
            DB::transaction(function () use ($candidate, $rows, $at, $results) {
                $dispatch = $this->lockedDispatch($candidate->dispatch_id);
                $projections = [];
                foreach ($rows as $row) {
                    $result = $results[$row->phone] ?? ['status' => 'unavailable'];
                    $query = DB::table('alimtalk_recipients')->where('id', $row->id)->where('result_checked_at', $at)
                        ->whereIn('status', self::RESULT_PENDING);
                    if (! in_array($result['status'], ['accepted', 'delivering', 'delivered', 'delivery_failed', 'cancelled'], true)) {
                        $query->update(['result_error' => '최종 결과를 조회하지 못했습니다. 기존 상태를 유지하며 다시 조회합니다.']);

                        continue;
                    }
                    $update = [
                        'status' => $result['status'], 'reason' => $result['reason'] ?? null, 'result_error' => null,
                        'provider_status_code' => $result['provider_status_code'] ?? null,
                        'delivery_channel' => $result['delivery_channel'] ?? null, 'updated_at' => now(),
                    ];
                    if ($query->update($update)) {
                        $projections[$row->phone] = $update + ['result_checked_at' => $at, 'projection_order' => $row->id];
                    }
                }
                if ($projections) {
                    $this->projectResults($dispatch, $projections);
                    $this->settleDispatch($dispatch);
                }
            });
        }

        return ['checked' => $checked, 'errors' => $errors, 'ready' => true];
    }

    public function requestScheduleManagement(string $id, int $adminId, bool $cancel): void
    {
        $this->assertQueue();
        DB::transaction(function () use ($id, $adminId, $cancel) {
            $dispatch = $this->ownedLocked($id, $adminId);
            if (! $dispatch->scheduled_at || ! in_array($dispatch->status, ['scheduled', 'partial_scheduled', 'needs_review', 'cancelling'], true)) {
                $this->invalid('예약 등록이 끝난 작업만 조회하거나 취소할 수 있습니다.');
            }
            if ($dispatch->status === 'cancelling' && $cancel) {
                return;
            }
            $groups = DB::table('alimtalk_recipients')->where('dispatch_id', $id)
                ->whereIn('status', ['scheduled', 'cancel_unknown', 'unknown', 'cancel_pending'])->whereNotNull('provider_group_id')
                ->distinct()->pluck('provider_group_id');
            if ($groups->isEmpty()) {
                $this->invalid('자동으로 확인할 수 있는 예약 그룹이 없습니다. 제공사 기록을 확인해주세요.');
            }
            if ($cancel) {
                DB::table('alimtalk_dispatches')->where('id', $id)->update(['status' => 'cancelling', 'updated_at' => now()]);
                DB::table('alimtalk_recipients')->where('dispatch_id', $id)->whereIn('status', ['scheduled', 'cancel_unknown', 'unknown'])
                    ->whereNotNull('provider_group_id')->update(['status' => 'cancel_pending', 'updated_at' => now()]);
            }
            foreach ($groups as $group) {
                ManageAdminAlimtalkSchedule::dispatch($id, $group, $cancel)
                    ->onConnection('database')->onQueue(self::QUEUE)->beforeCommit();
            }
        });
    }

    public function manageScheduleGroup(string $id, string $group, bool $cancel): void
    {
        $states = $cancel ? ['cancel_pending'] : ['scheduled', 'cancel_unknown', 'unknown', 'cancel_pending'];
        if (! DB::table('alimtalk_recipients')->where('dispatch_id', $id)->where('provider_group_id', $group)->whereIn('status', $states)->exists()) {
            return;
        }
        $result = $this->alimtalk->manageAdminSchedule($group, $cancel);
        $this->saveScheduleGroup($id, $group, $states, $result);
    }

    public function scheduleManagementFailed(string $id, string $group, bool $cancel): void
    {
        $this->saveScheduleGroup($id, $group, $cancel ? ['cancel_pending'] : ['scheduled', 'cancel_unknown', 'unknown', 'cancel_pending'], [
            'status' => $cancel ? 'cancel_unknown' : 'unavailable', 'reason' => '예약 상태 처리가 중단되었습니다. 제공사 기록을 확인해주세요.',
        ]);
    }

    private function saveScheduleGroup(string $id, string $group, array $states, array $result): void
    {
        DB::transaction(function () use ($id, $group, $states, $result) {
            $dispatch = $this->lockedDispatch($id);
            if (! $dispatch) {
                return;
            }
            $rows = DB::table('alimtalk_recipients')->where('dispatch_id', $id)->where('provider_group_id', $group)
                ->whereIn('status', $states)->lockForUpdate()->get();
            $projections = [];
            foreach ($rows as $row) {
                $resolved = $result;
                if ($resolved['status'] === 'unavailable') {
                    $resolved['status'] = $row->status;
                }
                if ($row->status === 'cancel_pending' && $resolved['status'] === 'scheduled') {
                    $resolved = ['status' => Carbon::parse($row->updated_at)->lt(now()->subSeconds(90)) ? 'cancel_unknown' : 'cancel_pending', 'reason' => '취소 응답 확인 중입니다. 잠시 후 예약 상태를 다시 조회해주세요.'];
                }
                DB::table('alimtalk_recipients')->where('id', $row->id)->update([
                    'status' => $resolved['status'], 'reason' => $resolved['reason'], 'updated_at' => now(),
                ]);
                $projections[$row->phone] = $resolved + ['projection_order' => $row->id];
            }
            $this->projectResults($dispatch, $projections);
            $this->settleDispatch($dispatch);
        });
    }

    private function settleDispatch(object $dispatch): void
    {
        $counts = DB::table('alimtalk_recipients')->where('dispatch_id', $dispatch->id)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $status = match (true) {
            ($counts['cancel_pending'] ?? 0) > 0 => 'cancelling',
            ($counts['pending'] ?? 0) + ($counts['processing'] ?? 0) > 0 => 'processing',
            ($counts['unknown'] ?? 0) + ($counts['cancel_unknown'] ?? 0) > 0 => 'needs_review',
            ($counts['scheduled'] ?? 0) > 0 => (($counts['failed'] ?? 0) + ($counts['skipped'] ?? 0) > 0 ? 'partial_scheduled' : 'scheduled'),
            ($counts['cancelled'] ?? 0) > 0 && ($counts['accepted'] ?? 0) + ($counts['delivering'] ?? 0) + ($counts['delivered'] ?? 0) + ($counts['delivery_failed'] ?? 0) === 0 => 'cancelled',
            default => 'completed',
        };
        DB::table('alimtalk_dispatches')->where('id', $dispatch->id)->update(['status' => $status, 'updated_at' => now()]);
        if ($this->finishChange($dispatch, $counts->all(), $status)) {
            return;
        }
        if (in_array($status, ['scheduled', 'partial_scheduled', 'cancelled', 'completed'], true)) {
            $this->release($dispatch, $status);
        }
    }

    private function snapshot(string $dispatchId, $query, string $kind, bool $allowEmpty = false): void
    {
        $selected = $invalid = $previous = 0;
        $query->select(self::SNAPSHOT_COLUMNS)
            ->chunkById(500, function ($rows) use ($dispatchId, $kind, &$selected, &$invalid, &$previous) {
                $targets = [];
                foreach ($rows as $row) {
                    $phone = $this->alimtalk->normalizedAdminPhone($row->parent_phone);
                    $valid = $this->alimtalk->validAdminPhone($phone);
                    $selected++;
                    $invalid += ! $valid;
                    $previous += in_array($row->alimtalkResult($kind)['status'] ?? '', ['sent', 'accepted', 'delivering', 'delivered', 'delivery_failed', 'unknown', 'scheduled', 'cancel_unknown'], true);
                    $targets[] = [
                        'dispatch_id' => $dispatchId, 'submission_id' => $row->id,
                        'phone' => $phone, 'valid_phone' => $valid, 'identity_hash' => $this->identity($row),
                    ];
                }
                DB::table('alimtalk_dispatch_targets')->insert($targets);
            });
        if ($selected === 0 && ! $allowEmpty) {
            $this->invalid('보낼 참가자가 없습니다.');
        }
        DB::table('alimtalk_recipients')->insertUsing(
            ['dispatch_id', 'phone', 'created_at', 'updated_at'],
            DB::table('alimtalk_dispatch_targets')->select('dispatch_id', 'phone')
                ->selectRaw('?, ?', [now(), now()])->where('dispatch_id', $dispatchId)
                ->where('valid_phone', true)->groupBy('dispatch_id', 'phone')
        );
        if (! $allowEmpty && ! DB::table('alimtalk_recipients')->where('dispatch_id', $dispatchId)->exists()) {
            $this->invalid('발송 가능한 휴대폰 번호가 없습니다.');
        }
        DB::table('alimtalk_dispatches')->where('id', $dispatchId)->update([
            'selected_count' => $selected, 'invalid_count' => $invalid, 'previous_count' => $previous,
        ]);
    }

    private function validateSchedule(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $format = str_contains($value, 'T') ? 'Y-m-d\TH:i' : 'Y-m-d H:i:s';
        try {
            $date = Carbon::createFromFormat('!'.$format, $value, 'Asia/Seoul');
            if ($date->format($format) !== $value || $date->lt(now()->addMinutes(5))) {
                $this->invalid('예약 시간은 한국 시간 기준 현재보다 5분 이후로 지정해주세요.');
            }
        } catch (\Exception $e) {
            $this->invalid('올바른 예약 시간을 입력해주세요. 한국 시간 기준 5분 이후여야 합니다.');
        }

        return $date->format('Y-m-d H:i:s');
    }

    public function summary(object $dispatch): array
    {
        $counts = DB::table('alimtalk_recipients')->where('dispatch_id', $dispatch->id)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all();
        $stalled = in_array($dispatch->status, ['queued', 'processing', 'cancelling'], true)
            && now()->diffInSeconds($dispatch->updated_at, true) > 120;

        return [
            'id' => $dispatch->id, 'admin_id' => $dispatch->admin_id, 'kind' => $dispatch->kind,
            'scope' => $dispatch->scope, 'status' => $dispatch->status,
            'revision' => $dispatch->revision,
            'change_pending' => (bool) ($dispatch->pending_edit || $dispatch->delete_requested),
            'change_message' => $dispatch->delete_requested ? '기존 예약의 취소 확인 후 목록에서 삭제합니다.' : ($dispatch->pending_edit ? '기존 예약의 취소 확인 후 수정한 내용을 다시 확인할 수 있습니다.' : null),
            'change_error' => $dispatch->change_error,
            'can_edit' => $this->canEdit($dispatch, $counts),
            'can_delete' => $this->canDelete($dispatch, $counts),
            'selected_count' => $dispatch->selected_count, 'invalid_count' => $dispatch->invalid_count,
            'previous_count' => $dispatch->previous_count, 'total' => array_sum($counts),
            'counts' => $counts, 'entry_link' => $dispatch->entry_link,
            'awaiting_results' => ($counts['accepted'] ?? 0) + ($counts['delivering'] ?? 0) > 0,
            'scheduled_at' => $dispatch->scheduled_at,
            'can_cancel_schedule' => $dispatch->scheduled_at && in_array($dispatch->status, ['scheduled', 'partial_scheduled', 'needs_review', 'cancelling'], true) && (($counts['scheduled'] ?? 0) + ($counts['cancel_unknown'] ?? 0) + ($counts['unknown'] ?? 0) + ($counts['cancel_pending'] ?? 0) > 0),
            'updated_at' => $dispatch->updated_at, 'expires_at' => $dispatch->expires_at,
            'expires_at_iso' => \Illuminate\Support\Carbon::parse($dispatch->expires_at)->toIso8601String(),
            'stalled' => $stalled,
            'errors' => DB::table('alimtalk_recipients')->where('dispatch_id', $dispatch->id)
                ->whereIn('status', ['failed', 'delivery_failed', 'unknown', 'skipped', 'cancel_unknown', 'scheduled', 'cancel_pending'])->whereNotNull('reason')
                ->select('reason')->distinct()->limit(5)->pluck('reason')->all(),
        ];
    }

    public function processBatch(string $id, array $recipientIds): void
    {
        if (count($recipientIds) > 100) {
            throw new \LogicException('Batch exceeds 100 recipients.');
        }
        $batch = DB::transaction(function () use ($id, $recipientIds) {
            $dispatch = $this->lockedDispatch($id);
            if (! $dispatch || ! in_array($dispatch->status, ['queued', 'processing'], true)) {
                return null;
            }
            $recipients = DB::table('alimtalk_recipients')->where('dispatch_id', $id)
                ->whereIn('id', $recipientIds)->where('status', 'pending')->lockForUpdate()->get();
            if ($recipients->isEmpty()) {
                return null; // Claimed/finished recipients are NEVER sent again on redelivery.
            }
            DB::table('alimtalk_recipients')->whereIn('id', $recipients->pluck('id'))->update([
                'status' => 'processing', 'started_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('alimtalk_dispatches')->where('id', $id)->update(['status' => 'processing', 'updated_at' => now()]);

            return [$dispatch, $recipients];
        });
        if (! $batch) {
            return;
        }
        [$dispatch, $recipients] = $batch;
        $phones = [];
        $results = [];
        try {
            $configurationValid = $dispatch->configuration_hash === $this->alimtalk->adminConfigurationHash($dispatch->kind);
        } catch (\Exception $e) {
            $configurationValid = false;
        }
        $eligible = [];
        DB::table('alimtalk_dispatch_targets')->where('dispatch_id', $id)->whereIn('phone', $recipients->pluck('phone'))
            ->orderBy('id')->chunkById(500, function ($targets) use (&$eligible) {
                $current = VideoSubmission::whereIn('id', $targets->pluck('submission_id'))->get(self::SNAPSHOT_COLUMNS)->keyBy('id');
                foreach ($targets as $target) {
                    $row = $current->get($target->submission_id);
                    if ($row && $this->identity($row) === $target->identity_hash) {
                        $eligible[$target->phone] = true;
                    }
                }
            });
        foreach ($recipients as $recipient) {
            if ($dispatch->scheduled_at && Carbon::parse($dispatch->scheduled_at)->lt(now()->addMinute())) {
                $results[$recipient->phone] = ['status' => 'skipped', 'reason' => '예약 시간이 지났거나 임박하여 즉시 발송을 방지했습니다.'];
            } elseif (! $configurationValid) {
                $results[$recipient->phone] = ['status' => 'skipped', 'reason' => '발송 설정이 변경되어 제외했습니다.'];
            } elseif (isset($eligible[$recipient->phone])) {
                $phones[] = $recipient->phone;
            } else {
                $results[$recipient->phone] = ['status' => 'skipped', 'reason' => '접수가 삭제되었거나 정보가 변경되어 제외했습니다.'];
            }
        }
        // pending -> processing -> accepted / failed / unknown / skipped.
        // Never hold database locks during the external request.
        if ($phones) {
            $results += $dispatch->scheduled_at
                ? $this->alimtalk->scheduleAdminBatch($phones, $dispatch->kind, $dispatch->entry_link, Carbon::parse($dispatch->scheduled_at))
                : $this->alimtalk->sendAdminBatch($phones, $dispatch->kind, $dispatch->entry_link);
        }
        Log::info('관리자 알림톡 요청 응답', [
            'dispatch_id' => $dispatch->id,
            'recipient_ids' => $recipients->pluck('id')->all(),
            'provider_group_ids' => array_values(array_unique(array_filter(array_column($results, 'provider_group_id')))),
        ]);
        $this->recordBatch($dispatch, $recipients, $results);
    }

    public function failBatch(string $id, array $recipientIds): void
    {
        DB::transaction(function () use ($id, $recipientIds) {
            $dispatch = $this->lockedDispatch($id);
            if (! $dispatch || ! in_array($dispatch->status, ['queued', 'processing'], true)) {
                return;
            }
            $recipients = DB::table('alimtalk_recipients')->where('dispatch_id', $id)
                ->whereIn('id', $recipientIds)->whereIn('status', ['pending', 'processing'])->get();
            $results = [];
            foreach ($recipients as $recipient) {
                $results[$recipient->phone] = $recipient->status === 'processing'
                    ? ['status' => 'unknown', 'reason' => '처리 도중 중단되었습니다. 제공사 기록을 확인해주세요.']
                    : ['status' => 'failed', 'reason' => '발송 작업을 시작하지 못했습니다.'];
            }
            $this->recordBatch($dispatch, $recipients, $results);
        });
    }

    /** Operator-only recovery AFTER verifying that the worker/request has stopped. */
    public function closeStopped(string $id): void
    {
        DB::transaction(function () use ($id) {
            $dispatch = $this->lockedDispatch($id);
            if (! $dispatch || ! in_array($dispatch->status, ['queued', 'processing', 'needs_review'], true)) {
                $this->invalid('종료 확인이 필요한 작업이 아닙니다.');
            }
            if (DB::table('alimtalk_recipients')->where('dispatch_id', $id)->whereIn('status', ['scheduled', 'cancel_pending', 'cancel_unknown'])->exists()) {
                $this->invalid('외부 예약이 남아 있습니다. 먼저 예약 취소와 제공사 기록 확인이 필요합니다.');
            }
            DB::table('alimtalk_recipients')->where('dispatch_id', $id)->where('status', 'processing')
                ->update(['status' => 'unknown', 'reason' => '운영자가 중단을 확인했습니다. 제공사 기록 확인이 필요합니다.', 'updated_at' => now()]);
            DB::table('alimtalk_recipients')->where('dispatch_id', $id)->where('status', 'pending')
                ->update(['status' => 'skipped', 'reason' => '운영자가 미발송 대상을 종료했습니다.', 'updated_at' => now()]);
            DB::table('alimtalk_recipients')->where('dispatch_id', $id)->whereIn('status', ['unknown', 'skipped'])
                ->orderBy('id')->chunkById(100, function ($rows) use ($dispatch) {
                    $results = [];
                    foreach ($rows as $row) {
                        $results[$row->phone] = ['status' => $row->status, 'reason' => $row->reason];
                    }
                    $this->projectResults($dispatch, $results);
                });
            $this->release($dispatch, 'closed');
        });
    }

    private function recordBatch(object $dispatch, $recipients, array $results): void
    {
        DB::transaction(function () use ($dispatch, $recipients, $results) {
            $current = $this->lockedDispatch($dispatch->id);
            if (! in_array($current->status, ['queued', 'processing'], true)) {
                return;
            }
            foreach ($recipients as $recipient) {
                $result = $results[$recipient->phone] ?? ['status' => 'unknown', 'reason' => '접수 여부를 확인하지 못했습니다.'];
                $result['projection_order'] = $recipient->id;
                $results[$recipient->phone] = $result;
                $changed = DB::table('alimtalk_recipients')->where('id', $recipient->id)
                    ->whereIn('status', ['pending', 'processing'])->update([
                        'status' => $result['status'], 'reason' => $result['reason'] ?? null,
                        'provider_group_id' => $result['provider_group_id'] ?? null,
                        'finished_at' => now(), 'updated_at' => now(),
                    ]);
                if (! $changed) {
                    unset($results[$recipient->phone]);
                }
            }
            $this->projectResults($dispatch, $results);
            DB::table('alimtalk_dispatches')->where('id', $dispatch->id)->update(['updated_at' => now()]);
            $this->settleDispatch($dispatch);
        });
    }

    private function projectResults(object $dispatch, array $results): void
    {
        DB::table('alimtalk_dispatch_targets')->where('dispatch_id', $dispatch->id)->whereIn('phone', array_keys($results))
            ->orderBy('id')->chunkById(500, function ($targets) use ($dispatch, $results) {
                $rows = VideoSubmission::whereIn('id', $targets->pluck('submission_id'))->orderBy('id')->lockForUpdate()->get(self::SNAPSHOT_COLUMNS)->keyBy('id');
                foreach ($targets as $target) {
                    $row = $rows->get($target->submission_id);
                    if (! $row || $this->identity($row) !== $target->identity_hash) {
                        continue;
                    }
                    $result = $results[$target->phone];
                    $bundle = $row->alimtalk_results ?? [];
                    if (($bundle[$dispatch->kind]['projection_order'] ?? 0) > ($result['projection_order'] ?? 0)) {
                        continue;
                    }
                    $payload = [
                        'status' => $result['status'], 'reason' => $result['reason'] ?? null,
                        'sent_at' => now()->toDateTimeString(), 'dispatch_id' => $dispatch->id,
                        'projection_order' => $result['projection_order'] ?? 0,
                        'provider_status_code' => $result['provider_status_code'] ?? null,
                        'delivery_channel' => $result['delivery_channel'] ?? null,
                        'result_checked_at' => $result['result_checked_at'] ?? null,
                    ];
                    $bundle = $row->alimtalk_results ?? [];
                    $bundle[$dispatch->kind] = $payload;
                    $row->forceFill([
                        'alimtalk_results' => $bundle, 'alimtalk_status' => $payload['status'],
                        'alimtalk_status_reason' => $payload['reason'] === null ? null : Str::substr($payload['reason'], 0, 255),
                        'alimtalk_sent_at' => now(),
                    ])->save();
                }
            });
    }

    private function unchanged(object $dispatch): bool
    {
        if ($dispatch->scope === 'all' && VideoSubmission::count() !== $dispatch->selected_count) {
            return false;
        }
        $unchanged = true;
        DB::table('alimtalk_dispatch_targets')->where('dispatch_id', $dispatch->id)->orderBy('id')
            ->chunkById(500, function ($targets) use (&$unchanged, $dispatch) {
                $query = VideoSubmission::whereIn('id', $targets->pluck('submission_id'));
                if ($dispatch->scope === 'failed' && count($this->matchingApplicationIds($query, $dispatch->kind, 'failed')) !== $targets->count()) {
                    $unchanged = false;

                    return false;
                }
                $rows = $query->get(self::SNAPSHOT_COLUMNS)->keyBy('id');
                foreach ($targets as $target) {
                    if (! $rows->has($target->submission_id) || $this->identity($rows[$target->submission_id]) !== $target->identity_hash) {
                        $unchanged = false;

                        return false;
                    }
                }
            });

        return $unchanged;
    }

    private function identity(VideoSubmission $row): string
    {
        return hash('sha256', json_encode([
            $row->id, (string) $row->created_at, $row->student_name_korean,
            $row->parent_name, $this->alimtalk->normalizedAdminPhone($row->parent_phone),
        ], JSON_THROW_ON_ERROR));
    }

    private function ownedLocked(string $id, int $adminId): object
    {
        $dispatch = $this->lockedDispatch($id);
        abort_unless($dispatch, 404);
        abort_unless((int) $dispatch->admin_id === $adminId, 403);
        abort_if($dispatch->deleted_at, 404);

        return $dispatch;
    }

    private function lockedDispatch(string $id): ?object
    {
        $dispatch = DB::table('alimtalk_dispatches')->find($id);
        if (! $dispatch) {
            return null;
        }
        // Every writer acquires kind -> dispatch -> recipients/submissions in that order.
        DB::table('alimtalk_dispatch_locks')->where('kind', $dispatch->kind)->lockForUpdate()->first();

        return DB::table('alimtalk_dispatches')->where('id', $id)->lockForUpdate()->first();
    }

    private function release(object $dispatch, string $status): void
    {
        DB::table('alimtalk_dispatches')->where('id', $dispatch->id)->update(['status' => $status, 'updated_at' => now()]);
        DB::table('alimtalk_dispatch_locks')->where('kind', $dispatch->kind)->where('dispatch_id', $dispatch->id)->update(['dispatch_id' => null]);
    }

    private function assertQueue(): void
    {
        if (config('queue.connections.database.driver') !== 'database'
            || (config('queue.connections.database.connection') ?: config('database.default')) !== config('database.default')
            || (int) config('queue.connections.database.retry_after') <= 65) {
            $this->invalid('알림톡용 데이터베이스 대기열 설정을 확인해주세요. 같은 DB 연결과 65초를 넘는 재처리 대기 시간이 필요합니다.');
        }
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['alimtalk' => $message]);
    }
}
