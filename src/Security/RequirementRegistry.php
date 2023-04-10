<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Security;

final class RequirementRegistry
{
    public function __construct(
        private array $requirement_classes
    ) {
    }

    public function getRequirementClasses(): array
    {
        return $this->requirement_classes;
    }
}
