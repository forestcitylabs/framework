<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\OAuth;

use Dflydev\FigCookies\Cookies;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\SetCookie;
use ForestCityLabs\Framework\Security\Exception\OAuthException;
use ForestCityLabs\Framework\Security\Model\UserInterface;
use ForestCityLabs\Framework\Security\OAuth\Grant\AuthorizationCodeGrant;
use ForestCityLabs\Framework\Utility\EncryptionService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class OAuthServer
{
    public function __construct(
        protected ResponseFactoryInterface $rf,
        protected StreamFactoryInterface $sf,
        protected EncryptionService $encryption_service,
        protected OAuthScopeRegistry $scope_registry,
        protected array $grants = [],
        protected string $cookie_key = '_oauth_session',
    ) {
    }

    public function handleAuthorizationRequest(ServerRequestInterface $request, string $redirect): ResponseInterface
    {
        // Check if any grant can handle the authorization request.
        foreach ($this->grants as $grant) {
            if ($grant->canHandleAuthorizationRequest($request)) {
                try {
                    $auth_request = $grant->handleAuthorizationRequest($request);
                } catch (OAuthException $e) {
                    // If the grant cannot handle the request, return a 400 Bad Request response.
                    return $this->rf->createResponse(400)->withBody(
                        $this->sf->createStream($e->getMessage())
                    );
                }

                // Create a cookie to store the encrypted authorization request.
                $set_cookie = SetCookie::create(
                    $this->cookie_key,
                    $this->encryption_service->encrypt(serialize($auth_request), $this->cookie_key)
                )
                    ->withHttpOnly(true)
                    ->withSecure($request->getUri()->getScheme() === 'https')
                    ->withSameSite(SameSite::strict())
                    ->withMaxAge($auth_request->getExpiresAt()->getTimestamp() - time());

                // Return the response with the cookie set.
                return FigResponseCookies::set($this->rf->createResponse(), $set_cookie)
                    ->withStatus(302)
                    ->withHeader('Location', $redirect);
            }
        }

        // If no grant can handle the request, return a 400 Bad Request response.
        return $this->rf->createResponse(400)->withBody(
            $this->sf->createStream('No valid grant found to handle the authorization request.')
        );
    }

    public function approveAuthorizationRequest(
        ServerRequestInterface $request,
        UserInterface $user,
        ?array $granted_scopes = null,
    ): ResponseInterface {
        // Check if any grant can approve the authorization request.
        foreach ($this->grants as $grant) {
            if ($grant instanceof AuthorizationCodeGrant) {
                // Must have an active authorization request to respond.
                if (null === $auth_request = $this->getAuthorizationRequest($request)) {
                    return $this->rf->createResponse(400)->withBody(
                        $this->sf->createStream('No authorization request found.')
                    );
                }

                // Get the authorization code.
                try {
                    $code = $grant->approveAuthorizationRequest($auth_request, $request, $granted_scopes, $user);
                } catch (OAuthException $e) {
                    return $this->rf->createResponse(400)->withBody(
                        $this->sf->createStream($e->getMessage())
                    );
                }

                return $this->rf->createResponse(302)
                    ->withHeader(
                        'Location',
                        $auth_request->getRedirectUri()
                            . '?code='
                            . urlencode($code->getCode())
                            . '&state='
                            . urlencode($auth_request->getState())
                    );
            }
        }

        // If no grant can handle the request, return a 400 Bad Request response.
        return $this->rf->createResponse(400)->withBody(
            $this->sf->createStream('No valid grant found to approve the authorization request.')
        );
    }

    public function handleTokenRequest(ServerRequestInterface $request): ResponseInterface
    {
        // Check if any grant can handle the token request.
        foreach ($this->grants as $grant) {
            if ($grant->canHandleTokenRequest($request)) {
                // Get the access and refresh tokens.
                try {
                    $token_response = $grant->handleTokenRequest($request, $this->getAuthorizationRequest($request));
                } catch (OAuthException $e) {
                    return $this->rf->createResponse(400)->withBody(
                        $this->sf->createStream($e->getMessage())
                    );
                }

                // Create a response.
                $response = $this->rf->createResponse(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody(
                        $this->sf->createStream(json_encode($token_response->formatResponse(), JSON_THROW_ON_ERROR))
                    );

                // If we have an authorization request expire that now.
                if (null !== $this->getAuthorizationRequest($request)) {
                    // Remove the cookie for the authorization request.
                    $response = FigResponseCookies::set(
                        $response,
                        SetCookie::create($this->cookie_key)->expire()
                    );
                }

                return $response;
            }
        }

        // If no grant can handle the request, return a 400 Bad Request response.
        return $this->rf->createResponse(400)->withBody(
            $this->sf->createStream('No valid grant found to handle the token request.')
        );
    }

    public function getAuthorizationRequest(ServerRequestInterface $request): ?AuthRequest
    {
        // Get cookies for this request.
        $cookies = Cookies::fromRequest($request);
        if ($cookies->has($this->cookie_key)) {
            // Decrypt the cookie value.
            return unserialize(
                $this->encryption_service->decrypt(
                    $cookies->get($this->cookie_key)->getValue(),
                    $this->cookie_key
                )
            );
        }

        // No cookie found, return null.
        return null;
    }
}
