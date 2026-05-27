<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class ToNull implements FilterInterface
{
    public const TYPE_STRING = 'string';
    public const TYPE_ZERO = 'zero';
    public const TYPE_EMPTY_ARRAY = 'empty_array';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_ALL = 'all';

    public function __construct(private string $type = self::TYPE_ALL)
    {
    }

    public function filter(mixed $value): mixed
    {
        return match ($this->type) {
            self::TYPE_STRING => $value === '' ? null : $value,
            self::TYPE_ZERO => $value === 0 || $value === '0' ? null : $value,
            self::TYPE_EMPTY_ARRAY => $value === [] ? null : $value,
            self::TYPE_BOOLEAN => $value === false ? null : $value,
            default => $value === '' || $value === 0 || $value === '0' || $value === [] || $value === false ? null : $value,
        };
    }
}