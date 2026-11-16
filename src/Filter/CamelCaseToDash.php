<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class CamelCaseToDash implements FilterInterface
{
    public function filter(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        $filtered = (new CamelCaseToSeparator([
            'separator' => '-',
            'lowercase' => true,
        ]))->filter($value);

        $normalized = preg_replace('/[-_\s]+/u', '-', $filtered) ?? $filtered;

        return trim($normalized, '-');
    }
}
