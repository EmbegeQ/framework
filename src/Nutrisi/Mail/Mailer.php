<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Mail;

use EmbegeQ\Nutrisi\Contracts\Mail\MailerInterface;
use RuntimeException;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

class Mailer implements MailerInterface
{
    /**
     * The Symfony Mailer transport instance.
     */
    protected ?TransportInterface $transport = null;

    /**
     * The Symfony Mailer instance.
     */
    protected ?SymfonyMailer $symfonyMailer = null;

    /**
     * Create a new mailer instance.
     *
     * @param array<string, mixed> $config
     */
    public function __construct(protected array $config)
    {
        $this->initialize();
    }

    /**
     * Initialize Symfony mailer transport.
     */
    protected function initialize(): void
    {
        if (!class_exists(SymfonyMailer::class)) {
            return;
        }

        $dsn = (string) ($this->config['dsn'] ?? 'smtp://localhost');
        $this->transport = Transport::fromDsn($dsn);
        $this->symfonyMailer = new SymfonyMailer($this->transport);
    }

    /**
     * {@inheritdoc}
     */
    public function raw(string $text, callable|string $callback): void
    {
        if ($this->symfonyMailer === null) {
            throw new RuntimeException('Symfony Mailer package is missing. Please run composer require symfony/mailer.');
        }

        $email = new Email();
        $email->text($text);

        if (is_callable($callback)) {
            $callback($email);
        } elseif (is_string($callback)) {
            $email->to($callback);
        }

        $this->symfonyMailer->send($email);
    }

    /**
     * {@inheritdoc}
     */
    public function send(string|object $mailable, array $data = [], ?callable $callback = null): void
    {
        if ($this->symfonyMailer === null) {
            throw new RuntimeException('Symfony Mailer package is missing. Please run composer require symfony/mailer.');
        }

        $email = new Email();

        if (is_object($mailable)) {
            if (method_exists($mailable, 'build')) {
                $mailable->build($email);
            }
        } elseif (is_string($mailable)) {
            $email->html($mailable);
        }

        if ($callback !== null) {
            $callback($email);
        }

        $this->symfonyMailer->send($email);
    }

    /**
     * Get the underlying transport.
     */
    public function getTransport(): ?TransportInterface
    {
        return $this->transport;
    }
}
