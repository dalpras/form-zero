<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Filter;

final class CamelCaseToSeparator implements FilterInterface
{
    private string $separator;
    private bool $lowercase;

    /**
     * @param string|array{separator?: string, lowercase?: bool} $options
     */
    public function __construct(string|array $options = '-')
    {
        if (is_array($options)) {
            $this->separator = (string) ($options['separator'] ?? '-');
            $this->lowercase = (bool) ($options['lowercase'] ?? false);
        } else {
            $this->separator = $options;
            $this->lowercase = false;
        }

        if ($this->separator === '') {
            $this->separator = '-';
        }
    }

    public function filter(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        $filtered = preg_replace(
            [
                '/(?<=[\p{Lu}])(?=\p{Lu}\p{Ll})/u',
                '/(?<=[\p{Ll}\p{Nd}])(?=\p{Lu})/u',
            ],
            $this->separator,
            $value
        );

        if ($filtered === null) {
            return $value;
        }

        if ($this->lowercase) {
            return function_exists('mb_strtolower') ? mb_strtolower($filtered) : strtolower($filtered);
        }

        return $filtered;
    }
}
