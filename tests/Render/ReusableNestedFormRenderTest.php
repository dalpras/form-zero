<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Render;

use DalPraS\FormZero\Decorator\AbstractDecorator;
use DalPraS\FormZero\Decorator\CallbackDecorator;
use DalPraS\FormZero\Decorator\ElementsDecorator;
use DalPraS\FormZero\Element\TextElement;
use DalPraS\FormZero\Factory\FormFactory;
use DalPraS\FormZero\FieldPath;
use DalPraS\FormZero\Upload\UploadedFileProviderInterface;
use DalPraS\FormZero\ZeroForm;
use DalPraS\SmartTemplate\Collection\RenderCollection;
use DalPraS\SmartTemplate\Plugins\EscaperInterface;
use DalPraS\SmartTemplate\Plugins\HelpersInterface;
use DalPraS\SmartTemplate\Plugins\TranslatorInterface;
use DalPraS\SmartTemplate\TemplateEngine;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ReusableNestedFormRenderTest extends TestCase
{
    public function testStandaloneFormProducesOneFormAndNestedFormProducesAFieldset(): void
    {
        $factory = $this->factory();
        $root = $factory->createForm(ZeroForm::class)->setName('root');
        $address = $factory->createForm(ZeroForm::class)->setName('AddressForm');
        $address->setAttribs([
            'name' => 'AddressForm',
            'method' => 'post',
            'action' => '/edit',
            'enctype' => 'multipart/form-data',
            'class' => 'address-form',
        ]);
        $address->setLegend('Address details');

        $city = (new TextElement())->setName('city');
        $city->setDecorators([new ReusableRenderInputDecorator()]);
        $address->addElement($city);

        $standalone = $address->render();
        self::assertSame(1, substr_count($standalone, '<form '));
        self::assertSame(0, substr_count($standalone, '<fieldset '));
        self::assertStringContainsString('name="city"', $standalone);

        $decoratorsBefore = $address->getDecorators();
        $root->addSubForm($address, 'shipping');
        $nested = $root->render();

        self::assertSame(1, substr_count($nested, '<form '));
        self::assertSame(1, substr_count($nested, '<fieldset '));
        self::assertStringContainsString('name="shipping[city]"', $nested);
        self::assertStringContainsString('fieldset-shipping', $nested);
        self::assertStringContainsString('<legend>Address details</legend>', $nested);
        self::assertStringNotContainsString('action="/edit"', $nested);
        self::assertStringNotContainsString('enctype="multipart/form-data"', $nested);
        self::assertStringNotContainsString('method="post"', $nested);
        self::assertSame($decoratorsBefore, $address->getDecorators());

        $root->removeSubForm('shipping');
        self::assertSame($standalone, $address->render());
    }

    public function testArticleStyleCustomDecoratorsAreNotOverridden(): void
    {
        $factory = $this->factory();
        $root = $factory->createForm(ZeroForm::class)->setName('ArticleForm');
        $metadata = $factory->createForm(ZeroForm::class);
        $metadata->setDecorators([
            ElementsDecorator::class,
            [CallbackDecorator::class, [
                'callback' => static fn(
                    string $content,
                    RenderCollection $render,
                    \DalPraS\FormZero\ElementInterface|ZeroForm $element,
                    string $namespace
                ): string => '<section class="metadata">' . $content . '</section>',
            ]],
        ]);

        $title = (new TextElement())->setName('metaTitle');
        $title->setDecorators([new ReusableRenderInputDecorator()]);
        $metadata->addElement($title);
        $root->addSubForm($metadata, 'metadata');

        $html = $root->render();
        self::assertSame(1, substr_count($html, '<form '));
        self::assertSame(0, substr_count($html, '<fieldset '));
        self::assertStringContainsString('<section class="metadata">', $html);
        self::assertStringContainsString('name="metadata[metaTitle]"', $html);
    }

    private function factory(): FormFactory
    {
        $helpers = $this->createStub(HelpersInterface::class);
        $helpers->method('escaper')->willReturn(new class implements EscaperInterface {
            public function escapeHtml(string $text): string
            {
                return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            public function escapeJs(string $text): string { return $text; }
            public function escapeCss(string $text): string { return $text; }
            public function escapeUrl(string $text): string { return rawurlencode($text); }
        });
        $helpers->method('translator')->willReturn($this->createStub(TranslatorInterface::class));

        $engine = (new TemplateEngine())->setHelpers($helpers)->register('test', [
            'tag' => [
                'form' => '<form action="{action}" method="{method}" {attributes}>{content}</form>',
                'fieldset' => '<fieldset {attributes}>{content}</fieldset>',
                'legend' => '<legend>{content}</legend>',
            ],
            'form' => [
                'components' => ['mandatory' => '<small>Required</small>'],
            ],
        ], true);

        return new FormFactory(
            $engine,
            new class implements UploadedFileProviderInterface {
                public function get(FieldPath $fieldPath): array|UploadedFile|null
                {
                    return null;
                }
            },
            $this->createStub(ValidatorInterface::class)
        );
    }
}

final class ReusableRenderInputDecorator extends AbstractDecorator
{
    public function render(string $content = ''): string
    {
        return $content . '<input name="' . $this->getElement()->getFullyQualifiedName() . '">';
    }
}
