<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Domain\RoleCode;
use App\Modules\Identity\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('logs a user in, audits it and lands on their panel', function (): void {
    /** @var User $user */
    $user = User::factory()->create([
        'email' => 'store@dekolife.test',
        'password' => 'password',
        'must_change_password' => false,
    ]);
    $user->assignRole(RoleCode::Store);

    $this->post('/login', ['email' => 'store@dekolife.test', 'password' => 'password'])
        ->assertRedirect(route('panel.redirect'));

    $this->assertAuthenticatedAs($user);

    $this->assertDatabaseHas('adm_audit_logs', [
        'event' => 'identity.login',
        'actor_id' => $user->id,
    ]);

    expect($user->fresh()->last_login_ip)->not->toBeNull();
});

it('rejects a wrong password with a generic message and audits the failure', function (): void {
    User::factory()->create(['email' => 'store@dekolife.test', 'password' => 'password']);

    $this->post('/login', ['email' => 'store@dekolife.test', 'password' => 'wrong'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    $this->assertDatabaseHas('adm_audit_logs', ['event' => 'identity.login.failed']);
});

it('forces a password change before any panel is reachable', function (): void {
    /** @var User $user */
    $user = User::factory()->create([
        'email' => 'invited@dekolife.test',
        'password' => 'temp-password1',
        'must_change_password' => true,
    ]);
    $user->assignRole(RoleCode::Store);

    $this->post('/login', ['email' => 'invited@dekolife.test', 'password' => 'temp-password1'])
        ->assertRedirect(route('password.change'));

    // Any panel redirects back to the change form while the flag is set.
    $this->get('/store')->assertRedirect(route('password.change'));

    $this->post('/password/change', [
        'current_password' => 'temp-password1',
        'password' => 'new-password1',
        'password_confirmation' => 'new-password1',
    ])->assertRedirect(route('panel.redirect'));

    expect($user->fresh()->must_change_password)->toBeFalse();

    $this->get('/store')->assertOk();
    $this->assertDatabaseHas('adm_audit_logs', ['event' => 'identity.password.changed']);
});

it('refuses a password change with the wrong current password', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['password' => 'temp-password1', 'must_change_password' => true]);

    $this->actingAs($user)
        ->post('/password/change', [
            'current_password' => 'not-it',
            'password' => 'new-password1',
            'password_confirmation' => 'new-password1',
        ])
        ->assertSessionHasErrors('current_password');

    expect($user->fresh()->must_change_password)->toBeTrue();
});

it('rejects a weak new password', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['password' => 'temp-password1', 'must_change_password' => true]);

    $this->actingAs($user)
        ->post('/password/change', [
            'current_password' => 'temp-password1',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertSessionHasErrors('password');
});

it('logs out and audits it', function (): void {
    /** @var User $user */
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

    $this->assertGuest();
    $this->assertDatabaseHas('adm_audit_logs', ['event' => 'identity.logout']);
});
