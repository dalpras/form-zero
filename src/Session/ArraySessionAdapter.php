<?php declare(strict_types=1);

namespace DalPraS\FormZero\Session;

final class ArraySessionAdapter implements SessionAdapterInterface
{
    private bool $started = true; // test-friendly
    private array $data = [];

    public function isStarted(): bool { return $this->started; }
    public function start(): void { $this->started = true; }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }
}
