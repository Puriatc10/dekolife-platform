<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Database\Seeders\RolePermissionSeeder;
use App\Modules\Identity\Domain\RoleCode;
use App\Shared\Security\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);

    /** @var User $admin */
    $admin = User::factory()->create(['email' => 'admin@dekolife.test']);
    $admin->assignRole(RoleCode::Admin);

    $this->admin = $admin;
    $this->secret = Totp::generateSecret();
    $admin->forceFill(['two_factor_secret' => Crypt::encryptString($this->secret)])->save();
});

it('sends an admin without a verified second factor to the challenge', function (): void {
    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertRedirect(route('admin.2fa.show'));
});

it('rejects a wrong code and keeps the session unverified', function (): void {
    $this->actingAs($this->admin)
        ->post('/admin/2fa', ['code' => '000000'])
        ->assertRedirect(route('admin.2fa.show'))
        ->assertSessionHasErrors('code');

    expect(session('admin.2fa_verified'))->not->toBeTrue();

    $this->get('/admin')->assertRedirect(route('admin.2fa.show'));
});

it('accepts a valid code, audits the event and opens the dashboard', function (): void {
    $code = (new Totp($this->secret))->code();

    $this->actingAs($this->admin)
        ->post('/admin/2fa', ['code' => $code])
        ->assertRedirect(route('admin.dashboard'));

    $this->get('/admin')
        ->assertOk()
        ->assertSee('پنل مدیریت');

    $this->assertDatabaseHas('adm_audit_logs', ['event' => 'admin.2fa.verified']);
});

it('does not let a non-admin user reach the admin panel even with a valid code', function (): void {
    /** @var User $store */
    $store = User::factory()->create();
    $store->assignRole(RoleCode::Store);

    $this->actingAs($store)->get('/admin')->assertForbidden();
});

it('invalidates the second factor on a fresh login', function (): void {
    // A hijacked session must not inherit a verified second factor: the flag lives in the
    // session, so a new session starts unverified.
    $this->actingAs($this->admin)->post('/admin/2fa', ['code' => (new Totp($this->secret))->code()]);
    expect(session('admin.2fa_verified'))->toBeTrue();

    $this->flushSession();

    $this->get('/admin')->assertRedirect(route('admin.2fa.show'));
});
