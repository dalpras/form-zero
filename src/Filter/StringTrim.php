<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class StringTrim implements FilterInterface
{
    public function filter(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return mb_trim($value, null, 'UTF-8');
    }
}
