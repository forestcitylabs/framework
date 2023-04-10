<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Utility;

use Closure;

trait DoctrineCollectionTrait
{
    public function filter(Closure $p)
    {
        return $this->createFrom(array_filter($this->elements, $p, ARRAY_FILTER_USE_BOTH));
    }

    public function removeElement($element)
    {
        return $this->remove($element);
    }

    public function containsKey($key)
    {
        return isset($this->data[$key]);
    }

    public function get($key)
    {
        return $this->data[$key];
    }

    public function getKeys()
    {
        return array_keys($this->data);
    }

    public function getValues()
    {
        return array_values($this->data);
    }

    public function set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    public function key()
    {
        return key($this->data);
    }

    public function current()
    {
        return current($this->data);
    }

    public function next()
    {
        return next($this->data);
    }

    public function exists(Closure $p)
    {
        foreach ($this->data as $key => $element) {
            if ($p($key, $element)) {
                return true;
            }
        }

        return false;
    }

    public function forAll(Closure $p)
    {
        foreach ($this->data as $key => $element) {
            if (!$p($key, $element)) {
                return false;
            }
        }

        return true;
    }

    public function partition(Closure $p)
    {
        $matches = $noMatches = [];

        foreach ($this->data as $key => $element) {
            if ($p($key, $element)) {
                $matches[$key] = $element;
            } else {
                $noMatches[$key] = $element;
            }
        }

        return [$this->createFrom($matches), $this->createFrom($noMatches)];
    }

    public function indexOf($element)
    {
        return array_search($element, $this->data, true);
    }

    public function slice($offset, $length = null)
    {
        return array_slice($this->data, $offset, $length, true);
    }

    protected function createFrom(array $elements)
    {
        return new static($elements);
    }
}
