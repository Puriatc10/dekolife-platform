<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Domain\RoleCode;
use App\Modules\Identity\Seeders\RolePermissionSeeder;
use App\Modules\Notifications\Contracts\SmsDriver;
use App\Modules\Notifications\Infrastructure\FakeSmsDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The API is not a second implementation: the same accounts, roles and audit trail, reached
 * with a Sanctum token instead of a session (appendix 1: the mobile app talks to this API).
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);

    $this->sms = new FakeSmsDriver;
    $this->app->instance(SmsDriver::class, $this->sms);
});

it('issues a token for a mobile and password and returns the caller identity', function (): void {
    $user = User::factory()->create([
        'mobile' => '09121330001',
        'email' => 'store-api@dekolife.test',
        'password' => 'secret-password',
        'must_change_password' => false,
    ]);
    $user->assignRole(RoleCode::Store);

    $response = $this->postJson('/api/v1/auth/token', [
        'mobile' => '09121330001',
        'password' => 'secret-password',
        'device_name' => 'android',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'token_type', 'expires_at', 'user' => ['id', 'mobile', 'roles']]);

    $token = $response->json('token');

    expect($token)->toBeString();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.mobile', '09121330001')
        ->assertJsonPath('data.roles.0.code', 'store');

    $this->assertDatabaseHas('personal_access_tokens', ['name' => 'android']);
    $this->assertDatabaseHas('adm_audit_logs', ['event' => 'api.token.issued']);
});

it('rejects a wrong password with a field error, not a 500', function (): void {
    User::factory()->create(['mobile' => '09121330002', 'password' => 'secret-password']);

    $this->postJson('/api/v1/auth/token', [
        'mobile' => '09121330002',
        'password' => 'nope',
    ])->assertStatus(422)->assertJsonValidationErrors('mobile');
});

it('refuses the /me endpoint without a token', function (): void {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});

it('issues a token from an SMS code', function (): void {
    $user = User::factory()->create(['mobile' => '09121330003', 'must_change_password' => false]);
    $user->assignRole(RoleCode::Consumer);

    $this->postJson('/api/v1/auth/otp', ['mobile' => '09121330003'])->assertOk();

    preg_match('/(\d{6})/u', (string) $this->sms->lastMessageTo('09121330003'), $matches);
    expect($matches[1] ?? null)->not->toBeNull();

    $this->postJson('/api/v1/auth/otp-token', [
        'mobile' => '09121330003',
        'code' => $matches[1],
        'device_name' => 'ios',
    ])->assertCreated()->assertJsonStructure(['token']);
});

it('revokes the token on logout', function (): void {
    $user = User::factory()->create([
        'mobile' => '09121330004',
        'password' => 'secret-password',
        'must_change_password' => false,
    ]);
    $user->assignRole(RoleCode::Consumer);

    $token = $this->postJson('/api/v1/auth/token', [
        'mobile' => '09121330004',
        'password' => 'secret-password',
    ])->json('token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    // Within one test the container survives between requests, so a resolved guard would
    // still hold the deleted token's user; a real request starts with empty guards.
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/me')
        ->assertUnauthorized();

    expect($user->tokens()->count())->toBe(0);
});
