<?php

namespace Tests\Feature;

use App\Http\Controllers\VideoSubmissionController;
use App\Models\Institution;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InstitutionSuggestionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function test_institution_suggestions_include_names_after_the_old_twenty_item_cutoff(): void
    {
        foreach (range(1, 25) as $number) {
            Institution::create([
                'name' => sprintf('기관 %02d', $number),
                'is_active' => true,
                'sort_order' => 0,
            ]);
        }

        $response = app(VideoSubmissionController::class)->getInstitutions(Request::create('/api/institutions', 'GET'));

        $this->assertSame(200, $response->getStatusCode());
        $names = $response->getData(true);
        $this->assertCount(25, $names);
        $this->assertContains('기관 21', $names);
        $this->assertContains('기관 25', $names);
    }
};
