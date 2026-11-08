<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $dbName = (string) config('database.connections.pgsql.database');
        if (! str_ends_with($dbName, '_testing')) {
            throw new \RuntimeException("BAHAYA: Test suite dilarang dieksekusi terhadap basis data yang TIDAK berakhiran '_testing'! Basis data aktif saat ini: '{$dbName}'");
        }
    }
}
