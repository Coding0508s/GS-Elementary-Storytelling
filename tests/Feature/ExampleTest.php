<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        (require database_path('migrations/2025_10_29_110006_create_site_settings_table.php'))->up();

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
