<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Security\Attribute;

use Attribute;
use ForestCityLabs\Framework\Security\Exception\InsufficientScopeException;
use ForestCityLabs\Framework\Security\Exception\UnauthorizedException;
use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;
use ForestCityLabs\Framework\Security\ScopeRegistry;
use ForestCityLabs\Framework\Utility\SerializerTrait;
use Psr\Http\Message\ServerRequestInterface;

#[Attribute(Attribute::TARGET_METHOD)]
class RequiresScope implements RequirementInterface
{
    use SerializerTrait;

    public function __construct(
        private string $scope
    ) {
    }

    public function checkRequirement(ServerRequestInterface $request): void
    {
        // Must have an access token.
        if (null === $access_token = $request->getAttribute('_access_token')) {
            throw new UnauthorizedException();
        }

        if (!$access_token instanceof AccessTokenInterface) {
            throw new UnauthorizedException(sprintf(
                '"_access_token" attribute needs to be an instance of "%s"',
                AccessTokenInterface::class
            ));
        }

        if (
            !$access_token->hasScope(ScopeRegistry::ALL)
            && !$access_token->hasScope($this->scope)
        ) {
            throw new InsufficientScopeException(sprintf(
                'Scope "%s" is required to access this resource.',
                $this->scope
            ));
        }
    }
}
