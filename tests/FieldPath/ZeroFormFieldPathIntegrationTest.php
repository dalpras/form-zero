<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\FieldPath;

use DalPraS\FormZero\Element\TextElement;
use DalPraS\FormZero\ZeroForm;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ZeroFormFieldPathIntegrationTest extends TestCase
{
    public function testNestedBelongsToStillMapsValuesAndDefaults(): void
    {
        $form = $this->newForm(ZeroForm::class);
        $form->setName('root');

        $title = (new TextElement())
            ->setName('title')
            ->setBelongsTo('article[metadata]')
            ->setValue('Title');
        $form->addElement($title);

        self::assertSame(
            ['article' => ['metadata' => ['title' => 'Title']]],
            $form->getValues()
        );

        $form->setDefaults(['article' => ['metadata' => ['title' => 'Default']]]);
        self::assertSame('Default', $title->getValue());
    }

    public function testNestedSubformsCompileStableRenderNamesAndIds(): void
    {
        $root = $this->newForm(ZeroForm::class);
        $root->setName('root');

        $basedata = $this->newForm(ZeroForm::class);
        $metadata = $this->newForm(ZeroForm::class);
        $metaTitle = (new TextElement())->setName('metaTitle');
        $metadata->addElement($metaTitle);
        $basedata->addSubForm($metadata, 'metadata');
        $root->addSubForm($basedata, 'basedata');

        self::assertSame('basedata[metadata][metaTitle]', $metaTitle->getFullyQualifiedName());
        self::assertSame('basedata-metadata-metaTitle', $metaTitle->getId());

        self::assertSame('basedata[metadata][metaTitle]', $metaTitle->getFullyQualifiedName());
        self::assertSame('basedata-metadata-metaTitle', $metaTitle->getId());
    }

    public function testChangingFormNamespacesUsesCurrentCompiledPath(): void
    {
        $form = $this->newForm(ZeroForm::class);
        $form->setName('profile');

        $city = (new TextElement())->setName('city')->setValue('Vicenza');
        $form->addElement($city);

        $form->setElementsBelongTo('billing[address]');
        self::assertSame('billing[address][city]', $city->getFullyQualifiedName());
        self::assertSame(['billing' => ['address' => ['city' => 'Vicenza']]], $form->getValues());

        // Changing the local path must not read a stale compiled namespace.
        $form->setElementsBelongTo('shipping[address]');
        self::assertSame('shipping[address][city]', $city->getFullyQualifiedName());
        $form->setDefaults(['shipping' => ['address' => ['city' => 'Padova']]]);
        self::assertSame('Padova', $city->getValue());
        self::assertSame(['shipping' => ['address' => ['city' => 'Padova']]], $form->getValues());
    }

    public function testFormLevelDescriptionAndOrderAreNotPartOfTheApi(): void
    {
        $form = $this->newForm(ZeroForm::class);
        self::assertFalse(method_exists($form, 'setDescription'));
        self::assertFalse(method_exists($form, 'getDescription'));
        self::assertFalse(method_exists($form, 'setOrder'));
        self::assertFalse(method_exists($form, 'getOrder'));

        // Element help text and child ordering are independent features.
        $city = (new TextElement())->setName('city')->setDescription('City of residence');
        $form->addElement($city, order: 5);
        self::assertSame('City of residence', $city->getDescription());
        self::assertSame(5, $form->get('city'));
    }

    /**
     * @template T of ZeroForm
     * @param class-string<T> $class
     * @return T
     */
    private function newForm(string $class): ZeroForm
    {
        /** @var T $form */
        $form = (new ReflectionClass($class))->newInstanceWithoutConstructor();
        return $form;
    }
}
