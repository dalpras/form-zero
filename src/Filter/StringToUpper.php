<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class StringToUpper implements FilterInterface
{
    public function filter(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return mb_strtoupper($value, 'UTF-8');
    }
}
