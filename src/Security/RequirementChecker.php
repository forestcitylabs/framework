<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Security;

use ForestCityLabs\Framework\Utility\FunctionReflectionFactory;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionFunctionAbstract;

class RequirementChecker
{
    public function __construct(
        private RequirementRegistry $registry
    ) {
    }

    public function checkRequirements($callable, ServerRequestInterface $request): void
    {
        // Get reflection.
        $reflection = FunctionReflectionFactory::createReflection($callable);

        // Iterate over requirements, if any return false immediately return.
        foreach ($this->parseRequirements($reflection) as $requirement) {
            $requirement->checkRequirement($request);
        }
    }

    private function parseRequirements(ReflectionFunctionAbstract $reflection): iterable
    {
        // Check all requirement attribute classes.
        foreach ($this->registry->getRequirementClasses() as $requirement_class) {
            foreach ($reflection->getAttributes($requirement_class) as $attribute) {
                yield $attribute->newInstance();
            }
        }
    }
}
