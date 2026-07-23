<?php

namespace Tests\Feature\Foundation;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TestingDatabaseIsolationTest extends TestCase
{
    public function test_automated_suite_uses_isolated_sqlite_database(): void
    {
        self::assertSame('sqlite', DB::getDriverName());
        self::assertSame(':memory:', DB::getDatabaseName());
    }
}
