<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Contracts\Validation;

/**
 * Validator interface for EmbegeQ.
 */
interface ValidatorInterface
{
    /**
     * Determine if the data fails validation.
     */
    public function fails(): bool;

    /**
     * Determine if the data passes validation.
     */
    public function passes(): bool;

    /**
     * Get all of the validation error messages.
     *
     * @return array<string, array<int, string>>
     */
    public function errors(): array;

    /**
     * Get the validated data.
     *
     * @return array<string, mixed>
     */
    public function validated(): array;
}
