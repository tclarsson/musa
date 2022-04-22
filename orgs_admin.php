<?php 
require_once 'environment.php';
// ------------------------------------------------------
$page_title='Organisationer';
$user->admit(['super']);
// ------------------------------------------------------
$ws="org.status_hidden<4";
$ws="org.status_hidden<=3";
if(isset($_REQUEST['recover'])) $ws="org.status_hidden>3";
$sql_table="
FROM musaOrgs
LEFT JOIN musaStatusTypes org ON org.status_code=musaOrgs.status_code
WHERE $ws
";
$sql_group="GROUP BY musaOrgs.org_id";

//$cols=update_columns_info(['status_name'=>'Status']);
$cols_visible=['org_id', 'org_name', 'org_info','status_name', 'org_created'];
$cols_searchable=['org_name', 'org_info','status_name'];
$cols=get_columns_info($cols_visible);
//print("<pre>");print_r(json_encode($cols,JSON_PRETTY_PRINT));print("</pre>");


$order = "org_name";
$purl="orgs_update.php";

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
$ts="";if(!empty($_REQUEST['search'])) $ts=htmlspecialchars($_REQUEST['search']);
print("
<form action='' method='get'>
<div class='form-row'>
    <div class='col'><h1>$page_title</h1></div>
    <div class='col-auto'>
        <input type='text' class='form-control' placeholder='Sök (tryck Enter)' name='search' value='$ts'>
    </div>
    <div class='col'>
        <a href='$purl?create' class='btn btn-success float-right ml-2' title='Skapa ny post' data-toggle='tooltip'><i class='fa fa-plus'></i> <i class='fa fa-home'></i></a>
        <a href='$_SERVER[SCRIPT_NAME]' class='btn btn-secondary ml-1' title='Återställ Tabell' data-toggle='tooltip'><i class='fa fa-undo'></i></a>
");
export_buttons();
print("
    </div>
</div>
</form>
");


  if($rl){
    print('<table class="table table-striped table-sm table-bordered border" style="white-space:nowrap;"><thead class="thead-dark"><tr>');
    foreach ($cols_visible as $col) {
      draw_header();
    }
    print('<th style="width:5em;">Action</th></tr></thead><tbody>');
    foreach ($rl as $i) {
      print('<tr>');
      foreach ($cols_visible as $col) {
          //$a='<a href="org_update.php?target_id=$i[org_id]" title="Redigera Organisation" data-toggle="tooltip"><i class="fa fa-users"></i></a> ';
          print("<td>".$i[$col]."</td>");
      }
      if(isset($_REQUEST['recover'])) print("
      <td>
      <a href='$purl?edit&org_id=$i[org_id]' title='Redigera' data-toggle='tooltip'><i class='fa fa-edit'></i></a>
      &nbsp|&nbsp<a href='$purl?erase&org_id=$i[org_id]' data-toggle='tooltip' ".confOp('delete')."</a>
      </td></tr>
      ");
      else print("
      <td>
      <a href='users_admin.php?org_id=$i[org_id]' title='Användare' data-toggle='tooltip'><i class='fa fa-users'></i></a>
      &nbsp|&nbsp<a href='$purl?edit&org_id=$i[org_id]' title='Redigera' data-toggle='tooltip'><i class='fa fa-edit'></i></a>
      &nbsp|&nbsp<a href='$purl?delete&org_id=$i[org_id]' data-toggle='tooltip' ".confOp('delete')."</a>
      </td></tr>
      ");
    }
    print("</tbody></table>");
    displayPagination($pageno,$total_pages,"&search=$search&order=$order&sort=$sort");

  } else{
    echo "<p class='lead'><em>Hittar inget.</em></p>";
  }
require_once 'footer.php';
?>
