<?php declare(strict_types=1);

namespace DalPraS\FormZero\Factory;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Decorator\DecoratorPreset;
use DalPraS\FormZero\ElementInterface;
use DalPraS\FormZero\Exception\FormFactoryException;
use DalPraS\FormZero\FormLayout;
use DalPraS\FormZero\ZeroForm;
use DalPraS\SmartTemplate\TemplateEngine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FormFactory implements FormFactoryInterface
{
    private bool $ignoreCsrfToken = false;

    public function __construct(
        private readonly TemplateEngine $template,
        private readonly Request $request,
        private readonly ValidatorInterface $validator,
        private readonly ?Translator $translator = null
    ) {
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }

    public function translator(): ?Translator
    {
        return $this->translator;
    }

    public function createForm(string $class, mixed ...$args): ZeroForm
    {
        if (!class_exists($class)) {
            throw new FormFactoryException("Invalid or inexistent class for '{$class}'!");
        }

        /** @var ZeroForm $instance */
        $instance = empty($args) ? new $class($this) : new $class($this, ...$args);

        if (!($instance instanceof ZeroForm)) {
            throw new FormFactoryException('Invalid Form class ' . get_class($instance));
        }

        $instance->init();
        $instance->loadDefaultDecorators();

        return $instance;
    }

    public function createElement(ElementInterface|string $element, string $name, array $options): ElementInterface
    {
        $layout = $options['layout'] ?? FormLayout::Horizontal;
        unset($options['layout']);

        if (empty($options['decorators'])) {
            $options['decorators'] = DecoratorPreset::forLayout($layout);
        }

        foreach ($options['decorators'] as $key => &$decorator) {
            switch (true) {
                case $decorator === null:
                    unset($options['decorators'][$key]);
                    continue 2;

                case $decorator instanceof AbstractDecorator:
                    continue 2;

                case is_string($decorator):
                    if (is_subclass_of($decorator, AbstractDecorator::class, true)) {
                        $decorator = new $decorator();
                    }
                    continue 2;

                case is_array($decorator):
                    if (empty($decorator)) {
                        throw new FormFactoryException(
                            'Invalid decorator array for ' . json_encode($decorator) . '!'
                        );
                    }

                    if (is_subclass_of($decorator[0], AbstractDecorator::class, true)) {
                        $decorator = new $decorator[0]($decorator[1] ?? []);
                    }
                    continue 2;
            }

            throw new FormFactoryException('Class is not a valid Form Decorator!');
        }

        unset($decorator);

        if (is_string($element)) {
            if (is_subclass_of($element, ElementInterface::class)) {
                $element = new $element();
            } else {
                throw new FormFactoryException("Invalid type {$element}");
            }
        }

        $attributes = [];

        if (isset($options['attribs'])) {
            $attributes = $options['attribs'];
            unset($options['attribs']);
        }

        $element->setFactory($this)
            ->setName($name)
            ->initOptions($options);

        foreach ($attributes as $key => $value) {
            $element->setAttrib($key, $value);
        }

        $element->init();

        return $element;
    }

    public function template(): TemplateEngine
    {
        return $this->template;
    }

    public function setIgnoreCsrfToken(bool $ignoreCsrfToken = true): static
    {
        $this->ignoreCsrfToken = $ignoreCsrfToken;

        return $this;
    }

    public function getIgnoreCsrfToken(): bool
    {
        return $this->ignoreCsrfToken;
    }
}