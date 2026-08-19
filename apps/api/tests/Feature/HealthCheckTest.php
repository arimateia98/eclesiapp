<?php

declare(strict_types=1);

test('the versioned api health endpoint is available', function (): void {
    $response = $this->withHeader('X-Request-Id', 'req_test_health')
        ->getJson('/api/v1/health');

    $response
        ->assertOk()
        ->assertHeader('X-Request-Id', 'req_test_health')
        ->assertExactJson([
            'data' => ['status' => 'ok'],
            'meta' => ['request_id' => 'req_test_health'],
        ]);
});
