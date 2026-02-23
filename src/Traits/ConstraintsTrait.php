<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Traits;

use InvalidArgumentException;
use Symfony\Component\Validator\Constraint;

trait ConstraintsTrait
{
    /** @var Constraint[] */
    private array $constraints = [];

    /**
     * Set constraints, replacing any existing ones.
     *
     * @param Constraint[] $constraints
     */
    public function setConstraints(array $constraints): static
    {
        $this->clearConstraints();
        $this->addConstraints($constraints);
        return $this;
    }
    
    /**
     * @return Constraint[]
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * Add a single Symfony constraint to this element.
     */
    public function addConstraint(Constraint $constraint): void
    {
        $this->constraints[] = $constraint;
    }

    /**
     * Add multiple Symfony constraints to this element.
     *
     * @param Constraint[] $constraints
     */
    public function addConstraints(array $constraints): static
    {
        foreach ($constraints as $constraint) {
            if (!$constraint instanceof Constraint) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Expected instance of %s, got %s',
                        Constraint::class,
                        is_object($constraint) ? get_class($constraint) : gettype($constraint)
                    )
                );
            }
            $this->constraints[] = $constraint;
        }

        return $this;
    }

    /**
     * Prepend a constraint so that it runs before the others.
     */
    public function prependConstraint(Constraint $constraint): static
    {
        array_unshift($this->constraints, $constraint);
        return $this;
    }

    /**
     * Remove all constraints.
     */
    public function clearConstraints(): static
    {
        $this->constraints = [];
        return $this;
    }

    /**
     * Check if element already has a constraint of a given class.
     */
    public function hasConstraint(string $constraintClass): bool
    {
        foreach ($this->constraints as $constraint) {
            if ($constraint instanceof $constraintClass) {
                return true;
            }
        }
        return false;
    }
}
