<?php declare(strict_types=1);

namespace DalPraS\FormZero;

use DalPraS\FormZero\Decorator\ElementsDecorator;
use DalPraS\FormZero\Decorator\FieldsetDecorator;
use DalPraS\FormZero\ZeroForm;

class SubZeroForm extends ZeroForm 
{
    /**
     * Whether or not form elements are members of an array
     */
    protected bool $isArray = true;

    public function loadDefaultDecorators(): static
    {
        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorators([
                ElementsDecorator::class,
                FieldsetDecorator::class
            ]);
        }
        return $this;
    }
}
