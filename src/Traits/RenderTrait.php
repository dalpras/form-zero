<?php

declare(strict_types=1);

namespace DalPraS\FormZero\Traits;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use InvalidArgumentException;

trait RenderTrait
{
    private bool $isRendered = false;

    /**
     * Decorators for rendering
     *
     * @var \DalPraS\FormZero\Decorator\AbstractDecorator[]
     */
    private array $decorators = [];

    /**
     * When render this method, is used to set $isRendered member to prevent repeatedly
     * merging belongsTo setting
     */
    public function setIsRendered(): static
    {
        $this->isRendered = true;
        return $this;
    }

    /**
     * Get the value of $isRendered member
     */
    public function getIsRendered(): bool
    {
        return $this->isRendered;
    }

    /**
     * Add many decorators at once
     */
    public function addDecorators(array $decorators): static
    {
        foreach ($decorators as $decorator) {
            $this->addDecorator($decorator);
        }
        return $this;
    }

    /**
     * Overwrite all decorators
     */
    public function setDecorators(array $decorators): static
    {
        $this->clearDecorators();
        return $this->addDecorators($decorators);
    }

    /**
     * Retrieve all decorators or only some by class name.
     */
    public function getDecorators(?string $name = null): array
    {
        if ($name === null) {
            return $this->decorators;
        }

        $result = [];
        foreach ($this->decorators as $key => &$decorator) {
            if (get_class($decorator) === $name) {
                $result[$key] = $decorator;
            }
        }
        return $result;
    }

    /**
     * Clear all decorators
     */
    public function clearDecorators(): static
    {
        $this->decorators = [];
        return $this;
    }

    /**
     * Add a decorator for rendering the element
     */
    public function addDecorator($decorator): static
    {
        switch (true) {
            case $decorator instanceof AbstractDecorator:
                break;

            case is_string($decorator) && is_subclass_of($decorator, AbstractDecorator::class, true):
                $decorator = new $decorator();
                break;

            case !is_array($decorator):
            case empty($decorator):
                throw new InvalidArgumentException('Invalid decorator passed to addDecorators()');

            case ($candidate = array_shift($decorator)) && is_string($candidate) && is_subclass_of($candidate, AbstractDecorator::class, true):
                $options = array_shift($decorator) ?? [];
                $decorator = new $candidate($options);
                break;
                
            default:
                throw new InvalidArgumentException('Invalid decorator passed to addDecorators()');
        }

        $this->decorators[] = $decorator;
        return $this;
    }

    /**
     * Renders the form.
     */
    public function render(): string
    {
        $content = '';
        /** @var \DalPraS\FormZero\Decorator\AbstractDecorator $decorator */
        foreach ($this->getDecorators() as $decorator) {
            $decorator->setElement($this);
            $content = $decorator->render($content);
        }
        $this->setIsRendered();
        return $content;
    }
   
}
