<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class ToBoolean implements FilterInterface
{
    public function filter(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1 ? true : ($value === 0 ? false : $value);
        }

        if (!is_string($value)) {
            return $value;
        }

        $normalized = strtolower(trim($value));

        return match ($normalized) {
            '1', 'true', 'yes', 'y', 'on', 'si', 'sì' => true,
            '0', 'false', 'no', 'n', 'off', '' => false,
            default => $value,
        };
    }
}
