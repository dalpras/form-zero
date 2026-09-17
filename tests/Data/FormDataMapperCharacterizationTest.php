<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Data;

use DalPraS\FormZero\Element;
use DalPraS\FormZero\Element\TextElement;
use DalPraS\FormZero\FormDataMapper;
use DalPraS\FormZero\SubZeroForm;
use DalPraS\FormZero\ZeroForm;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class FormDataMapperCharacterizationTest extends TestCase
{
    public function testFlatValuesDefaultsAndIgnoredElementsKeepExistingSemantics(): void
    {
        $form = $this->newForm(ZeroForm::class);
        $form->setName('article');

        $title = (new TextElement())->setName('title');
        $ignored = (new TextElement())->setName('ignored')->setIgnore(true);
        $form->addElement($title);
        $form->addElement($ignored);

        $form->setDefaults([
            'title' => 'Article',
            'ignored' => 'secret',
        ]);

        self::assertSame('Article', $title->getValue());
        self::assertSame('secret', $ignored->getValue());
        self::assertSame(['title' => 'Article'], $form->getValues());
    }

    public function testMapperCanProduceTheSameValuesAsZeroFormFacade(): void
    {
        [$root] = $this->createRepresentativeNestedForm();
        $root->setDefaults([
            'basedata' => [
                'title' => 'Article',
                'metadata' => [
                    'metaTitle' => 'Meta title',
                ],
            ],
        ]);

        $mapper = new FormDataMapper();

        self::assertSame($root->getValues(), $mapper->getValues($root));
    }

    public function testNestedArraySubformsKeepExistingValueShape(): void
    {
        [$root, , , $metaTitle] = $this->createRepresentativeNestedForm();

        $root->setDefaults([
            'basedata' => [
                'title' => 'Article',
                'metadata' => [
                    'metaTitle' => 'Meta title',
                ],
            ],
        ]);

        $expected = [
            'basedata' => [
                'title' => 'Article',
                'metadata' => [
                    'metaTitle' => 'Meta title',
                ],
            ],
        ];

        self::assertSame('Meta title', $metaTitle->getValue());
        self::assertSame($expected, $root->getValues());
        self::assertSame($expected, $root->getValues());
    }

    public function testExplicitBelongsToAndNonArraySubformKeepExistingShape(): void
    {
        $root = $this->newForm(ZeroForm::class);
        $root->setName('root');

        $seoTitle = (new TextElement())->setName('seoTitle')->setBelongsTo('seo');
        $root->addElement($seoTitle);

        $plain = $this->newForm(SubZeroForm::class);
        $plain->setIsArray(false);
        $note = (new TextElement())->setName('note');
        $plain->addElement($note);
        $root->addSubForm($plain, 'plain');

        $root->setDefaults([
            'seo' => ['seoTitle' => 'SEO title'],
            'plain' => ['note' => 'Plain note'],
        ]);

        self::assertSame('SEO title', $seoTitle->getValue());
        self::assertSame('Plain note', $note->getValue());
        self::assertSame([
            'seo' => ['seoTitle' => 'SEO title'],
            'plain' => ['note' => 'Plain note'],
        ], $root->getValues());
    }

    public function testValidValuesOmitMissingAndInvalidNestedBranches(): void
    {
        $root = $this->newForm(ZeroForm::class);
        $root->setName('root');

        $basedata = $this->newForm(SubZeroForm::class);
        $metadata = $this->newForm(SubZeroForm::class);

        $metaTitle = (new CharacterizationElement())->setName('metaTitle');
        $metaDescription = (new CharacterizationElement())->setName('metaDescription');
        $metadata->addElement($metaTitle);
        $metadata->addElement($metaDescription);

        $basedata->addSubForm($metadata, 'metadata');
        $root->addSubForm($basedata, 'basedata');

        self::assertSame([
            'basedata' => [
                'metadata' => [
                    'metaTitle' => 'Meta title',
                ],
            ],
        ], $root->getValidValues([
            'basedata' => [
                'metadata' => [
                    'metaTitle' => 'Meta title',
                    // metaDescription intentionally omitted
                ],
            ],
        ]));

        self::assertSame([], $root->getValidValues([
            'basedata' => [
                'metadata' => [
                    'metaTitle' => CharacterizationElement::INVALID_VALUE,
                ],
            ],
        ]));
    }

    public function testArrayValuedAndDisabledElementsKeepExistingSemantics(): void
    {
        $form = $this->newForm(ZeroForm::class);
        $form->setName('article');

        $tags = (new CharacterizationElement())
            ->setName('tags')
            ->setIsArray(true)
            ->setValue(['one', 'two']);

        $disabled = (new CharacterizationElement())
            ->setName('disabled')
            ->setValue('stored');
        $disabled->setAttrib('disabled', true);

        $form->addElement($tags);
        $form->addElement($disabled);

        self::assertSame([
            'tags' => ['one', 'two'],
            'disabled' => 'stored',
        ], $form->getValues());

        self::assertSame([
            'tags' => ['three', 'four'],
            'disabled' => 'submitted',
        ], $form->getValidValues([
            'tags' => ['three', 'four'],
            'disabled' => 'submitted',
        ]));
    }

    /**
     * @return array{ZeroForm, SubZeroForm, SubZeroForm, TextElement}
     */
    private function createRepresentativeNestedForm(): array
    {
        $root = $this->newForm(ZeroForm::class);
        $root->setName('root');

        $basedata = $this->newForm(SubZeroForm::class);
        $title = (new TextElement())->setName('title');
        $basedata->addElement($title);

        $metadata = $this->newForm(SubZeroForm::class);
        $metaTitle = (new TextElement())->setName('metaTitle');
        $metadata->addElement($metaTitle);

        $basedata->addSubForm($metadata, 'metadata');
        $root->addSubForm($basedata, 'basedata');

        return [$root, $basedata, $metadata, $metaTitle];
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

final class CharacterizationElement extends Element
{
    public const string INVALID_VALUE = '__invalid__';

    public function isValid($value, $context = null): bool
    {
        $this->setValue($value);
        return $value !== self::INVALID_VALUE;
    }
}
