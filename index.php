<?php
require_once 'environment.php';
$user->admit('root');
//$page_nocontainer=true;
require_once 'header.php';
pa($user->user_data);
require_once 'footer.php';
?>