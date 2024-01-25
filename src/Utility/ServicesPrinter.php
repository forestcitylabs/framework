<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Utility;

use PhpParser\Node\Expr\Array_;
use PhpParser\PrettyPrinter\Standard;

class ServicesPrinter extends Standard
{
    protected function pExpr_Array(Array_ $node): string
    {
        $this->indent();
        return "[" . $this->pCommaSeparatedMultiline($node->items, true) . "\n    ]";
    }
}
