<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Validation;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Database\ConnectionResolverInterface;
use EmbegeQ\Nutrisi\Contracts\Validation\ValidatorInterface;

class Validator implements ValidatorInterface
{
    /**
     * The validation errors.
     *
     * @var array<string, array<int, string>>
     */
    protected array $errors = [];

    /**
     * Create a new Validator instance.
     *
     * @param ContainerInterface $container
     * @param array<string, mixed> $data
     * @param array<string, array<int, string>|string> $rules
     * @param array<string, string> $messages
     */
    public function __construct(
        protected ContainerInterface $container,
        protected array $data,
        protected array $rules,
        protected array $messages = []
    ) {}

    /**
     * {@inheritdoc}
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * {@inheritdoc}
     */
    public function passes(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $fieldRules) {
            $rulesArray = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            $value = $this->data[$field] ?? null;
            $hasValue = $value !== null && $value !== '';

            // If field is empty and not required, skip all other validations
            if (!$hasValue && !$this->hasRequiredRule($rulesArray)) {
                continue;
            }

            foreach ($rulesArray as $rule) {
                $parameters = [];
                if (str_contains($rule, ':')) {
                    [$rule, $parameterStr] = explode(':', $rule, 2);
                    $parameters = explode(',', $parameterStr);
                }

                $method = 'validate' . ucfirst($rule);

                if (method_exists($this, $method)) {
                    /** @var array<int, string> $parameters */
                    if (!$this->$method($field, $value, $parameters)) {
                        $this->addError($field, $rule, $parameters);
                        break;
                    }
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Determine if required rule exists.
     *
     * @param array<int, string> $rules
     */
    protected function hasRequiredRule(array $rules): bool
    {
        return in_array('required', $rules, true);
    }

    /**
     * {@inheritdoc}
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * {@inheritdoc}
     */
    public function validated(): array
    {
        if ($this->fails()) {
            throw new \RuntimeException('The given data failed validation.');
        }

        $validated = [];

        foreach (array_keys($this->rules) as $field) {
            if (array_key_exists($field, $this->data)) {
                $validated[$field] = $this->data[$field];
            }
        }

        return $validated;
    }

    /**
     * Add an error message for a field.
     *
     * @param string $field
     * @param string $rule
     * @param array<int, string> $parameters
     */
    protected function addError(string $field, string $rule, array $parameters): void
    {
        $messageKey = "{$field}.{$rule}";
        if (isset($this->messages[$messageKey])) {
            $this->errors[$field][] = $this->messages[$messageKey];
            return;
        }

        $this->errors[$field][] = $this->getDefaultMessage($field, $rule, $parameters);
    }

    /**
     * Get default validation error message.
     *
     * @param string $field
     * @param string $rule
     * @param array<int, string> $parameters
     */
    protected function getDefaultMessage(string $field, string $rule, array $parameters): string
    {
        return match ($rule) {
            'required' => "The {$field} field is required.",
            'email' => "The {$field} field must be a valid email address.",
            'numeric' => "The {$field} field must be a number.",
            'min' => "The {$field} field must be at least {$parameters[0]}.",
            'max' => "The {$field} field must not be greater than {$parameters[0]}.",
            'unique' => "The {$field} has already been taken.",
            default => "The {$field} field is invalid.",
        };
    }

    // Rules implementation

    /**
     * Validate required rule.
     *
     * @param string $field
     * @param mixed $value
     * @param array<int, string> $parameters
     * @return bool
     */
    protected function validateRequired(string $field, mixed $value, array $parameters): bool
    {
        return $value !== null && $value !== '';
    }

    /**
     * Validate email rule.
     *
     * @param string $field
     * @param mixed $value
     * @param array<int, string> $parameters
     * @return bool
     */
    protected function validateEmail(string $field, mixed $value, array $parameters): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate numeric rule.
     *
     * @param string $field
     * @param mixed $value
     * @param array<int, string> $parameters
     * @return bool
     */
    protected function validateNumeric(string $field, mixed $value, array $parameters): bool
    {
        return is_numeric($value);
    }

    /**
     * Validate min rule.
     *
     * @param string $field
     * @param mixed $value
     * @param array<int, string> $parameters
     * @return bool
     */
    protected function validateMin(string $field, mixed $value, array $parameters): bool
    {
        $min = (float) ($parameters[0] ?? 0);

        if (is_numeric($value)) {
            return (float) $value >= $min;
        }

        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        return false;
    }

    /**
     * Validate max rule.
     *
     * @param string $field
     * @param mixed $value
     * @param array<int, string> $parameters
     * @return bool
     */
    protected function validateMax(string $field, mixed $value, array $parameters): bool
    {
        $max = (float) ($parameters[0] ?? 0);

        if (is_numeric($value)) {
            return (float) $value <= $max;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        return false;
    }

    /**
     * Validate unique rule.
     *
     * @param string $field
     * @param mixed $value
     * @param array<int, string> $parameters
     * @return bool
     */
    protected function validateUnique(string $field, mixed $value, array $parameters): bool
    {
        $table = $parameters[0] ?? null;
        $column = $parameters[1] ?? $field;

        if ($table === null) {
            throw new \InvalidArgumentException('Unique validation rule requires a table name.');
        }

        if (!$this->container->has(ConnectionResolverInterface::class)) {
            return true; // fail safe if DB not bound
        }

        $resolver = $this->container->get(ConnectionResolverInterface::class);
        $connection = $resolver->connection();

        $count = $connection->table($table)->where($column, $value)->count();

        return $count === 0;
    }
}
