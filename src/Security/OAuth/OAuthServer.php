<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\OAuth;

use ForestCityLabs\Framework\Security\Exception\OAuthException;
use ForestCityLabs\Framework\Security\Model\UserInterface;
use ForestCityLabs\Framework\Security\OAuth\Grant\AuthorizationCodeGrant;
use ForestCityLabs\Framework\Security\OAuth\Storage\AuthRequestStorageInterface;
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
        protected AuthRequestStorageInterface $auth_request_storage,
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

                return $this
                    ->auth_request_storage
                    ->storeAuthRequest($request, $auth_request)
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
                if (null === $auth_request = $this->auth_request_storage->getAuthRequest($request)) {
                    return $this->rf->createResponse(400)->withBody(
                        $this->sf->createStream('No authorization request found.')
                    );
                }

                // Create the authorization code.
                try {
                    $code = $grant->approveAuthorizationRequest($auth_request, $request, $granted_scopes, $user);
                } catch (OAuthException $e) {
                    return $this->rf->createResponse(400)->withBody(
                        $this->sf->createStream($e->getMessage())
                    );
                }

                $response = $this->rf->createResponse(302)
                    ->withHeader(
                        'Location',
                        $auth_request->getRedirectUri()
                            . '?code='
                            . urlencode($code->getCode())
                            . '&state='
                            . urlencode($auth_request->getState())
                    );

                // Remove auth request.
                $this->auth_request_storage->removeAuthRequest($request);
                return $response;
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
                    $token_response = $grant->handleTokenRequest($request);
                } catch (OAuthException $e) {
                    return $this->rf->createResponse(400)->withBody(
                        $this->sf->createStream($e->getMessage())
                    );
                }

                // Create a response.
                return $this->rf->createResponse()
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody(
                        $this->sf->createStream(json_encode($token_response->formatResponse(), JSON_THROW_ON_ERROR))
                    );
            }
        }

        // If no grant can handle the request, return a 400 Bad Request response.
        return $this->rf->createResponse(400)->withBody(
            $this->sf->createStream('No valid grant found to handle the token request.')
        );
    }
}
