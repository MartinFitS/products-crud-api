<?php

namespace App\Services;

use App\Exceptions\InactiveUserException;
use App\Mail\ResetPasswordMail;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthService
{
    /**
     * @return array{user: User, token: string, token_type: string}|null
     */
    public function login(string $email, string $password): ?array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        if ($user->is_active === false) {
            throw new InactiveUserException;
        }

        return [
            'user' => $user,
            'token' => $user->createToken('api-token')->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function forgotPassword(string $email): void
    {
        if (! User::where('email', $email)->exists()) {
            return;
        }

        PasswordResetToken::where('email', $email)->delete();

        $plainToken = bin2hex(random_bytes(32));

        PasswordResetToken::create([
            'email' => $email,
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes((int) config('auth.passwords.users.expire', 30)),
            'used_at' => null,
        ]);

        $resetUrl = rtrim((string) config('app.frontend_url'), '/')
            .'/reset-password?'.http_build_query([
                'token' => $plainToken,
                'email' => $email,
            ]);

        Mail::to($email)->send(new ResetPasswordMail($resetUrl));
    }

    public function resetPassword(string $email, string $plainToken, string $password): bool
    {
        $resetToken = PasswordResetToken::where('email', $email)
            ->whereNull('used_at')
            ->latest('created_at')
            ->first();

        if (! $resetToken
            || $resetToken->expires_at->isPast()
            || ! hash_equals($resetToken->token, hash('sha256', $plainToken))) {
            return false;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return false;
        }

        $claimed = PasswordResetToken::query()
            ->whereKey($resetToken->getKey())
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        if ($claimed !== 1) {
            return false;
        }

        $user->update(['password' => $password]);
        $user->tokens()->delete();

        return true;
    }
}
