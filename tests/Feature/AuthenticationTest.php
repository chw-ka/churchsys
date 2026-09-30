<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the authentication boundary.
 *
 * Every route except /login sits behind the `auth` middleware, so a regression
 * here would expose member records to anyone.
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attributes = []): User
    {
        $user = new User(array_merge([
            'username'    => 'tester',
            'password'    => bcrypt('secret123'),
            'email'       => 'tester@test.local',
            'member_code' => '0001',
        ], $attributes));

        // create_time / update_time are not mass assignable on this model.
        $user->create_time = now();
        $user->update_time = now();
        $user->save();

        return $user;
    }

    public function test_login_page_is_reachable_for_guests(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('用戶名', false);
    }

    public function test_guests_are_redirected_to_login_from_protected_pages(): void
    {
        foreach ([
            '/',
            '/members',
            '/members/create',
            '/worship',
            '/worship/attendance/take',
            '/worship/attendance/admin-take',
            '/worship/attendance/by-member',
            '/worship/attendance/by-worship',
            '/worship/report',
        ] as $path) {
            $this->get($path)
                ->assertRedirect(route('login'));
        }
    }

    public function test_an_unknown_username_is_rejected(): void
    {
        $this->from('/login')
            ->post('/login', ['username' => 'nobody', 'password' => 'secret123'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $this->makeUser();

        $this->from('/login')
            ->post('/login', ['username' => 'tester', 'password' => 'wrong-password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_a_valid_login_reaches_the_dashboard(): void
    {
        $user = $this->makeUser();

        $this->post('/login', ['username' => 'tester', 'password' => 'secret123'])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);

        $this->get('/')->assertOk();
    }

    public function test_logging_in_records_the_last_login_time(): void
    {
        $user = $this->makeUser();

        $this->assertNull($user->last_login_at);

        $this->post('/login', ['username' => 'tester', 'password' => 'secret123']);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_a_legacy_md5_password_is_verified_and_upgraded_to_bcrypt(): void
    {
        // The production database was migrated from a Yii 1.1 application that
        // stored MD5 hashes. verifyPassword() must accept them once and rewrite
        // the stored value as bcrypt.
        $user = $this->makeUser(['password' => md5('secret123')]);

        $this->assertTrue($user->isMd5Password());

        $this->post('/login', ['username' => 'tester', 'password' => 'secret123'])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);

        $stored = $user->fresh()->password;
        $this->assertNotSame(md5('secret123'), $stored, 'the MD5 hash should have been replaced');
        $this->assertStringStartsWith('$2y$', $stored, 'password should have been upgraded to bcrypt');
    }

    public function test_logout_ends_the_session(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
