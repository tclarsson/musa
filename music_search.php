<?php 
require_once 'environment.php';
// ------------------------------------------------------
//$page_nocontainer=true;
$page_title='Musikarkivet';
$user->admit([]);

// ------------------------------------------------------
$sql_table="
FROM musaUsers 
LEFT JOIN musaOrgs ON musaOrgs.org_id=musaUsers.org_id
LEFT JOIN musaStatusTypes user ON user.status_code=musaUsers.status_code
WHERE musaUsers.org_id={$user->current_org_id()}
";
$sql_group="";
//print_r("SELECT * $sql_table");exit;
//$r=$db->getRecFrmQry("SELECT * $sql_table");pa('$cols_visible='.json_encode(array_keys($r[0])).';');exit;
$cols_visible=["user_id","name","title","email","phone","external_visible","email_verified","status_code","role_code","last_login","user_created","org_name"];
$cols_searchable=["user_id","name","title","email","phone","external_visible","email_verified","user.status_code","role_code"];
$cols=get_columns_info($cols_visible);

$order = "name";
$purl="users_update.php";

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
//Card: Search
print("
<div class='card bg-light'>
    <div class='card-header'>
      <div class='row card-header-title'> 
          <div class='col'>{$user->current_org_name()}: Sökning</div>
          <div class='col-auto'>
          <button type='button' data-target='#pageHelp' title='Information och Hjälp' class='btn btn-info float-right ml-2' data-toggle='modal'><i class='fa fa-info-circle'></i></button>
          </div>
      </div>
    </div>
    <div class='card-body'>
        <div class='container'>
        <form action='' method='post'  class='needs-validation' novalidate>
        <input type='text' class='form-control' placeholder='Sök (tryck Enter)' name='search' value=''>
        <button type='submit' name='bt_create' class='btn btn-success'><i class='fa fa-eye'></i> Sök</button>

        </form>

        </div>
    </div>
</div>
</br>
");

$ts="";if(!empty($_REQUEST['search'])) $ts=htmlspecialchars($_REQUEST['search']);
print("
<form action='' method='get'>
<div class='form-row'>
    <div class='col'><h1>$page_title</h1></div>
    <div class='col-auto'>
        <input type='text' class='form-control' placeholder='Sök (tryck Enter)' name='search' value='$ts'>
    </div>
    <div class='col'>
        <a href='$purl?create' class='btn btn-success float-right ml-2' title='Skapa ny post' data-toggle='tooltip'><i class='fa fa-plus'></i> <i class='fa fa-user'></i></a>
        <a href='$_SERVER[SCRIPT_NAME]' class='btn btn-secondary ml-1' title='Återställ Tabell' data-toggle='tooltip'><i class='fa fa-undo'></i></a>
");
export_buttons();
print("
    </div>
</div>
</form>
");

?>


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
            //$a='<a href="$purl?target_id='. $i['org_id'] .'" title="Redigera Organisation" data-toggle="tooltip"><i class="fa fa-users"></i></a> ';
            print("<td>".$i[$col]."</td>");
        }
        print("<td>
        <a href='$purl?edit&user_id=$i[user_id]' title='Redigera' data-toggle='tooltip'><i class='fa fa-edit'></i></a>
        &nbsp| <a href='$purl?delete&user_id=$i[user_id]' data-toggle='tooltip' ".confOp('delete')."</a>'
        </td></tr>");
      }
      print('</tbody></table>');
      displayPagination($pageno,$total_pages,"&search=$search&order=$order&sort=$sort");

    ?> 
      
      
    <?php
    } else{
      echo "<p class='lead'><em>Hittar inget.</em></p>";
    }
require_once 'footer.php';
//------------------------------------------------
// modals:
//------------------------------------------------
$m['id']="pageHelp";
$m['head']=$page_title;
$m['body']="
<h3>Administrera användare</h3> 
<p>Här kan du </p>
<ul>
  <li>Lägga till, editera och ta bort användare</li>
</ul>
  <p>Om du har några frågor eller synpunkter, kan du skicka ett mail till <a href='mailto:".EMAIL_SUPPORT."?subject=MUSA Feedback'>".EMAIL_SUPPORT."</a></p>
";
make_modal($m)

?>
