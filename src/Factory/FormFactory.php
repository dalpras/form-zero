<?php declare(strict_types=1);

namespace DalPraS\FormZero\Factory;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\ElementInterface;
use DalPraS\FormZero\Exception\FormFactoryException;
use DalPraS\FormZero\Factory\FormFactoryInterface;
use DalPraS\FormZero\ZeroForm;
use DalPraS\SmartTemplate\TemplateEngine;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FormFactory implements FormFactoryInterface
{
    private bool $ignoreCsrfToken = false;

    protected static string $defaultTemplateFile = 'form.php';

    public function __construct(
        private ?string $templateFile = null,
        private ?TemplateEngine $template = null,
        private ?Request $request = null,
        private ?Translator $translator = null
    ) {
        $this->templateFile ??= self::$defaultTemplateFile;
    }

    public function getPsrRequest(): ServerRequestInterface
    {
        $psrHttpFactory = new PsrHttpFactory(
            new ServerRequestFactory(),
            new StreamFactory(),
            new UploadedFileFactory(),
            new ResponseFactory()
        );
        return $psrHttpFactory->createRequest($this->request);
    }

    public function getHttpRequest(): Request
    {
        return $this->request;
    }

    public function getValidator(): ValidatorInterface
    {
        $vb = Validation::createValidatorBuilder();
        if ($this->translator !== null) {
            $vb ->setTranslationDomain('validators')
                ->setTranslator($this->translator)
            ;
        }
        return $vb->getValidator();
    }

    public function getTranslator(): ?Translator 
    {
        return $this->translator;    
    }

    /**
     * Form & SubZeroForm builder.
     *
     * @param string $class  Nome della classe da istanziare
     * @param mixed ...$args Argomenti da passare al costruttore della classe
     */
    public function createForm(string $class, ...$args): ZeroForm
    {
        if (! class_exists($class)) {
            throw new FormFactoryException("Invalid or inexistent class for '{$class}'!");
        }

        /** @var \DalPraS\FormZero\ZeroForm $instance */
        $instance = empty($args) ? new $class($this) : new $class($this, ...$args);

        if (! ($instance instanceof ZeroForm)) {
            throw new FormFactoryException('Invalid Form class ' . get_class($instance));
        }

        // carico la form factory nella form per gererare gli altri elementi della form
        $instance->init();
        $instance->loadDefaultDecorators();
        return $instance;
    }

    /**
     * Form Element builder.
     */
    public function createElement(ElementInterface|string $element, string $name, array $options): ElementInterface
    {
        // scorro le classi di decorazione
        foreach ($options['decorators'] as $key => &$decorator) {
            switch (true) {
                case $decorator === null:
                    unset($options['decorators'][$key]);
                    continue 2;

                case $decorator instanceof AbstractDecorator:
                    continue 2;

                case is_string($decorator):
                    // controllo che siano stati inseriti dei decoratori e li instanzio
                    if (is_subclass_of($decorator, AbstractDecorator::class, true)) {
                        $decorator = new $decorator();
                    }
                    continue 2;

                case is_array($decorator):
                    if (empty($decorator)) {
                        throw new FormFactoryException('Invalid decorator array for ' . json_encode($decorator) . '!');
                    }
                    if (is_subclass_of($decorator[0], AbstractDecorator::class, true)) {
                        $decorator = new $decorator[0]($decorator[1] ?? []);
                    }
                    continue 2;
            }
            throw new FormFactoryException('Class is not a valid Form Decorator!');
        }


        if ( is_string($element)) {
            if (is_subclass_of($element, ElementInterface::class)) {
                $element = new $element;
            } else {
                throw new FormFactoryException("Invalid type {$element}");
            }
        }

        // tolgo gli attributi altrimenti sono sovrascritti quelli presenti nel costruttore
        $attribs = [];
        if (isset($options['attribs'])) {
            $attribs = $options['attribs'];
            unset($options['attribs']);
        }
        $element->setFactory($this)->setName($name)->initOptions($options);

        // aggiungo gli attributi indicati rispetto a quelli del costruttore
        // $instance->addAttribs($attribs);
        foreach ($attribs as $key => $value) {
            $element->setAttrib($key, $value);
        }

        // Extensions...
        $element->init();

        return $element;
    }

    public function getTemplate(): TemplateEngine
    {
        if ($this->template === null) {
            $this->template = new TemplateEngine(__DIR__ . '/../Template', self::$defaultTemplateFile);
            $this->template->addCustomParamCallback('{attributes}', function($param) {
                return ($param === null) ? '' : $this->template->attributes($param);
            });
        }
        return $this->template;
    }

    public function getTemplateFile(): string
    {
        return $this->templateFile;
    }    


    public function setIgnoreCsrfToken(bool $ignoreCsrfToken = true): self
    {
        $this->ignoreCsrfToken = $ignoreCsrfToken;
        return $this;
    }

    public function getIgnoreCsrfToken(): bool
    {
        return $this->ignoreCsrfToken;
    }
}
