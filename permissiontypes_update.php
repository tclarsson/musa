<?php 
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'environment.php';
require_once 'authentication.php'; 
require_once 'event.php'; 
require_once 'utils.php'; 

admit(['admin']);
// ------------------------------------------------------
require_once 'forms.php'; 
// ------------------------------------------------------
$return_url='permissiontypes_admin.php';
$conf['title']="Rättigheter";
$conf['table']="tbPermissionTypes";
$conf['cols']=["pt_permission_code","pt_permission_name","pt_permission_description","pt_permission_uris"];
$conf['keys']=[$conf['cols'][0]];
$conf['select']=[];
$conf['extern']=[];

// ------------------------------------------------------


// ------------------------------------------------------
// prepare key(s)
// ------------------------------------------------------
$dbkey=[];
foreach($conf['keys'] as $c) {
    if(isset($_REQUEST[$c])) $dbkey[$c]=$_REQUEST[$c];
}
$kid=$conf['keys'][0];

// ------------------------------------------------------

// ------------------------------------------------------
// get form values
foreach(array_merge($conf['cols'],$conf['extern']) as $c) $columns[$c]['value']=(!empty($_REQUEST[$c]))?$_REQUEST[$c]:null;
// ------------------------------------------------------

// ------------------------------------------------------
// Processing form data when form is submitted
// ------------------------------------------------------
// create new record
if(isset($_POST['bt_create'])){
    $rec=[];
    // get posted data
    foreach($conf['cols'] as $c) $rec[$c]=$columns[$c]['value'];
    // update record
    //pa($rec);
    $r=$db->insert($conf['table'],$rec);
    $id=$db->lastInsertId();
    $dbkey=[$kid=>$id];
    header("location: $return_url");
    exit;
}
// update record
if(isset($_POST['bt_update'])){
    if(!empty($_POST[$kid])){
        $rec=[];
        // get posted data
        foreach($conf['cols'] as $c) $rec[$c]=$columns[$c]['value'];

        // update record
        $r=$db->update($conf['table'],$rec,$dbkey);

        header("location: $return_url");
        exit;
    } 
}

// ------------------------------------------------------
// Processing form data when form called first time (GET)
// ------------------------------------------------------
// create new record


if(isset($_GET['create'])){
    // empty form
    foreach(array_merge($conf['cols'],$conf['extern']) as $c) $columns[$c]['value']=null;
    $title=$conf['title']."; Skapa ny post";
} else {
    if(!empty($_REQUEST[$kid])){
        // record key is needed

        // delete record
        if(isset($_GET['delete'])){
            $rec=$db->delete($conf['table'],$dbkey);
            header("location: $return_url");
            exit;
        }

        if(isset($_GET['read'])||isset($_REQUEST['edit'])){
            // get current record
            $rec=$db->get($conf['table'],$dbkey);
            if(count($rec)!=1) {
                pa($rec);
                print "URL doesn't contain valid id. Redirect to error page";
                exit;
            }
            $rec=$rec[0];
            // place in form
            foreach($conf['cols'] as $c) $columns[$c]['value']=$rec[$c];

            $title=$conf['title'];
        } else {
            pa($rec);
            print "Unknown operation.";
            exit();
        }
    
    } else {
        // no key = error
        //header("location: error.php");
        pa($rec);
        print "URL doesn't contain valid id. Redirect to error page";
        exit();
    }

}




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once 'header.inc';?>
    <title><?php print $title;?></title>
</head>
<body>
    <?php require_once 'navbar.php';?>
    <div class="container">
    <?php displayMessages($errors);?>
    </br>

    <h2><?php print $title;?></h2>

<form action="" method="post"  class="needs-validation" novalidate>

<?php

foreach($conf['cols'] as $c) if(in_array($c,$conf['select'])) gen_select($c); else gen_input($c);;
foreach($dbkey as $n => $v) print '<input type="hidden" name="'.$n.'" value="'.$v.'"/>';

if(isset($_REQUEST['edit'])) {
    print '<button type="submit" name="bt_update" class="btn btn-success"><i class="fa fa-edit"></i> Uppdatera</button> ';
} else if(isset($_REQUEST['create'])) {
    print '<button type="submit" name="bt_create" class="btn btn-success"><i class="fa fa-edit"></i> Spara</button> ';
} 
print('<a href="'.$return_url.'" class="btn btn-secondary fcommon" title="Återgå"><i class="fa fa-undo"></i> Tillbaka</a>');
?>



</form>
</div>

<?php require_once 'footer.php';?>
</body>
    <!-- Error messages   -->
    <?php modalErrors($errors);?>

</html>


