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

function userWithRole(RoleCode ...$roles): User
{
    /** @var User $user */
    $user = User::factory()->create();

    foreach ($roles as $role) {
        $user->assignRole($role);
    }

    return $user->fresh();
}

it('lets a store owner into the store panel', function (): void {
    $user = userWithRole(RoleCode::Store);

    $this->actingAs($user)
        ->get('/store')
        ->assertOk()
        ->assertSee('فروشگاه');
});

it('stores the active role in the session and defaults to the first held role', function (): void {
    $user = userWithRole(RoleCode::Store);

    $this->actingAs($user)->get('/store')->assertOk();

    expect(session('active_role_code'))->toBe('store');
});

it('refuses the store panel while another role is active', function (): void {
    // A store owner who is also a marketer must not read store data with the marketer
    // role active: the panel and the price scope belong to the active role (ADR-0007).
    $user = userWithRole(RoleCode::Store, RoleCode::Marketer);

    $this->actingAs($user);
    $user->switchActiveRole(RoleCode::Marketer);

    $this->get('/store')->assertForbidden();
    $this->get('/marketer')->assertOk();
});

it('switches the active role through the panel switcher and audits it', function (): void {
    $user = userWithRole(RoleCode::Store, RoleCode::Marketer);

    $this->actingAs($user)
        ->post('/panel/role', ['role' => 'marketer'])
        ->assertRedirect('/marketer');

    expect(session('active_role_code'))->toBe('marketer');

    $this->assertDatabaseHas('adm_audit_logs', [
        'event' => 'identity.role.switched',
        'actor_id' => $user->id,
    ]);
});

it('refuses to switch to a role the user does not hold', function (): void {
    $user = userWithRole(RoleCode::Consumer);

    $this->actingAs($user)
        ->post('/panel/role', ['role' => 'admin'])
        ->assertForbidden();

    expect(session('active_role_code'))->not->toBe('admin');
});

it('keeps a consumer out of the admin panel', function (): void {
    $user = userWithRole(RoleCode::Consumer);

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

it('redirects guests to login instead of leaking that a panel exists', function (): void {
    $this->get('/store')->assertRedirect(route('login'));
});

it('grants permissions from the active role only, never the union', function (): void {
    $user = userWithRole(RoleCode::Store, RoleCode::Consumer);

    $this->actingAs($user);
    $user->switchActiveRole(RoleCode::Consumer);

    expect($user->hasPermission('consumer.orders.read'))->toBeTrue()
        ->and($user->hasPermission('store.catalog.manage'))->toBeFalse();

    $user->switchActiveRole(RoleCode::Store);

    expect($user->hasPermission('store.catalog.manage'))->toBeTrue()
        ->and($user->hasPermission('consumer.orders.read'))->toBeFalse();
});
