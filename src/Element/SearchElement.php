<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element;

final class SearchElement extends Element
{
    public function init(): void
    {
        if ($this->getRequiredMessage() === null) {
            $this->setRequiredMessage('Inserisci un termine da ricercare');
        }
    }
}
