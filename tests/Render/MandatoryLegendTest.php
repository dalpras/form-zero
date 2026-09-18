<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Render;

use DalPraS\FormZero\Decorator\FormDecorator;
use DalPraS\FormZero\Element\TextElement;
use DalPraS\FormZero\Factory\FormFactory;
use DalPraS\FormZero\FieldPath;
use DalPraS\FormZero\SubZeroForm;
use DalPraS\FormZero\Upload\UploadedFileProviderInterface;
use DalPraS\FormZero\ZeroForm;
use DalPraS\SmartTemplate\Plugins\HelpersInterface;
use DalPraS\SmartTemplate\TemplateEngine;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class MandatoryLegendTest extends TestCase
{
    public function testMandatoryLegendIsAutomaticallyRenderedForRequiredLabeledElement(): void
    {
        [, $form] = $this->form();

        $form->add(new TextElement(), 'email', [
            'label' => 'Email',
            'required' => true,
        ]);

        self::assertStringContainsString('MANDATORY LEGEND', $form->render());
    }

    public function testMandatoryLegendIsNotAutomaticallyRenderedWithoutRequiredLabeledElement(): void
    {
        [, $form] = $this->form();

        $form->add(new TextElement(), 'email', [
            'label' => 'Email',
            'required' => false,
        ]);

        self::assertStringNotContainsString('MANDATORY LEGEND', $form->render());
    }

    public function testMandatoryLegendIsNotAutomaticallyRenderedForBlankLabel(): void
    {
        [, $form] = $this->form();

        $form->add(new TextElement(), 'token', [
            'label' => '   ',
            'required' => true,
        ]);

        self::assertStringNotContainsString('MANDATORY LEGEND', $form->render());
    }

    public function testMandatoryLegendDetectsRequiredElementsInNestedSubforms(): void
    {
        [$factory, $form] = $this->form();

        $subForm = $factory->createForm(SubZeroForm::class);
        $subForm->add(new TextElement(), 'city', [
            'label' => 'City',
            'required' => true,
        ]);
        $form->addSubForm($subForm, 'address');

        self::assertStringContainsString('MANDATORY LEGEND', $form->render());
    }

    public function testMandatoryFalseExplicitlySuppressesAutomaticLegend(): void
    {
        [, $form] = $this->form(false);

        $form->add(new TextElement(), 'email', [
            'label' => 'Email',
            'required' => true,
        ]);

        self::assertStringNotContainsString('MANDATORY LEGEND', $form->render());
    }

    public function testMandatoryTrueExplicitlyForcesLegendWithoutRequiredFields(): void
    {
        [, $form] = $this->form(true);

        $form->add(new TextElement(), 'email', [
            'label' => 'Email',
            'required' => false,
        ]);

        self::assertStringContainsString('MANDATORY LEGEND', $form->render());
    }

    /**
     * @return array{FormFactory, ZeroForm}
     */
    private function form(?bool $mandatory = null): array
    {
        $engine = (new TemplateEngine())
            ->setHelpers($this->createStub(HelpersInterface::class))
            ->register('test', [
                'tag' => [
                    'form' => '<form>{content}</form>',
                ],
                'form' => [
                    'components' => [
                        'mandatory' => '<p>MANDATORY LEGEND</p>',
                    ],
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

        $form = $factory->createForm(ZeroForm::class);
        $form->setName('test');

        $options = $mandatory === null ? [] : ['mandatory' => $mandatory];
        $form->setDecorators([new FormDecorator($options)]);

        return [$factory, $form];
    }
}
