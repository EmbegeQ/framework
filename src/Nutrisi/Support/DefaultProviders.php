<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Support;

/**
 * Default framework-level service providers.
 *
 * This class contains the canonical list of service providers that ship
 * with the EmbegeQ framework. They are registered automatically by
 * Application::registerConfiguredProviders() and do NOT need to be
 * listed in the application's bootstrap/providers.php file.
 *
 * Use merge(), replace(), and except() to customize the list if needed.
 */
class DefaultProviders
{
    /**
     * The current providers.
     *
     * @var array<int, class-string>
     */
    protected array $providers;

    /**
     * Create a new default provider collection.
     *
     * @param  array<int, class-string>|null  $providers
     */
    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ?? [
            \EmbegeQ\Nutrisi\Cache\CacheServiceProvider::class,
            \EmbegeQ\Nutrisi\Database\DatabaseServiceProvider::class,
            \EmbegeQ\Nutrisi\Events\EventServiceProvider::class,
            \EmbegeQ\Nutrisi\Filesystem\FilesystemServiceProvider::class,
            \EmbegeQ\Nutrisi\Log\LogServiceProvider::class,
            \EmbegeQ\Nutrisi\Mail\MailServiceProvider::class,
            \EmbegeQ\Nutrisi\Queue\QueueServiceProvider::class,
            \EmbegeQ\Nutrisi\Session\SessionServiceProvider::class,
            \EmbegeQ\Nutrisi\Validation\ValidationServiceProvider::class,
        ];
    }

    /**
     * Merge the given providers into the provider collection.
     *
     * @param  array<int, class-string>  $providers
     * @return static
     */
    public function merge(array $providers): static
    {
        $this->providers = array_merge($this->providers, $providers);

        return new static($this->providers);
    }

    /**
     * Replace the given providers with other providers.
     *
     * @param  array<class-string, class-string>  $replacements
     * @return static
     */
    public function replace(array $replacements): static
    {
        $current = $this->providers;

        foreach ($replacements as $from => $to) {
            $key = array_search($from, $current, true);

            if ($key !== false) {
                $current[$key] = $to;
            }
        }

        return new static(array_values($current));
    }

    /**
     * Disable the given providers.
     *
     * @param  array<int, class-string>  $providers
     * @return static
     */
    public function except(array $providers): static
    {
        return new static(array_values(
            array_filter($this->providers, fn (string $p): bool => !in_array($p, $providers, true))
        ));
    }

    /**
     * Convert the provider collection to an array.
     *
     * @return array<int, class-string>
     */
    public function toArray(): array
    {
        return $this->providers;
    }
}
