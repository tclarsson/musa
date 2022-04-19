<?php 
require_once 'environment.php';
// ------------------------------------------------------
$page_title='Användare';
$user->admit(['super']);
// ------------------------------------------------------
$sql_table="
FROM musaUsers
LEFT JOIN musaOrgs ON musaOrgs.org_id=musaUsers.org_id
WHERE musaUsers.org_id=$_REQUEST[org_id]
";
$sql_group="";

//$r=$db->getRecFrmQry("SELECT * $sql_table");pa('$cols_visible='.json_encode(array_keys($r[0])).';');exit;
$cols_visible=["user_id","name","title","email","phone","role","show","email_verified","status_code","role_code","last_login","user_created","org_name"];
$cols_searchable=["user_id","name","title","email","phone","role","show","email_verified","status_code","role_code"];
$cols=get_columns_info($cols_visible);

$order = "org_name";
// ------------------------------------------------------

// ------------------------------------------------------
$export_enable="A";
set_search_sort_pagination();
// ------------------------------------------------------
// do query with pagination limits for display
$rl=$db->getRecFrmQry("SELECT * $sql LIMIT $offset, $no_of_records_per_page");

// ------------------------------------------------------
//setMessage("Draft Org admin");

// ------------------------------------------------------
// display page
// ------------------------------------------------------
require_once 'header.php';

?>


    <form action="" method="get">
        <div class="form-row">
            <div class="col">
                <h1><?php print($page_title);?></h1>
            </div>
            <div class="col">
                <?php 
                export_buttons();
                ?>
            </div>
        </div>
    </form>

    <?php

    if($rl){
      print('<table class="table table-striped table-sm table-bordered border"><thead class="thead-dark"><tr>');
      foreach ($cols_visible as $col) {
        draw_header();
      }
      print('<th style="width:5em;">Action</th></tr></thead><tbody>');
      foreach ($rl as $i) {
        print('<tr>');
        foreach ($cols_visible as $col) {
            //$a='<a href="user_update.php?target_id='. $i['org_id'] .'" title="Redigera Organisation" data-toggle="tooltip"><i class="fa fa-users"></i></a> ';
            print("<td>".$i[$col]."</td>");
        }
        print('<td>');
        print('<a href="user_update.php?edit&target_id='. $i['user_id'] .'" title="Redigera" data-toggle="tooltip"><i class="fa fa-edit"></i></a> ');
        print('&nbsp| <a href="user_update.php?delete&target_id='. $i['user_id'] .'" data-toggle="tooltip" '.confOp("delete").'</a>');
        print('</td></tr>');
      }
      print('</tbody></table>');
      displayPagination($pageno,$total_pages,"&search=$search&order=$order&sort=$sort");

    ?> 
      
      
    <?php
    } else{
      echo "<p class='lead'><em>Hittar inget.</em></p>";
    }
require_once 'footer.php';
?>
