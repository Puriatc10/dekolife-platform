<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Domain\RoleCode;
use App\Modules\Identity\Seeders\RolePermissionSeeder;
use App\Modules\Notifications\Contracts\SmsDriver;
use App\Modules\Notifications\Infrastructure\FakeSmsDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * Registration, recovery and mobile change all share one delivery channel: a text message.
 * The tests drive a FakeSmsDriver, so they assert on the code that was actually sent instead
 * of on a hard-coded value — changing the code format cannot silently pass these tests.
 */
beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);

    $this->sms = new FakeSmsDriver;
    $this->app->instance(SmsDriver::class, $this->sms);
});

function codeSentTo(FakeSmsDriver $sms, string $mobile): string
{
    $message = $sms->lastMessageTo($mobile);

    expect($message)->not->toBeNull("هیچ پیامکی برای {$mobile} ارسال نشد.");
    preg_match('/(\d{6})/u', (string) $message, $matches);

    expect($matches[1] ?? null)->not->toBeNull('کد شش‌رقمی در متن پیامک پیدا نشد.');

    return $matches[1];
}

it('registers a customer by mobile and one-time code', function (): void {
    $mobile = '09121110001';

    $this->post('/register', ['mobile' => $mobile])
        ->assertRedirect(route('register.verify'));

    $code = codeSentTo($this->sms, $mobile);

    // The code must never ride back in the response outside a local debugging session.
    expect(session('debug_code'))->toBeNull();

    $this->post('/register/verify', [
        'code' => $code,
        'name' => 'مشتری نمونه',
    ])->assertRedirect(route('panel.consumer'));

    $user = User::query()->where('mobile', $mobile)->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('مشتری نمونه')
        ->and($user->mobile_verified_at)->not->toBeNull()
        ->and($user->hasRole(RoleCode::Consumer))->toBeTrue();

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('adm_audit_logs', ['event' => 'identity.register']);
});

it('refuses a second registration on a number that already has an account', function (): void {
    User::factory()->create(['mobile' => '09121110002']);

    $this->post('/register', ['mobile' => '09121110002'])
        ->assertSessionHasErrors('mobile');

    expect($this->sms->messages())->toBeEmpty();
});

it('accepts Persian digits in the mobile field', function (): void {
    $this->post('/register', ['mobile' => '۰۹۱۲۱۱۱۰۰۰۳'])
        ->assertRedirect(route('register.verify'));

    expect($this->sms->lastMessageTo('09121110003'))->not->toBeNull();
});

it('kills the code after three wrong attempts', function (): void {
    $mobile = '09121110004';

    $this->post('/register', ['mobile' => $mobile]);
    $code = codeSentTo($this->sms, $mobile);
    $wrong = $code === '000000' ? '111111' : '000000';

    foreach ([1, 2, 3] as $attempt) {
        $this->post('/register/verify', ['code' => $wrong, 'name' => 'x'])
            ->assertSessionHasErrors('code');
    }

    // Even the correct code is refused once the attempts are spent.
    $this->post('/register/verify', ['code' => $code, 'name' => 'x'])
        ->assertSessionHasErrors('code');

    expect(User::query()->where('mobile', $mobile)->exists())->toBeFalse();
});

it('does not send a second code within a minute', function (): void {
    $mobile = '09121110005';

    $this->post('/register', ['mobile' => $mobile]);
    $this->post('/register', ['mobile' => $mobile])
        ->assertSessionHasErrors('mobile');

    expect($this->sms->messages())->toHaveCount(1);
});

it('resets the password with the code sent to the registered mobile', function (): void {
    $user = User::factory()->create([
        'mobile' => '09121110006',
        'email' => 'reset@dekolife.test',
        'password' => 'old-password',
        'must_change_password' => true,
    ]);
    $user->assignRole(RoleCode::Consumer);

    $this->post('/password/forgot', ['mobile' => '09121110006'])
        ->assertRedirect(route('password.reset.verify'));

    $code = codeSentTo($this->sms, '09121110006');

    $this->post('/password/forgot/verify', [
        'code' => $code,
        'password' => 'new-password-1',
        'password_confirmation' => 'new-password-1',
    ])->assertRedirect(route('login'));

    $user->refresh();

    expect(Hash::check('new-password-1', $user->password))->toBeTrue()
        ->and($user->must_change_password)->toBeFalse();

    $this->assertDatabaseHas('adm_audit_logs', ['event' => 'identity.password.reset']);
});

it('answers the same way for an unknown number and sends nothing', function (): void {
    $this->post('/password/forgot', ['mobile' => '09129999999'])
        ->assertRedirect(route('password.reset.verify'))
        ->assertSessionHas('status');

    expect($this->sms->messages())->toBeEmpty();
});

it('changes the mobile only after the code sent to the new number is confirmed', function (): void {
    $user = User::factory()->create([
        'mobile' => '09121110007',
        'must_change_password' => false,
    ]);
    $user->assignRole(RoleCode::Consumer);

    $this->actingAs($user)
        ->post('/account/profile/mobile', ['mobile' => '09121110008'])
        ->assertRedirect(route('profile.edit'));

    $code = codeSentTo($this->sms, '09121110008');

    // Nothing changes before the code is confirmed.
    expect($user->fresh()->mobile)->toBe('09121110007');

    $this->actingAs($user)
        ->post('/account/profile/mobile/confirm', ['code' => $code])
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->mobile)->toBe('09121110008')
        ->and($user->mobile_verified_at)->not->toBeNull();

    $this->assertDatabaseHas('adm_audit_logs', ['event' => 'identity.mobile.changed']);
});

it('refuses a mobile change to a number owned by another account', function (): void {
    User::factory()->create(['mobile' => '09121110010']);

    $user = User::factory()->create(['mobile' => '09121110009', 'must_change_password' => false]);
    $user->assignRole(RoleCode::Consumer);

    $this->actingAs($user)
        ->post('/account/profile/mobile', ['mobile' => '09121110010'])
        ->assertSessionHasErrors('mobile');
});

it('stores a valid national id once and rejects a duplicate on the second account', function (): void {
    $first = User::factory()->create(['must_change_password' => false]);
    $first->assignRole(RoleCode::Consumer);

    $this->actingAs($first)
        ->post('/account/profile', ['name' => 'اول', 'national_id' => '0499370899'])
        ->assertRedirect(route('profile.edit'));

    expect(DB::table('usr_national_ids')->count())->toBe(1);

    $second = User::factory()->create(['must_change_password' => false]);
    $second->assignRole(RoleCode::Consumer);

    $this->actingAs($second)
        ->post('/account/profile', ['name' => 'دوم', 'national_id' => '0499370899'])
        ->assertSessionHasErrors('national_id');

    expect(DB::table('usr_national_ids')->count())->toBe(1);
});

it('rejects a national id that fails the checksum', function (): void {
    $user = User::factory()->create(['must_change_password' => false]);
    $user->assignRole(RoleCode::Consumer);

    $this->actingAs($user)
        ->post('/account/profile', ['name' => 'نمونه', 'national_id' => '0499370898'])
        ->assertSessionHasErrors('national_id');
});
