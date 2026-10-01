<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Models\Admin;
use App\Models\VideoSubmission;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResetReceiptNumberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role')->default('judge');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('video_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('region');
            $table->string('institution_name');
            $table->string('class_name')->nullable();
            $table->string('student_name_korean');
            $table->string('student_name_english')->nullable();
            $table->string('grade');
            $table->integer('age')->nullable();
            $table->string('parent_name');
            $table->string('parent_phone');
            $table->string('video_file_path')->nullable();
            $table->string('video_file_name')->nullable();
            $table->string('video_file_type')->nullable();
            $table->unsignedBigInteger('video_file_size')->nullable();
            $table->string('unit_topic')->nullable();
            $table->text('teacher_question')->nullable();
            $table->boolean('privacy_consent')->default(false);
            $table->timestamp('privacy_consent_at')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('notification_sent_at')->nullable();
            $table->string('status')->default('uploaded');
            $table->timestamps();
            $table->softDeletes();
        });

        foreach (['evaluations', 'video_assignments', 'ai_evaluations'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('video_submission_id')->nullable();
                $table->timestamps();
            });
        }
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
