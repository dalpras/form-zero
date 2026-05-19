<?php declare(strict_types=1);

namespace DalPraS\FormZero;

use Countable;
use DalPraS\FormZero\Element;
use DalPraS\FormZero\ZeroForm;
use Iterator;

abstract class ElementsOrdered implements Iterator, Countable
{
    /**
     * Order in which to display and iterate elements and forms
     * Associative array: "element name" => "order"
     */
    private array $ordered = [];

    /**
     * Ritorna l'ultimo valore di ordinamento inserito.
     *
     * @return integer
     */
    public function last(): int
    {
        return empty($this->ordered) ? 0 : end($this->ordered);
    }

    /**
     * Returns a one dimensional numerical indexed array with the
     * Elements, SubZeroForms Values.
     *
     * Subitems are inserted based on their order Setting if set,
     * otherwise they are appended, the resulting numerical index
     * may differ from the order value.
     */
    public function getElementsAndSubFormsOrdered(): array
    {
        $ordered = [];
        foreach ($this->ordered as $name => $order) {
            $element = $this->getElementOrSubform($name);
            switch (true) {
                case $element instanceof Element:
                case $element instanceof ZeroForm:
                    // aggiungo i vari pezzi a partire da quelli ordinati
                    array_splice($ordered, $order, 0, array($element));
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
     * Current element/subform/display group
     */
    public function current(): mixed
    {
        current($this->ordered);
        $key = key($this->ordered);
        return $this->getElementOrSubform($key);
    }

    /**
     * Current element/subform name
     */
    public function key(): string|int|null
    {
        return key($this->ordered);
    }

    /**
     * Move pointer to next element/subform/display group
     */
    public function next(): void
    {
        next($this->ordered);
    }

    /**
     * Move pointer to beginning of element/subform/display group loop
     */
    public function rewind(): void
    {
        reset($this->ordered);
    }

    /**
     * Determine if current element/subform/display group is valid
     */
    public function valid(): bool
    {
        return (current($this->ordered) !== false);
    }

    /**
     * Count of elements/subforms that are iterable
     */
    public function count(): int
    {
        return count($this->ordered);
    }

    /**
     * Sort items according to their order
     */
    protected function sort(): void
    {
        $items = [];
        $index = 0;
        foreach ($this->ordered as $key => $order) {
            if (null === $order) {
                // $order = $this->getElementOrSubform($key)->getOrder();
                $order = $this->get($key);
                if ($order === null) {
                    while (array_search($index, $this->ordered, true)) {
                        ++$index;
                    }
                    $items[$index] = $key;
                    ++$index;
                } else {
                    $items[$order] = $key;
                }
            } elseif (isset($items[$order]) && $items[$order] !== $key) {
                throw new \LogicException('Form elements ' .
                    $items[$order] . ' and ' . $key . ' have the same order (' . $order . ') - ' . 'this would result in only the last added element to be rendered'
                );
            } else {
                $items[$order] = $key;
            }
        }

        $items = array_flip($items);
        asort($items);
        $this->ordered = $items;
    }

}