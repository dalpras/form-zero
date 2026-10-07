<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Render;

use DalPraS\FormZero\Decorator\ElementsDecorator;
use DalPraS\FormZero\Decorator\FieldsetDecorator;
use DalPraS\FormZero\Factory\FormFactory;
use DalPraS\FormZero\FieldPath;
use DalPraS\FormZero\SubZeroForm;
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
        $form = $this->subForm();
        $form->setName('metadata');
        $form->setLegend('Localized metadata');

        $html = $form->render();

        self::assertMatchesRegularExpression('/<legend[^>]*>Localized metadata<\/legend>/', $html);
        self::assertSame(1, substr_count($html, '<legend'));
        self::assertMatchesRegularExpression('/<fieldset[^>]*>.*<legend[^>]*>Localized metadata<\/legend>/s', $html);
    }

    public function testLegendIsEscaped(): void
    {
        $form = $this->subForm();
        $form->setName('metadata');
        $form->setLegend('<script>alert(1)</script>');

        $html = $form->render();

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function testFieldsetDecoratorCanSuppressLegendExplicitly(): void
    {
        $form = $this->subForm();
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
        $form = $this->subForm();
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
        $form = $this->subForm();
        $form->setName('metadata');

        self::assertStringNotContainsString('<legend', $form->render());
    }

    private function subForm(): SubZeroForm
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

        return $factory->createForm(SubZeroForm::class);
    }
}
