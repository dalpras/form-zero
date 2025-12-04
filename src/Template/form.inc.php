<?php
/* form.inc.php */

use DalPraS\FormZero\Element\CheckboxElement;
use DalPraS\FormZero\Element\CheckboxMultiElement;
use DalPraS\FormZero\Element\DatePickerElement;
use DalPraS\FormZero\Element\EmailElement;
use DalPraS\FormZero\Element\HashElement;
use DalPraS\FormZero\Element\HiddenElement;
use DalPraS\FormZero\Element\PasswordElement;
use DalPraS\FormZero\Element\RadioElement;
use DalPraS\FormZero\Element\SearchElement;
use DalPraS\FormZero\Element\SelectElement;
use DalPraS\FormZero\Element\SelectMultiElement;
use DalPraS\FormZero\Element\SubmitElement;
use DalPraS\FormZero\Element\SymfileElement;
use DalPraS\FormZero\Element\SymfileMultiElement;
use DalPraS\FormZero\Element\TextareaElement;
use DalPraS\FormZero\Element\TextElement;

return [
    'components' => [
        'feedback'
            => require(__DIR__ . '/include/components/feedback.php'),
        'label'
            => require(__DIR__ . '/include/components/label.php'),
        'description'
            => require(__DIR__ . '/include/components/description.php'),
        'mandatory'
            => require(__DIR__ . '/include/components/mandatory.php'),
    ],

    'element' => fn($type) => match ($type) {
        CheckboxElement::class 
            => require(__DIR__ . '/include/elements/checkbox.php'),

        DatePickerElement::class
            => require(__DIR__ . '/include/elements/date-picker.php'),

        HiddenElement::class, 
        HashElement::class,
            => require(__DIR__ . '/include/elements/hidden.php'),

        RadioElement::class, 
        CheckboxMultiElement::class
            => require(__DIR__ . '/include/elements/radio.php'),

        SelectElement::class, 
        SelectMultiElement::class
            => require(__DIR__ . '/include/elements/select.php'),

        SubmitElement::class
            => require(__DIR__ . '/include/elements/submit.php'),

        TextElement::class, 
        EmailElement::class, 
        SearchElement::class, 
        PasswordElement::class
            => require(__DIR__ . '/include/elements/input.php'),
        
        SymfileElement::class, 
        SymfileMultiElement::class
            => require(__DIR__ . '/include/elements/symfile.php'),
        
        TextareaElement::class
            => require(__DIR__ . '/include/elements/textarea.php'),
    },

    'html' 
        => require(__DIR__ . '/include/html/html.php'),

];