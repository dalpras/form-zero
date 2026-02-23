<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Traits;

trait ErrorsTrait
{
    /**
     * Custom error messages
     */
    private array $errorMessages = [];

    abstract protected function markAsError(): void;

    /**
     * Retrieve custom error messages
     */
    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    /**
     * Clear custom error messages stack
     */
    public function clearErrorMessages(): void
    {
        $this->errorMessages = [];
    }

    /**
     * Add a custom error message to return in the event of failed validation
     */
    public function addErrorMessage(string $message): static
    {
        $this->errorMessages[] = $message;
        return $this;
    }

    /**
     * Are there custom error messages registered?
     */
    public function hasErrorMessages(): bool
    {
        return !empty($this->errorMessages);
    }

    /**
     * Add an error message and mark element as failed validation
     */
    public function addError(string $message): static
    {
        $this->addErrorMessage($message);
        $this->markAsError();
        return $this;
    }

    /**
     * Add multiple error messages and flag element as failed validation
     * @param list<string> $messages
     */
    public function addErrors(array $messages): static
    {
        foreach ($messages as $message) {
            $this->addError((string) $message);
        }
        return $this;
    }

    /**
     * Overwrite any previously set error messages and flag as failed validation
     */
    public function setErrors(array $messages): static
    {
        $this->clearErrorMessages();
        return $this->addErrors($messages);
    }

}
