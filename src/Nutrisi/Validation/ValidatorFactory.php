<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Validation;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Validation\ValidatorFactoryInterface;
use EmbegeQ\Nutrisi\Contracts\Validation\ValidatorInterface;

class ValidatorFactory implements ValidatorFactoryInterface
{
    /**
     * Create a new validator factory instance.
     */
    public function __construct(protected ContainerInterface $container) {}

    /**
     * {@inheritdoc}
     */
    public function make(array $data, array $rules, array $messages = []): ValidatorInterface
    {
        return new Validator($this->container, $data, $rules, $messages);
    }
}
