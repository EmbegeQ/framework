<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Log;

use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger as MonologLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Log Manager implementing PSR-3 LoggerInterface.
 */
class LogManager implements LoggerInterface
{
    use LoggerTrait;

    /**
     * The resolved logger channels.
     *
     * @var array<string, LoggerInterface>
     */
    protected array $channels = [];

    /**
     * Custom logger creators.
     *
     * @var array<string, callable>
     */
    protected array $customCreators = [];

    /**
     * Create a new LogManager instance.
     */
    public function __construct(protected ContainerInterface $container) {}

    /**
     * Get a logger channel instance by name.
     */
    public function channel(?string $channel = null): LoggerInterface
    {
        $channel = $channel ?: $this->getDefaultDriver();

        return $this->channels[$channel] ??= $this->resolve($channel);
    }

    /**
     * Get a logger driver instance by name.
     */
    public function driver(?string $driver = null): LoggerInterface
    {
        return $this->channel($driver);
    }

    /**
     * Resolve the given logger channel.
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve(string $channel): LoggerInterface
    {
        if (isset($this->customCreators[$channel])) {
            return ($this->customCreators[$channel])($this->container);
        }

        /** @var RepositoryInterface $config */
        $config = $this->container->get('config');
        $channelConfig = $config->get("logging.channels.{$channel}");

        if (!$channelConfig) {
            if ($channel === 'single') {
                $channelConfig = ['driver' => 'single'];
            } else {
                throw new \InvalidArgumentException("Logging channel [{$channel}] is not configured.");
            }
        }

        $driver = $channelConfig['driver'] ?? 'single';
        $method = 'create' . ucfirst($driver) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method($channel, $channelConfig);
        }

        throw new \InvalidArgumentException("Logging driver [{$driver}] not supported.");
    }

    /**
     * Create an instance of the "single" log driver.
     *
     * @param array<string, mixed> $config
     */
    protected function createSingleDriver(string $name, array $config): LoggerInterface
    {
        $path = $config['path'] ?? null;
        if (!$path) {
            $basePath = $this->container->has('app') ? $this->container->get('app')->basePath() : sys_get_temp_dir();
            $path = rtrim($basePath, '\/') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'embegeq.log';
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $level = $config['level'] ?? 'debug';
        $logger = new MonologLogger($name);
        $logger->pushHandler(new StreamHandler($path, $level));

        return $logger;
    }

    /**
     * Create an instance of the "daily" log driver.
     *
     * @param array<string, mixed> $config
     */
    protected function createDailyDriver(string $name, array $config): LoggerInterface
    {
        $path = $config['path'] ?? null;
        if (!$path) {
            $basePath = $this->container->has('app') ? $this->container->get('app')->basePath() : sys_get_temp_dir();
            $path = rtrim($basePath, '\/') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'embegeq.log';
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $level = $config['level'] ?? 'debug';
        $days = (int) ($config['days'] ?? 7);

        $logger = new MonologLogger($name);
        $logger->pushHandler(new RotatingFileHandler($path, $days, $level));

        return $logger;
    }

    /**
     * Create an instance of the "syslog" log driver.
     *
     * @param array<string, mixed> $config
     */
    protected function createSyslogDriver(string $name, array $config): LoggerInterface
    {
        $level = $config['level'] ?? 'debug';
        $logger = new MonologLogger($name);
        $logger->pushHandler(new SyslogHandler('embegeq', LOG_USER, $level));

        return $logger;
    }

    /**
     * Get the default logging driver name.
     */
    public function getDefaultDriver(): string
    {
        /** @var RepositoryInterface $config */
        $config = $this->container->get('config');

        return (string) $config->get('logging.default', 'single');
    }

    /**
     * Register a custom driver creator closure.
     */
    public function extend(string $driver, callable $callback): void
    {
        $this->customCreators[$driver] = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->channel()->log($level, $message, $context);
    }
}
