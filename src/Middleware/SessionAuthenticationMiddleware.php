<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Middleware;

use DateTimeImmutable;
use Dflydev\FigCookies\Cookies;
use Dflydev\FigCookies\SetCookie;
use Doctrine\ORM\EntityManagerInterface;
use ForestCityLabs\Framework\Security\AccessTokenManagerInterface;
use ForestCityLabs\Framework\Security\Repository\AccessTokenRepositoryInterface;
use ForestCityLabs\Framework\Security\Repository\RefreshTokenRepositoryInterface;
use ForestCityLabs\Framework\Session\Session;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionAuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AccessTokenRepositoryInterface $access_token_repo,
        private RefreshTokenRepositoryInterface $refresh_token_repo,
        private AccessTokenManagerInterface $token_manager,
        private EntityManagerInterface $em,
        private string $path_regex = '/.*/'
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Enable this if we match the path.
        if (1 === preg_match($this->path_regex, $request->getUri()->getPath())) {
            // Get the session from the request.
            if (null === $session = $request->getAttribute('_session')) {
                throw new LogicException('To use session authentication you must enable the session middleware.');
            }

            // Ensure we have what we think we have.
            assert($session instanceof Session);

            // Get the current date/time.
            $now = new DateTimeImmutable();

            // Get the token from the session if it exists.
            if (null !== $token = $session->getValue('_access_token')) {
                // Attempt to lookup the access token.
                if (null !== $access_token = $this->access_token_repo->findToken($token)) {
                    // Check that the token is not expired.
                    if (null === $access_token->getExpiry() || $access_token->getExpiry() > $now) {
                        // Attach the token to the request.
                        return $handler->handle(
                            $request->withAttribute('_access_token', $access_token)
                        );
                    }
                }
            }

            // Check for a refresh token.
            if (null !== $token = Cookies::fromRequest($request)->get('_refresh_token')) {
                // Attempt to lookup the refresh token.
                if (null !== $refresh_token = $this->refresh_token_repo->findToken($token->getValue())) {
                    // Check that the token is not expired.
                    if (null === $refresh_token->getExpiry() || $refresh_token->getExpiry() > $now) {
                        // Exchange the refresh token and generate a new one.
                        $access_token = $this->token_manager->exchangeRefreshToken($refresh_token);
                        $refresh_token = $this->token_manager->generateRefreshToken($access_token);

                        // Store the access and refresh tokens.
                        $session->setValue('_access_token', $access_token->getToken());
                        $cookie = SetCookie::create('_refresh_token', $refresh_token->getToken())
                            ->withExpires($refresh_token->getExpiry())
                            ->withPath('/')
                            ->withSecure()
                            ->withHttpOnly();

                        // Attach the token to the request, store refresh token.
                        return $handler->handle(
                            $request->withAttribute('_access_token', $access_token)
                        )->withAddedHeader('set-cookie', (string) $cookie);
                    }
                }
            }
        }

        // Dispatch the request.
        return $handler->handle($request);
    }
}
