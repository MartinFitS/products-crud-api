<?php

namespace Tests\Unit\Mail;

use App\Mail\ResetPasswordMail;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BrevoTransportTest extends TestCase
{
    public function test_it_sends_a_mailable_through_the_brevo_http_api(): void
    {
        config([
            'mail.from.address' => 'verified@example.com',
            'mail.from.name' => 'TAP Admission',
            'services.brevo.key' => 'test-api-key',
            'services.brevo.endpoint' => 'https://api.brevo.test/v3/smtp/email',
            'services.brevo.timeout' => 5,
        ]);

        Http::fake([
            'api.brevo.test/*' => Http::response(['messageId' => 'brevo-message-id'], 201),
        ]);

        Mail::mailer('brevo')
            ->to('recipient@example.com')
            ->send(new ResetPasswordMail('https://frontend.test/reset-password?token=secret'));

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.brevo.test/v3/smtp/email'
                && $request->hasHeader('api-key', 'test-api-key')
                && $request['sender'] === [
                    'email' => 'verified@example.com',
                    'name' => 'TAP Admission',
                ]
                && $request['to'] === [['email' => 'recipient@example.com']]
                && $request['subject'] === 'Restablece tu contraseña'
                && str_contains($request['htmlContent'], 'https://frontend.test/reset-password');
        });
    }
}
