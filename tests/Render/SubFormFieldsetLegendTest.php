<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Render;

use DalPraS\FormZero\Decorator\ElementsDecorator;
use DalPraS\FormZero\Decorator\FieldsetDecorator;
use DalPraS\FormZero\Factory\FormFactory;
use DalPraS\FormZero\FieldPath;
use DalPraS\FormZero\ZeroForm;
use DalPraS\FormZero\Upload\UploadedFileProviderInterface;
use DalPraS\SmartTemplate\Plugins\EscaperInterface;
use DalPraS\SmartTemplate\Plugins\HelpersInterface;
use DalPraS\SmartTemplate\Plugins\TranslatorInterface;
use DalPraS\SmartTemplate\TemplateEngine;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SubFormFieldsetLegendTest extends TestCase
{
    public function testDefaultSubFormDecoratorsRenderConfiguredLegend(): void
    {
        $form = $this->formGroup();
        $form->setName('metadata');
        $form->setLegend('Localized metadata');

        $html = $form->render();

        self::assertMatchesRegularExpression('/<legend[^>]*>Localized metadata<\/legend>/', $html);
        self::assertSame(1, substr_count($html, '<legend'));
        self::assertMatchesRegularExpression('/<fieldset[^>]*>.*<legend[^>]*>Localized metadata<\/legend>/s', $html);
    }

    public function testTheSameFormRendersAsFormStandaloneAndFieldsetWhenNested(): void
    {
        $form = $this->formGroup(false);
        $form->setName('metadata');
        $form->setLegend('Metadata');

        $standalone = $form->render();
        self::assertStringContainsString('<form', $standalone);
        self::assertStringNotContainsString('<fieldset', $standalone);

        $parent = $form->getFactory()->createForm(ZeroForm::class);
        $parent->addSubForm($form, 'metadata');
        $nested = $form->render();
        self::assertStringContainsString('<fieldset', $nested);
        self::assertStringNotContainsString('<form', $nested);
        self::assertSame(1, substr_count($nested, '<legend'));

        $parent->removeSubForm('metadata');
        self::assertSame($standalone, $form->render());
    }

    public function testLegendIsEscaped(): void
    {
        $form = $this->formGroup();
        $form->setName('metadata');
        $form->setLegend('<script>alert(1)</script>');

        $html = $form->render();

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function testFieldsetDecoratorCanSuppressLegendExplicitly(): void
    {
        $form = $this->formGroup();
        $form->setName('metadata');
        $form->setLegend('Hidden legend');
        $form->setDecorators([
            ElementsDecorator::class,
            [FieldsetDecorator::class, ['renderLegend' => false]],
        ]);

        $html = $form->render();

        self::assertStringContainsString('<fieldset', $html);
        self::assertStringNotContainsString('<legend', $html);
        self::assertStringNotContainsString('Hidden legend', $html);
    }

    public function testFieldsetDecoratorUsesFormLegendAsSingleSourceOfTruth(): void
    {
        $form = $this->formGroup();
        $form->setName('metadata');
        $form->setLegend('Visible legend');
        $form->setDecorators([
            ElementsDecorator::class,
            FieldsetDecorator::class,
        ]);

        $html = $form->render();

        self::assertMatchesRegularExpression('/<fieldset[^>]*>.*<legend[^>]*>Visible legend<\/legend>/s', $html);
        self::assertSame(1, substr_count($html, '<legend'));
    }

    public function testEmptyFormLegendDoesNotRenderEmptyLegendTag(): void
    {
        $form = $this->formGroup();
        $form->setName('metadata');

        self::assertStringNotContainsString('<legend', $form->render());
    }

    private function formGroup(bool $nested = true): ZeroForm
    {
        $helpers = $this->createStub(HelpersInterface::class);
        $helpers->method('escaper')->willReturn(new class implements EscaperInterface {
            public function escapeHtml(string $text): string
            {
                return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            public function escapeJs(string $text): string
            {
                return $text;
            }

            public function escapeCss(string $text): string
            {
                return $text;
            }

            public function escapeUrl(string $text): string
            {
                return rawurlencode($text);
            }
        });
        $helpers->method('translator')->willReturn($this->createStub(TranslatorInterface::class));

        $engine = (new TemplateEngine())
            ->setHelpers($helpers)
            ->register('test', [
                'tag' => [
                    'form' => '<form {attributes}>{content}</form>',
                    'fieldset' => '<fieldset {attributes}>{content}</fieldset>',
                    'legend' => '<legend {attributes}>{content}</legend>',
                ],
            ], true);

        $factory = new FormFactory(
            $engine,
            new class implements UploadedFileProviderInterface {
                public function get(FieldPath $fieldPath): array|UploadedFile|null
                {
                    return null;
                }
            },
            $this->createStub(ValidatorInterface::class),
        );

        $group = $factory->createForm(ZeroForm::class);
        if ($nested) {
            $parent = $factory->createForm(ZeroForm::class);
            $parent->addSubForm($group, 'metadata');
        }
        return $group;
    }
}
