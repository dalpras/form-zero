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

        $value = preg_replace('/(?<!^)[A-Z]/', '-$0', $value) ?? $value;
        $value = preg_replace('/[-_\s]+/', '-', $value) ?? $value;

        return strtolower(trim($value, '-'));
    }
}
