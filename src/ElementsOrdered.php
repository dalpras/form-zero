<?php declare(strict_types=1);

namespace DalPraS\FormZero;

use Countable;
use IteratorAggregate;
use Traversable;

abstract class ElementsOrdered implements IteratorAggregate, Countable
{
    /**
     * Order in which to display and iterate elements and forms
     * Associative array: "element name" => "order"
     */
    private array $ordered = [];

    /**
     * Ritorna l'ultimo valore di ordinamento inserito.
     */
    public function last(): int
    {
        if ($this->ordered === []) {
            return 0;
        }

        $key = array_key_last($this->ordered);
        return $this->ordered[$key];
    }

    /**
     * Returns a one dimensional numerical indexed array with the
     * Elements, SubZeroForms Values.
     *
     * The canonical iterator order is reused here so ordering logic lives in
     * one place and no repeated array_splice() operations are required.
     */
    public function getElementsAndSubFormsOrdered(): array
    {
        $ordered = [];

        foreach ($this as $element) {
            if ($element instanceof Element || $element instanceof ZeroForm) {
                $ordered[] = $element;
            }
        }

        return $ordered;
    }

    /**
     * Ritorna l'elemento o la subForm corrispondente al nome.
     */
    abstract public function getElementOrSubform($name);

    /**
     * Set element/subform order
     *
     * @param string|int $name
     */
    public function set(string|int $name, int $order): void
    {
        $this->ordered[$name] = $order;
    }

    /**
     * Ritorna l'ordinamento dell'elemento della form indicato
     */
    public function get(string|int $name): ?int
    {
        return $this->ordered[$name] ?? null;
    }

    /**
     * Remove element name/form from ordered
     */
    public function del(string|int $name): void
    {
        if (array_key_exists($name, $this->ordered)) {
            unset($this->ordered[$name]);
        }
    }

    /**
     * Iterate elements/subforms without mutable internal cursor state.
     */
    public function getIterator(): Traversable
    {
        foreach ($this->ordered as $name => $order) {
            yield $name => $this->getElementOrSubform($name);
        }
    }

    /**
     * Count of elements/subforms that are iterable
     */
    public function count(): int
    {
        return count($this->ordered);
    }

    /**
     * Sort items according to their order.
     */
    protected function sort(): void
    {
        $orders = [];

        foreach ($this->ordered as $key => $order) {
            if (isset($orders[$order]) && $orders[$order] !== $key) {
                throw new \LogicException(
                    'Form elements ' . $orders[$order] . ' and ' . $key .
                    ' have the same order (' . $order . ') - ' .
                    'this would result in only the last added element to be rendered'
                );
            }

            $orders[$order] = $key;
        }

        asort($this->ordered, SORT_NUMERIC);
    }
}
