<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

interface FilterInterface
{
    public function filter(mixed $value): mixed;
}
