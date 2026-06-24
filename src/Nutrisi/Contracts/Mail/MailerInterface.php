<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Mail;

/**
 * Mailer interface for EmbegeQ.
 */
interface MailerInterface
{
    /**
     * Send a raw email.
     *
     * @param string $text
     * @param callable|string $callback
     */
    public function raw(string $text, callable|string $callback): void;

    /**
     * Send a mailable email.
     *
     * @param string|object $mailable
     * @param array<string, mixed> $data
     * @param callable|null $callback
     */
    public function send(string|object $mailable, array $data = [], ?callable $callback = null): void;
}
