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
        $normalized = str_replace($separator, ' ', $value);
        $camel = str_replace(' ', '', (new StringToTitle())->filter($normalized));

        return $upperFirst ? $camel : $this->lowerFirst($camel);
    }

    private function lowerFirst(string $value): string
    {
        return mb_lcfirst($value, 'UTF-8');
    }
}
