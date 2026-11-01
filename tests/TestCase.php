<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Test tidak boleh bergantung pada build asset Vite.
     * Tanpa ini, halaman Inertia gagal render karena manifest.json belum dibuat.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
