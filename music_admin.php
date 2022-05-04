<?php 
require_once 'environment.php';
require_once 'music.php';
require_once 'crud_simple.php';
// ------------------------------------------------------
//$page_nocontainer=true;
$page_title='Musikarkivet';
$user->admit([]);

// ------------------------------------------------------
$crud=New MusicCrud('Musik',$user->current_org_id());
$crud->base_on_class('Music');
//pa($crud);
$crud->sql_body="
,musaMusic.music_id
,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',comp.first_name,comp.family_name) SEPARATOR ', ') as comp
,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',arr.first_name,arr.family_name) SEPARATOR ', ') as arr
,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',auth.first_name,auth.family_name) SEPARATOR ', ') as auth
FROM musaMusic
LEFT JOIN musaOrgs ON musaOrgs.org_id=musaMusic.org_id
LEFT JOIN musaOrgStatusTypes ON musaOrgStatusTypes.org_status_code=musaOrgs.org_status_code
LEFT JOIN musaStorages ON musaStorages.storage_id=musaMusic.storage_id
LEFT JOIN musaMusicComposers ON musaMusicComposers.music_id=musaMusic.music_id
LEFT JOIN musaPersons comp ON comp.person_id=musaMusicComposers.person_id
LEFT JOIN musaMusicArrangers ON musaMusicArrangers.music_id=musaMusic.music_id
LEFT JOIN musaPersons arr ON arr.person_id=musaMusicArrangers.person_id
LEFT JOIN musaMusicAuthors ON musaMusicAuthors.music_id=musaMusic.music_id
LEFT JOIN musaPersons auth ON auth.person_id=musaMusicAuthors.person_id";
//$crud->sql_where="WHERE musaOrgStatusTypes.org_status_hidden=0 AND musaMusic.org_id={$user->current_org_id()}";
$crud->sql_where="WHERE 1";
$crud->sql_group="GROUP BY musaMusic.music_id";
//print_r("SELECT * $sql_table");exit;
//$r=$db->getRecFrmQry("SELECT * $sql_table");pa('$cols_visible='.json_encode(array_keys($r[0])).';');exit;
$crud->cols_visible=["title","subtitle","yearOfComp","movements","notes","publisher","comp","arr","auth"];
$crud->cols_searchable=["title","subtitle","yearOfComp","movements","notes","publisher"];
$crud->order = "title";
// ------------------------------------------------------
$card=New Card("Musikarkivet");
$card->helpmodal=New Modal("helppage".__LINE__);
$card->helpmodal->body="
<p>Här kan du söka i musikarkivet</p>
";
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