<?php declare(strict_types=1);

namespace DalPraS\FormZero\Session;

use Symfony\Component\HttpFoundation\Session\SessionInterface as SfSession;

final class SymfonySessionAdapter implements SessionAdapterInterface
{
    public function __construct(private SfSession $session) {}

    public function isStarted(): bool { return $this->session->isStarted(); }
    public function start(): void { $this->session->start(); }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->session->get($key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->session->set($key, $value);
    }

    public function remove(string $key): void
    {
        $this->session->remove($key);
    }
}
