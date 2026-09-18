<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Render;

use DalPraS\FormZero\Decorator\ElementLabelDecorator;
use DalPraS\FormZero\Element\TextElement;
use DalPraS\FormZero\Factory\FormFactory;
use DalPraS\FormZero\Upload\UploadedFileProviderInterface;
use DalPraS\SmartTemplate\TemplateEngine;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RequiredLabelDecoratorTest extends TestCase
{
    public function testRequiredClassIsAppendedToDefaultLabelClass(): void
    {
        $element = $this->requiredElement();
        $decorator = (new ElementLabelDecorator())->setElement($element);

        self::assertSame(
            '<label class="form-label required" for="email">Email</label>',
            $decorator->render()
        );
    }

    public function testRequiredClassIsAppendedToConfiguredLabelClass(): void
    {
        $element = $this->requiredElement();
        $decorator = (new ElementLabelDecorator([
            'class' => 'col-form-label col-12 col-sm-3',
        ]))->setElement($element);

        self::assertSame(
            '<label class="col-form-label col-12 col-sm-3 required" for="email">Email</label>',
            $decorator->render()
        );
    }

    public function testOptionalLabelDoesNotReceiveRequiredClass(): void
    {
        $element = $this->element();
        $decorator = (new ElementLabelDecorator([
            'class' => 'col-form-label col-12',
        ]))->setElement($element);

        self::assertSame(
            '<label class="col-form-label col-12" for="email">Email</label>',
            $decorator->render()
        );
    }

    private function requiredElement(): TextElement
    {
        return $this->element()->setRequired();
    }

    private function element(): TextElement
    {
        $engine = (new TemplateEngine())->register('test', [
            'tag' => [
                'label' => '<label {attributes}>{content}</label>',
            ],
        ], true);

        $engine->addCustomParamCallback(
            '{attributes}',
            static fn(mixed $value): string => $value === null
                ? ''
                : $engine->attributes((array) $value)
        );

        $factory = new FormFactory(
            $engine,
            $this->createStub(UploadedFileProviderInterface::class),
            $this->createStub(ValidatorInterface::class),
        );

        return (new TextElement())
            ->setFactory($factory)
            ->setName('email')
            ->setLabel('Email')
            ->setDisableTranslator(true);
    }
}
