<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Decorator\ElementContentDecorator;
use DalPraS\FormZero\Element;
use DalPraS\FormZero\Session\SessionAdapterInterface;

/**
 * CSRF protection form element (Symfony-like Session, no external Clock)
 *
 * Key points:
 * - Each form instance (scope) is identified by getId(); multiple forms on the same page
 *   are supported because token/expiry/rotation flags are namespaced by that id.
 * - A token is stored in the session alongside its expiry timestamp.
 * - Validation checks that the incoming token matches the stored one and hasn't expired.
 * - Token rotation happens on validation to reduce token re-use; optionally it can be limited
 *   to "rotate at most once per user session per form id".
 *
 * Security notes:
 * - Tokens are generated with random_bytes(32) and Base64URL-encoded (≈43 chars).
 * - hash_equals() is used for timing-safe comparison.
 * - Minimum TTL is enforced (60s) to avoid extremely short windows that risk UX issues.
 * - Rotation-after-use limits replay windows, but be mindful of parallel requests:
 *   two concurrent submissions may race; this class favors freshness and will rotate.
 *
 * Usage:
 * - Call init() before rendering/validating.
 * - Use render() to inject the current token as the element value.
 * - Call isValid($value) during form processing; it will validate and rotate as configured.
 */
final class HashElement extends Element
{
    /** Session key prefixes (scoped by sanitized getId()). */
    private const TOKEN_KEY_PREFIX = 'formzero.csrf.token.';
    private const EXP_KEY_PREFIX   = 'formzero.csrf.expires_at.';
    private const ROT_KEY_PREFIX   = 'formzero.csrf.rotated.'; // tracks "rotated once" state

    /** Token Time-To-Live in seconds. */
    private int $ttlSeconds;

    /** Session abstraction (Symfony-compatible). Must be started before use. */
    private SessionAdapterInterface $session;

    /** The current token to render inside the form (cached per request). */
    private ?string $hash = null;

    /**
     * When true, rotate at most once per user session (per form id).
     * When false (default), rotate on every successful validation (existing behavior).
     */
    private bool $rotateOncePerSession = false;

    /**
     * @param SessionAdapterInterface $session  Session handler to persist token/metadata.
     * @param int $ttlSeconds                  Desired TTL; clamped to a minimum of 60s.
     */
    public function __construct(SessionAdapterInterface $session, int $ttlSeconds = 3600)
    {
        $this->session    = $session;
        $this->ttlSeconds = max(60, $ttlSeconds); // enforce a safe minimum TTL
    }

    /**
     * Enable/disable the "rotate at most once per session" strategy for this form id.
     *
     * @return $this
     */
    public function setRotateOncePerSession(bool $on): static
    {
        $this->rotateOncePerSession = $on;
        return $this;
    }

    /**
     * Initialize the element:
     * - Starts the session if needed.
     * - Ensures a valid (non-expired) token exists and caches it.
     * - Applies required/empty constraints unless CSRF is explicitly ignored by the factory.
     * - Applies the content decorator.
     */
    public function init(): void
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        // Ensure a token is present and valid; create one if missing/expired.
        $this->hash = $this->ensureValidToken();

        // If CSRF is not being ignored by the form factory, make the field required.
        if (!$this->getFactory()->getIgnoreCsrfToken()) {
            $this->setAllowEmpty(false)->setRequired(true);
        }

        // Render-only element; use a content decorator (e.g., hidden input).
        $this->clearDecorators()->addDecorator(ElementContentDecorator::class);
    }

    /**
     * CSRF element has no visual label.
     */
    public function getLabel(): string
    {
        return '';
    }

    /**
     * Returns the current token to be injected in the rendered field.
     * Lazily revalidates/creates a token if not already cached for this request.
     */
    public function getHash(): string
    {
        if ($this->hash === null) {
            $this->hash = $this->ensureValidToken();
        }
        return $this->hash;
    }

    /**
     * Validate the incoming CSRF token (unless the factory says to ignore CSRF).
     * After validation, rotates the token according to the configured strategy.
     *
     * @param mixed $value   The token value submitted with the form.
     * @param mixed $context Optional validation context (unused here).
     */
    public function isValid($value, $context = null): bool
    {
        if ($this->getFactory()->getIgnoreCsrfToken() !== true) {
            $okCsrf = $this->validateIncomingToken((string)$value);

            // Always rotate (legacy behavior) or at most once per session (if enabled).
            $this->rotateToken();

            if (!$okCsrf) {
                return false;
            }
        }

        // Defer to parent for any additional validation behavior.
        return parent::isValid($value, $context);
    }

    /**
     * Injects the current token as the element value and delegates to parent renderer.
     * Typically renders a hidden input with the token.
     */
    public function render(): string
    {
        $this->setValue($this->getHash());
        return parent::render();
    }

    // ============================
    //          Helpers
    // ============================

    /**
     * Time source (wrapped for potential testability).
     */
    private function now(): int
    {
        return time();
    }

    /**
     * Create a new cryptographically strong token.
     * Uses 32 random bytes, Base64URL-encoded (no padding) for safe transport in HTML forms.
     */
    private function generateToken(): string
    {
        $raw = random_bytes(32);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '='); // ~43 characters
    }

    /**
     * Namespaced session key for the token value.
     */
    private function tokenKey(): string
    {
        return self::TOKEN_KEY_PREFIX . $this->sanitizeId($this->getId());
    }

    /**
     * Namespaced session key for the token expiry timestamp (unix epoch).
     */
    private function expKey(): string
    {
        return self::EXP_KEY_PREFIX . $this->sanitizeId($this->getId());
    }

    /**
     * Namespaced session key for the "already rotated" flag for this session/scope.
     */
    private function rotKey(): string
    {
        return self::ROT_KEY_PREFIX . $this->sanitizeId($this->getId());
    }

    /**
     * Ensure a valid (non-expired) token exists in session; create if missing/expired.
     * Also initializes the rotation flag depending on the configured strategy:
     * - If rotateOncePerSession is ON and a valid token exists, pre-mark as "rotated"
     *   so rotateToken() becomes a no-op for this session/scope.
     */
    private function ensureValidToken(): string
    {
        $now    = $this->now();
        $token  = $this->session->get($this->tokenKey());
        $expiry = (int) $this->session->get($this->expKey(), 0);

        if (!is_string($token) || $expiry < $now) {
            // Missing or expired token: issue a new one and clear the "rotated" flag.
            $token = $this->generateToken();
            $this->session->set($this->tokenKey(), $token);
            $this->session->set($this->expKey(), $now + $this->ttlSeconds);
            $this->session->set($this->rotKey(), false);
        } else {
            // Existing, still-valid token.
            if ($this->rotateOncePerSession) {
                // In "rotate at most once per session" mode, mark as already rotated
                // so that rotateToken() becomes a no-op for this session/id.
                $this->session->set($this->rotKey(), true);
            }
        }

        return $token;
    }

    /**
     * Rotate the token according to the configured policy:
     * - If rotateOncePerSession = false: always rotate (legacy aggressive rotation).
     * - If rotateOncePerSession = true: rotate only if we haven't rotated yet in THIS
     *   user session for THIS form id.
     */
    private function rotateToken(): void
    {
        if (!$this->rotateOncePerSession) {
            // Legacy behavior: always rotate after validation.
            $this->doRotate();
            return;
        }

        // "Rotate once per session": check the rotation flag.
        $alreadyRotated = (bool) $this->session->get($this->rotKey(), false);
        if ($alreadyRotated) {
            return; // do not rotate again for this session/scope
        }

        $this->doRotate();
        $this->session->set($this->rotKey(), true);
    }

    /**
     * Actual rotation logic:
     * - Generate a new token, store it, extend expiry, and update the cached $hash.
     */
    private function doRotate(): void
    {
        $now = $this->now();
        $new = $this->generateToken();
        $this->session->set($this->tokenKey(), $new);
        $this->session->set($this->expKey(), $now + $this->ttlSeconds);
        $this->hash = $new;
    }

    /**
     * Validate the incoming token value:
     * - Rejects empty values.
     * - Fails if no stored token or it's expired.
     * - Uses hash_equals for timing-safe comparison.
     */
    private function validateIncomingToken(string $incoming): bool
    {
        if ($incoming === '') {
            return false;
        }

        $now    = $this->now();
        $stored = $this->session->get($this->tokenKey());
        $exp    = (int) $this->session->get($this->expKey(), 0);

        if (!is_string($stored) || $exp < $now) {
            return false; // absent or expired
        }

        return hash_equals($stored, $incoming);
    }

    /**
     * Sanitize the element id for safe use as a session key suffix.
     * Allows alphanumerics plus [._:-]; everything else becomes '_'.
     * Falls back to "default" if the replacement returns null (shouldn't happen).
     */
    private function sanitizeId(string $id): string
    {
        return preg_replace('/[^a-z0-9_.:-]/i', '_', $id) ?? 'default';
    }
}
