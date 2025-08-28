<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Decorator\ElementContentDecorator;
use DalPraS\FormZero\Element;
use DalPraS\FormZero\Session\SessionAdapterInterface;

/**
 * CSRF form protection element (Symfony Session, no external Clock)
 * - Supporta più form sulla stessa pagina usando getId() come scope
 */
final class HashElement extends Element
{
    private const TOKEN_KEY_PREFIX = 'formzero.csrf.token.';
    private const EXP_KEY_PREFIX   = 'formzero.csrf.expires_at.';

    /** TTL del token (secondi) */
    private int $ttlSeconds;

    /** Symfony session */
    private SessionAdapterInterface $session;

    /** Token corrente da renderizzare */
    private ?string $hash = null;

    public function __construct(SessionAdapterInterface $session, int $ttlSeconds = 1200)
    {
        parent::__construct();
        $this->session    = $session;
        $this->ttlSeconds = max(60, $ttlSeconds); // minimo 60s per sicurezza
    }

    public function init(): void
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        $this->hash = $this->ensureValidToken();

        if (!$this->getFactory()->getIgnoreCsrfToken()) {
            $this->setAllowEmpty(false)->setRequired(true);
        }

        $this->clearDecorators()->addDecorator(ElementContentDecorator::class);
    }

    public function getLabel(): string
    {
        return '';
    }

    public function getHash(): string
    {
        if ($this->hash === null) {
            $this->hash = $this->ensureValidToken();
        }
        return $this->hash;
    }

    public function isValid($value, $context = null): bool
    {
        if ($this->getFactory()->getIgnoreCsrfToken() !== true) {
            $okCsrf = $this->validateIncomingToken((string)$value);

            // Ruota comunque il token per ridurre rischio replay
            $this->rotateToken();

            if (!$okCsrf) {
                return false;
            }
        }

        return parent::isValid($value, $context);
    }

    public function render(): string
    {
        $this->setValue($this->getHash());
        return parent::render();
    }

    // ============================
    //          Helpers
    // ============================

    private function now(): int
    {
        return time();
    }

    private function generateToken(): string
    {
        $raw = random_bytes(32);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '='); // ~43 char
    }

    private function tokenKey(): string
    {
        return self::TOKEN_KEY_PREFIX . $this->sanitizeId($this->getId());
    }

    private function expKey(): string
    {
        return self::EXP_KEY_PREFIX . $this->sanitizeId($this->getId());
    }

    private function ensureValidToken(): string
    {
        $now    = $this->now();
        $token  = $this->session->get($this->tokenKey());
        $expiry = (int) $this->session->get($this->expKey(), 0);

        if (!is_string($token) || $expiry < $now) {
            $token = $this->generateToken();
            $this->session->set($this->tokenKey(), $token);
            $this->session->set($this->expKey(), $now + $this->ttlSeconds);
        }

        return $token;
    }

    private function rotateToken(): void
    {
        $now = $this->now();
        $new = $this->generateToken();
        $this->session->set($this->tokenKey(), $new);
        $this->session->set($this->expKey(), $now + $this->ttlSeconds);
        $this->hash = $new;
    }

    private function validateIncomingToken(string $incoming): bool
    {
        if ($incoming === '') {
            return false;
        }

        $now    = $this->now();
        $stored = $this->session->get($this->tokenKey());
        $exp    = (int) $this->session->get($this->expKey(), 0);

        if (!is_string($stored) || $exp < $now) {
            return false; // assente o scaduto
        }

        return hash_equals($stored, $incoming);
    }

    private function sanitizeId(string $id): string
    {
        // Solo caratteri sicuri per la chiave di sessione
        return preg_replace('/[^a-z0-9_.:-]/i', '_', $id) ?? 'default';
    }
}
