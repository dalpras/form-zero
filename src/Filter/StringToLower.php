<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class StringToLower implements FilterInterface
{
    public function filter(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    }
}
