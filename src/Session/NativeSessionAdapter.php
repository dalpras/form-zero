<?php declare(strict_types=1);

namespace DalPraS\FormZero\Session;

final class NativeSessionAdapter implements SessionAdapterInterface
{
    public function isStarted(): bool
    {
        return \PHP_SESSION_ACTIVE === session_status();
    }

    public function start(): void
    {
        if (!$this->isStarted()) {
            session_start();
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }
}
