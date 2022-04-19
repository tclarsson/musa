<?php 
require_once 'environment.php';
require_once 'authentication.php'; 
require_once 'event.php'; 
require_once 'utils.php'; 

admit(['admin']);

/*
antal seniorer i familjer:
SELECT *
,admins.given_name as admin_name
,IF((YEAR(NOW()) - tbMembers.birth_year)<=18,'JUNIOR','SENIOR') as age_group 
,IF((YEAR(NOW()) - tbMembers.birth_year)>18,1,0) as senior 
, COUNT(tbMembers.user_id)
FROM tbFamilies
JOIN tbMembers ON tbFamilies.families_family_id=tbMembers.family_id 
JOIN tbMembers as admins ON tbFamilies.family_admin_id=admins.user_id 
LEFT JOIN tbMembershipTypes ON tbMembershipTypes.mt_membership_code=tbMembers.membership_code 
WHERE membership_hide is null AND (YEAR(NOW()) - tbMembers.birth_year)>18
GROUP BY families_family_id

antal medlemmar i familj:
SELECT *
, COUNT(tbMembers.user_id)
FROM tbFamilies
JOIN tbMembers ON tbFamilies.families_family_id=tbMembers.family_id 
JOIN tbMembers as admins ON tbFamilies.family_admin_id=admins.user_id 
LEFT JOIN tbMembershipTypes ON tbMembershipTypes.mt_membership_code=tbMembers.membership_code 
WHERE membership_hide is null
GROUP BY families_family_id

familjer utan medlemmar:
SELECT *
FROM tbFamilies
LEFT JOIN tbMembers ON tbFamilies.families_family_id=tbMembers.family_id 
WHERE tbMembers.user_id is null

medlemmar utan familj:
SELECT *
FROM tbMembers
LEFT JOIN tbFamilies ON tbFamilies.families_family_id=tbMembers.family_id 
WHERE not tbMembers.family_id is null and tbFamilies.families_family_id is null

emailadresser som inte är verifierade:
SELECT *
FROM tbMembers
WHERE not email is null AND (not email_verified='VERIFIED' OR email_verified is null) AND not membership_code = 'INBJUDEN'

// duplicates
SELECT *
FROM tbMembers
GROUP BY given_name,family_name

// members without transactions
SELECT *
FROM tbMembers
LEFT JOIN tbTransactions ON tbTransactions.transaction_user_id=tbMembers.user_id
WHERE tbTransactions.transaction_user_id is null and not membership_code = 'INBJUDEN' and email is null
GROUP BY user_id
*/
// ------------------------------------------------------
// set table constants
// ------------------------------------------------------

//$sql_table=get_sql_members()." WHERE membership_hide is null";
//$r=$db->getRecFrmQry("SELECT * $sql_table");pa('$cols_visible='.json_encode(array_keys($r[0])).';');exit;

/*
,COUNT(tbMembers.user_id)
JOIN tbMembers as admins ON tbFamilies.family_admin_id=admins.user_id 
LEFT JOIN tbMembershipTypes ON tbMembershipTypes.mt_membership_code=tbMembers.membership_code 
WHERE membership_hide is null
*/


$sql_table="
,COUNT(families_family_id) as family_num_members
FROM tbFamilies
JOIN tbMembers as admins ON tbFamilies.family_admin_id=admins.user_id 
JOIN tbMembers ON tbFamilies.families_family_id=tbMembers.family_id 
LEFT JOIN tbMembershipTypes ON tbMembershipTypes.mt_membership_code=tbMembers.membership_code 
WHERE membership_hide is null
";
$sql_group="GROUP BY families_family_id";

//$cols_visible=['user_id', 'given_name', 'family_name', 'email', 'membership_code', 'family_id', 'mobile', 'created', 'role_code', 'family_family_name', 'member_membership'];
$cols_visible=['families_family_id','family_family_name','family_num_members', 'given_name', 'family_name', 'mobile', 'email'];
$cols_searchable=['families_family_id','family_family_name', 'given_name', 'family_name', 'mobile', 'email'];
$cols=get_columns_info($cols_visible);

$order = "family_family_name";
$page_title='Familjer';
// ------------------------------------------------------

// ------------------------------------------------------
$export_enable="AE";
set_search_sort_pagination();
// ------------------------------------------------------

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once 'header.inc';?>
    <title><?php print($page_title);?></title>
</head>
<body>
    <?php require_once 'navbar.php';?>
    </br>
    <div class="container-fluid">

<?php displayMessages($errors);?>

    <form action="" method="get">
        <div class="form-row">
            <div class="col">
                <h1><?php print($page_title);?></h1>
            </div>
            <?php /*
            <div class="col-auto">
                <input type="text" class="form-control" placeholder="Sök (tryck Enter)" name="search" value="<?php if(!empty($_REQUEST['search'])) print(htmlspecialchars($_REQUEST['search']));?>">
            </div>
*/?>
            <div class="col">
                <?php 
                //if($user->can(['admin','create'])) print('<a href="families_update.php?create" class="btn btn-success float-right ml-2" title="Skapa ny familj" data-toggle="tooltip"><i class="fa fa-user-plus"></i></a>');
                export_buttons();
                //print('<a href="<'.$_SERVER['SCRIPT_NAME'].'>" class="btn btn-secondary ml-1" title="Återställ Tabell" data-toggle="tooltip"><i class="fa fa-undo"></i></a>');
                ?>
            </div>
        </div>
    </form>

    <?php

    // ------------------------------------------------------
    // do query with pagination limits for display
    $rl=$db->getRecFrmQry("SELECT * $sql LIMIT $offset, $no_of_records_per_page");
    if($rl){
      print('<table class="table table-striped table-sm table-bordered border"><thead class="thead-dark"><tr>');
      foreach ($cols_visible as $col) {
        draw_header();
      }
      print('<th style="width:5em;">Action</th></tr></thead><tbody>');
      foreach ($rl as $i) {
        print('<tr>');
        foreach ($cols_visible as $col) {
            $a="";
            if($col=='family_family_name') if(!empty($i['family_id'])) if($user->can(['admin','edit'])) 
            $a='<a href="families_update.php?target_id='. $i['family_id'] .'" title="Redigera Familj" data-toggle="tooltip"><i class="fa fa-users"></i></a> ';
            print("<td>".$i[$col]." $a</td>");
        }
        print('<td>');
        if($user->can(['admin','edit'])) print('<a href="families_update.php?edit&target_id='. $i['families_family_id'] .'" title="Redigera" data-toggle="tooltip"><i class="fa fa-users"></i></a> ');
        //if($user->can(['admin','delete'])) print('&nbsp| <a href="families_update.php?delete&target_id='. $i['families_family_id'] .'" data-toggle="tooltip" '.confOp("familydelete").'</a>');
        print('</td></tr>');
      }
      print('</tbody></table>');
      displayPagination($pageno,$total_pages,"&search=$search&order=$order&sort=$sort");

    ?> 
      
      
    <?php
    } else{
      echo "<p class='lead'><em>Hittar inget.</em></p>";
    }
      ?>
                </div>
            </div>
        </div>

<!--  Card with family admin-->
<?php
$sql="
SELECT *
,COUNT(families_family_id) as family_num_members
,IF((YEAR(NOW()) - tbMembers.birth_year)<=".$membership_table['JUNIOR']['membership_max_age'].",'JUNIOR','SENIOR') as  age_group
FROM tbFamilies
JOIN tbMembers as admins ON tbFamilies.family_admin_id=admins.user_id 
JOIN tbMembers ON tbFamilies.families_family_id=tbMembers.family_id 
LEFT JOIN tbMembershipTypes ON tbMembershipTypes.mt_membership_code=tbMembers.membership_code 
WHERE membership_hide is null
AND NOT (YEAR(NOW()) - tbMembers.birth_year)<=".$membership_table['JUNIOR']['membership_max_age']."
GROUP BY families_family_id
HAVING family_num_members>2
ORDER BY family_family_name
";
$fl=$db->getRecFrmQry($sql);

?>
<div class="container">

</br>
<div class="card bg-light">
<form action="" method="post" class="card-header">
    <div class="row card-header-title"> 
        <div class="col">Fler än 2 Seniorer per familj</div>
        <div class="col">
        <!--
        <button type="button" onclick="goBack()" title="Tillbaka" class="btn btn-secondary float-right ml-1" ><i class="fa fa-undo"></i> </button>
        <a href="family_update.php?add&family_id=<?php print($family_data['family_id']);?>" class="btn btn-success float-right ml-1" title='Skapa ny familjemedlem' data-toggle='tooltip'><i class='fa fa-user-plus'></i> Medlem</a>
        <a href="family_delete.php?erase&family_id=<?php print($family_data['family_id']);?>" class="btn btn-danger float-right ml-1" title='Avregistrera familj' data-toggle='tooltip'><i class='fa fa-trash'></i> Familj</a>
        -->
        </div>
    </div>

  <div class="card-body">
<?php if(!empty($fl)):?>    

<table class="table  table-striped table-sm border">

  <thead class="thead-dark">
    <tr>
    <th>Familj</th>
    <th>Antal Seniorer</th>
    <th>Familjeansvarig</th>
    </tr>
  </thead>      
<?php
foreach ($fl as $i) {
        print("<tr>");
        print("<td>($i[family_id]) $i[family_family_name] <a href='families_update.php?edit&target_id=$i[families_family_id]' title='Redigera familj' data-toggle='tooltip'><i class='fa fa-users'></i></a></td>");
        print("<td>$i[family_num_members] <a href='families_update.php?edit&target_id=$i[families_family_id]' title='Redigera familj' data-toggle='tooltip'><i class='fa fa-users'></i></a></td>");
        print("<td>$i[given_name] $i[family_name] <a href='members_update.php?edit&target_id=$i[family_admin_id]' title='Redigera familjeansvarig' data-toggle='tooltip'><i class='fa fa-user-edit'></i></a></td>");
        print("</tr>");
    }
?>
</table>
<?php endif;  // check empty family ?>
</div>
    </form>

</div>


        <?php require_once 'footer.php';?>
</body>
</html>