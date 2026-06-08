<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Middleware;

use ForestCityLabs\Framework\Security\Manager\AccessTokenManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BearerTokenAuthenticationMiddleware extends AbstractAuthenticationMiddleware
{
    public function __construct(
        private AccessTokenManagerInterface $token_manager,
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
            // Get the header and strip the "Bearer" part.
            $token = substr($request->getHeader('Authorization')[0], 7);

            // Attempt to lookup the access token.
            if (null === $access_token = $this->token_manager->findAccessToken($token)) {
                return $handler->handle($request);
            }

            // Dispatch request with token attached.
            return $handler->handle($this->dispatchRequestWithToken($request, $access_token));
        }

        // Dispatch the request.
        return $handler->handle($request);
    }
}
