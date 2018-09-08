<?php
$obj1 = $this->service('DateTime');
$obj2 = $this->DateTime;

echo get_class($obj1).' '.($obj1===$obj2?'===':'!==').' '.get_class($obj2);
?>