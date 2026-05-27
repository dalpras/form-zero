<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class SeparatorToSeparator implements FilterInterface
{
    /**
     * @param array{from?: string|list<string>, to?: string} $options
     */
    public function __construct(private array $options = [])
    {
    }

    public function filter(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $from = $this->options['from'] ?? '_';
        $to = $this->options['to'] ?? '-';

        return str_replace($from, $to, $value);
    }
}
