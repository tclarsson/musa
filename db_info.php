<?php
require_once 'environment.php';
$user->admit();
//$page_nocontainer=true;
require_once 'header.php';

print("<h2>Database Info</h2>");
$sql="SELECT concat('DROP TABLE IF EXISTS `', table_name, '`;') as cmd FROM information_schema.tables WHERE table_schema = 'musa'";
$r=$db->getColFrmQry($sql);
foreach($r as $l) print("$l</br>");

print("<h2>Database Info</h2>");
$sql="SHOW TABLES";
$r=$db->getColFrmQry($sql);
foreach($r as $l) print("DROP TABLE IF EXISTS `$l`;</br>");

?>