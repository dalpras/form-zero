<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class StripNewlines implements FilterInterface
{
    public function filter(mixed $value): mixed
    {
        return is_string($value) ? str_replace(["\r", "\n"], '', $value) : $value;
    }
}
