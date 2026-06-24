<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Mail;

use EmbegeQ\Nutrisi\Config\Repository;
use EmbegeQ\Nutrisi\Container\ApplicationContainer;
use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Contracts\Mail\MailerInterface;
use EmbegeQ\Nutrisi\Mail\Mailer;
use EmbegeQ\Nutrisi\Mail\MailServiceProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;

class MailTest extends TestCase
{
    protected ApplicationContainer $container;
    protected Mailer $mailer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new ApplicationContainer();

        $config = new Repository([
            'mail' => [
                'dsn' => 'null://null',
            ],
        ]);

        $this->container->instance(RepositoryInterface::class, $config);
        $this->container->alias(RepositoryInterface::class, 'config');

        (new MailServiceProvider())->register($this->container);

        $this->mailer = $this->container->get(Mailer::class);
    }

    public function test_mailer_resolves_correctly(): void
    {
        $this->assertInstanceOf(Mailer::class, $this->mailer);
        $this->assertInstanceOf(MailerInterface::class, $this->mailer);
        $this->assertSame($this->mailer, $this->container->get('mailer'));
    }

    public function test_mailer_can_send_raw_email(): void
    {
        $sent = false;

        $this->mailer->raw('Hello World', function (Email $email) use (&$sent) {
            $email->from('sender@example.com');
            $email->to('receiver@example.com');
            $email->subject('Test Subject');
            
            $sent = true;
        });

        $this->assertTrue($sent);
    }

    public function test_mailer_can_send_mailable_object(): void
    {
        $mailable = new class {
            public function build(Email $email): void
            {
                $email->from('sender@example.com');
                $email->to('receiver@example.com');
                $email->subject('Mailable Subject');
                $email->text('Mailable Text');
            }
        };

        $sent = false;

        $this->mailer->send($mailable, [], function (Email $email) use (&$sent) {
            $sent = true;
        });

        $this->assertTrue($sent);
    }
}
