<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class ToInt implements FilterInterface
{
    public function filter(mixed $value): mixed
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return $value;
        }

        return is_numeric($normalized) ? (int) $normalized : $value;
    }
}
