<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Session\Middleware;

use EmbegeQ\Nutrisi\Contracts\Config\RepositoryInterface;
use EmbegeQ\Nutrisi\Contracts\Container\ContainerInterface;
use EmbegeQ\Nutrisi\Contracts\Session\SessionInterface;
use EmbegeQ\Nutrisi\Session\SessionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 Middleware to bootstrap and finalize the request-scoped Session.
 */
class StartSessionMiddleware implements MiddlewareInterface
{
    /**
     * Create a new StartSessionMiddleware instance.
     */
    public function __construct(
        protected SessionManager $sessionManager,
        protected ContainerInterface $container
    ) {}

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var RepositoryInterface $config */
        $config = $this->container->get('config');
        $cookieName = (string) $config->get('session.cookie', 'embegeq_session');

        // Resolve a fresh, request-scoped Session Store.
        $session = $this->sessionManager->driver();

        // Detect session ID from cookies.
        $cookies = $request->getCookieParams();
        if (isset($cookies[$cookieName]) && is_string($cookies[$cookieName])) {
            $session->setId($cookies[$cookieName]);
        }

        // Start session (reads from handler, generates CSRF token).
        $session->start();

        // Bind the active Session Store into the RequestContainer so it is available
        // to controllers and other services via dependency injection.
        $requestContainer = $request->getAttribute(ContainerInterface::class);
        if ($requestContainer instanceof ContainerInterface) {
            $requestContainer->instance(SessionInterface::class, $session);
            $requestContainer->alias(SessionInterface::class, 'session');
        }

        // Add the Session to the request attributes for convenient access.
        $request = $request->withAttribute('session', $session);

        // Process request pipeline.
        $response = $handler->handle($request);

        // Save session attributes back to persistent handler.
        $session->save();

        // Attach Session cookie to response headers.
        return $this->addCookieToResponse($response, $session, $config, $cookieName);
    }

    /**
     * Format and add the Session cookie to the response.
     */
    protected function addCookieToResponse(
        ResponseInterface $response,
        SessionInterface $session,
        RepositoryInterface $config,
        string $cookieName
    ): ResponseInterface {
        $lifetime = (int) $config->get('session.lifetime', 120);
        $expires = time() + ($lifetime * 60);

        $path = (string) $config->get('session.path', '/');
        $domain = (string) $config->get('session.domain', '');
        $secure = $config->get('session.secure', false) ? '; Secure' : '';
        $httpOnly = $config->get('session.http_only', true) ? '; HttpOnly' : '';
        $sameSite = (string) $config->get('session.same_site', 'lax');

        $cookieValue = $session->getId();
        $cookieString = sprintf(
            '%s=%s; Expires=%s; Path=%s%s%s%s%s',
            $cookieName,
            $cookieValue,
            gmdate('D, d M Y H:i:s T', $expires),
            $path,
            $domain !== '' ? '; Domain=' . $domain : '',
            $secure,
            $httpOnly,
            $sameSite !== '' ? '; SameSite=' . $sameSite : ''
        );

        return $response->withAddedHeader('Set-Cookie', $cookieString);
    }
}
