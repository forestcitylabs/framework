<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\OAuth\Storage;

use Dflydev\FigCookies\Cookies;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\SetCookie;
use Psr\Http\Message\ServerRequestInterface;
use ForestCityLabs\Framework\Security\OAuth\AuthRequest;
use ForestCityLabs\Framework\Utility\EncryptionService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Twig\Error\RuntimeError;

class CookieAuthRequestStorage implements AuthRequestStorageInterface
{
    public const COOKIE_KEY = '_oauth_session';

    public function __construct(
        private ResponseFactoryInterface $rf,
        private EncryptionService $encryption_service,
    ) {
    }

    public function storeAuthRequest(ServerRequestInterface $request, AuthRequest $auth_request): ResponseInterface
    {
        if ($request->getUri()->getScheme() !== 'https') {
            throw new RuntimeError('Cookie auth request storage requires either https!');
        }

        // Create a cookie to store the encrypted authorization request.
        $set_cookie = SetCookie::create(
            self::COOKIE_KEY,
            $this->encryption_service->encrypt(serialize($auth_request), self::COOKIE_KEY)
        )
            ->withHttpOnly(true)
            ->withSecure(true)
            ->withSameSite(SameSite::none())
            ->withMaxAge($auth_request->getExpiresAt()->getTimestamp() - time());

        // Return the response with the cookie set.
        return FigResponseCookies::set($this->rf->createResponse(), $set_cookie);
        return $this->rf->createResponse();
    }

    public function getAuthRequest(ServerRequestInterface $request): ?AuthRequest
    {
        // Get cookies for this request.
        $cookies = Cookies::fromRequest($request);
        if ($cookies->has(self::COOKIE_KEY)) {
            // Decrypt the cookie value.
            return unserialize(
                $this->encryption_service->decrypt(
                    $cookies->get(self::COOKIE_KEY)->getValue(),
                    self::COOKIE_KEY
                )
            );
        }

        // No cookie found, return null.
        return null;
    }

    public function removeAuthRequest(ServerRequestInterface $request): ResponseInterface
    {
        return FigResponseCookies::set(
            $this->rf->createResponse(),
            SetCookie::create(self::COOKIE_KEY)->expire()
        );
    }
}
