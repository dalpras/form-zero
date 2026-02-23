<?php declare(strict_types=1);

namespace DalPraS\FormZero\Decorator;

use DalPraS\FormZero\ElementInterface;
use DalPraS\FormZero\Exception\InvalidElementException;
use DalPraS\FormZero\Element\Intefaces\MultiChoicesInterface;
use DalPraS\FormZero\ZeroForm;
use Exception;

abstract class AbstractDecorator
{
    protected ElementInterface|ZeroForm $element;

    /**
     * Decorator options
     */
    protected array $options = [];

    /**
     * Separator between new content and old
     */
    protected string $separator = PHP_EOL;

    public function __construct(array $options = [])
    {
        if ( !empty($options) ) {
            $this->setOptions($options);
        }
    }

    public function setOptions(array $options):static
    {
        $this->options = $options;
        return $this;
    }

    public function setOption(string $key, mixed $value):static
    {
        $this->options[(string) $key] = $value;
        return $this;
    }

    public function getOption(string $key): mixed
    {
        $key = (string) $key;
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }
        return null;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function removeOption(string $key): bool
    {
        if (null !== $this->getOption($key)) {
            unset($this->options[$key]);
            return true;
        }
        return false;
    }

    public function clearOptions():static
    {
        $this->options = [];
        return $this;
    }

    /**
     * Set current form element
     */
    public function setElement(ElementInterface|ZeroForm $element):static
    {
        switch (true) {
            case $element instanceof ElementInterface:
            case $element instanceof ZeroForm:
                $this->element = $element;
                return $this;
        }
        throw new InvalidElementException('Invalid element type passed to decorator');
    }

    public function getElement(): ElementInterface|MultiChoicesInterface|ZeroForm
    {
        return $this->element;
    }

    public function getSeparator(): string
    {
        $separator = $this->separator;
        if (null !== ($separatorOpt = $this->getOption('separator'))) {
            $separator = $this->separator = (string) $separatorOpt;
            $this->removeOption('separator');
        }
        return $separator;
    }

    public function render(string $content = ''): string
    {
        throw new Exception('render() not implemented');
    }
}
