<?php
/* mandatory.php */

return function(): string {
    $helpers = $this->getHelpers();    

    return '<p>' . 
        $helpers->translator()->trans("(*) Fields marked with an asterisk are mandatory") 
        . '</p>';
};
