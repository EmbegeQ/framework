# EmbegeQ Framework

<p align="center">
<a href="https://github.com/EmbegeQ/framework/actions"><img src="https://github.com/EmbegeQ/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/embegeq/framework"><img src="https://img.shields.io/packagist/v/embegeq/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/embegeq/framework"><img src="https://img.shields.io/packagist/l/embegeq/framework" alt="License"></a>
</p>

## About EmbegeQ Framework

EmbegeQ Framework is the core kernel of the EmbegeQ PHP ecosystem. It provides a memory-safe, PSR-compliant foundation specifically engineered for **stateful PHP runtimes** such as FrankenPHP, RoadRunner, and Swoole.

Unlike traditional PHP frameworks built around the shared-nothing PHP-FPM model, EmbegeQ solves critical architectural flaws that emerge when running PHP in a persistent event loop:

- **Memory Leak Prevention** through a strict Dual-Scope Dependency Injection container.
- **State Bleeding Protection** by isolating `ApplicationScope` (boot-once singletons) from `RequestScope` (per-request instances).
- **PSR Bridge Architecture** that wraps battle-tested libraries (`nyholm/psr7`, `nikic/fast-route`, `monolog/monolog`) behind unified, cohesive Contracts.

## The Nutrisi Component Layer

EmbegeQ's internal components live under the `EmbegeQ\Nutrisi\` namespace (analogous to Laravel's `Illuminate\`). The name **Nutrisi** reflects the framework's "Nutritional Architecture" philosophy: each component is a distinct nutrient that the application absorbs to function at peak performance.

```text
src/Nutrisi/
├── Auth/           Guards, User Providers
├── Broadcasting/   Broadcaster Interfaces
├── Bus/            Command Bus, Dispatcher
├── Cache/          CacheManager (PSR-16)
├── Collections/    LazyCollections, Arrays
├── Config/         Repository, FileLoader
├── Console/        CLI Application
├── Container/      Dual-Scope PSR-11 DI
├── Contracts/      Unified Framework Interfaces
├── Cookie/         CookieJar, Encryption Middleware
├── Database/       ConnectionManager, Query Builder
├── Encryption/     OpenSSL/Sodium Encrypter
├── Events/         PSR-14 Event Dispatcher
├── Filesystem/     FilesystemManager (Flysystem)
├── Foundation/     Application Kernel, Bootstrappers
├── Hashing/        Bcrypt, Argon Hashers
├── Http/           PSR-15 Middleware Pipeline
├── Log/            LogManager (Monolog)
├── Mail/           Mailer (Symfony Mailer)
├── Pagination/     Paginator, CursorPaginator
├── Pipeline/       Generic Task Pipeline
├── Queue/          QueueManager, Worker
├── Redis/          RedisManager
├── Routing/        Router (FastRoute)
├── Session/        Stateful-Safe SessionManager
├── Support/        Helpers, ServiceProviders
├── Translation/    Translator, FileLoader
├── Validation/     ValidatorFactory, Rules
└── View/           ViewFactory, Compilers
```

## Requirements

- PHP >= 8.4
- ext-ctype
- ext-mbstring
- ext-openssl

## Installation

```bash
composer require embegeq/framework
```

## Security Vulnerabilities

If you discover a security vulnerability within EmbegeQ Framework, please send an email to the security team at **security@embegeq.dev.dyzulk.com**. All security vulnerabilities will be promptly addressed.

## License

The EmbegeQ Framework is open-sourced software licensed under the [MIT license](LICENSE.md).
