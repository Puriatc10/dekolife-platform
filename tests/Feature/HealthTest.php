<?php

declare(strict_types=1);

it('reports health with a database probe', function (): void {
    $response = $this->getJson('/health');

    $response->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('database', true);

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('exposes the health endpoint without authentication', function (): void {
    // A load balancer has no session; the endpoint must never redirect to login.
    $this->get('/health')->assertOk();
});
