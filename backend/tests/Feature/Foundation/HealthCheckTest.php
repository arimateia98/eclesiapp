<?php

namespace Tests\Feature\Foundation;

use Tests\TestCase;

final class HealthCheckTest extends TestCase
{
    public function test_framework_liveness_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_versioned_api_health_contract_is_stable(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'service',
                    'status',
                    'timestamp',
                ],
            ])
            ->assertJsonPath('data.service', 'eclesiapp-api')
            ->assertJsonPath('data.status', 'ok');
    }
}
