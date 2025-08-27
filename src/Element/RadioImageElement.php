<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element\MultiElement;
use Laminas\Validator\InArray;

/**
 * Text form element
 */
class RadioImageElement extends MultiElement
{
    protected array $attribs = [];

    /**
     * Is the value provided valid?
     *
     * Autoregisters InArray validator if necessary.
     *
     * @param string $value
     * @param mixed $context
     * @return bool
     */
    public function isValid($value, $context = null): bool
    {
        if ( empty($this->getValidator(InArray::class)) ) {
            $multiOptions = $this->getMultiOptions();
            $options      = [];

            foreach ($multiOptions as $opt_value => $opt_label) {
                $options[] = $opt_value;
            }

            $this->getValidatorChain()->attachByName(InArray::class, ['haystack' => $options], true);
        }
        return parent::isValid($value, $context);
    }

}
