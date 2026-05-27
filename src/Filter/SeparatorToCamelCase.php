<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class SeparatorToCamelCase implements FilterInterface
{
    /**
     * @param array{separator?: string|list<string>, upperFirst?: bool} $options
     */
    public function __construct(private array $options = [])
    {
    }

    public function filter(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        $separator = $this->options['separator'] ?? ['-', '_'];
        $upperFirst = $this->options['upperFirst'] ?? false;
        $normalized = str_replace($separator, ' ', strtolower($value));
        $camel = str_replace(' ', '', ucwords($normalized));

        return $upperFirst ? $camel : lcfirst($camel);
    }
}
