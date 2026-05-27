<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class ToFloat implements FilterInterface
{
    public function filter(mixed $value): mixed
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return $value;
        }

        $normalized = str_replace(' ', '', $normalized);
        if (str_contains($normalized, ',') && !str_contains($normalized, '.')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : $value;
    }
}
