<?php 
require_once 'environment.php';
require_once 'authentication.php'; 
require_once 'event.php'; 
require_once 'utils.php'; 

admit(['roller','edit']);

// ------------------------------------------------------
// set table constants
// ------------------------------------------------------
$table_name='tbRoleTypes';
$page_title='Rolltyper';


$sql_table="
FROM $table_name
WHERE 1
";

//$r=$db->getRecFrmQry("SELECT * $sql_table");pa('$cols_visible='.json_encode(array_keys($r[0])).';');exit;
$cols_visible=["rt_role_code","role_name","permissions"];
$cols_searchable=$cols_visible;
$cols=get_columns_info($cols_visible);
$order = $cols_visible[0];
$sort = 'asc';
$purl="roletypes_";
// ------------------------------------------------------



// ------------------------------------------------------
$export_enable="A";
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
            <div class="col-auto">
                <input type="text" class="form-control" placeholder="Sök (tryck Enter)" name="search" value="<?php if(!empty($_REQUEST['search'])) print(htmlspecialchars($_REQUEST['search']));?>">
            </div>
            <div class="col">
                <?php if($user->can(['admin','create'])):?>
                <a href="<?php print($purl);?>update.php?create" class="btn btn-success float-right ml-2" title='Skapa ny post' data-toggle='tooltip'><i class='fa fa-plus'></i></a>
                <?php endif;?>
                <a href="<?php print($_SERVER['SCRIPT_NAME']);?>" class="btn btn-secondary ml-1" title='Återställ Tabell' data-toggle='tooltip'><i class='fa fa-undo'></i></a>
                <?php export_buttons();?>
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
        foreach ($cols_visible as $col) print("<td>".$i[$col]."</td>");
        print('<td>');
        if($user->can(['admin','edit'])) print('<a href="'.$purl.'update.php?edit&amp;'.$cols_visible[0].'='.$i[$cols_visible[0]].'" title="Redigera" data-toggle="tooltip"><i class="fa fa-edit"></i></a> ');
        if($user->can(['admin','delete'])) print('<a href="'.$purl.'update.php?delete&amp;'.$cols_visible[0].'='.$i[$cols_visible[0]].'" data-toggle="tooltip" '.confOp("delete").'</a>');
        print('</td></tr>');
      }
      print('</tbody></table>');
      displayPagination($pageno,$total_pages,"&search=$search&order=$order&sort=$sort");
?>      
    <?php
    } else{
      print("<p class='lead'><em>Hittar inget som matchar: \"$search\".</em></p>");
    }
      ?>
                </div>
            </div>
        </div>
        <?php require_once 'footer.php';?>
</body>
</html>