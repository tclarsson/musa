<?php
require_once 'environment.php';
$user->admit();
//$page_nocontainer=true;
require_once 'header.php';
pa($user->data);
require_once 'footer.php';
?>