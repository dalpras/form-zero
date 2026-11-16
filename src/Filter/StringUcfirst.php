<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class StringUcfirst implements FilterInterface
{
    public function filter(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
            return mb_strtoupper(mb_substr($value, 0, 1, 'UTF-8'), 'UTF-8')
                . mb_substr($value, 1, null, 'UTF-8');
        }

        return ucfirst($value);
    }
}
