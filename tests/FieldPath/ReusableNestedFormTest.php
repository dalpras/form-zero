<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\FieldPath;

use DalPraS\FormZero\Element;
use DalPraS\FormZero\Element\TextElement;
use DalPraS\FormZero\Utils\Hydrator;
use DalPraS\FormZero\ZeroForm;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ReusableNestedFormTest extends TestCase
{
    public function testAnOrdinaryFormIsStandaloneNestedAndStandaloneAgain(): void
    {
        $address = $this->form('AddressForm');
        $city = (new TextElement())->setName('city')->setValue('Vicenza');
        $address->addElement($city);

        self::assertFalse($address->isNested());
        self::assertFalse($address->isArray());
        self::assertSame('city', $city->getFullyQualifiedName());
        self::assertSame(['city' => 'Vicenza'], $address->getValues());

        $property = $this->form('property');
        $property->addSubForm($address, 'shipping');

        self::assertSame($address, $property->getSubForm('shipping'));
        self::assertTrue($address->isNested());
        self::assertTrue($address->isArray());
        self::assertSame('shipping[city]', $city->getFullyQualifiedName());
        self::assertSame('shipping', $address->getFullyQualifiedName());
        self::assertSame(['shipping' => ['city' => 'Vicenza']], $property->getValues());

        $property->setDefaults(['shipping' => ['city' => 'Padova']]);
        self::assertSame('Padova', $city->getValue());

        self::assertTrue($property->removeSubForm('shipping'));
        self::assertFalse($address->isNested());
        self::assertFalse($address->isArray());
        self::assertSame('AddressForm', $address->getName());
        self::assertSame('city', $city->getFullyQualifiedName());
        self::assertSame(['city' => 'Padova'], $address->getValues());

        // A previously detached instance may be embedded elsewhere.
        $other = $this->form('other');
        $other->addSubForm($address, 'billing');
        self::assertSame('billing[city]', $city->getFullyQualifiedName());
    }

    public function testAnArticleLikeDynamicTreeKeepsStableNamesAndData(): void
    {
        $article = $this->form('ArticleForm');
        $base = $this->form();
        $metadata = $this->form();
        $title = (new TextElement())->setName('metaTitle')->setValue('Example');
        $metadata->addElement($title);
        $base->addSubForm($metadata, 'metadata');
        $article->addSubForm($base, 'basedata');

        $sections = $this->form();
        $section = $this->form();
        $contents = $this->form();
        $content = $this->form();
        $text = (new TextElement())->setName('text')->setValue('Hello');
        $content->addElement($text);
        $contents->addSubForm($content, '42');
        $section->addSubForm($contents, 'contents');
        $sections->addSubForm($section, '7');
        $article->addSubForm($sections, 'sections');

        self::assertSame('basedata[metadata][metaTitle]', $title->getFullyQualifiedName());
        self::assertSame('sections[7][contents][42][text]', $text->getFullyQualifiedName());
        self::assertSame('sections-7-contents-42', $content->getId());
        self::assertSame([
            'basedata' => ['metadata' => ['metaTitle' => 'Example']],
            'sections' => [7 => ['contents' => [42 => ['text' => 'Hello']]]],
        ], $article->getValues());

        $article->setDefaults([
            'basedata' => ['metadata' => ['metaTitle' => 'Updated']],
            'sections' => [7 => ['contents' => [42 => ['text' => 'New text']]]],
        ]);
        self::assertSame('Updated', $title->getValue());
        self::assertSame('New text', $text->getValue());
    }

    public function testArticleImageLocaleWithHyphenPreservesFormNameAndFieldPath(): void
    {
        $article = $this->form('ImageEditForm');
        $localized = $this->form();
        $fallback = $this->form();
        $title = (new TextElement())->setName('title')->setValue('Caption');
        $fallback->addElement($title);

        $localized->addSubForm($fallback, 'x-default');
        $article->addSubForm($localized, 'localized');

        self::assertSame('x-default', $fallback->getName());
        self::assertSame('localized[x-default][title]', $title->getFullyQualifiedName());
        self::assertSame([
            'localized' => ['x-default' => ['title' => 'Caption']],
        ], $article->getValues());

        $article->setDefaults(['localized' => ['x-default' => ['title' => 'Updated']]]);
        self::assertSame('Updated', $title->getValue());
    }

    public function testArticleAccordionsCanPreserveLocalSelectorWhileFieldsAreNested(): void
    {
        $article = $this->form('ArticleForm');
        $sections = $this->form();
        $section = $this->form();
        $contents = $this->form();
        $contents->setAttrib('id', 'contents');
        $content = $this->form();
        $text = (new TextElement())->setName('body')->setValue('Text');
        $content->addElement($text);

        $contents->addSubForm($content, '42');
        $section->addSubForm($contents, 'contents');
        $sections->addSubForm($section, '7');
        $article->addSubForm($sections, 'sections');

        self::assertSame('contents', $contents->getId());
        self::assertSame('sections[7][contents]', $contents->getFullyQualifiedName());
        self::assertSame('sections[7][contents][42][body]', $text->getFullyQualifiedName());
    }

    public function testNestedFormsAlwaysNamespaceFieldsAndAccordionSelectorsArePreserved(): void
    {
        $root = $this->form('root');
        $plain = $this->form();
        $note = (new TextElement())->setName('note')->setValue('Note');
        $plain->addElement($note);
        $root->addSubForm($plain, 'plain');

        self::assertTrue($plain->isArray());
        self::assertSame('plain[note]', $note->getFullyQualifiedName());
        self::assertSame(['plain' => ['note' => 'Note']], $root->getValues());

        $sections = $this->form();
        $root->addSubForm($sections, 'sections');
        $section = $this->form();
        // AccordionDecorator uses getId() for its data-row-for attribute.
        $section->setAttrib('id', '42');
        $sections->addSubForm($section, '42');
        self::assertSame('42', $section->getId());
        self::assertSame('sections[42]', $section->getFullyQualifiedName());
    }

    public function testStandaloneExplicitNamespaceDoesNotDependOnArrayFlag(): void
    {
        $form = $this->form('AddressForm');
        $city = (new TextElement())->setName('city')->setValue('Vicenza');
        $form->addElement($city);

        $form->setElementsBelongTo('address');
        self::assertFalse($form->isNested());
        self::assertTrue($form->isArray());
        self::assertSame('address[city]', $city->getFullyQualifiedName());
        self::assertSame(['address' => ['city' => 'Vicenza']], $form->getValues());

        $form->setDefaults(['address' => ['city' => 'Padova']]);
        self::assertSame('Padova', $city->getValue());
        self::assertFalse(method_exists(ZeroForm::class, 'setIsArray'));
        self::assertTrue(method_exists(Element::class, 'setIsArray'));
    }

    public function testDetachingRestoresExplicitArrayNamespaces(): void
    {
        $root = $this->form('root');
        $child = $this->form('Original');
        $child->setElementsBelongTo('Original');
        $name = (new TextElement())->setName('field');
        $child->addElement($name);
        $originalBelongsTo = $name->getBelongsTo();

        $root->addSubForm($child, 'alias');
        self::assertSame('alias[field]', $name->getFullyQualifiedName());
        $root->clearSubForms();

        self::assertSame('Original', $child->getName());
        self::assertSame($originalBelongsTo, $name->getBelongsTo());
        self::assertSame('Original[field]', $name->getFullyQualifiedName());
    }

    public function testContactAccordionKeepsLocalRowSelectorAndNestedDataNames(): void
    {
        $root = $this->form('TypeForm');
        $options = $this->form();
        $area = $this->form();
        $area->setAttrib('id', 'italy');
        $attachment = $this->form();
        $label = (new TextElement())->setName('label')->setValue('CSV label');
        $attachment->addElement($label);
        $area->addSubForm($attachment, 'attachment');
        $options->addSubForm($area, 'italy');
        $root->addSubForm($options, 'options');

        self::assertSame('italy', $area->getId());
        self::assertSame('options[italy][attachment][label]', $label->getFullyQualifiedName());
        self::assertSame(['options' => ['italy' => ['attachment' => ['label' => 'CSV label']]]], $root->getValues());
    }

    public function testFieldsAddedWhileAttachedDoNotAcquirePermanentNamespace(): void
    {
        $root = $this->form('root');
        $address = $this->form('AddressForm');
        $root->addSubForm($address, 'shipping');

        $zip = (new TextElement())->setName('zip')->setValue('36100');
        $address->addElement($zip);
        self::assertSame('shipping[zip]', $zip->getFullyQualifiedName());
        self::assertSame(['shipping' => ['zip' => '36100']], $root->getValues());

        $root->removeSubForm('shipping');
        self::assertSame('', $zip->getBelongsTo());
        self::assertSame('zip', $zip->getFullyQualifiedName());
        self::assertSame(['zip' => '36100'], $address->getValues());
    }

    public function testExplicitElementNamespacesArePreservedUnderNesting(): void
    {
        $profile = $this->form('ProfileForm');
        $nickname = (new TextElement())
            ->setName('nickname')
            ->setBelongsTo('account[preferences]')
            ->setValue('N');
        $profile->addElement($nickname);

        self::assertSame('account[preferences][nickname]', $nickname->getFullyQualifiedName());
        self::assertSame([
            'account' => ['preferences' => ['nickname' => 'N']],
        ], $profile->getValues());

        $parent = $this->form('root');
        $parent->addSubForm($profile, 'profile');
        self::assertSame('profile[account][preferences][nickname]', $nickname->getFullyQualifiedName());
        self::assertSame([
            'profile' => ['account' => ['preferences' => ['nickname' => 'N']]],
        ], $parent->getValues());

        $parent->removeSubForm('profile');
        self::assertSame('account[preferences][nickname]', $nickname->getFullyQualifiedName());
    }

    public function testMissingNestedInputCannotLeakUnrelatedValuesIntoChild(): void
    {
        $root = $this->form('root');
        $address = $this->form('AddressForm');
        $city = (new ReusableFormCheckElement())->setName('city');
        $address->addElement($city);
        $root->addSubForm($address, 'shipping');

        // A flat city key must not fill or validate shipping[city].
        $root->setDefaults(['city' => 'Misplaced']);
        self::assertNull($city->getValue());
        self::assertFalse($root->isValid(['city' => 'Misplaced']));
        self::assertSame(['shipping' => ['city' => ['invalid']]], $root->getMessages());
        self::assertSame([], $root->getValidValues(['city' => 'Misplaced']));

        // Only the correctly wrapped child input is accepted.
        self::assertTrue($root->isValid(['shipping' => ['city' => 'Vicenza']]));
        self::assertSame(['shipping' => ['city' => 'Vicenza']], $root->getValues());
    }

    public function testNestedValidationPartialValidationAndErrorPaths(): void
    {
        $article = $this->form('article');
        $metadata = $this->form();
        $title = (new ReusableFormCheckElement())->setName('metaTitle');
        $metadata->addElement($title);
        $article->addSubForm($metadata, 'metadata');

        self::assertTrue($article->isValid(['metadata' => ['metaTitle' => 'OK']]));
        self::assertSame(
            ['metadata' => ['metaTitle' => 'OK']],
            $article->getValidValues(['metadata' => ['metaTitle' => 'OK']])
        );
        self::assertFalse($article->isValid(['metadata' => ['metaTitle' => 'INVALID']]));
        self::assertSame(
            ['metadata' => ['metaTitle' => ['invalid']]],
            $article->getMessages()
        );
        self::assertTrue($article->isValidPartial([]));
    }

    public function testArticleHydratorCanPopulateAttachedMetadataDirectly(): void
    {
        $article = $this->form('ArticleForm');
        $basedata = $this->form();
        $metadata = $this->form();
        $metaTitle = (new TextElement())->setName('metaTitle');
        $metadata->addElement($metaTitle);
        $basedata->addSubForm($metadata, 'metadata');
        $article->addSubForm($basedata, 'basedata');

        // ArticleForm::hydrateDefaults() performs precisely this direct call
        // on metadata/dates after the complete hierarchy has been attached.
        Hydrator::hydrateForm($metadata, static fn(string $field): string =>
            $field === 'metaTitle' ? 'Article metadata' : ''
        );
        self::assertSame('Article metadata', $metaTitle->getValue());
        self::assertSame([
            'basedata' => ['metadata' => ['metaTitle' => 'Article metadata']],
        ], $article->getValues());

        // The same code must also work for a newly reusable ordinary form.
        $address = $this->form('AddressForm');
        $city = (new TextElement())->setName('city');
        $address->addElement($city);
        $article->addSubForm($address, 'address');
        Hydrator::hydrateForm($address, static fn(string $field): string => 'Vicenza');
        self::assertSame('Vicenza', $city->getValue());
    }

    public function testHydratorCanReadAnOrdinaryAttachedFormForObjectUpdates(): void
    {
        $parent = $this->form('parent');
        $address = $this->form();
        $address->addElement((new TextElement())->setName('city')->setValue('Vicenza'));
        $parent->addSubForm($address, 'address');
        $entity = new class {
            public array $address = [];
            public function setAddress(array $value): void { $this->address = $value; }
        };

        Hydrator::hydrateObject($entity, $parent, static fn(string $field, mixed $value): mixed => $value);
        self::assertSame(['address' => ['city' => 'Vicenza']], $entity->address);
    }

    public function testChildCannotHaveTwoParents(): void
    {
        $a = $this->form('a');
        $b = $this->form('b');
        $child = $this->form('child');
        $a->addSubForm($child, 'child');

        $this->expectException(InvalidArgumentException::class);
        $b->addSubForm($child, 'child');
    }

    public function testCycleAndDuplicateOrderAreRejectedWithoutPartialAttachment(): void
    {
        $root = $this->form('root');
        $child = $this->form('child');
        $grandchild = $this->form('grandchild');
        $root->addSubForm($child, 'child');
        $child->addSubForm($grandchild, 'grandchild');

        try {
            $grandchild->addSubForm($root, 'root');
            self::fail('A cyclic form must be rejected');
        } catch (InvalidArgumentException) {
            self::assertNull($grandchild->getSubForm('root'));
        }

        $other = $this->form('other');
        $other->addSubForm($this->form(), 'first', 10);
        $candidate = $this->form('Candidate');
        try {
            $other->addSubForm($candidate, 'second', 10);
            self::fail('Duplicate order must be rejected');
        } catch (LogicException) {
            self::assertFalse($candidate->isNested());
            self::assertSame('Candidate', $candidate->getName());
        }
    }

    /** @param class-string<ZeroForm> $class */
    private function form(string $name = '', string $class = ZeroForm::class): ZeroForm
    {
        $form = (new ReflectionClass($class))->newInstanceWithoutConstructor();
        if ($name !== '') {
            $form->setName($name);
        }
        return $form;
    }
}

final class ReusableFormCheckElement extends Element
{
    public function isValid($value, $context = null): bool
    {
        $this->setValue($value);
        if ($value === null || $value === 'INVALID') {
            $this->setErrors(['invalid']);
            return false;
        }
        $this->clearErrorMessages();
        return true;
    }

    public function getMessages(): array
    {
        return $this->getErrorMessages();
    }
}
