<?php

declare(strict_types=1);

namespace DalPraS\UnitTests;

use DalPraS\FormZero\Element\TextElement;
use DalPraS\FormZero\ElementsOrdered;
use DalPraS\FormZero\SubZeroForm;
use DalPraS\FormZero\ZeroForm;
use IteratorAggregate;
use LogicException;
use ReflectionClass;
use PHPUnit\Framework\TestCase;

final class ElementsOrderedTest extends TestCase
{
    public function testUsesIteratorAggregate(): void
    {
        self::assertInstanceOf(IteratorAggregate::class, new ElementsOrderedFixture());
    }

    public function testNestedIterationDoesNotCorruptOuterIteration(): void
    {
        $ordered = new ElementsOrderedFixture();
        $ordered->add('a', 1);
        $ordered->add('b', 2);
        $ordered->add('c', 3);

        $outerKeys = [];
        $innerKeysByOuter = [];

        foreach ($ordered as $outerKey => $element) {
            $outerKeys[] = $outerKey;
            $innerKeys = [];
            foreach ($ordered as $innerKey => $innerElement) {
                $innerKeys[] = $innerKey;
            }
            $innerKeysByOuter[$outerKey] = $innerKeys;
        }

        self::assertSame(['a', 'b', 'c'], $outerKeys);
        self::assertSame([
            'a' => ['a', 'b', 'c'],
            'b' => ['a', 'b', 'c'],
            'c' => ['a', 'b', 'c'],
        ], $innerKeysByOuter);
    }

    public function testExplicitOrderIsSortedAndReturnedAsNumericArray(): void
    {
        $ordered = new ElementsOrderedFixture();
        $ordered->add('a', 1);
        $ordered->add('b', 2);
        $ordered->add('c', 0, sort: true);

        self::assertSame(['c', 'a', 'b'], array_keys(iterator_to_array($ordered)));

        $elements = $ordered->getElementsAndSubFormsOrdered();
        self::assertSame([0, 1, 2], array_keys($elements));
        self::assertSame(
            ['c', 'a', 'b'],
            array_map(static fn(TextElement $element): string => $element->getName(), $elements)
        );
    }

    public function testExplicitSubformOrderParticipatesInCanonicalIterationOrder(): void
    {
        /** @var ZeroForm $form */
        $form = (new ReflectionClass(ZeroForm::class))->newInstanceWithoutConstructor();
        $form->addElement((new TextElement())->setName('a'));
        $form->addElement((new TextElement())->setName('b'));

        /** @var SubZeroForm $subForm */
        $subForm = (new ReflectionClass(SubZeroForm::class))->newInstanceWithoutConstructor();
        $form->addSubForm($subForm, 'group', 0);

        self::assertSame(['group', 'a', 'b'], array_keys(iterator_to_array($form)));
        self::assertSame(
            ['group', 'a', 'b'],
            array_map(
                static fn(TextElement|ZeroForm $item): string => $item->getName(),
                $form->getElementsAndSubFormsOrdered()
            )
        );
    }

    public function testDuplicateExplicitOrderKeepsExistingException(): void
    {
        $ordered = new ElementsOrderedFixture();
        $ordered->add('first', 1);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Form elements first and second have the same order (1) - this would result in only the last added element to be rendered'
        );

        $ordered->add('second', 1, sort: true);
    }

    public function testLastDoesNotAffectIterationState(): void
    {
        $ordered = new ElementsOrderedFixture();
        $ordered->add('a', 1);
        $ordered->add('b', 2);
        $ordered->add('c', 3);

        foreach ($ordered as $key => $element) {
            self::assertSame(3, $ordered->last());
            self::assertSame('a', $key);
            break;
        }
    }
}

final class ElementsOrderedFixture extends ElementsOrdered
{
    /** @var array<string, TextElement> */
    private array $items = [];

    public function add(string $name, int $order, bool $sort = false): void
    {
        $this->items[$name] = (new TextElement())->setName($name);
        $this->set($name, $order);

        if ($sort) {
            $this->sort();
        }
    }

    public function getElementOrSubform($name): ?TextElement
    {
        return $this->items[$name] ?? null;
    }
}
