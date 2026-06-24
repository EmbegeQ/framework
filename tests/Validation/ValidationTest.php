<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Validation;

use EmbegeQ\Nutrisi\Config\Repository;
use EmbegeQ\Nutrisi\Container\ApplicationContainer;
use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Contracts\Database\ConnectionResolverInterface;
use EmbegeQ\Nutrisi\Contracts\Validation\ValidatorFactoryInterface;
use EmbegeQ\Nutrisi\Database\DatabaseServiceProvider;
use EmbegeQ\Nutrisi\Validation\ValidationServiceProvider;
use EmbegeQ\Nutrisi\Validation\ValidatorFactory;
use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    protected ApplicationContainer $container;
    protected ValidatorFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new ApplicationContainer();

        $config = new Repository([
            'database' => [
                'default' => 'sqlite',
                'connections' => [
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'database' => ':memory:',
                    ],
                ],
            ],
        ]);

        $this->container->instance(RepositoryInterface::class, $config);
        $this->container->alias(RepositoryInterface::class, 'config');

        (new DatabaseServiceProvider())->register($this->container);
        (new ValidationServiceProvider())->register($this->container);

        $this->factory = $this->container->get(ValidatorFactory::class);

        // Set up a mock users table for unique validation tests
        $db = $this->container->get(ConnectionResolverInterface::class);
        $db->connection()->unprepared("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email VARCHAR(255) NOT NULL
            )
        ");
        $db->connection()->table('users')->insert(['email' => 'taken@example.com']);
    }

    public function test_factory_resolves_correctly(): void
    {
        $this->assertInstanceOf(ValidatorFactory::class, $this->factory);
        $this->assertInstanceOf(ValidatorFactoryInterface::class, $this->factory);
        $this->assertSame($this->factory, $this->container->get('validator'));
    }

    public function test_validation_rules_work(): void
    {
        // 1. Passes
        $validator = $this->factory->make(
            ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 25],
            ['name' => 'required|min:3', 'email' => 'required|email|unique:users,email', 'age' => 'numeric|max:100']
        );

        $this->assertTrue($validator->passes());
        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors());
        $this->assertSame(['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 25], $validator->validated());

        // 2. Fails required
        $validator2 = $this->factory->make(
            ['name' => ''],
            ['name' => 'required']
        );
        $this->assertTrue($validator2->fails());
        $this->assertSame(['name' => ['The name field is required.']], $validator2->errors());

        // 3. Fails email and unique
        $validator3 = $this->factory->make(
            ['email' => 'taken@example.com'],
            ['email' => 'required|email|unique:users']
        );
        $this->assertTrue($validator3->fails());
        $this->assertSame(['email' => ['The email has already been taken.']], $validator3->errors());

        // 4. Fails min/max
        $validator4 = $this->factory->make(
            ['name' => 'Al', 'age' => 150],
            ['name' => 'min:3', 'age' => 'max:100']
        );
        $this->assertTrue($validator4->fails());
        $this->assertCount(2, $validator4->errors());
    }

    public function test_custom_messages_work(): void
    {
        $validator = $this->factory->make(
            ['email' => 'invalid-email'],
            ['email' => 'email'],
            ['email.email' => 'Custom Email Error!']
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(['email' => ['Custom Email Error!']], $validator->errors());
    }
}
