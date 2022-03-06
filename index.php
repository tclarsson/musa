<?php
require_once 'environment.php';
$user->admit('root');
//$page_nocontainer=true;
require_once 'header.php';
pa($user->data);
require_once 'footer.php';
?>