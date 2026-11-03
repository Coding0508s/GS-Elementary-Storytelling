<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Models\Admin;
use App\Models\VideoSubmission;
use App\Services\SolapiAlimtalkService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResetReceiptNumberTest extends TestCase
{
    use \Tests\Concerns\CreatesSeminarSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSeminarSchema();
    }

    public function test_reset_permanently_deletes_trash_and_restarts_receipt_numbers(): void
    {
        Storage::fake('s3');
        Storage::fake('local');
        Storage::fake('public');

        $admin = Admin::create([
            'username' => 'admin',
            'password' => 'secret-pass',
            'name' => '관리자',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $kept = $this->makeSubmission('보관');
        $trashed = $this->makeSubmission('휴지통');
        $trashed->delete();

        $this->assertSame(1, $kept->id);
        $this->assertSame(2, $trashed->id);
        $this->assertSame(1, VideoSubmission::count());
        $this->assertSame(1, VideoSubmission::onlyTrashed()->count());

        $this->actingAs($admin, 'admin');
        $request = Request::create('/admin/reset-execute', 'POST', [
            'confirmation_text' => '모든 데이터를 영구적으로 삭제합니다',
            'admin_password' => 'secret-pass',
        ]);

        $response = app(AdminController::class)->executeReset($request);

        $this->assertTrue($response->isRedirect(route('admin.dashboard')));
        $this->assertSame(0, VideoSubmission::withTrashed()->count());

        $next = $this->makeSubmission('새접수');
        $this->assertSame(1, $next->id);
        $this->assertSame('GSK-00001', $next->receipt_number);
    }

    public function test_delete_moves_application_to_trash(): void
    {
        $admin = Admin::create([
            'username' => 'admin',
            'password' => 'secret-pass',
            'name' => '관리자',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $submission = $this->makeSubmission('삭제대상');

        $this->actingAs($admin, 'admin');
        $request = Request::create('/admin/applications/'.$submission->id, 'DELETE', [
            'search' => '삭제대상',
        ]);

        $response = app(AdminController::class)->deleteSeminarApplication($request, $submission);

        $this->assertTrue($response->isRedirect(route('admin.applications', ['search' => '삭제대상'])));
        $this->assertSoftDeleted('video_submissions', ['id' => $submission->id]);
        $this->assertSame(0, VideoSubmission::count());
        $this->assertSame(1, VideoSubmission::onlyTrashed()->count());
    }

    public function test_alimtalk_without_template_does_not_send(): void
    {
        config([
            'services.solapi.kakao_pf_id' => '',
            'services.solapi.kakao_template_id' => '',
            'services.solapi.kakao_template_morning' => '',
        ]);

        $admin = Admin::create([
            'username' => 'admin',
            'password' => 'secret-pass',
            'name' => '관리자',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $submission = $this->makeSubmission('알림톡대상');

        $this->actingAs($admin, 'admin');
        $request = Request::create('/admin/applications/alimtalk', 'POST', [
            'search' => '알림톡대상',
            'kind' => 'morning',
            'ids' => [$submission->id],
        ]);

        $response = app(AdminController::class)->sendSeminarAlimtalk($request);

        $this->assertTrue($response->isRedirect(route('admin.applications', ['search' => '알림톡대상'])));
        $this->assertSame('템플릿이 연결되지 않았습니다.', session('error'));
        $this->assertSame(1, VideoSubmission::count());
    }

    public function test_alimtalk_without_selection_does_not_send(): void
    {
        $admin = Admin::create([
            'username' => 'admin',
            'password' => 'secret-pass',
            'name' => '관리자',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->makeSubmission('선택없음');

        $this->actingAs($admin, 'admin');
        $request = Request::create('/admin/applications/alimtalk', 'POST', [
            'kind' => 'morning',
        ]);

        $response = app(AdminController::class)->sendSeminarAlimtalk($request);

        $this->assertTrue($response->isRedirect(route('admin.applications')));
        $this->assertSame('보낼 사람을 선택해주세요.', session('error'));
    }

    public function test_morning_and_entry_results_stay_separate(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
        Schema::table('video_submissions', function (Blueprint $table) {
            $table->json('alimtalk_results')->nullable();
            $table->string('alimtalk_status', 20)->nullable();
            $table->string('alimtalk_status_reason')->nullable();
            $table->timestamp('alimtalk_sent_at')->nullable();
        });

        $admin = Admin::create([
            'username' => 'admin',
            'password' => 'secret-pass',
            'name' => '관리자',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $submission = $this->makeSubmission('결과분리');

        $this->app->instance(SolapiAlimtalkService::class, new class($submission->id) extends SolapiAlimtalkService
        {
            public function __construct(private int $submissionId) {}

            public function isReady(string $kind = self::KIND_COMPLETED): bool
            {
                return true;
            }

            public function entryLinkValue(string $url): string
            {
                return 'us02web.zoom.us/j/1';
            }

            public function adminConfigurationHash(string $kind): string
            {
                return 'test-configuration';
            }

            public function sendAdminBatch(array $phones, string $kind, ?string $entryLink): array
            {
                return array_fill_keys($phones, [
                    'status' => $kind === self::KIND_ENTRY ? 'failed' : 'accepted',
                    'reason' => $kind === self::KIND_ENTRY ? '템플릿 불일치' : null,
                ]);
            }
        });

        $this->actingAs($admin, 'admin');

        (require database_path('migrations/2026_11_03_010000_create_admin_alimtalk_dispatch_tables.php'))->up();
        (require database_path('migrations/2026_11_03_020000_add_scheduling_to_alimtalk_dispatches.php'))->up();
        (require database_path('migrations/2026_11_03_030000_add_change_management_to_alimtalk_dispatches.php'))->up();
        (require database_path('migrations/0001_01_01_000002_create_jobs_table.php'))->up();
        $service = app(\App\Services\AdminAlimtalkDispatchService::class);
        foreach (['morning', 'entry'] as $kind) {
            $dispatch = $service->preview($admin->id, (string) \Illuminate\Support\Str::uuid(), $kind, 'selected', [$submission->id], 'https://youtu.be/test');
            $service->confirm($dispatch->id, $admin->id, true);
            $ids = \Illuminate\Support\Facades\DB::table('alimtalk_recipients')->where('dispatch_id', $dispatch->id)->pluck('id')->all();
            $service->processBatch($dispatch->id, $ids);
        }

        $submission->refresh();
        $this->assertSame('accepted', $submission->alimtalkResult('morning')['status']);
        $this->assertSame('failed', $submission->alimtalkResult('entry')['status']);
        $this->assertSame('템플릿 불일치', $submission->alimtalkResult('entry')['reason']);
    }

    private function makeSubmission(string $name): VideoSubmission
    {
        return VideoSubmission::create([
            'region' => '서울특별시 강남구',
            'institution_name' => '테스트기관',
            'student_name_korean' => $name,
            'grade' => '만 3세',
            'parent_name' => '학부모',
            'parent_phone' => '01000000000',
            'privacy_consent' => true,
        ]);
    }
}
