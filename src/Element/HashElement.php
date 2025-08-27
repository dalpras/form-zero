<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Decorator\ElementContentDecorator;
use DalPraS\FormZero\Element;
use Laminas\Validator\Identical;

/**
 * CSRF form protection
 */
class HashElement extends Element
{
    /**
     * Key used to store the form hash in the session.
     *
     * @var string
     */
    private const string SESSION_HASH_KEY = 'form-element-hash-key';

    protected array $attribs = [];

    /**
     * Actual hash used.
     */
    private ?string $hash = null;

    /**
     * Creates session namespace for CSRF token, and adds validator for CSRF
     * token.
     */
    public function init(): void
    {
        // Ensure PHP session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->hash = $this->initHash(true);

        if ( !$this->getFactory()->getIgnoreCsrfToken() ) {
            $this->initCsrfValidator($this->hash)->setAllowEmpty(false)->setRequired(true);
        }
        $this->clearDecorators()->addDecorator(ElementContentDecorator::class);
    }

    /**
     * Initialize the hash in the session.
     * If $useSessionHash is true and a session hash already exists, it will be used.
     * Otherwise, a new hash will be generated.
     * Returns the initialized hash.
     */
    private function initHash(bool $useSessionHash): string
    {
        if ($useSessionHash && isset($_SESSION[self::SESSION_HASH_KEY])) {
            $rightHash = $_SESSION[self::SESSION_HASH_KEY];
        } else {
            $rightHash = $this->generateRightHash();
            $_SESSION[self::SESSION_HASH_KEY] = $rightHash; // Store it in session
        }
        return $rightHash;
    }

    /**
     * Generate a 32-character hexadecimal random string.
     */
    private function generateRightHash(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Initialize CSRF validator and attach it to the validator chain.
     */
    private function initCsrfValidator($rightHash): self
    {
        // se la sessione non è stata iniettata, non la considero nel validatore
        $this->getValidatorChain()->attachByName(Identical::class, ['token' => $rightHash], true);
        return $this;
    }

    /**
     * Retrieve CSRF token
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Override getLabel() to always be empty
     */
    public function getLabel(): string
    {
        return '';
    }

    /**
     * @see \DalPraS\FormZero\Element::isValid()
     */
    public function isValid($value, $context = null): bool
    {
        $isValid = parent::isValid($value, $context);
        if (!$isValid) {
            $this->hash = $this->initHash(false);
        }
        return $isValid;
    }

    /**
     * Render CSRF token in form
     */
    public function render(): string
    {
        $this->setValue($this->hash);
        return parent::render();
    }
}
