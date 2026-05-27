<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class StripTags implements FilterInterface
{
    public function filter(mixed $value): mixed
    {
        return is_string($value) ? strip_tags($value) : $value;
    }
}
