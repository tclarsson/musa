<?php
if(empty($page_title)) $page_title='Musikarkivet';
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <title><?php print($page_title);?></title>
    <?php require_once 'header.inc';?>
</head>
<body>
<?php 
if(empty($page_nocontainer)) {
    print('<div class="container">');
}
require_once 'navbar.php';
require_once 'messages.php';
setMessage("Testar message");
displayMessages($errors);
?>
