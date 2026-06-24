<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Mail;

use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ServiceProviderInterface;
use EmbegeQ\Nutrisi\Contracts\Mail\MailerInterface;

class MailServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $app): void
    {
        $app->singleton(Mailer::class, function (ContainerInterface $container) {
            /** @var RepositoryInterface $config */
            $config = $container->get(RepositoryInterface::class);
            $mailConfig = (array) $config->get('mail', []);

            return new Mailer($mailConfig);
        });

        $app->singleton(MailerInterface::class, function (ContainerInterface $container) {
            return $container->get(Mailer::class);
        });

        $app->alias(Mailer::class, 'mailer');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $app): void
    {
        //
    }
}
