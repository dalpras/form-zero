<?php

declare (strict_types=1);

namespace DalPraS\FormZero\Element\Traits;

use InvalidArgumentException;
use DalPraS\FormZero\Filter\FilterChain;
use DalPraS\FormZero\Filter\FilterInterface;

trait FiltersTrait
{
    
    /**
     * Filters to apply to element
     */
    private ?FilterChain $filterChain = null;

    /**
     * Add filters to element
     */
    public function addFilters(array $filters): void
    {
        $filterChain = $this->getFilterChain();
        foreach ($filters as $filterInfo) {
            switch (true) {
                case $filterInfo instanceof FilterInterface:
                    $filterChain->attach($filterInfo);
                    break;

                case is_string($filterInfo):
                    $filterChain->attachByName($filterInfo);
                    break;

                case is_array($filterInfo):
                    $name = $filterInfo['name'] ?? $filterInfo[0] ?? null;
                    $options = $filterInfo['options'] ?? $filterInfo[1] ?? [];

                    // Laminas accepted a numeric priority as the second positional
                    // argument. FormZero keeps filter order explicit through the
                    // array order, so priority is intentionally ignored here.
                    if (is_int($options) || is_float($options)) {
                        $options = [];
                    }

                    if (!is_string($name) || !is_array($options)) {
                        throw new InvalidArgumentException('Invalid filter array passed to addFilters()');
                    }

                    $filterChain->attachByName($name, $options);
                    break;

                default:
                    throw new InvalidArgumentException('Invalid filter passed to addFilters()');
            }
        }
    }

    /**
     * Add filters to element, overwriting any already existing
     */
    public function setFilters(array $filters): static
    {
        $this->clearFilterChain();
        $this->addFilters($filters);
        return $this;
    }

    /**
     * Retrieve a single filter by name
     */
    public function getFilter(string $name): ?FilterInterface
    {
        $filters = $this->getFilterChain();
        foreach ($filters as $filter) {
            if ( get_class($filter) === $name ) {
                return $filter;
            }
        }
        return null;
    }

    public function getFilterChain(): FilterChain
    {
        if ($this->filterChain === null) {
            $this->filterChain = new FilterChain();
        }
        return $this->filterChain;
    }

    public function clearFilterChain(): void
    {
        $this->filterChain = null;
    }
    
}
