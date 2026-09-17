<?php declare(strict_types=1);

namespace DalPraS\FormZero;

/**
 * Immutable compiled representation of a PHP-style form field path.
 *
 * Example: foo[bar][baz] is stored as ['foo', 'bar', 'baz'] so callers can
 * reuse the parsed path for rendering and nested array operations.
 *
 * @internal
 */
final readonly class FieldPath
{
    /** @param list<string> $segments */
    private function __construct(private array $segments)
    {
    }

    public static function fromString(string $path): self
    {
        if ($path === '') {
            return new self([]);
        }

        $segments = preg_split('/[\[\]]+/', $path, -1, PREG_SPLIT_NO_EMPTY);

        return new self($segments === false ? [$path] : array_values($segments));
    }

    public function isEmpty(): bool
    {
        return $this->segments === [];
    }

    public function leaf(): string
    {
        return $this->segments === [] ? '' : $this->segments[array_key_last($this->segments)];
    }

    public function append(self|string $path): self
    {
        $path = is_string($path) ? self::fromString($path) : $path;

        if ($this->isEmpty()) {
            return $path;
        }

        if ($path->isEmpty()) {
            return $this;
        }

        return new self([...$this->segments, ...$path->segments]);
    }

    public function toString(): string
    {
        if ($this->segments === []) {
            return '';
        }

        $segments = $this->segments;
        $path = array_shift($segments);

        foreach ($segments as $segment) {
            $path .= '[' . $segment . ']';
        }

        return $path;
    }

    public function toId(): string
    {
        return implode('-', $this->segments);
    }

    public function read(array $value): mixed
    {
        foreach ($this->segments as $segment) {
            if (is_array($value) && isset($value[$segment])) {
                $value = $value[$segment];
            }
        }

        return $value;
    }

    public function remove(array $array, string $key): array
    {
        $target = &$array;

        foreach ($this->segments as $segment) {
            if (!array_key_exists($segment, (array) $target)) {
                return $array;
            }
            $target = &$target[$segment];
        }

        if (array_key_exists($key, (array) $target)) {
            unset($target[$key]);
        }

        return $array;
    }

    public function wrap(mixed $value): array
    {
        if ($this->segments === []) {
            return ['' => $value];
        }

        for ($index = count($this->segments) - 1; $index >= 0; --$index) {
            $value = [$this->segments[$index] => $value];
        }

        return $value;
    }
}
