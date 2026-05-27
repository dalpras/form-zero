<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class ToString implements FilterInterface
{
    public function filter(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        return $value;
    }
}