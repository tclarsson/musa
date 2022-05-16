<?php
require_once 'environment.php';
require_once 'music.php';
require_once 'crud_simple.php';
$user->admit();

//$page_nocontainer=true;
// check import
if(isset($_REQUEST['execute'])&&!empty($_REQUEST['table'])){
    pa($_REQUEST);
    foreach($_REQUEST['table'] as $t) {
        $sql="DELETE FROM musa.musa$t";$db->deleteFrmQry($sql);pa($sql);    
    }
    //$sql="DELETE FROM musa.musaMusic";$db->deleteFrmQry($sql);pa($sql);
    //$sql="DELETE FROM musa.musaInstruments";$db->deleteFrmQry($sql);pa($sql);
    
}

$deletemod=New Modal("confirmdelete".__LINE__);


$cf=new Card('Radera Data');
$cf->helpmodal=New Modal("helppage".__LINE__);
$cf->helpmodal->body="
<h3>Radera era data<h3> 
<p>Här kan du radera all musik eller all extrainformation som inte används i något musikstycke.</p>
<h3>Varning! Raderad data kan inte återställas!<h3> 
<p>Följande kan raderas:</p>
<ul>
<li>Musik (*)</li>
<li>Personer</li>
</ul>
<p>Not(*): Om all musik raderas kommer all övrig information också vara möjlig att radera.</p>
";
$cf->body="
<form action='' method='post'>
<div class='form-check'>
<input class='form-check-input' type='checkbox' value='Music' name='table[]'>
<label class='form-check-label'>Music</label>
</div>
<div class='form-check'>
<input class='form-check-input' type='checkbox' value='Persons' name='table[]'>
<label class='form-check-label'>Persons</label>
</div>
</br>
    <div class='row'>
    <button type='submit' name='execute' class='btn-sm btn-danger' ".$deletemod->trigger()." title='Radera post'><i class='fa fa-trash'></i> Radera all markerad data</button>
    </div>
</form>
";

require_once 'header.php';
$cf->render();
require_once 'footer.php';
?>
