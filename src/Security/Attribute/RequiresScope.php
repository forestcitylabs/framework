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
use ForestCityLabs\Framework\Utility\SerializerTrait;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionFunctionAbstract;
use RuntimeException;

#[Attribute(Attribute::TARGET_METHOD)]
class RequiresScope implements RequirementInterface
{
    use SerializerTrait;

    public const AND = 'AND';
    public const OR = 'OR';

    public function __construct(
        private array $scopes,
        private string $conjunction = self::AND
    ) {
        // Validate the conjunction.
        if (!in_array($conjunction, [self::AND, self::OR])) {
            throw new RuntimeException("Invalid conjuntion.");
        }
    }

    public function checkRequirement(
        ServerRequestInterface $request,
        array $args,
        ReflectionFunctionAbstract $reflection
    ): void {
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

        // Iterate over defined scopes.
        $result = false;
        foreach ($this->scopes as $scope) {
            if (
                $this->conjunction === self::AND
                && !$access_token->hasScope($scope)
            ) {
                throw new InsufficientScopeException(sprintf(
                    'Scope "%s" is required to access this resource.',
                    $scope
                ));
            } elseif ($access_token->hasScope($scope)) {
                $result = true;
            }
        }

        // Check the result.
        if (!$result) {
            throw new InsufficientScopeException(sprintf(
                'At least one scope of "%s" is required to access this resource.',
                implode(", ", $this->scopes)
            ));
        }
    }
}
