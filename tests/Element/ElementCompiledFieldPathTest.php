<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\Element;

use DalPraS\FormZero\Element;
use PHPUnit\Framework\TestCase;

final class ElementCompiledFieldPathTest extends TestCase
{
    public function testFullyQualifiedNameAndIdAreStableAcrossRepeatedReads(): void
    {
        $element = (new Element())
            ->setName('metaTitle')
            ->setBelongsTo('basedata[metadata]');

        self::assertSame('basedata[metadata][metaTitle]', $element->getFullyQualifiedName());
        self::assertSame('basedata-metadata-metaTitle', $element->getId());

        self::assertSame('basedata[metadata][metaTitle]', $element->getFullyQualifiedName());
        self::assertSame('basedata-metadata-metaTitle', $element->getId());
    }

    public function testChangingNameInvalidatesCompiledNameAndId(): void
    {
        $element = (new Element())
            ->setName('first')
            ->setBelongsTo('group');

        self::assertSame('group[first]', $element->getFullyQualifiedName());
        self::assertSame('group-first', $element->getId());

        $element->setName('second');

        self::assertSame('group[second]', $element->getFullyQualifiedName());
        self::assertSame('group-second', $element->getId());
    }

    public function testChangingBelongsToInvalidatesCompiledNameAndId(): void
    {
        $element = (new Element())
            ->setName('title')
            ->setBelongsTo('first');

        self::assertSame('first[title]', $element->getFullyQualifiedName());

        $element->setBelongsTo('second[group]');

        self::assertSame('second[group][title]', $element->getFullyQualifiedName());
        self::assertSame('second-group-title', $element->getId());
    }

    public function testRenderBelongsToOverridesLogicalPathAndCanBeCleared(): void
    {
        $element = (new Element())
            ->setName('metaTitle')
            ->setBelongsTo('metadata');

        $element->setRenderBelongsTo('basedata[metadata]');
        self::assertSame('basedata[metadata][metaTitle]', $element->getFullyQualifiedName());

        $element->setRenderBelongsTo(null);
        self::assertSame('metadata[metaTitle]', $element->getFullyQualifiedName());
    }

    public function testArrayModeChangesNameButNotId(): void
    {
        $element = (new Element())
            ->setName('tags')
            ->setBelongsTo('article');

        self::assertSame('article[tags]', $element->getFullyQualifiedName());
        self::assertSame('article-tags', $element->getId());

        $element->setIsArray(true);

        self::assertSame('article[tags][]', $element->getFullyQualifiedName());
        self::assertSame('article-tags', $element->getId());
    }
}
