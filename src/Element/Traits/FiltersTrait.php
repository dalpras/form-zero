<?php

declare (strict_types=1);

namespace DalPraS\FormZero\Element\Traits;

use InvalidArgumentException;
use Laminas\Filter\FilterChain;
use Laminas\Filter\FilterInterface;

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
                case is_string($filterInfo):
                    $filterChain->attachByName($filterInfo);
                    break;

                case is_array($filterInfo):
                    $filterChain->attachByName(...$filterInfo);
                    break;

                default:
                    throw new InvalidArgumentException('Invalid filter passed to addFilters()');
            }
        }
    }

    /**
     * Add filters to element, overwriting any already existing
     *
     * @param array|\Laminas\Filter\FilterInterface[] $filters
     */
    public function setFilters(array $filters): self
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
        /** @var \Laminas\Stdlib\PriorityQueue $filters */
        $filters = $this->getFilterChain()->getFilters();
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
