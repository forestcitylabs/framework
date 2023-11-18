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
use ForestCityLabs\Framework\Security\Repository\AccessTokenRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BearerTokenAuthenticationMiddleware
{
    public function __construct(
        private AccessTokenRepositoryInterface $repo,
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
        // If the request includes an authorization header.
        if (
            $request->hasHeader('Authorization')
            && 1 == count($request->getHeader('Authorization'))
            && str_starts_with($request->getHeader('Authorization')[0], 'Bearer ')
            && null === $request->getAttribute('_access_token')
        ) {
            $now = new DateTimeImmutable();

            // Get the header and strip the "Bearer" part.
            $token = substr($request->getHeader('Authorization')[0], 7);

            // Attempt to lookup the access token.
            if (null === $access_token = $this->repo->findToken($token)) {
                return $handler->handle($request);
            }

            // Check that the token is not expired.
            if ($access_token->getExpiry() > $now) {
                // Attach the token to the request.
                $request = $request->withAttribute('_access_token', $access_token);
            }
        }

        // Dispatch the request.
        return $handler->handle($request);
    }
}
