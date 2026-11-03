<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Production forces database sessions; isolated tests do not have a sessions table.
        config(['session.driver' => 'array']);
    }
}
