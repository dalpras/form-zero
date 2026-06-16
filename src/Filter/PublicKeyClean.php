<?php declare(strict_types=1);

namespace DalPraS\FormZero\Filter;


class PublicKeyClean implements FilterInterface
{
    public function filter(mixed $value): mixed
    {
        if (! is_scalar($value)) {
            return $value;
        }
        $value = (string) $value;

        // Define a regular expression pattern to match lines that start with the specified pattern
        $pattern = '~(-----BEGIN PUBLIC KEY-----)(.*)(-----END PUBLIC KEY-----)\s*~ms';

        // Replace matching lines, preserving spaces only in lines that start with the pattern
        $result = preg_replace_callback($pattern, fn(array $matches) => $matches[1] . preg_replace('~\s*~ms', '', $matches[2]) . $matches[3], $value);

        return $result;
    }
}