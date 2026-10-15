<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Render;

use DalPraS\FormZero\Decorator\ElementWrapperDecorator;
use DalPraS\FormZero\Element\TextElement;
use DalPraS\FormZero\Factory\FormFactory;
use DalPraS\FormZero\Upload\UploadedFileProviderInterface;
use DalPraS\SmartTemplate\HtmlAttributes;
use DalPraS\SmartTemplate\TemplateEngine;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ElementWrapperAttributesTest extends TestCase
{
    public function testClassOnlyWrapperRemainsUnchanged(): void
    {
        self::assertSame('<div class="col-6">text</div>', $this->render(['class' => 'col-6']));
    }

    public function testConfiguredAttributesAreRendered(): void
    {
        $html = $this->render(['attributes' => ['class' => 'col-6', 'data-test' => 'wrapper']]);
        self::assertStringContainsString('class="col-6"', $html);
        self::assertStringContainsString('data-test="wrapper"', $html);
    }

    public function testClosureAttributesAreEvaluatedForElement(): void
    {
        self::assertSame(
            '<div class="col-6">text</div>',
            $this->render(['attributes' => fn(TextElement $element) => ['class' => $element->getName() === 'field' ? 'col-6' : '']])
        );
    }

    private function render(array $options): string
    {
        $engine = (new TemplateEngine())->register('test', [
            'tag' => ['div' => '<div {attributes}>{content}</div>'],
        ], true);
        (new HtmlAttributes())->register($engine);

        $factory = new FormFactory(
            $engine,
            $this->createStub(UploadedFileProviderInterface::class),
            $this->createStub(ValidatorInterface::class),
        );
        $element = (new TextElement())->setFactory($factory)->setName('field');

        return (new ElementWrapperDecorator($options))->setElement($element)->render('text');
    }
}
