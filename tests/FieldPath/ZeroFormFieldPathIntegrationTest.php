<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\FieldPath;

use DalPraS\FormZero\Element\TextElement;
use DalPraS\FormZero\SubZeroForm;
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

        $basedata = $this->newForm(SubZeroForm::class);
        $metadata = $this->newForm(SubZeroForm::class);
        $metaTitle = (new TextElement())->setName('metaTitle');
        $metadata->addElement($metaTitle);
        $basedata->addSubForm($metadata, 'metadata');
        $root->addSubForm($basedata, 'basedata');

        self::assertSame('basedata[metadata][metaTitle]', $metaTitle->getFullyQualifiedName());
        self::assertSame('basedata-metadata-metaTitle', $metaTitle->getId());

        self::assertSame('basedata[metadata][metaTitle]', $metaTitle->getFullyQualifiedName());
        self::assertSame('basedata-metadata-metaTitle', $metaTitle->getId());
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
