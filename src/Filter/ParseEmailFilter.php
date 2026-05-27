<?php declare(strict_types=1);

namespace DalPraS\FormZero\Filter;


final class ParseEmailFilter implements FilterInterface
{
    /** @var non-empty-list<string> */
    private array $separators;

    public function __construct(string ...$separators)
    {
        // Default separator if none provided
        $this->separators = $separators !== [] ? $separators : [';', ','];
    }

    /**
     * @return list<string>
     */
    public function filter(mixed $value): array
    {
        if (!is_string($value) || $value === '') {
            return [];
        }

        // Normalize all separators to the first one
        $normalized = str_replace(
            $this->separators,
            $this->separators[0],
            $value
        );

        return array_values(array_unique(
            array_filter(
                array_map(
                    static fn (string $e): string => strtolower(trim($e)),
                    explode($this->separators[0], $normalized)
                ),
                static fn (string $email): bool =>
                    $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            )
        ));
    }
}
