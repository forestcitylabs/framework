<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\Oidc;

use ForestCityLabs\Framework\Security\Model\UserInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ClaimResolver
{
    public function __construct(
        protected OidcClaimRegistry $claim_registry,
        protected PropertyAccessor $property_accessor,
    ) {
    }

    public function resolveClaims(array $scopes, UserInterface $user): iterable
    {
        foreach ($scopes as $scope) {
            // Ensure this is a claim of some sort.
            if (
                !$this->claim_registry->isValidClaim($scope)
                && !$this->claim_registry->isValidGroup($scope)
            ) {
                continue;
            }

            // If this is a group get the claims for the group.
            if ($this->claim_registry->isValidGroup($scope)) {
                $claims = $this->claim_registry->getGroup($scope);
            } else {
                $claims = [$scope];
            }

            foreach ($claims as $claim) {
                yield $claim => $this->property_accessor->getValue($user, $claim);
            }
        }
    }
}
