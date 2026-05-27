<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * Small dependency-free filter chain used by FormZero elements.
 *
 * It intentionally supports the common Laminas-style form config shapes:
 *
 * - SomeFilter::class
 * - new SomeFilter(...)
 * - ['name' => SomeFilter::class, 'options' => [...]]
 * - [SomeFilter::class, [...]]
 */
final class FilterChain implements IteratorAggregate
{
    /** @var list<FilterInterface> */
    private array $filters = [];

    public function attach(FilterInterface $filter): static
    {
        $this->filters[] = $filter;
        return $this;
    }

    /**
     * @param array<string,mixed>|list<mixed> $options
     */
    public function attachByName(string $name, array $options = []): static
    {
        if (!class_exists($name)) {
            throw new InvalidArgumentException(sprintf('Unknown filter class "%s".', $name));
        }

        $filter = $options === [] ? new $name() : new $name($options);

        if (!$filter instanceof FilterInterface) {
            throw new InvalidArgumentException(sprintf('Filter "%s" must implement %s.', $name, FilterInterface::class));
        }

        return $this->attach($filter);
    }

    public function filter(mixed $value): mixed
    {
        foreach ($this->filters as $filter) {
            $value = $filter->filter($value);
        }

        return $value;
    }

    /**
     * @return Traversable<FilterInterface>
     */
    public function getIterator(): Traversable
    {
        yield from $this->filters;
    }
}
