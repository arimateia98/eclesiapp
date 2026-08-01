<?php

namespace Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $application = parent::createApplication();
        $config = $application->make(Repository::class);

        $postgresDatabase = getenv('ECLESIAPP_POSTGRES_TEST_DATABASE');

        if (is_string($postgresDatabase) && $postgresDatabase !== '') {
            $config->set('database.default', 'pgsql');
            $config->set('database.connections.pgsql.database', $postgresDatabase);
        } else {
            $config->set('database.default', 'sqlite');
            $config->set('database.connections.sqlite.url');
            $config->set('database.connections.sqlite.database', ':memory:');
            $config->set('database.connections.sqlite.foreign_key_constraints', true);
        }

        return $application;
    }
}
