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
use ForestCityLabs\Framework\Security\Exception\ForbiddenException;
use ForestCityLabs\Framework\Security\Exception\UnauthorizedException;
use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;
use ForestCityLabs\Framework\Utility\SerializerTrait;
use Psr\Http\Message\ServerRequestInterface;

#[Attribute(Attribute::TARGET_METHOD)]
class RequiresRole implements RequirementInterface
{
    use SerializerTrait;

    public function __construct(
        private string $role
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

        if (!$access_token->getUser()->hasRole($this->role)) {
            throw new ForbiddenException(sprintf(
                'Role "%s" is required to access this resource.',
                $this->role
            ));
        }
    }
}
