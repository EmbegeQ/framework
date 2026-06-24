<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Validation;

/**
 * Validator factory interface for EmbegeQ.
 */
interface ValidatorFactoryInterface
{
    /**
     * Create a new Validator instance.
     *
     * @param array<string, mixed> $data
     * @param array<string, array<int, string>|string> $rules
     * @param array<string, string> $messages
     * @return ValidatorInterface
     */
    public function make(array $data, array $rules, array $messages = []): ValidatorInterface;
}
