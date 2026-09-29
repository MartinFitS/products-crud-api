<?php

namespace Tests\Feature\Auth;

use App\Mail\ResetPasswordMail;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthTest extends TestCase
{
    private ?User $user = null;

    protected function setUp(): void
    {
        parent::setUp();

        $id = (string) Str::uuid();
        $this->user = User::create([
            'code' => 'TEST-'.Str::upper(Str::random(8)),
            'name' => 'Auth Test User',
            'email' => "auth-{$id}@example.test",
            'phone' => null,
            'photo' => null,
            'password' => 'InitialPassword123!',
            'profile_ids' => [],
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->user) {
            PasswordResetToken::where('email', $this->user->email)->delete();
            $this->user->tokens()->delete();
            $this->user->delete();
        }

        parent::tearDown();
    }

    public function test_login_with_valid_credentials_returns_user_and_token(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'InitialPassword123!',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $this->user->email)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonMissingPath('data.user.password');

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertSame(1, $this->user->tokens()->count());
    }

    public function test_login_rejects_an_incorrect_password(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'IncorrectPassword123!',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonMissingPath('data.token');
    }

    public function test_login_rejects_an_unknown_email(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'unknown@example.test',
            'password' => 'InitialPassword123!',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'No autenticado.',
                'errors' => null,
            ]);
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $token = $this->user->createToken('auth-test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $this->user->email)
            ->assertJsonMissingPath('data.password');
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $currentToken = $this->user->createToken('current')->plainTextToken;
        $this->user->createToken('other');

        $this->withToken($currentToken)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, $this->user->tokens()->count());
        $this->assertSame('other', $this->user->tokens()->first()->name);

        // Request guards cache the authenticated user inside the test application.
        $this->app['auth']->forgetGuards();

        $this->withToken($currentToken)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_forgot_password_validates_the_email(): void
    {
        $this->postJson('/api/auth/forgot-password', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('email');
    }

    public function test_forgot_password_stores_only_a_hash_and_sends_the_plain_token_in_the_link(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/forgot-password', ['email' => $this->user->email])
            ->assertOk();

        $storedToken = PasswordResetToken::where('email', $this->user->email)->firstOrFail();

        Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail): bool {
            parse_str((string) parse_url($mail->resetUrl, PHP_URL_QUERY), $query);

            return ($query['email'] ?? null) === $this->user->email
                && hash_equals(
                    PasswordResetToken::where('email', $this->user->email)->value('token'),
                    hash('sha256', $query['token'] ?? '')
                );
        });

        $this->assertSame(64, strlen($storedToken->token));
        $this->assertNotNull($storedToken->expires_at);
    }

    public function test_forgot_password_does_not_reveal_an_unknown_email(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/forgot-password', ['email' => 'unknown@example.test'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.'
            );

        Mail::assertNothingSent();
    }

    public function test_reset_password_rejects_an_invalid_token(): void
    {
        $this->createResetToken('valid-token');

        $this->postJson('/api/auth/reset-password', $this->resetPayload('invalid-token'))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_reset_password_rejects_an_expired_token(): void
    {
        $this->createResetToken('expired-token', now()->subMinute());

        $this->postJson('/api/auth/reset-password', $this->resetPayload('expired-token'))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_reset_password_updates_the_hash_consumes_the_token_and_revokes_sessions(): void
    {
        $this->user->createToken('existing-session');
        $resetToken = $this->createResetToken('valid-token');

        $this->postJson('/api/auth/reset-password', $this->resetPayload('valid-token'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->user->refresh();
        $resetToken->refresh();

        $this->assertTrue(Hash::check('NewPassword123!', $this->user->password));
        $this->assertNotNull($resetToken->used_at);
        $this->assertSame(0, $this->user->tokens()->count());

        $this->postJson('/api/auth/reset-password', $this->resetPayload('valid-token'))
            ->assertUnprocessable();
    }

    private function createResetToken(string $plainToken, mixed $expiresAt = null): PasswordResetToken
    {
        return PasswordResetToken::create([
            'email' => $this->user->email,
            'token' => hash('sha256', $plainToken),
            'expires_at' => $expiresAt ?? now()->addMinutes(30),
            'used_at' => null,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function resetPayload(string $token): array
    {
        return [
            'email' => $this->user->email,
            'token' => $token,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ];
    }
}
