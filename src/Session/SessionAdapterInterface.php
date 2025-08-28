<?php declare(strict_types=1);

namespace DalPraS\FormZero\Session;

interface SessionAdapterInterface
{
    public function isStarted(): bool;
    public function start(): void;

    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;

    // Optional but handy:
    public function remove(string $key): void;
}