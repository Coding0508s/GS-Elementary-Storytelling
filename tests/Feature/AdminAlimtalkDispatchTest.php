<?php

namespace Tests\Feature;

use App\Jobs\BatchAiEvaluationJob;
use App\Jobs\SendAdminAlimtalkBatch;
use App\Models\Admin;
use App\Models\VideoSubmission;
use App\Services\AdminAlimtalkDispatchService;
use App\Services\SolapiAlimtalkService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminAlimtalkDispatchTest extends TestCase
{
    use \Tests\Concerns\CreatesSeminarSchema;

    private Admin $admin;

    private FakeAdminAlimtalk $sender;

    private AdminAlimtalkDispatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSeminarSchema();
        Schema::table('video_submissions', function (Blueprint $table) {
            $table->json('alimtalk_results')->nullable();
            $table->string('alimtalk_status')->nullable();
            $table->text('alimtalk_status_reason')->nullable();
            $table->timestamp('alimtalk_sent_at')->nullable();
        });
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_11_03_010000_create_admin_alimtalk_dispatch_tables.php'))->up();
        (require database_path('migrations/2026_11_03_020000_add_scheduling_to_alimtalk_dispatches.php'))->up();
        (require database_path('migrations/2026_11_03_030000_add_change_management_to_alimtalk_dispatches.php'))->up();
        (require database_path('migrations/2026_11_03_040000_add_delivery_results_to_alimtalk_recipients.php'))->up();
        (require database_path('migrations/0001_01_01_000002_create_jobs_table.php'))->up();
        config([
            'session.driver' => 'array',
            'services.solapi.api_key' => 'fake-key', 'services.solapi.api_secret' => 'fake-secret',
            'services.solapi.from_number' => '15449055', 'services.solapi.kakao_pf_id' => 'fake-pf',
            'services.solapi.kakao_template_morning' => 'fake-morning', 'services.solapi.kakao_template_entry' => 'fake-entry',
            'queue.connections.database.connection' => null, 'queue.connections.database.retry_after' => 90,
        ]);
        $this->admin = Admin::create([
            'username' => 'sender', 'password' => 'secret', 'name' => '관리자',
            'email' => 'sender@example.com', 'role' => 'admin', 'is_active' => true,
        ]);
        $this->actingAs($this->admin, 'admin');
        $this->sender = new FakeAdminAlimtalk;
        $this->app->instance(SolapiAlimtalkService::class, $this->sender);
        $this->service = app(AdminAlimtalkDispatchService::class);
    }

    public function test_all_preview_ignores_search_and_pages_and_excludes_trash_and_duplicate_numbers(): void
    {
        for ($i = 0; $i < 21; $i++) {
            $this->submission(sprintf('010%08d', $i));
        }
        $this->submission('82-10-0000-0000');
        $this->submission('02-123-4567');
        $this->submission('01099999999')->delete();
        $this->post(route('admin.applications.alimtalk'), [
            'kind' => 'morning', 'scope' => 'all', 'search' => '검색결과없음', 'request_token' => (string) Str::uuid(),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $dispatch = DB::table('alimtalk_dispatches')->first();
        $this->assertSame(23, $dispatch->selected_count);
        $this->assertSame(1, $dispatch->invalid_count);
        $this->assertSame(21, DB::table('alimtalk_recipients')->count());
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame([], $this->sender->calls);
        $this->get(route('admin.applications', ['search' => '검색결과없음']))->assertOk()
            ->assertSee('등록자 전체 23명 선택')->assertSee('21개 번호에 발송 확정');
    }

    public function test_confirmation_uses_real_database_queue_and_duplicate_submission_does_not_enqueue_again(): void
    {
        $row = $this->submission('010-1234-5678');
        $sibling = $this->submission('821012345678');
        $dispatch = $this->preview();
        $url = route('admin.applications.alimtalk.confirm', $dispatch->id);
        $this->post($url)->assertRedirect()->assertSessionHasNoErrors();
        $this->post($url)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame([], $this->sender->calls);
        $this->runQueue();
        $this->assertSame([['01012345678']], $this->sender->calls);
        $this->assertSame('accepted', $row->fresh()->alimtalkResult('morning')['status']);
        $this->assertSame('accepted', $sibling->fresh()->alimtalkResult('morning')['status']);
        $this->assertSame('completed', DB::table('alimtalk_dispatches')->find($dispatch->id)->status);
        $this->assertNull(DB::table('alimtalk_dispatch_locks')->where('kind', 'morning')->value('dispatch_id'));
    }

    public function test_selected_scope_limits_recipients_and_repeated_preview_is_idempotent(): void
    {
        $a = $this->submission();
        $this->submission('01022222222');
        $token = (string) Str::uuid();
        $first = $this->service->preview($this->admin->id, $token, 'morning', 'selected', [$a->id], null);
        $again = $this->service->preview($this->admin->id, $token, 'morning', 'selected', [$a->id], null);
        $this->assertSame($first->id, $again->id);
        $this->assertSame(1, DB::table('alimtalk_dispatch_targets')->count());
    }

    public function test_same_kind_is_blocked_but_different_kind_can_run_and_results_are_preserved(): void
    {
        $row = $this->submission();
        $morning = $this->preview();
        try {
            $this->preview();
            $this->fail('Second preview allowed');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('같은 종류', $e->getMessage());
        }
        $entry = $this->preview('entry');
        $this->service->confirm($morning->id, $this->admin->id, false);
        $this->service->confirm($entry->id, $this->admin->id, false);
        $this->runQueue();
        $this->assertSame('accepted', $row->fresh()->alimtalkResult('morning')['status']);
        $this->assertSame('accepted', $row->fresh()->alimtalkResult('entry')['status']);
    }

    public function test_expiry_and_cancel_release_only_their_own_kind(): void
    {
        $this->submission();
        $a = $this->preview();
        $entry = $this->preview('entry');
        $this->travel(16)->minutes();
        $b = $this->preview();
        $this->assertSame('expired', DB::table('alimtalk_dispatches')->find($a->id)->status);
        $this->post(route('admin.applications.alimtalk.cancel', $b->id))->assertRedirect();
        $this->assertNull(DB::table('alimtalk_dispatch_locks')->where('kind', 'morning')->value('dispatch_id'));
        $this->assertSame($entry->id, DB::table('alimtalk_dispatch_locks')->where('kind', 'entry')->value('dispatch_id'));
        $this->post(route('admin.applications.alimtalk.confirm', $a->id))->assertSessionHasErrors();
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_changed_number_with_same_count_requires_new_confirmation(): void
    {
        $row = $this->submission();
        $dispatch = $this->preview();
        $row->update(['parent_phone' => '01022222222']);
        $this->post(route('admin.applications.alimtalk.confirm', $dispatch->id))->assertSessionHasErrors();
        $this->assertSame('cancelled', DB::table('alimtalk_dispatches')->find($dispatch->id)->status);
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_new_registration_before_confirmation_requires_reconfirmation(): void
    {
        $this->submission();
        $dispatch = $this->preview();
        $this->submission('01022222222');
        $this->post(route('admin.applications.alimtalk.confirm', $dispatch->id))->assertSessionHasErrors();
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_confirmed_snapshot_excludes_newcomers_deleted_and_changed_recipients(): void
    {
        $kept = $this->submission();
        $deleted = $this->submission('01022222222');
        $changed = $this->submission('01033333333');
        $dispatch = $this->preview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $deleted->delete();
        $changed->update(['parent_phone' => '01044444444']);
        $this->submission('01055555555');
        $this->runQueue();
        $this->assertSame([[$kept->parent_phone]], $this->sender->calls);
        $this->assertSame(2, DB::table('alimtalk_recipients')->where('status', 'skipped')->count());
    }

    public function test_101_numbers_create_two_jobs_and_all_results_are_saved(): void
    {
        for ($i = 0; $i < 101; $i++) {
            $this->submission(sprintf('010%08d', $i));
        }
        $dispatch = $this->preview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->assertSame(2, DB::table('jobs')->count());
        $this->runQueue();
        $this->assertSame([100, 1], array_map('count', $this->sender->calls));
        $this->assertSame(101, DB::table('alimtalk_recipients')->where('status', 'accepted')->count());
    }

    public function test_large_snapshot_crosses_database_chunk_boundaries_without_missing_recipients(): void
    {
        foreach (array_chunk(range(1, 1001), 500) as $numbers) {
            VideoSubmission::insert(array_map(fn ($i) => [
                'region' => '서울', 'institution_name' => '기관', 'student_name_korean' => '학생 '.$i,
                'grade' => '만 3세', 'parent_name' => '학부모', 'parent_phone' => sprintf('010%08d', $i),
                'created_at' => now(), 'updated_at' => now(),
            ], $numbers));
        }
        $dispatch = $this->preview();
        $this->assertSame(1001, $dispatch->selected_count);
        $this->assertSame(1001, DB::table('alimtalk_dispatch_targets')->count());
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->assertSame(11, DB::table('jobs')->count());
        $this->runQueue();
        $this->assertSame(1001, DB::table('alimtalk_recipients')->where('status', 'accepted')->count());
        $this->assertSame(1001, array_sum(array_map('count', $this->sender->calls)));
    }

    public function test_unknown_result_blocks_new_dispatch_and_is_never_automatically_retried(): void
    {
        $row = $this->submission();
        $dispatch = $this->preview();
        $this->sender->outcome = 'unknown';
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $ids = DB::table('alimtalk_recipients')->pluck('id')->all();
        $this->service->processBatch($dispatch->id, $ids);
        $this->assertCount(1, $this->sender->calls);
        $this->assertSame('needs_review', DB::table('alimtalk_dispatches')->find($dispatch->id)->status);
        $this->assertSame('unknown', $row->fresh()->alimtalkResult('morning')['status']);
        $this->expectException(ValidationException::class);
        $this->preview();
    }

    public function test_failed_job_preserves_uncertainty_and_redelivery_does_not_resend_claimed_numbers(): void
    {
        $this->submission();
        $dispatch = $this->preview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $ids = DB::table('alimtalk_recipients')->pluck('id')->all();
        DB::table('alimtalk_recipients')->update(['status' => 'processing', 'started_at' => now()]);
        $this->service->processBatch($dispatch->id, $ids);
        $this->assertSame([], $this->sender->calls);
        (new SendAdminAlimtalkBatch($dispatch->id, $ids))->failed(new \RuntimeException('worker stopped'));
        $this->assertSame('unknown', DB::table('alimtalk_recipients')->value('status'));
        $this->assertSame('needs_review', DB::table('alimtalk_dispatches')->value('status'));
    }

    public function test_queue_failure_rolls_back_confirmation_and_all_enqueued_jobs(): void
    {
        for ($i = 0; $i < 101; $i++) {
            $this->submission(sprintf('010%08d', $i));
        }
        $dispatch = $this->preview();
        config(['queue.connections.database.after_commit' => true]);
        $manager = Queue::getFacadeRoot();
        $calls = 0;
        Queue::shouldReceive('connection')->andReturnUsing(function ($name) use ($manager, &$calls) {
            if (++$calls === 2) {
                $this->assertSame(1, DB::table('jobs')->count());
                throw new \RuntimeException('queue unavailable');
            }

            return $manager->connection($name);
        });
        try {
            $this->service->confirm($dispatch->id, $this->admin->id, false);
            $this->fail('Queue failure swallowed');
        } catch (\RuntimeException $e) {
            $this->assertSame('queue unavailable', $e->getMessage());
        }
        $this->assertSame('preview', DB::table('alimtalk_dispatches')->value('status'));
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_dispatch_migration_rolls_back_without_removing_registrations(): void
    {
        $this->submission();
        $migration = require database_path('migrations/2026_11_03_010000_create_admin_alimtalk_dispatch_tables.php');
        $migration->down();
        $this->assertSame(1, VideoSubmission::count());
        $this->assertFalse(Schema::hasTable('alimtalk_dispatches'));
        $migration->up();
        $this->assertSame(2, DB::table('alimtalk_dispatch_locks')->count());
    }

    public function test_result_storage_failure_after_provider_acceptance_never_causes_resend(): void
    {
        $row = $this->submission();
        $dispatch = $this->preview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $failOnce = true;
        VideoSubmission::saving(function ($submission) use (&$failOnce) {
            if ($failOnce && ($submission->alimtalkResult('morning')['status'] ?? '') === 'accepted') {
                $failOnce = false;
                throw new \RuntimeException('simulated result storage failure');
            }
        });
        $this->runQueue();
        $this->assertCount(1, $this->sender->calls);
        $this->assertSame('unknown', DB::table('alimtalk_recipients')->value('status'));
        $this->assertSame('unknown', $row->fresh()->alimtalkResult('morning')['status']);
        $this->assertSame('needs_review', DB::table('alimtalk_dispatches')->value('status'));
        $this->runQueue();
        $this->assertCount(1, $this->sender->calls);
    }

    public function test_changed_configuration_requires_reconfirmation_or_skips_unsent_work(): void
    {
        $this->submission();
        $preview = $this->preview();
        config(['services.solapi.kakao_template_morning' => 'changed']);
        $this->post(route('admin.applications.alimtalk.confirm', $preview->id))->assertSessionHasErrors();
        $this->assertSame('cancelled', DB::table('alimtalk_dispatches')->find($preview->id)->status);
        $dispatch = $this->preview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        config(['services.solapi.api_key' => '']);
        $this->runQueue();
        $this->assertSame([], $this->sender->calls);
        $this->assertSame('skipped', DB::table('alimtalk_recipients')->where('dispatch_id', $dispatch->id)->value('status'));
    }

    public function test_invalid_entry_link_shows_a_specific_error_without_creating_a_dispatch(): void
    {
        $this->submission();
        $this->post(route('admin.applications.alimtalk'), [
            'kind' => 'entry', 'scope' => 'all', 'zoom_url' => 'https://example.com/not-youtube',
        ])->assertSessionHasErrors('alimtalk');
        $this->assertStringContainsString('유튜브 링크만', session('errors')->first('alimtalk'));
        $this->assertSame(0, DB::table('alimtalk_dispatches')->count());
    }

    public function test_long_rejection_reason_is_preserved_without_overflowing_legacy_column(): void
    {
        $row = $this->submission();
        $dispatch = $this->preview();
        $this->sender->outcome = 'failed';
        $this->sender->reason = str_repeat('거절 사유 ', 100);
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $this->assertSame($this->sender->reason, DB::table('alimtalk_recipients')->value('reason'));
        $this->assertSame(255, mb_strlen($row->fresh()->alimtalk_status_reason));
        $this->assertSame('failed', $row->fresh()->alimtalkResult('morning')['status']);
    }

    public function test_resend_requires_explicit_confirmation_and_does_not_silently_skip_previous_recipients(): void
    {
        $row = $this->submission();
        $row->update(['alimtalk_results' => ['morning' => ['status' => 'sent']]]);
        $dispatch = $this->preview();
        $this->assertSame(1, $dispatch->previous_count);
        $url = route('admin.applications.alimtalk.confirm', $dispatch->id);
        $this->post($url)->assertSessionHasErrors();
        $this->post($url, ['confirm_resend' => '1'])->assertRedirect()->assertSessionHasNoErrors();
        $this->runQueue();
        $this->assertCount(1, $this->sender->calls);
    }

    public function test_other_admin_cannot_confirm_cancel_or_reuse_preview_token(): void
    {
        $this->submission();
        $dispatch = $this->preview();
        $other = Admin::create(['username' => 'other', 'password' => 'secret', 'name' => '다른 관리자', 'email' => 'other@example.com', 'role' => 'admin']);
        $this->actingAs($other, 'admin');
        $this->post(route('admin.applications.alimtalk.confirm', $dispatch->id))->assertForbidden();
        $this->post(route('admin.applications.alimtalk.cancel', $dispatch->id))->assertForbidden();
        $this->post(route('admin.applications.alimtalk'), ['kind' => 'morning', 'scope' => 'all', 'request_token' => $dispatch->request_token])->assertForbidden();
    }

    public function test_judge_cannot_preview_confirm_or_view_progress(): void
    {
        $this->admin->update(['role' => 'judge']);
        $this->get(route('admin.applications.alimtalk.progress'))->assertForbidden();
        $this->post(route('admin.applications.alimtalk'), ['kind' => 'morning', 'scope' => 'all'])->assertForbidden();
        $this->post(route('admin.applications.alimtalk.confirm', Str::uuid()))->assertForbidden();
    }

    public function test_expired_admin_session_redirects_to_admin_login_and_progress_returns_unauthorized(): void
    {
        auth('admin')->logout();
        $this->get(route('admin.applications'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.applications.alimtalk'), ['kind' => 'morning', 'scope' => 'all'])
            ->assertRedirect(route('admin.login'));
        $this->getJson(route('admin.applications.alimtalk.progress'))->assertUnauthorized();
    }

    public function test_empty_invalid_selection_and_unsafe_queue_configuration_do_not_create_jobs(): void
    {
        $this->post(route('admin.applications.alimtalk'), ['kind' => 'morning', 'ids' => 'bad'])->assertSessionHasErrors();
        $this->post(route('admin.applications.alimtalk'), ['kind' => 'morning', 'scope' => 'everything'])->assertSessionHasErrors();
        $this->post(route('admin.applications.alimtalk'), ['kind' => 'morning', 'scope' => 'all'])->assertSessionHasErrors();
        $this->submission('invalid');
        $this->post(route('admin.applications.alimtalk'), ['kind' => 'morning', 'scope' => 'all'])->assertSessionHasErrors();
        $this->submission();
        config(['queue.connections.database.retry_after' => 30]);
        $this->post(route('admin.applications.alimtalk'), ['kind' => 'morning', 'scope' => 'all'])->assertSessionHasErrors();
        $this->assertSame(0, DB::table('alimtalk_dispatches')->count());
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_progress_reports_stalled_work_and_recovery_requires_operator_confirmation(): void
    {
        $this->submission();
        $dispatch = $this->preview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->travel(3)->minutes();
        $this->getJson(route('admin.applications.alimtalk.progress'))->assertOk()->assertJsonPath('0.stalled', true);
        $this->assertSame(1, Artisan::call('alimtalk:close', ['dispatch' => $dispatch->id]));
        $this->assertSame('queued', DB::table('alimtalk_dispatches')->value('status'));
        $this->assertSame(0, Artisan::call('alimtalk:close', ['dispatch' => $dispatch->id, '--confirmed-stopped' => true]));
        $this->runQueue();
        $this->assertSame([], $this->sender->calls);
        $this->assertSame('closed', DB::table('alimtalk_dispatches')->value('status'));
    }

    public function test_mysql_concurrent_preview_confirmation_and_different_kind_result_merges(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Requires an isolated MySQL test DB and pcntl for real concurrent connections.');
        }
        $row = $this->submission();
        $outcomes = $this->race(function () {
            try {
                return ['created', $this->preview()->id];
            } catch (ValidationException $e) {
                return ['blocked'];
            }
        });
        $this->assertSame(['blocked', 'created'], collect($outcomes)->pluck(0)->sort()->values()->all());
        $this->assertSame(1, DB::table('alimtalk_dispatches')->count());
        $morningId = DB::table('alimtalk_dispatches')->value('id');
        $this->race(function () use ($morningId) {
            $this->service->confirm($morningId, $this->admin->id, false);

            return ['confirmed'];
        });
        $this->assertSame(1, DB::table('jobs')->count());
        $entry = $this->preview('entry');
        $this->service->confirm($entry->id, $this->admin->id, false);
        $dispatches = [$morningId, $entry->id];
        $this->race(function ($index) use ($dispatches) {
            $id = $dispatches[$index];
            $ids = DB::table('alimtalk_recipients')->where('dispatch_id', $id)->pluck('id')->all();
            $this->service->processBatch($id, $ids);

            return ['processed'];
        });
        $this->assertSame('accepted', $row->fresh()->alimtalkResult('morning')['status']);
        $this->assertSame('accepted', $row->fresh()->alimtalkResult('entry')['status']);
        $this->assertSame(2, DB::table('alimtalk_recipients')->where('status', 'accepted')->count());
        $this->runQueue(); // Re-delivered queued jobs must not send those recipients again.
        $this->assertSame([], $this->sender->calls);
    }

    private function race(\Closure $action): array
    {
        $gate = tempnam(sys_get_temp_dir(), 'alimtalk-gate-');
        unlink($gate);
        $files = [tempnam(sys_get_temp_dir(), 'alimtalk-child-'), tempnam(sys_get_temp_dir(), 'alimtalk-child-')];
        DB::disconnect(); // Never share a live PDO socket across fork().
        $children = [];
        foreach ([0, 1] as $index) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                $this->fail('Unable to fork test process');
            }
            if ($pid === 0) {
                try {
                    DB::purge();
                    $deadline = microtime(true) + 5;
                    while (! is_file($gate)) {
                        if (microtime(true) > $deadline) {
                            throw new \RuntimeException('Barrier timeout');
                        }
                        usleep(1000);
                    }
                    $result = $action($index);
                } catch (\Throwable $e) {
                    $result = ['error', get_class($e), $e->getMessage()];
                }
                file_put_contents($files[$index], json_encode($result, JSON_THROW_ON_ERROR));
                DB::disconnect();
                exit(0);
            }
            $children[] = $pid;
        }
        touch($gate);
        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
            $this->assertSame(0, pcntl_wexitstatus($status));
        }
        DB::purge();
        $results = array_map(fn ($file) => json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR), $files);
        foreach (array_merge([$gate], $files) as $file) {
            unlink($file);
        }
        foreach ($results as $result) {
            $this->assertNotSame('error', $result[0], json_encode($result));
        }

        return $results;
    }

    public function test_scheduled_http_flow_registers_now_with_provider_time_and_can_cancel(): void
    {
        $row = $this->submission();
        $aiJobId = Queue::connection('database')->push(new BatchAiEvaluationJob(0, $this->admin->id), '', 'default');
        $when = now()->addHour()->format('Y-m-d\TH:i');
        $this->post(route('admin.applications.alimtalk'), ['kind' => 'morning', 'scope' => 'all', 'delivery_mode' => 'scheduled', 'scheduled_at' => $when])
            ->assertRedirect()->assertSessionHasNoErrors();
        $dispatch = DB::table('alimtalk_dispatches')->first();
        $this->assertNotNull($dispatch->scheduled_at);
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $this->assertSame('scheduled', DB::table('alimtalk_dispatches')->value('status'));
        $this->assertSame('scheduled', $row->fresh()->alimtalkResult('morning')['status']);
        $this->assertSame($dispatch->scheduled_at, $this->sender->scheduledTimes[0]);
        $this->assertNull(DB::table('alimtalk_dispatch_locks')->where('kind', 'morning')->value('dispatch_id'));
        $this->get(route('admin.applications'))->assertOk()->assertSee('예약 접수 완료')->assertSee('한국 시간');
        $url = route('admin.applications.alimtalk.schedule', $dispatch->id);
        $this->post($url, ['action' => 'cancel'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('cancelling', DB::table('alimtalk_dispatches')->value('status'));
        $this->runQueue();
        $this->assertSame('cancelled', DB::table('alimtalk_dispatches')->value('status'));
        $this->assertSame('cancelled', $row->fresh()->alimtalkResult('morning')['status']);
        $this->assertDatabaseHas('jobs', ['id' => $aiJobId, 'queue' => 'default', 'attempts' => 0, 'reserved_at' => null]);
    }

    public function test_schedule_invalid_past_and_missing_dates_never_create_dispatches(): void
    {
        $this->submission();
        foreach (['', 'not-a-date', now()->subMinute()->format('Y-m-d\TH:i'), now()->addMinute()->format('Y-m-d\TH:i')] as $date) {
            $this->post(route('admin.applications.alimtalk'), ['kind' => 'morning', 'scope' => 'all', 'delivery_mode' => 'scheduled', 'scheduled_at' => $date])
                ->assertSessionHasErrors();
        }
        $this->assertSame(0, DB::table('alimtalk_dispatches')->count());
    }

    public function test_late_worker_never_converts_reservation_to_immediate_send(): void
    {
        $this->submission();
        $dispatch = $this->scheduledPreview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->travel(2)->hours();
        $this->runQueue();
        $this->assertSame([], $this->sender->calls);
        $this->assertSame('skipped', DB::table('alimtalk_recipients')->value('status'));
    }

    public function test_uncertain_cancellation_is_not_success_and_refresh_can_resolve_it(): void
    {
        $this->submission();
        $dispatch = $this->scheduledPreview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $this->sender->managementResult = ['status' => 'cancel_unknown', 'reason' => 'connection lost'];
        $this->service->requestScheduleManagement($dispatch->id, $this->admin->id, true);
        $this->runQueue();
        $this->assertSame('needs_review', DB::table('alimtalk_dispatches')->value('status'));
        $this->assertSame('cancel_unknown', DB::table('alimtalk_recipients')->value('status'));
        $this->sender->managementResult = ['status' => 'unavailable', 'reason' => 'still unavailable'];
        $this->service->requestScheduleManagement($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $this->assertSame('cancel_unknown', DB::table('alimtalk_recipients')->value('status'));
        $this->sender->managementResult = ['status' => 'cancelled', 'reason' => null];
        $this->service->requestScheduleManagement($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $this->assertSame('cancelled', DB::table('alimtalk_dispatches')->value('status'));
    }

    public function test_old_reservation_cancellation_does_not_overwrite_a_newer_immediate_result(): void
    {
        $row = $this->submission();
        $reserved = $this->scheduledPreview();
        $this->service->confirm($reserved->id, $this->admin->id, false);
        $this->runQueue();
        $immediate = $this->preview();
        $this->service->confirm($immediate->id, $this->admin->id, true);
        $this->runQueue();
        $this->service->requestScheduleManagement($reserved->id, $this->admin->id, true);
        $this->runQueue();
        $this->assertSame($immediate->id, $row->fresh()->alimtalkResult('morning')['dispatch_id']);
        $this->assertSame('accepted', $row->fresh()->alimtalkResult('morning')['status']);
    }

    public function test_same_kind_and_time_duplicate_is_blocked_but_other_times_are_allowed(): void
    {
        $this->submission();
        $a = $this->scheduledPreview();
        $this->service->confirm($a->id, $this->admin->id, false);
        $this->runQueue();
        try {
            $this->scheduledPreview();
            $this->fail('Duplicate reservation allowed');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('같은 종류와 시간', $e->getMessage());
        }
        $later = $this->service->preview($this->admin->id, (string) Str::uuid(), 'morning', 'all', [], null, now()->addHours(2)->format('Y-m-d\TH:i'));
        $this->assertSame('preview', $later->status);
    }

    private function scheduledPreview(): object
    {
        return $this->service->preview($this->admin->id, (string) Str::uuid(), 'morning', 'all', [], null, now()->addHour()->format('Y-m-d\TH:i'));
    }

    public function test_result_badge_shows_queued_reservation_instead_of_saved_acceptance_for_each_selected_person(): void
    {
        $a = $this->submission();
        $b = $this->submission();
        foreach ([$a, $b] as $row) {
            $row->update(['alimtalk_results' => ['morning' => ['status' => 'accepted']]]);
        }
        $dispatch = $this->scheduledPreview();
        $this->service->confirm($dispatch->id, $this->admin->id, true);
        $results = $this->service->applicationResults(VideoSubmission::all());
        foreach ([$a, $b] as $row) {
            $this->assertSame('예약 중', $results[$row->id]['morning']['label']);
            $this->assertStringContainsString('예약 등록 대기', $results[$row->id]['morning']['title']);
            $this->assertSame('accepted', $row->fresh()->alimtalkResult('morning')['status']); // Presentation must not rewrite actual delivery records.
        }
        $this->assertSame([], $this->sender->calls);
        $this->get(route('admin.applications'))->assertOk()->assertSee('예약 중');
        $this->getJson(route('admin.applications.alimtalk.results', ['ids' => [$a->id, $b->id]]))
            ->assertOk()->assertJsonPath($a->id.'.morning.label', '예약 중')->assertJsonPath($b->id.'.morning.label', '예약 중');
    }

    public function test_unconfirmed_or_deleted_work_does_not_override_actual_result_badges(): void
    {
        $row = $this->submission();
        $row->update(['alimtalk_results' => ['morning' => ['status' => 'sent']]]);
        $dispatch = $this->scheduledPreview();
        $this->assertSame('요청 접수', $this->service->applicationResults(VideoSubmission::all())[$row->id]['morning']['label']);
        $this->service->confirm($dispatch->id, $this->admin->id, true);
        $this->service->deleteDispatch($dispatch->id, $this->admin->id, 1);
        $this->assertSame('요청 접수', $this->service->applicationResults(VideoSubmission::all())[$row->id]['morning']['label']);
        $this->assertSame([], $this->sender->calls);
    }

    public function test_active_reservation_badge_survives_newer_immediate_request_and_changes_after_cancel(): void
    {
        $row = $this->submission();
        $reservation = $this->scheduledPreview();
        $this->service->confirm($reservation->id, $this->admin->id, false);
        $this->runQueue();
        $immediate = $this->preview();
        $this->service->confirm($immediate->id, $this->admin->id, true);
        $this->runQueue();
        $results = $this->service->applicationResults(VideoSubmission::all());
        $this->assertSame('예약 중', $results[$row->id]['morning']['label']);
        $this->assertStringContainsString('SOLAPI 예약 접수 완료', $results[$row->id]['morning']['title']);
        $this->service->requestScheduleManagement($reservation->id, $this->admin->id, true);
        $this->assertSame('취소 확인 중', $this->service->applicationResults(VideoSubmission::all())[$row->id]['morning']['label']);
        $this->runQueue();
        $this->assertSame('요청 접수', $this->service->applicationResults(VideoSubmission::all())[$row->id]['morning']['label']);
    }

    public function test_reservation_badges_do_not_apply_to_unselected_people_with_same_phone_or_hide_uncertainty(): void
    {
        $a = $this->submission();
        $b = $this->submission();
        $dispatch = $this->service->preview($this->admin->id, (string) Str::uuid(), 'morning', 'selected', [$a->id], null, now()->addHour()->format('Y-m-d\TH:i'));
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $results = $this->service->applicationResults(VideoSubmission::all());
        $this->assertSame('예약 중', $results[$a->id]['morning']['label']);
        $this->assertSame('미발송', $results[$b->id]['morning']['label']);
        DB::table('alimtalk_recipients')->where('dispatch_id', $dispatch->id)->update(['status' => 'unknown']);
        $this->assertSame('확인 필요', $this->service->applicationResults(VideoSubmission::all())[$a->id]['morning']['label']);
    }

    public function test_result_refresh_validates_page_ids_and_admin_access(): void
    {
        $url = route('admin.applications.alimtalk.results');
        $this->getJson($url)->assertUnprocessable();
        $this->getJson($url.'?ids=bad')->assertUnprocessable();
        $this->getJson($url.'?'.http_build_query(['ids' => range(1, 21)]))->assertUnprocessable();
        $this->admin->update(['role' => 'judge']);
        $this->getJson($url.'?ids[]=1')->assertForbidden();
        auth('admin')->logout();
        $this->getJson($url.'?ids[]=1')->assertUnauthorized();
    }

    public function test_final_delivery_updates_siblings_and_history_without_sending_again(): void
    {
        $a = $this->submission();
        $b = $this->submission();
        $this->submission('01099999999');
        $dispatch = $this->preview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        DB::table('alimtalk_recipients')->update(['provider_group_id' => 'G-delivery']);
        $calls = $this->sender->calls;
        $this->sender->deliveryResults = [
            '01012345678' => ['status' => 'delivered', 'provider_status_code' => '4000', 'delivery_channel' => 'ATA'],
            '01099999999' => ['status' => 'delivery_failed', 'provider_status_code' => '3043', 'reason' => '단말기 꺼짐'],
        ];
        $this->postJson(route('admin.applications.alimtalk.results.sync'))->assertOk()->assertJsonPath('checked', 1);
        foreach ([$a, $b] as $row) {
            $this->assertSame('delivered', $row->fresh()->alimtalkResult('morning')['status']);
        }
        $this->getJson(route('admin.applications.alimtalk.results', ['ids' => [$a->id]]))
            ->assertJsonPath($a->id.'.morning.label', '전달 완료');
        $summary = $this->service->summary(DB::table('alimtalk_dispatches')->find($dispatch->id));
        $this->assertSame(1, $summary['counts']['delivered']);
        $this->assertSame(1, $summary['counts']['delivery_failed']);
        $this->assertFalse($summary['can_edit']);
        $this->assertFalse($summary['awaiting_results']);
        $this->assertSame($calls, $this->sender->calls);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, $this->service->syncDeliveryResults()['checked']);
        $next = $this->preview();
        $this->assertSame(3, $next->previous_count);
    }

    public function test_sync_failure_preserves_status_and_retries_after_cooldown(): void
    {
        $row = $this->submission();
        $dispatch = $this->preview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        DB::table('alimtalk_recipients')->update(['provider_group_id' => 'G-retry']);
        $this->sender->deliveryThrows = true;
        $this->assertSame(1, $this->service->syncDeliveryResults()['errors']);
        $this->assertSame('accepted', $row->fresh()->alimtalkResult('morning')['status']);
        $this->assertNotNull(DB::table('alimtalk_recipients')->value('result_error'));
        $this->assertSame(0, $this->service->syncDeliveryResults()['checked']);
        $this->travel(31)->seconds();
        $this->sender->deliveryThrows = false;
        $this->sender->deliveryResults = ['01012345678' => ['status' => 'delivered', 'delivery_channel' => 'SMS', 'provider_status_code' => '4000']];
        $this->assertSame(1, $this->service->syncDeliveryResults()['checked']);
        $this->assertNull(DB::table('alimtalk_recipients')->value('result_error'));
        $this->assertSame('문자 전달 완료', $this->service->applicationResults(VideoSubmission::all())[$row->id]['morning']['label']);
    }

    public function test_reservation_is_only_queried_after_due_time_and_cancel_in_flight_wins(): void
    {
        $row = $this->submission();
        $dispatch = $this->scheduledPreview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $this->assertSame(0, $this->service->syncDeliveryResults()['checked']);
        $this->assertSame([], $this->sender->deliveryCalls);
        $this->travel(2)->days();
        $this->sender->deliveryResults = ['01012345678' => ['status' => 'delivering', 'provider_status_code' => '3000']];
        $this->service->syncDeliveryResults();
        $this->assertSame('delivering', $row->fresh()->alimtalkResult('morning')['status']);
        $this->assertSame('발송 중', $this->service->applicationResults(VideoSubmission::all())[$row->id]['morning']['label']);
        $this->travel(31)->seconds();
        $this->sender->duringDeliveryLookup = function () {
            DB::table('alimtalk_recipients')->update(['status' => 'cancel_pending']);
        };
        $this->sender->deliveryResults = ['01012345678' => ['status' => 'delivered', 'provider_status_code' => '4000']];
        $this->service->syncDeliveryResults();
        $this->assertSame('cancel_pending', DB::table('alimtalk_recipients')->value('status'));
    }

    public function test_archived_delivery_updates_projection_without_overwriting_newer_send(): void
    {
        $row = $this->submission();
        $old = $this->preview();
        $this->service->confirm($old->id, $this->admin->id, false);
        $this->runQueue();
        DB::table('alimtalk_recipients')->update(['provider_group_id' => 'G-old']);
        $this->service->deleteDispatch($old->id, $this->admin->id, 1);
        $this->sender->deliveryResults = ['01012345678' => ['status' => 'delivered', 'provider_status_code' => '4000']];
        $this->service->syncDeliveryResults();
        $this->assertSame('delivered', $row->fresh()->alimtalkResult('morning')['status']);
        $new = $this->preview();
        $this->service->confirm($new->id, $this->admin->id, true);
        $this->runQueue();
        DB::table('alimtalk_recipients')->where('dispatch_id', $old->id)->update(['status' => 'accepted', 'result_checked_at' => null]);
        $this->service->syncDeliveryResults();
        $this->assertSame($new->id, $row->fresh()->alimtalkResult('morning')['dispatch_id']);
        $this->assertSame('accepted', $row->fresh()->alimtalkResult('morning')['status']);
    }

    public function test_result_sync_requires_admin_and_legacy_schema_stays_usable(): void
    {
        $url = route('admin.applications.alimtalk.results.sync');
        $this->admin->update(['role' => 'judge']);
        $this->postJson($url)->assertForbidden();
        auth('admin')->logout();
        $this->postJson($url)->assertUnauthorized();
        $this->admin->update(['role' => 'admin']);
        $this->actingAs($this->admin, 'admin');
        (require database_path('migrations/2026_11_03_040000_add_delivery_results_to_alimtalk_recipients.php'))->down();
        $this->postJson($url)->assertOk()->assertJsonPath('ready', false);
        $this->submission();
        $this->get(route('admin.applications'))->assertOk();
    }

    public function test_result_lookup_claim_prevents_nested_duplicate_query_and_missing_number_keeps_status(): void
    {
        $row = $this->submission();
        $dispatch = $this->preview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        DB::table('alimtalk_recipients')->update(['provider_group_id' => 'G-claim']);
        $this->sender->duringDeliveryLookup = function () {
            $this->assertSame(0, $this->service->syncDeliveryResults()['checked']);
        };
        $this->sender->deliveryResults = ['01099999999' => ['status' => 'delivered', 'provider_status_code' => '4000']];
        $this->assertSame(1, $this->service->syncDeliveryResults()['errors']);
        $this->assertSame(['G-claim'], $this->sender->deliveryCalls);
        $this->assertSame('accepted', $row->fresh()->alimtalkResult('morning')['status']);
        $this->assertStringContainsString('최종 결과를 조회하지 못했습니다', DB::table('alimtalk_recipients')->value('result_error'));
    }

    public function test_editing_queued_work_creates_a_new_preview_and_fences_out_old_jobs(): void
    {
        $this->submission();
        $old = $this->preview();
        $this->service->confirm($old->id, $this->admin->id, false);
        $when = now()->addHours(2)->format('Y-m-d\TH:i');
        $this->put(route('admin.applications.alimtalk.update', $old->id), [
            'revision' => 1, 'delivery_mode' => 'scheduled', 'scheduled_at' => $when,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull(DB::table('alimtalk_dispatches')->find($old->id)->deleted_at);
        $new = DB::table('alimtalk_dispatches')->whereNull('deleted_at')->first();
        $this->assertSame('preview', $new->status);
        $this->assertNotSame($old->id, $new->id);
        $this->runQueue();
        $this->assertSame([], $this->sender->calls);
        $this->service->confirm($new->id, $this->admin->id, false);
        $this->runQueue();
        $this->assertCount(1, $this->sender->scheduledTimes);
        $this->assertSame($new->scheduled_at, $this->sender->scheduledTimes[0]);
    }

    public function test_deleting_queued_work_cancels_it_and_hides_it_without_losing_audit_records(): void
    {
        $this->submission();
        $dispatch = $this->preview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->delete(route('admin.applications.alimtalk.destroy', $dispatch->id), ['revision' => 1])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->runQueue();
        $this->assertSame([], $this->sender->calls);
        $this->assertSame([], $this->service->recent());
        $this->assertSame(1, DB::table('alimtalk_dispatches')->count());
        $this->assertSame('cancelled', DB::table('alimtalk_dispatches')->value('status'));
        $this->assertSame(1, DB::table('alimtalk_dispatch_targets')->count());
    }

    public function test_completed_work_cannot_be_edited_but_its_history_can_be_deleted(): void
    {
        $this->submission();
        $dispatch = $this->preview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $this->put(route('admin.applications.alimtalk.update', $dispatch->id), ['revision' => 1, 'delivery_mode' => 'immediate'])->assertSessionHasErrors();
        $this->delete(route('admin.applications.alimtalk.destroy', $dispatch->id), ['revision' => 1])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([], $this->service->recent());
        $this->assertSame('accepted', DB::table('alimtalk_recipients')->value('status'));
        $this->assertCount(1, $this->sender->calls);
    }

    public function test_registered_reservation_edit_waits_for_cancellation_and_requires_new_confirmation(): void
    {
        $this->submission();
        $dispatch = $this->service->preview($this->admin->id, (string) Str::uuid(), 'entry', 'all', [], 'https://youtu.be/old', now()->addHour()->format('Y-m-d\TH:i'));
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $data = ['revision' => 1, 'delivery_mode' => 'scheduled', 'scheduled_at' => now()->addHours(2)->format('Y-m-d\TH:i'), 'zoom_url' => 'https://youtu.be/new'];
        $url = route('admin.applications.alimtalk.update', $dispatch->id);
        $this->put($url, $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('cancelling', DB::table('alimtalk_dispatches')->find($dispatch->id)->status);
        $this->assertSame(1, DB::table('alimtalk_dispatches')->count());
        $this->put($url, $data)->assertSessionHasErrors(); // Stale revision cannot enqueue a second cancellation.
        $this->assertSame(1, DB::table('jobs')->count());
        $this->runQueue();
        $new = DB::table('alimtalk_dispatches')->whereNull('deleted_at')->first();
        $this->assertSame('preview', $new->status);
        $this->assertSame('youtu.be/new', $new->entry_link);
        $this->assertSame(str_replace('T', ' ', $data['scheduled_at']).':00', $new->scheduled_at);
        $this->assertCount(1, $this->sender->scheduledTimes); // Saving alone never reserves again.
        $this->service->confirm($new->id, $this->admin->id, false);
        $this->runQueue();
        $this->assertCount(2, $this->sender->scheduledTimes);
    }

    public function test_reserved_delete_remains_visible_until_provider_cancellation_is_confirmed(): void
    {
        $this->submission();
        $dispatch = $this->scheduledPreview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $this->service->deleteDispatch($dispatch->id, $this->admin->id, 1);
        $this->assertCount(1, $this->service->recent());
        $this->assertTrue($this->service->recent()[0]['change_pending']);
        $this->sender->managementResult = ['status' => 'cancel_unknown', 'reason' => 'network failure'];
        $this->runQueue();
        $this->assertNull(DB::table('alimtalk_dispatches')->find($dispatch->id)->deleted_at);
        $this->assertSame('needs_review', $this->service->recent()[0]['status']);
        $this->sender->managementResult = ['status' => 'cancelled', 'reason' => null];
        $this->service->requestScheduleManagement($dispatch->id, $this->admin->id, true);
        $this->runQueue();
        $this->assertSame([], $this->service->recent());
        $this->assertSame('cancelled', DB::table('alimtalk_recipients')->value('status'));
    }

    public function test_edit_is_not_applied_when_provider_has_already_started_sending(): void
    {
        $this->submission();
        $dispatch = $this->scheduledPreview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $this->service->updateDispatch($dispatch->id, $this->admin->id, 1, ['delivery_mode' => 'immediate']);
        $this->sender->managementResult = ['status' => 'accepted', 'reason' => 'already started'];
        $this->runQueue();
        $row = DB::table('alimtalk_dispatches')->find($dispatch->id);
        $this->assertSame('completed', $row->status);
        $this->assertNull($row->pending_edit);
        $this->assertNull($row->deleted_at);
        $this->assertStringContainsString('이미 발송', $row->change_error);
        $this->assertSame(1, DB::table('alimtalk_dispatches')->count());
    }

    public function test_crud_rejects_wrong_owner_stale_revision_and_claimed_work(): void
    {
        $this->submission();
        $dispatch = $this->preview();
        $this->put(route('admin.applications.alimtalk.update', $dispatch->id), ['revision' => 5, 'delivery_mode' => 'immediate'])->assertSessionHasErrors();
        $this->delete(route('admin.applications.alimtalk.destroy', $dispatch->id), ['revision' => 5])->assertSessionHasErrors();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        DB::table('alimtalk_recipients')->update(['status' => 'processing']);
        $this->delete(route('admin.applications.alimtalk.destroy', $dispatch->id), ['revision' => 1])->assertSessionHasErrors();
        $this->put(route('admin.applications.alimtalk.update', $dispatch->id), ['revision' => 1, 'delivery_mode' => 'immediate'])->assertSessionHasErrors();
        $other = Admin::create(['username' => 'crud-other', 'password' => 'secret', 'name' => '다른 관리자', 'email' => 'crud-other@example.com', 'role' => 'admin']);
        $this->actingAs($other, 'admin');
        $this->put(route('admin.applications.alimtalk.update', $dispatch->id), ['revision' => 1, 'delivery_mode' => 'immediate'])->assertForbidden();
        $this->delete(route('admin.applications.alimtalk.destroy', $dispatch->id), ['revision' => 1])->assertForbidden();
        $this->assertNull(DB::table('alimtalk_dispatches')->value('deleted_at'));
    }

    public function test_crud_enqueue_failure_rolls_back_change_request_and_keeps_original_reservation(): void
    {
        $this->submission();
        $dispatch = $this->scheduledPreview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        Queue::shouldReceive('connection')->andThrow(new \RuntimeException('queue unavailable'));
        try {
            $this->service->deleteDispatch($dispatch->id, $this->admin->id, 1);
            $this->fail('Queue error swallowed');
        } catch (\RuntimeException $e) {
            $this->assertSame('queue unavailable', $e->getMessage());
        }
        $row = DB::table('alimtalk_dispatches')->find($dispatch->id);
        $this->assertSame('scheduled', $row->status);
        $this->assertFalse((bool) $row->delete_requested);
        $this->assertSame(1, $row->revision);
        $this->assertSame('scheduled', DB::table('alimtalk_recipients')->value('status'));
    }

    public function test_edit_after_all_targets_were_deleted_produces_an_empty_preview_not_an_empty_send(): void
    {
        $row = $this->submission();
        $dispatch = $this->scheduledPreview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $this->service->updateDispatch($dispatch->id, $this->admin->id, 1, ['delivery_mode' => 'immediate']);
        $row->delete();
        $this->runQueue();
        $new = DB::table('alimtalk_dispatches')->whereNull('deleted_at')->first();
        $this->assertSame('preview', $new->status);
        $this->assertSame(0, $new->selected_count);
        $this->post(route('admin.applications.alimtalk.confirm', $new->id))->assertSessionHasErrors();
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_edit_preserves_individually_selected_people_and_recounts_all_scope(): void
    {
        $selected = $this->submission();
        $this->submission('01022222222');
        $one = $this->service->preview($this->admin->id, (string) Str::uuid(), 'morning', 'selected', [$selected->id], null);
        $this->service->updateDispatch($one->id, $this->admin->id, 1, ['delivery_mode' => 'immediate']);
        $edited = DB::table('alimtalk_dispatches')->whereNull('deleted_at')->first();
        $this->assertSame(1, $edited->selected_count);
        $this->assertSame([$selected->id], DB::table('alimtalk_dispatch_targets')->where('dispatch_id', $edited->id)->pluck('submission_id')->all());
        $this->service->cancel($edited->id, $this->admin->id);
        $all = $this->preview();
        $this->submission('01033333333');
        $this->service->updateDispatch($all->id, $this->admin->id, 1, ['delivery_mode' => 'immediate']);
        $this->assertSame(3, DB::table('alimtalk_dispatches')->where('status', 'preview')->whereNull('deleted_at')->value('selected_count'));
    }

    public function test_mysql_edit_and_worker_claim_race_never_send_both_old_and_new_work(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Requires isolated MySQL and pcntl for independent concurrent connections.');
        }
        $this->submission();
        $dispatch = $this->preview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $ids = DB::table('alimtalk_recipients')->pluck('id')->all();
        $trace = tempnam(sys_get_temp_dir(), 'alimtalk-send-trace-');
        $this->sender->traceFile = $trace;
        $this->race(function ($index) use ($dispatch, $ids) {
            if ($index === 0) {
                $this->service->processBatch($dispatch->id, $ids);

                return ['worker'];
            }
            try {
                $this->service->updateDispatch($dispatch->id, $this->admin->id, 1, ['delivery_mode' => 'immediate']);

                return ['edited'];
            } catch (ValidationException $e) {
                return ['blocked'];
            }
        });
        $this->runQueue();
        $sent = count(file($trace));
        $hasDraft = DB::table('alimtalk_dispatches')->whereNull('deleted_at')->where('status', 'preview')->exists();
        $this->assertSame($hasDraft ? 0 : 1, $sent);
        $this->assertLessThanOrEqual(1, DB::table('alimtalk_recipients')->where('status', 'accepted')->count());
        unlink($trace);
    }

    public function test_explicitly_rejected_reservation_does_not_block_corrected_retry_at_same_time(): void
    {
        $this->submission();
        $dispatch = $this->scheduledPreview();
        $this->sender->failScheduledBatch = 1;
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $this->assertSame('failed', DB::table('alimtalk_recipients')->value('status'));
        $this->assertSame('preview', $this->scheduledPreview()->status);
    }

    public function test_partial_registration_can_cancel_successful_groups_without_claiming_full_success(): void
    {
        for ($i = 0; $i < 101; $i++) {
            $this->submission(sprintf('010%08d', $i));
        }
        $this->sender->failScheduledBatch = 2;
        $dispatch = $this->scheduledPreview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $this->assertSame('partial_scheduled', DB::table('alimtalk_dispatches')->value('status'));
        $this->assertSame(100, DB::table('alimtalk_recipients')->where('status', 'scheduled')->count());
        $this->service->requestScheduleManagement($dispatch->id, $this->admin->id, true);
        $this->runQueue();
        $this->assertSame(100, DB::table('alimtalk_recipients')->where('status', 'cancelled')->count());
        $this->assertSame(1, DB::table('alimtalk_recipients')->where('status', 'failed')->count());
    }

    public function test_cancellation_registration_failure_rolls_back_and_other_admin_cannot_cancel(): void
    {
        $this->submission();
        $dispatch = $this->scheduledPreview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        try {
            $this->service->requestScheduleManagement($dispatch->id, $this->admin->id + 1, true);
            $this->fail('Ownership bypass');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        Queue::shouldReceive('connection')->andThrow(new \RuntimeException('queue unavailable'));
        try {
            $this->service->requestScheduleManagement($dispatch->id, $this->admin->id, true);
            $this->fail('Queue failure swallowed');
        } catch (\RuntimeException $e) {
            $this->assertSame('queue unavailable', $e->getMessage());
        }
        $this->assertSame('scheduled', DB::table('alimtalk_dispatches')->value('status'));
        $this->assertSame('scheduled', DB::table('alimtalk_recipients')->value('status'));
    }

    public function test_stopped_cancellation_can_be_reconciled_without_claiming_success(): void
    {
        $this->submission();
        $dispatch = $this->scheduledPreview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $this->service->requestScheduleManagement($dispatch->id, $this->admin->id, true);
        $this->service->requestScheduleManagement($dispatch->id, $this->admin->id, true);
        $this->assertSame(1, DB::table('jobs')->count());
        DB::table('jobs')->delete(); // Simulate a worker lost before processing cancellation.
        $this->travel(2)->minutes();
        $this->sender->managementResult = ['status' => 'scheduled', 'reason' => null];
        $this->service->requestScheduleManagement($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $this->assertSame('cancel_unknown', DB::table('alimtalk_recipients')->value('status'));
        $this->assertSame('needs_review', DB::table('alimtalk_dispatches')->value('status'));
    }

    public function test_cancel_failure_callback_and_resolved_in_flight_cancellation_are_safe(): void
    {
        $this->submission();
        $dispatch = $this->scheduledPreview();
        $this->service->confirm($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $this->service->requestScheduleManagement($dispatch->id, $this->admin->id, true);
        $group = DB::table('alimtalk_recipients')->value('provider_group_id');
        $this->service->scheduleManagementFailed($dispatch->id, $group, true);
        $this->assertSame('cancel_unknown', DB::table('alimtalk_recipients')->value('status'));
        $this->sender->managementResult = ['status' => 'accepted', 'reason' => 'already started'];
        $this->service->requestScheduleManagement($dispatch->id, $this->admin->id, false);
        $this->runQueue();
        $this->assertSame('accepted', DB::table('alimtalk_recipients')->value('status'));
        $this->assertSame('completed', DB::table('alimtalk_dispatches')->value('status'));
    }

    public function test_failure_filter_uses_effective_results_and_keeps_unknown_separate(): void
    {
        $failed = $this->submission();
        $failed->update(['alimtalk_results' => ['morning' => ['status' => 'failed', 'reason' => '번호 오류']]]);
        $unknown = $this->submission('01022222222');
        $unknown->update(['alimtalk_results' => ['morning' => ['status' => 'unknown']]]);
        $delivered = $this->submission('01033333333');
        $delivered->update(['alimtalk_results' => ['morning' => ['status' => 'delivered']]]);
        // The saved JSON still says failed; an active dispatch must override it.
        $draft = $this->preview();
        $this->service->confirm($draft->id, $this->admin->id, true);
        DB::table('alimtalk_recipients')->where('dispatch_id', $draft->id)->where('phone', '01012345678')->update(['status' => 'delivery_failed', 'reason' => '수신 불가']);
        DB::table('alimtalk_recipients')->where('dispatch_id', $draft->id)->where('phone', '01033333333')->update(['status' => 'unknown']);
        $response = $this->getJson(route('admin.applications', ['result_status' => 'failed', 'notice_kind' => 'morning']));
        $response->assertOk()->assertJsonPath('total', 1);
        $this->assertStringContainsString('수신 불가', $response->json('html'));
        $this->assertStringNotContainsString('data-application-id="'.$unknown->id.'"', $response->json('html'));
        $this->get(route('admin.applications', ['result_status' => 'failed']))->assertOk()->assertSee('재발송 대상 확인');
    }

    public function test_cursor_loads_without_duplicates_when_new_applications_arrive(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->submission(sprintf('010%08d', $i));
        }
        $first = $this->getJson(route('admin.applications'))->assertOk()->assertJsonPath('total', 25);
        preg_match_all('/data-application-id="(\d+)"/', $first->json('html'), $ids);
        $this->assertCount(20, $ids[1]);
        $this->submission('01099999999');
        $next = $this->getJson($first->json('next'))->assertOk()->assertJsonPath('next', null);
        preg_match_all('/data-application-id="(\d+)"/', $next->json('html'), $nextIds);
        $this->assertCount(5, $nextIds[1]);
        $this->assertCount(25, array_unique(array_merge($ids[1], $nextIds[1])));
    }

    public function test_retry_all_is_limited_to_search_and_notice_failures_and_deduplicates_numbers(): void
    {
        foreach (['01012345678', '821012345678', '01022222222'] as $phone) {
            $row = $this->submission($phone);
            $row->update(['institution_name' => '재발송기관', 'alimtalk_results' => ['morning' => ['status' => 'delivery_failed']]]);
        }
        $other = $this->submission('01033333333');
        $other->update(['alimtalk_results' => ['morning' => ['status' => 'failed']]]);
        $unknown = $this->submission('01044444444');
        $unknown->update(['institution_name' => '재발송기관', 'alimtalk_results' => ['morning' => ['status' => 'unknown'], 'entry' => ['status' => 'failed']]]);
        $this->post(route('admin.applications.alimtalk'), [
            'kind' => 'morning', 'retry_failed' => 1, 'scope' => 'failed', 'failed_all' => 1, 'search' => '재발송기관',
        ])->assertSessionHasNoErrors();
        $draft = DB::table('alimtalk_dispatches')->first();
        $this->assertSame('failed', $draft->scope);
        $this->assertSame(3, $draft->selected_count);
        $this->assertSame(2, DB::table('alimtalk_recipients')->count());
        $this->assertSame(0, DB::table('jobs')->count());
        $this->post(route('admin.applications.alimtalk.confirm', $draft->id), ['confirm_resend' => 1])->assertSessionHasNoErrors();
        $this->runQueue();
        $this->assertSame([['01012345678', '01022222222']], $this->sender->calls);
    }

    public function test_selected_retry_cannot_include_successes_and_rechecks_status_at_confirmation(): void
    {
        $failed = $this->submission();
        $failed->update(['alimtalk_results' => ['morning' => ['status' => 'failed']]]);
        $success = $this->submission('01022222222');
        $success->update(['alimtalk_results' => ['morning' => ['status' => 'delivered']]]);
        $this->post(route('admin.applications.alimtalk'), [
            'kind' => 'morning', 'retry_failed' => 1, 'scope' => 'all', 'ids' => [$failed->id, $success->id],
        ])->assertSessionHasNoErrors();
        $draft = DB::table('alimtalk_dispatches')->first();
        $this->assertSame(1, $draft->selected_count);
        $this->post(route('admin.applications.alimtalk.confirm', $draft->id))->assertSessionHasErrors();
        $failed->update(['alimtalk_results' => ['morning' => ['status' => 'delivered']]]);
        $this->post(route('admin.applications.alimtalk.confirm', $draft->id), ['confirm_resend' => 1])->assertSessionHasErrors();
        $this->assertSame('cancelled', DB::table('alimtalk_dispatches')->first()->status);
        $this->assertSame(0, DB::table('jobs')->count());
    }

    private function preview(string $kind = 'morning'): object
    {
        return $this->service->preview($this->admin->id, (string) Str::uuid(), $kind, 'all', [], 'https://youtu.be/test');
    }

    private function submission(string $phone = '01012345678'): VideoSubmission
    {
        return VideoSubmission::create([
            'region' => '서울', 'institution_name' => '기관', 'student_name_korean' => '학생',
            'grade' => '만 3세', 'parent_name' => '학부모', 'parent_phone' => $phone, 'privacy_consent' => true,
        ]);
    }

    private function runQueue(): void
    {
        $this->assertSame(0, Artisan::call('queue:work', [
            'connection' => 'database', '--queue' => AdminAlimtalkDispatchService::QUEUE,
            '--stop-when-empty' => true, '--sleep' => 0, '--tries' => 1,
        ]));
    }
}

class FakeAdminAlimtalk extends SolapiAlimtalkService
{
    public array $deliveryCalls = [];

    public array $deliveryResults = [];

    public bool $deliveryThrows = false;

    public ?\Closure $duringDeliveryLookup = null;

    public function adminDeliveryResults(string $groupId): array
    {
        $this->deliveryCalls[] = $groupId;
        if ($this->duringDeliveryLookup) {
            ($this->duringDeliveryLookup)();
        }
        if ($this->deliveryThrows) {
            throw new \RuntimeException('test-only lookup failure');
        }

        return $this->deliveryResults;
    }

    public array $calls = [];

    public string $outcome = 'accepted';

    public ?string $reason = null;

    public ?string $traceFile = null;

    public array $scheduledTimes = [];

    public ?int $failScheduledBatch = null;

    public array $managementResult = ['status' => 'cancelled', 'reason' => null];

    public function scheduleAdminBatch(array $phones, string $kind, ?string $entryLink, \DateTimeInterface $when): array
    {
        $this->calls[] = $phones;
        $this->scheduledTimes[] = $when->format('Y-m-d H:i:s');
        if (count($this->scheduledTimes) === $this->failScheduledBatch) {
            return array_fill_keys($phones, ['status' => 'failed', 'reason' => '예약 거절']);
        }

        return array_fill_keys($phones, ['status' => 'scheduled', 'reason' => null, 'provider_group_id' => 'G-test-'.count($this->calls)]);
    }

    public function manageAdminSchedule(string $groupId, bool $cancel): array
    {
        return $this->managementResult;
    }

    public function sendAdminBatch(array $phones, string $kind, ?string $entryLink): array
    {
        if ($this->traceFile) {
            file_put_contents($this->traceFile, "sent\n", FILE_APPEND | LOCK_EX);
        }
        $this->calls[] = $phones;

        return array_fill_keys($phones, ['status' => $this->outcome, 'reason' => $this->reason ?? ($this->outcome === 'unknown' ? '확인 필요' : null)]);
    }
}
