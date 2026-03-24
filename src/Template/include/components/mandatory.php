<?php
/* mandatory.php */

use DalPraS\SmartTemplate\Collection\RenderCollection;

return function(RenderCollection $render) {
    $helpers = $this->getHelpers();    

    return '<p>' . 
        $helpers->translator()->trans("(*) Fields marked with an asterisk are mandatory") 
        . '</p>';
};