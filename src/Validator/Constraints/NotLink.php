<?php declare(strict_types=1);

namespace DalPraS\FormZero\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures the input does not contain dangerous link-like patterns
 * that would get auto-detected as links in email clients.
 */
class NotLink extends Constraint
{
    public string $httpMessage  = "Input must not contain 'http://' or 'https://'.";
    public string $mailtoMessage = "Input must not contain 'mailto://'.";
    public string $telMessage    = "Input must not contain 'tel:'.";
    public string $slashMessage  = "Input must not contain '//'.";
    
    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}
