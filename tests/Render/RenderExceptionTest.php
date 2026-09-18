<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Render;

use Closure;
use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Decorator\CallbackDecorator;
use DalPraS\FormZero\Decorator\ElementBaseDecorator;
use DalPraS\FormZero\Element;
use DalPraS\FormZero\Element\TextElement;
use DalPraS\FormZero\Factory\FormFactory;
use DalPraS\FormZero\FieldPath;
use DalPraS\FormZero\Upload\UploadedFileProviderInterface;
use DalPraS\SmartTemplate\Collection\RenderCollection;
use DalPraS\SmartTemplate\TemplateEngine;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;

final class RenderExceptionTest extends TestCase
{
    public function testCallbackDecoratorPropagatesOriginalException(): void
    {
        $expected = new RuntimeException('callback render failure');
        $engine = (new TemplateEngine())->register('test', [], true);
        $element = $this->textElement($engine);

        $decorator = new CallbackDecorator([
            'callback' => static function (
                string $content,
                RenderCollection $render,
                Element $element,
                string $namespace,
            ) use ($expected): string {
                throw $expected;
            },
        ]);
        $decorator->setElement($element);

        try {
            $decorator->render('before');
            self::fail('CallbackDecorator must propagate rendering exceptions.');
        } catch (RuntimeException $actual) {
            self::assertSame($expected, $actual);
        }
    }

    public function testElementBaseDecoratorPropagatesTemplateException(): void
    {
        $expected = new RuntimeException('element template failure');
        $engine = (new TemplateEngine())->register('test', [
            'form' => [
                'element' => static fn(string $class): Closure => static function (
                    RenderCollection $render,
                    Element $element,
                    ElementBaseDecorator $decorator,
                ) use ($expected): string {
                    throw $expected;
                },
            ],
        ], true);
        $element = $this->textElement($engine);
        $decorator = (new ElementBaseDecorator())->setElement($element);

        try {
            $decorator->render('before');
            self::fail('ElementBaseDecorator must propagate template exceptions.');
        } catch (RuntimeException $actual) {
            self::assertSame($expected, $actual);
        }
    }

    public function testElementBaseDecoratorSuccessfulRenderingIsUnchanged(): void
    {
        $engine = (new TemplateEngine())->register('test', [
            'form' => [
                'element' => static fn(string $class): Closure => static fn(
                    RenderCollection $render,
                    Element $element,
                    ElementBaseDecorator $decorator,
                ): string => '<input name="' . $element->getFullyQualifiedName() . '">',
            ],
        ], true);
        $element = $this->textElement($engine);
        $decorator = (new ElementBaseDecorator())->setElement($element);

        self::assertSame('before<input name="field">', $decorator->render('before'));
    }

    public function testUnsupportedElementTypeThrowsLogicException(): void
    {
        $engine = (new TemplateEngine())->register('test', [], true);
        $element = (new RenderExceptionUnsupportedElement())
            ->setFactory($this->factory($engine))
            ->setName('unsupported');
        $decorator = (new ElementBaseDecorator())->setElement($element);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(RenderExceptionUnsupportedElement::class);

        $decorator->render();
    }

    public function testStringCastPropagatesRenderException(): void
    {
        $expected = new RuntimeException('string render failure');
        $element = (new TextElement())->setName('field');
        $element->setDecorators([new RenderExceptionThrowingDecorator($expected)]);

        try {
            (string) $element;
            self::fail('Element string conversion must propagate rendering exceptions.');
        } catch (RuntimeException $actual) {
            self::assertSame($expected, $actual);
        }
    }

    public function testCallbackDecoratorSuccessfulRenderingIsUnchanged(): void
    {
        $engine = (new TemplateEngine())->register('test', [], true);
        $element = $this->textElement($engine);
        $decorator = new CallbackDecorator([
            'callback' => static fn(
                string $content,
                RenderCollection $render,
                Element $element,
                string $namespace,
            ): string => $content . '-' . $namespace,
        ]);
        $decorator->setElement($element);

        self::assertSame('before-test', $decorator->render('before'));
    }

    private function textElement(TemplateEngine $engine): TextElement
    {
        return (new TextElement())
            ->setFactory($this->factory($engine))
            ->setName('field');
    }

    private function factory(TemplateEngine $engine): FormFactory
    {
        return new FormFactory(
            $engine,
            new class implements UploadedFileProviderInterface {
                public function get(FieldPath $fieldPath): array|UploadedFile|null
                {
                    return null;
                }
            },
            Validation::createValidator(),
        );
    }
}

final class RenderExceptionUnsupportedElement extends Element
{
}

final class RenderExceptionThrowingDecorator extends AbstractDecorator
{
    public function __construct(private readonly RuntimeException $exception)
    {
        parent::__construct();
    }

    public function render(string $content = ''): string
    {
        throw $this->exception;
    }
}
