<?php
/* mandatory.php */

use DalPraS\SmartTemplate\Collection\RenderCollection;
use DalPraS\SmartTemplate\TemplateEngine;

return function(RenderCollection $render, TemplateEngine $template) {
    /** @var \DalPraS\SmartTemplate\TemplateEngine $template */
    $helpers = $template->getHelpers();    

    return '<p>' . $helpers->translator()->trans("(*) Fields marked with an asterisk are mandatory") . '</p>';
};