<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Validation;

use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Validation\ValidatorFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class FormRequest
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    abstract public function rules(): array;

    public function authorize(): bool
    {
        return true;
    }

    public function validate(ServerRequestInterface $request): array
    {
        if (!$this->authorize()) {
            throw new \RuntimeException('Form request authorization failed.');
        }

        $data = $this->validationData($request);
        $rules = $this->rules();
        $messages = $this->messages();

        /** @var ValidatorFactoryInterface $factory */
        $factory = $this->container->get(ValidatorFactoryInterface::class);
        $validator = $factory->make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new \RuntimeException(json_encode($validator->errors(), JSON_THROW_ON_ERROR));
        }

        return $validator->validated();
    }

    protected function validationData(ServerRequestInterface $request): array
    {
        return array_merge(
            $request->getParsedBody() ?? [],
            $request->getQueryParams(),
            $request->getUploadedFiles()
        );
    }

    protected function messages(): array
    {
        return [];
    }
}
