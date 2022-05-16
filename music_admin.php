<?php 
require_once 'environment.php';
require_once 'music.php';
require_once 'crud_simple.php';
// ------------------------------------------------------
$page_nocontainer=true;
$page_title='Musikarkivet';
$user->admit([]);

// ------------------------------------------------------
$crud=New MusicCrud('Musik',$user->current_org_id());
$crud->cols_visible=["title","subtitle","yearOfComp","movements","copies","notes","choirvoice_name","HOLIDAYS","EXTERNAL","OWNER","COMPOSER","ARRANGER","AUTHOR"];
//$crud->cols_visible=["storage_id","choirvoice_id","title","subtitle","yearOfComp","movements","copies","notes","serial_number","publisher","identifier","org_name","org_info","org_status_code","org_created","org_status_name","org_status_hidden","storage_id_owner","storage_name","person_id","person_id_owner","gender_id","country_id","family_name","first_name","date_born","date_dead","owner","COMPOSER","ARRANGER","AUTHOR"];
//$crud->cols_visible=["title","subtitle","yearOfComp","movements","copies","notes","publisher","COMPOSER","ARRANGER","AUTHOR"];
$crud->cols_searchable=["title","subtitle","yearOfComp","movements","copies","notes","publisher"];
$crud->order = "title";
// ------------------------------------------------------
$card=New Card("Musikarkivet");
$card->helpmodal=New Modal("helppage".__LINE__);
$card->helpmodal->body="<p>Här kan du söka i musikarkivet</p>";
// ------------------------------------------------------
$crud->controller();
// ------------------------------------------------------
// display page
// ------------------------------------------------------
require_once 'header.php';
$card->render();
$crud->render();
require_once 'footer.php';
exit;

?>