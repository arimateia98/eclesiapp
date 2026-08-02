<?php

namespace Tests\Feature\Foundation;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TestingDatabaseIsolationTest extends TestCase
{
    public function test_automated_suite_uses_an_explicitly_isolated_database(): void
    {
        $postgresDatabase = getenv('ECLESIAPP_POSTGRES_TEST_DATABASE');

        if (is_string($postgresDatabase) && $postgresDatabase !== '') {
            self::assertSame('pgsql', DB::getDriverName());
            self::assertSame($postgresDatabase, DB::getDatabaseName());

            return;
        }

        self::assertSame('sqlite', DB::getDriverName());
        self::assertSame(':memory:', DB::getDatabaseName());
    }
}
