<?php
/* mandatory.php */
return function () {
    return '<p>' . $this->getHelpers()->trans("(*) Fields marked with an asterisk are mandatory") . '</p>';
};