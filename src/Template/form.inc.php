<?php
/* form.inc.php */

use DalPraS\FormZero\Element\HashElement;
use DalPraS\FormZero\Element\TextElement;
use DalPraS\FormZero\Element\EmailElement;
use DalPraS\FormZero\Element\RadioElement;
use DalPraS\FormZero\Element\HiddenElement;
use DalPraS\FormZero\Element\SearchElement;
use DalPraS\FormZero\Element\SelectElement;
use DalPraS\FormZero\Element\SubmitElement;
use DalPraS\FormZero\Element\SymfileElement;
use DalPraS\FormZero\Element\CheckboxElement;
use DalPraS\FormZero\Element\PasswordElement;
use DalPraS\FormZero\Element\TextareaElement;
use DalPraS\FormZero\Element\DatePickerElement;
use DalPraS\FormZero\Element\SelectMultiElement;
use DalPraS\FormZero\Element\SymfileMultiElement;
use DalPraS\FormZero\Element\CheckboxMultiElement;

return [
    'components' => [
        'feedback'
            => $this->require(__DIR__ . '/include/components/feedback.php'),
        'label'
            => $this->require(__DIR__ . '/include/components/label.php'),
        'description'
            => $this->require(__DIR__ . '/include/components/description.php'),
        'mandatory'
            => $this->require(__DIR__ . '/include/components/mandatory.php'),
    ],

    'element' => fn($type) => match ($type) {
        CheckboxElement::class 
            => $this->require(__DIR__ . '/include/elements/checkbox.php'),

        DatePickerElement::class
            => $this->require(__DIR__ . '/include/elements/date-picker.php'),

        HiddenElement::class, 
        HashElement::class,
            => $this->require(__DIR__ . '/include/elements/hidden.php'),

        RadioElement::class, 
        CheckboxMultiElement::class
            => $this->require(__DIR__ . '/include/elements/radio.php'),

        SelectElement::class, 
        SelectMultiElement::class
            => $this->require(__DIR__ . '/include/elements/select.php'),

        SubmitElement::class
            => $this->require(__DIR__ . '/include/elements/submit.php'),

        TextElement::class, 
        EmailElement::class, 
        SearchElement::class, 
        PasswordElement::class
            => $this->require(__DIR__ . '/include/elements/input.php'),
        
        SymfileElement::class, 
        SymfileMultiElement::class
            => $this->require(__DIR__ . '/include/elements/symfile.php'),
        
        TextareaElement::class
            => $this->require(__DIR__ . '/include/elements/textarea.php'),
    },

    'html' 
        => $this->require(__DIR__ . '/include/html/html.php'),

];