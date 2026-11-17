<?php declare(strict_types=1);

namespace DalPraS\FormZero\Element;

use DalPraS\FormZero\Element;
use DalPraS\FormZero\Element\Intefaces\ChoicesAlignmentInterface;
use DalPraS\FormZero\Element\Traits\MultiChoicesTrait;
use DalPraS\FormZero\Element\Intefaces\MultiChoicesInterface;
use DalPraS\FormZero\Element\Traits\ChoicesAlignmentTrait;

final class RadioElement extends Element implements MultiChoicesInterface, ChoicesAlignmentInterface
{
    use MultiChoicesTrait;
    use ChoicesAlignmentTrait;

    /**
     * Radio choice labels are escaped by default. Enable this only for
     * trusted, application-generated markup (for example icon templates).
     */
    private bool $htmlChoiceLabels = false;

    public function setHtmlChoiceLabels(bool $enabled): static
    {
        $this->htmlChoiceLabels = $enabled;
        return $this;
    }

    public function allowsHtmlChoiceLabels(): bool
    {
        return $this->htmlChoiceLabels;
    }
}
