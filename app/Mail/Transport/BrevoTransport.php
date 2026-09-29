<?php

namespace App\Mail\Transport;

use Illuminate\Http\Client\Factory as HttpFactory;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Throwable;

class BrevoTransport extends AbstractTransport
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $apiKey,
        private readonly string $endpoint,
        private readonly int $timeout,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $payload = array_filter([
            'sender' => $this->address($envelope->getSender()),
            'to' => $this->addresses($this->recipients($email, $envelope)),
            'cc' => $this->addresses($email->getCc()),
            'bcc' => $this->addresses($email->getBcc()),
            'replyTo' => $email->getReplyTo() === [] ? null : $this->address($email->getReplyTo()[0]),
            'subject' => $email->getSubject(),
            'htmlContent' => $email->getHtmlBody(),
            'textContent' => $email->getHtmlBody() === null ? $email->getTextBody() : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);

        try {
            $response = $this->http
                ->withHeaders([
                    'accept' => 'application/json',
                    'api-key' => $this->apiKey,
                ])
                ->timeout($this->timeout)
                ->post($this->endpoint, $payload);

            if (! $response->successful()) {
                throw new TransportException(
                    sprintf(
                        'Brevo rechazó el correo (%d): %s',
                        $response->status(),
                        $response->json('message', 'Respuesta inesperada del proveedor')
                    )
                );
            }

            if ($messageId = $response->json('messageId')) {
                $email->getHeaders()->addTextHeader('X-Brevo-Message-ID', $messageId);
            }
        } catch (TransportException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TransportException(
                'No fue posible conectar con la API de Brevo.',
                0,
                $exception
            );
        }
    }

    /**
     * @return array{email: string, name?: string}
     */
    private function address(Address $address): array
    {
        return array_filter([
            'email' => $address->getAddress(),
            'name' => $address->getName(),
        ]);
    }

    /**
     * @param  Address[]  $addresses
     * @return array<int, array{email: string, name?: string}>
     */
    private function addresses(array $addresses): array
    {
        return array_map(fn (Address $address): array => $this->address($address), $addresses);
    }

    /**
     * @return Address[]
     */
    private function recipients(Email $email, Envelope $envelope): array
    {
        return array_values(array_filter(
            $envelope->getRecipients(),
            fn (Address $address): bool => ! in_array($address, [...$email->getCc(), ...$email->getBcc()], true)
        ));
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
