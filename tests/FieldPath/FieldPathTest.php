<?php

declare(strict_types=1);

namespace DalPraS\UnitTests\FieldPath;

use DalPraS\FormZero\FieldPath;
use PHPUnit\Framework\TestCase;

final class FieldPathTest extends TestCase
{
    public function testNestedNotationIsParsedOnceAndRenderedCanonically(): void
    {
        $path = FieldPath::fromString('foo[bar][baz]');

        self::assertSame('foo[bar][baz]', $path->toString());
        self::assertSame('baz', $path->leaf());
        self::assertFalse($path->isEmpty());
    }

    public function testAppendMergesNestedPaths(): void
    {
        $path = FieldPath::fromString('basedata')
            ->append(FieldPath::fromString('metadata[seo]'))
            ->append('metaTitle');

        self::assertSame('basedata[metadata][seo][metaTitle]', $path->toString());
    }

    public function testReadPreservesExistingFormZeroTraversalSemantics(): void
    {
        $data = [
            'foo' => [
                'bar' => [
                    'baz' => 'value',
                ],
            ],
        ];

        self::assertSame('value', FieldPath::fromString('foo[bar][baz]')->read($data));
    }

    public function testFindResolvesExactlyAndReturnsNullForMissingSegments(): void
    {
        $data = [
            'foo' => [
                'bar' => [
                    'baz' => 'value',
                ],
            ],
        ];

        self::assertSame('value', FieldPath::fromString('foo[bar][baz]')->find($data));
        self::assertNull(FieldPath::fromString('foo[missing][baz]')->find($data));
    }

    public function testWrapCreatesNestedArrayStructure(): void
    {
        self::assertSame(
            ['foo' => ['bar' => ['baz' => 'value']]],
            FieldPath::fromString('foo[bar][baz]')->wrap('value')
        );
    }

    public function testRemoveDeletesKeyAtNestedPathWithoutChangingSiblingData(): void
    {
        $data = [
            'foo' => [
                'bar' => [
                    'remove' => 1,
                    'keep' => 2,
                ],
            ],
            'other' => 3,
        ];

        self::assertSame(
            [
                'foo' => [
                    'bar' => [
                        'keep' => 2,
                    ],
                ],
                'other' => 3,
            ],
            FieldPath::fromString('foo[bar]')->remove($data, 'remove')
        );
    }
}
