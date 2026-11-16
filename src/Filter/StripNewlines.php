<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class StripNewlines implements FilterInterface
{
    public function filter(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return preg_replace('/\R/u', '', $value) ?? $value;
    }
}
