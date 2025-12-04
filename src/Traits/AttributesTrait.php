<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Traits;

trait AttributesTrait
{
    /**
     * Attributes
     */
    private array $attribs = [];

    /**
     * Set form attribute
     *
     * @param string $key
     * @param mixed $value
     */
    public function setAttrib(string $key, $value): void
    {
        $this->attribs[$key] = $value;
    }

    /**
     * Add multiple form attributes at once
     */
    public function addAttribs(array $attribs): void
    {
        foreach ($attribs as $key => $value) {
            $this->setAttrib($key, $value);
        }
    }

    /**
     * Set multiple form attributes at once
     *
     * Overwrites any previously set attributes.
     *
     */
    public function setAttribs(array $attribs): void
    {
        $this->clearAttribs();
        $this->addAttribs($attribs);
    }

    /**
     * Retrieve a single form attribute
     */
    public function getAttrib(string $key)
    {
        if (!isset($this->attribs[$key])) {
            return null;
        }
        return $this->attribs[$key];
    }

    /**
     * Retrieve all form attributes/metadata
     */
    public function getAttribs(): array
    {
        return $this->attribs;
    }

    /**
     * Remove attribute
     */
    public function removeAttrib(string $key): bool
    {
        if (isset($this->attribs[$key])) {
            unset($this->attribs[$key]);
            return true;
        }
        return false;
    }

    /**
     * Clear all form attributes
     */
    public function clearAttribs(): void
    {
        $this->attribs = [];
    }
}
