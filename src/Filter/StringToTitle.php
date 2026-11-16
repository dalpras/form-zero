<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class StringToTitle implements FilterInterface
{
    public function filter(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }
}
