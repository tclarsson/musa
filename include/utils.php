<?php 


// ------------------------------------------------------
// get global columns info
$columns_filename='columns.json';
$columns=json_decode(file_get_contents($columns_filename,true),true);
// ------------------------------------------------------

function get_columns_info($ca){
    global $columns;

    $keys=array_diff(($ca),['token','password']);
    $cols=[];
    foreach($keys as $i) {
        if(empty($columns[$i])) {
            //$columns[$i]=['name'=>$i,'header'=>$i,'type'=>'s']; 
            $columns[$i]=['header'=>$i,'type'=>'s']; 
        }
        $cols[$i]=$columns[$i]; 
    }
    return $cols;
}

function update_columns_info($ca){
    global $columns,$columns_filename;
    $update=false;
    if(!empty($ca)){
        $keys=array_diff(array_keys($ca),['token','password']);
        // add missing keys with default values
        foreach($keys as $i) {
            if(empty($columns[$i])) {
                $columns[$i]=['name'=>$i,'header'=>$i,'type'=>'s']; 
                if(!empty($ca[$i])) $columns[$i]['header']=$ca[$i];
                $update=true;
            }
        }
        if($update){
            // update file
            //$fn=realpath($columns_filename);
            $fn=$columns_filename;
            //var_dump($columns_filename);var_dump($fn);exit();
            file_put_contents($fn,json_encode($columns,JSON_PRETTY_PRINT));
        }
    }
    return $update;
}

// ------------------------------------------------------
// Modals
// ------------------------------------------------------
function make_modal($m){
    print("
    <div class='modal fade' id='$m[id]' tabindex='-1' role='dialog'>
        <div class='modal-dialog' role='document'>
            <div class='modal-content'>
            <div class='modal-header modal-header-title bg-info text-light'>$m[head]
                <button type='button' class='close text-light' data-dismiss='modal'>&times;</button>
            </div>
            <div class='modal-body'>$m[body]</div>
            <div class='modal-footer'><button type='button' class='btn btn-secondary' data-dismiss='modal'>Stäng</button></div>
            </div>
        </div>
    </div>
    ");
}
// ------------------------------------------------------
// Below features are not reviewed
// ------------------------------------------------------

function arr2table($t){
    if(!empty($t)) {
        print('
        <table class="table  table-striped table-sm border">
        <thead class="thead-dark">
        <tr>');
        foreach (array_keys($t[0]) as $h) {
            print("<th>$h</th>");
        }
        print("</tr></thead>");
        foreach ($t as $ri => $rd) {
            print("<tr>");
            foreach ($rd as $ci => $v) {
                print("<td>".$v."</td>");
            }
            print("</tr>");
        }
        print("</table>");
    } else {
        print("<p>Inget tidigare data.</p>");
    }
}

function conf2table($t,$tc){
    print('
    <table class="table  table-striped table-sm border">
    <thead class="thead-dark">
      <tr>');
      foreach ($tc['labels'] as $h) {
          print("<th>$h</th>");
      }
      print('<th class="text-center">Action</th>');
    print("</tr></thead><tbody>");
    foreach ($t as $ri => $rd) {
        print("<tr>");
        foreach ($rd as $ci => $v) {
            print("<td>".$v."</td>");
        }
        print('<td align="center">');
        $id=$rd[$tc['keys'][0]];
        print('<a href="crud_edit.php?editId='.$id.'" class="text-primary"><i class="fa fa-fw fa-edit"></i> Ändra</a> | 
        <a href="crud_delete.php?delId='.$id.'" class="text-danger" onClick="return confirm(\'Säker på att ta bort?\');"><i class="fa fa-fw fa-trash"></i> Ta bort</a>
    </td>');

        print("</tr>");
    }
    print("</tbody></table>");
}

// ------------------------------------------------------
// export to csv-file
function export_email_list($data,$title='ATK'){
    $fn=$title."-email.txt";
    // Redirect output to a client’s web browser 
    header('Content-Type: application/txt;charset=utf-8;');
    header('Content-Disposition: attachment;filename="'.$fn.'"');
    header('Cache-Control: max-age=0');
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header ('Pragma: public'); // HTTP/1.0



    // Add arr data
    foreach ($data as $ri=>$rd) {
        if(!empty($rd['email'])) {
          $n="";
          if(!empty($rd['given_name'])) $n.=$rd['given_name'];
          if(!empty($rd['family_name'])) $n.=" ".$rd['family_name'];
          print($n.'<'.$rd['email'].'>');
          print(PHP_EOL);
        }
    }
    $error['csv']="Fil $fn skapad och nedladdad!";
    exit(0);
}

// ------------------------------------------------------
// export to excel
function export_excel($labels,$data,$title='ATK'){
    $fn=$title.".xls";
    require_once 'PHPExcel.php';
    // Create new PHPExcel object
    $objPHPExcel = new PHPExcel();

    // Set document properties
    $objPHPExcel->getProperties()->setCreator(APPLICATION_NAME)
							 ->setLastModifiedBy(APPLICATION_NAME)
							 ->setTitle($title);
    $objPHPExcel->getDefaultStyle()
                             ->getNumberFormat()
                             ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);

    // Redirect output to a client’s web browser (Excel5)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="'.$fn.'"');
    header('Cache-Control: max-age=0');
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header ('Pragma: public'); // HTTP/1.0


    // Add columns
    $row = 1; // 1-based index
    foreach (array_values($labels) as $ci=>$h) {
        if(isset($h['header'])) $cid=$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($ci, $row, $h['header'],true);
        else $cid=$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($ci, $row, $h,true);
        $cid->getStyle()->getFont()->setBold(true);
        $cid->getStyle()->getFont()->setUnderline(true);
    }
    $row++;
    // Add arr data
    foreach ($data as $ri=>$rd) {
        foreach (array_keys($labels) as $ci=>$k) {
            $cdt=PHPExcel_Cell_DataType::TYPE_STRING;
            if(is_numeric($rd[$k])) $cdt=PHPExcel_Cell_DataType::TYPE_NUMERIC;
            if(substr($rd[$k],0,1)=='0') $cdt=PHPExcel_Cell_DataType::TYPE_STRING;
            $objPHPExcel->getActiveSheet()->setCellValueExplicitByColumnAndRow($ci, $row+$ri, $rd[$k],$cdt);
        }
    }

    // Rename worksheet
    $objPHPExcel->getActiveSheet()->setTitle($title);


    // Set active sheet index to the first sheet, so Excel opens this as the first sheet
    $objPHPExcel->setActiveSheetIndex(0);


    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
    $error['excel']="Excel Fil $fn skapad och nedladdad!";
}

// ------------------------------------------------------
// export to excel multi tab
function export_excel_multitab($labels,$mdata,$title='ATK'){
    $fn=$title.".xls";
    require_once 'PHPExcel.php';
    // Create new PHPExcel object
    $objPHPExcel = new PHPExcel();

    // Set document properties
    $objPHPExcel->getProperties()->setCreator(APPLICATION_NAME)
							 ->setLastModifiedBy(APPLICATION_NAME)
							 ->setTitle($title);
    $objPHPExcel->getDefaultStyle()
                             ->getNumberFormat()
                             ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);

    // Redirect output to a client’s web browser (Excel5)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="'.$fn.'"');
    header('Cache-Control: max-age=0');
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header ('Pragma: public'); // HTTP/1.0

    // iterate through tabs/sheets
    $active_sheet=0;
    foreach($mdata as $k=>$data){
        $objPHPExcel->createSheet();
        $objPHPExcel->setActiveSheetIndex($active_sheet);
        // Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle($k);

        // Add columns + header
        $row = 1; // 1-based index
        foreach (array_values($labels) as $ci=>$h) {
            if(isset($h['header'])) $cid=$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($ci, $row, $h['header'],true);
            else $cid=$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($ci, $row, $h,true);
            $cid->getStyle()->getFont()->setBold(true);
            $cid->getStyle()->getFont()->setUnderline(true);
        }
        $row++;
        // Add arr data
        foreach ($data as $ri=>$rd) {
            foreach (array_keys($labels) as $ci=>$k) {
                if(isset($rd[$k])) {
                    $cdt=PHPExcel_Cell_DataType::TYPE_STRING;
                    if(is_numeric($rd[$k])) $cdt=PHPExcel_Cell_DataType::TYPE_NUMERIC;
                    if(substr($rd[$k],0,1)=='0') $cdt=PHPExcel_Cell_DataType::TYPE_STRING;
                    $objPHPExcel->getActiveSheet()->setCellValueExplicitByColumnAndRow($ci, $row+$ri, $rd[$k],$cdt);
                }
            }
        }

        $active_sheet++;
    }


    // Set active sheet index to the first sheet, so Excel opens this as the first sheet
    $objPHPExcel->setActiveSheetIndex(0);


    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
    $error['excel']="Excel Fil $fn skapad och nedladdad!";
}

// ------------------------------------------------------
function getFieldData($name) {
	global $user_data;
  if (isset($_REQUEST[$name])) {
    return htmlspecialchars($_REQUEST[$name]);
  } else if (!empty($user_data[$name])) {
      return htmlspecialchars($user_data[$name]);
  } else return '';
}

function fd($data,$label,$name){
    print('<tr><td>'.$label.'</td>');
    print('<td><strong>'.$data.'</strong></td></tr>');
  }
  

function dt2date($dt,$format=''){
    $s=strtotime($dt);
    $time=strftime('%H:%M',$s);
    $ddiff=round(($s-time())/(3600*24),0);
    if(abs($ddiff)==1) $ps="dag"; else $ps="dagar";
    if($ddiff>0) $ds="om $ddiff $ps";
    else if($ddiff<0) $ds="för ".abs($ddiff)." $ps sedan";
    else $ds="idag";
    
    switch($format){
        case 't':
            return strftime('%H:%M',$s);
        case 'r':
            return "$ds";
        case 'w':
            return strftime('%A',$s);
        case 'l':
            return strftime('%e %B %Y',$s);
        case 'wl':
            return strftime('%A, %e %B %Y',$s);
        case 'wltr':
            if($time!='00:00') return strftime('%A, %e %B %Y kl %H:%M',$s)." - $ds";
        case 'wlr':
            return strftime('%A, %e %B %Y',$s)." - $ds";
        case 'dt':
            if($time!='00:00') return strftime('%Y-%m-%d %H:%M',$s);
        case 'd':
        default:
            return strftime('%Y-%m-%d',$s);
    }
}

function contact_info($md){
    $r='';
    if(!empty($md)) {
        if(!empty($md['given_name'])) $r.=" ".$md['given_name'];
        if(!empty($md['family_name'])) $r.=" ".$md['family_name'];
//        if(!empty($md['mobile'])) $r.=', <a href="'.ROOT_URL.'send_message.php?mobile='.$md['mobile'].'"><i class="fas fa-mobile-alt"></i> <i class="fas fa-sms"></i> '.$md['mobile'].'</a>';
        if(!empty($md['mobile'])) $r.=', <i class="fas fa-mobile-alt"></i> <i class="fas fa-sms"></i> '.$md['mobile'];
        if(!empty($md['email'])) $r.=', <a href="mailto:'.$md['email'].'"><i class="fa fa-envelope"></i> '.$md['email'].'</a>';
        if(!empty($md['phone'])) $r.=', <i class="fas fa-phone"></i> '.$md['phone'];
    }
    return $r;
}

function generateRef($user_id,$id){
    $r=$user_id.'-'.$id;
    return $r.'-'.strval(abs(crc32($r))%10);
}

function verifyRef($ref,&$user_id,&$event_id){
    $a=explode("-",$ref);
    if(count($a)!=3) return false;
    $user_id=$a[0];
    $event_id=$a[1];
    return ($ref==generateRef($user_id,$event_id));
}

function delete_transaction_eventtype($user_id,$ed){
    global $db;
    $sql="
    DELETE 
    FROM tbTransactions
    WHERE transaction_user_id=$user_id AND transaction_event_id=$ed[event_id] AND transaction_type = '".TRANSACTIONTYPE_EVENT."'
    ";
    $tl=$db->deleteFrmQry($sql);
}

function make_transaction_eventtype($user_id,$ed,$fee){
    global $db;
    if(!is_array($ed)) {
        $sql="SELECT * FROM tbEvents WHERE event_id=$ed";
        $l=$db->getRecFrmQry($sql);
        $ed=$l[0];
    }
    // remove possible previos Transaction
    delete_transaction_eventtype($user_id,$ed);
    // create Transaction
    $key=['transaction_user_id'=>$user_id,'transaction_event_id'=>$ed['event_id'],'transaction_type'=>TRANSACTIONTYPE_EVENT];
    $rec=['transaction_amount'=>$fee,'transaction_reference'=>generateRef($user_id,$ed['event_id']),'transaction_name'=>$ed['event_name'],
    'transaction_duedate'=>$ed['registration_stop'],'transaction_user_id'=>$_SESSION['user_id']];
    // set date
    $rec['transaction_date']=strftime('%F');

    $db->insert('tbTransactions',array_merge($rec,$key));
}




function printCategoryInfoCard($c){
    global $db;

    $cols_export=['participant_name','participant_contact'];
    $lp=participants_in_category($c,$cols_export);

    print('</br><div class="card bg-light">
    <h3 class="card-header card-header-title  bg-info">['.$c['category_code'].'] '.$c['category_name'].': '.$c['registrations'].'</h3>
    <div class="card-body"><div class="container">
    <ol>');
    foreach($lp as $idx=>$i){$idx++;
        print("
        <li class='list-group-item d-flex justify-content-between align-items-center'>
        [$idx] $i[participant_name]
        </li>
        ");
    }
    print('</ol>');

    //pa($c);
    print('</div></div></div>');
}

    

function participants_in_category($category_id,$cols=[]){
    global $db;
    if(!empty($category_id['category_id'])) $category_id=$category_id['category_id'];
    if(empty($cols)) $cols=['participant_name','participant_contact','registration_note','registration_json','registration_date','preference_order'];


    $sql="
    SELECT m.*
    ,CONCAT_WS(' ',m.given_name,m.family_name) as participant_name
    ,CONCAT_WS(',',m.email,m.mobile,m.phone) as participant_contact
    ,CONCAT_WS(' ',JSON_VALUE(partner_notmember_json,'$.given_name'),JSON_VALUE(partner_notmember_json,'$.family_name')) as json_name
    ,CONCAT_WS(' ',JSON_VALUE(partner_notmember_json,'$.email')) as json_contact
    ,tbEntries.*
    ,tbRegistrations.*
    FROM tbEntries
    JOIN tbMembers m ON m.user_id=tbEntries.entry_user_id 
    LEFT JOIN tbRegistrations ON registration_user_id=entry_user_id AND registration_event_id=entry_event_id
    LEFT JOIN tbMembers p ON p.user_id=tbEntries.partner_user_id 
    WHERE tbEntries.entry_category_id=$category_id
    ORDER BY m.family_name ASC
    ";
    //pa($sql);
    $participants=$db->getRecFrmQry($sql);
    // test
    //unset($participants[0]['partner_user_id']);
    //$participants[0]['partner_notmember_json']='{"given_name":"HEJSAN"}';

    //pa($participants);
    $part2team=[];
    $teams=[];
    foreach($participants as $idx=>$i){
        //check if participant already recorded 
        if(!isset($part2team[$i['user_id']])){
            // check if partner has a team
            if(!empty($i['partner_user_id'])&&isset($part2team[$i['partner_user_id']])){
                // use partner team index
                $part2team[$i['user_id']]=$part2team[$i['partner_user_id']];
                // add idx->mid to team array
                $teams[$part2team[$i['user_id']]][$idx]=$i['user_id'];  // add to team
            } else {
                // not on list, no partner, create new team
                $part2team[$i['user_id']]=count($teams);$teams[]=[$idx=>$i['user_id']];  // new team
            }
        } 
    }
    //pa($teams);
    $data=[];
    foreach($teams as $tid=>$team){
        foreach($cols as $col){
            $tn="";
            foreach($team as $idx=>$mid){
                if(!empty($tn)) $tn.=" | ";
                if(!empty($participants[$idx][$col])) $tn.=$participants[$idx][$col];
                else $tn.="-";
            }
            $data[$tid][$col]=$tn;
        }
        foreach($team as $idx=>$mid){
            $i=$participants[$idx];
            if(!empty($i['json_name'])) $data[$tid]['participant_name'].=" | $i[json_name]";
            if(!empty($i['json_contact'])) $data[$tid]['participant_contact'].=" | $i[json_contact]";
        }
    }
    //pa($data);exit;
    return $data;
}


// default lines per page
$no_of_records_per_page = LINES_PER_PAGE;
function set_search_sort_pagination(){
    global $db,$page_title;
    global $offset,$pageno,$no_of_records_per_page,$total_rows,$total_pages;
    global $orderBy,$cols_visible,$order;
    global $sort,$rsort;
    global $search,$sql,$sql_table,$sql_group,$cols_searchable,$cols;
    
    //Pagination
    if (isset($_GET['pageno'])) {
        $pageno = $_GET['pageno'];
    } else {
        $pageno = 1;
    }
    $offset = ($pageno-1) * $no_of_records_per_page;


    //Column sorting on column name
    $orderBy = $cols_visible;
    if(empty($order)) $order = $orderBy[0];
    if (isset($_GET['order']) && in_array($_GET['order'], $orderBy)) {
        $order = $_GET['order'];
    }

    //Column sort order
    $sortBy = array('asc', 'desc'); 
    if (isset($_GET['sort']) && in_array($_GET['sort'], $sortBy)) $sort=$_GET['sort'];
    if(empty($sort)) $sort = $sortBy[0];
    if($sort==$sortBy[1]) $rsort=$sortBy[0]; 
    else {
        $sort==$sortBy[0];$rsort = $sortBy[1];
    }

    // Attempt select query execution
    if(!empty($_GET['search'])) {
        $search = ($_GET['search']);
        $sql = "$sql_table 
            AND CONCAT_WS('#',".implode(",", $cols_searchable).")
            LIKE '%$search%'";
    }
    else {
        $search = "";
        $sql = "$sql_table ";
    }

    // grouping
    if(empty($sql_group)) $sql_group="";
    $sql = "$sql $sql_group";
    //pa($sql);

    // calculate num rows & pages
    $r=$db->getRecFrmQry("SELECT COUNT(*) as 'rows' $sql");
    if(empty($sql_group)) {
        $total_rows = $r[0]['rows'];
    } else {
        $total_rows = count($r);
    }
    if(empty($no_of_records_per_page)) $no_of_records_per_page = LINES_PER_PAGE;

    $total_pages = ceil($total_rows / $no_of_records_per_page);
    
    $sql = "$sql ORDER BY $order $sort ";
    // ------------------------------------------------------
    //excel export
    if (isset($_GET['export'])) {
        // do query with no limits for export
        $rl=$db->getRecFrmQry("SELECT * $sql");
        if($rl){
            $n=$page_title;
            if(!empty($_REQUEST['search'])) $n.='-'.$_REQUEST['search'];
            export_excel($cols,$rl,$n);
        }
    }
    // ------------------------------------------------------
    //mail export
    if (isset($_GET['exportemail'])) {
        // do query with no limits for mail
        $rl=$db->getRecFrmQry("SELECT * $sql");
        if($rl){
            $n=$page_title;
            if(!empty($_REQUEST['search'])) $n.='-'.$_REQUEST['search'];
            export_email_list($rl,$n);
        }
    }

}
// ------------------------------------------------------
function draw_header(){
    global $col,$cols;
    global $offset,$pageno,$no_of_records_per_page,$total_pages;
    global $orderBy,$cols_visible,$order;
    global $sort,$rsort;
    global $search,$sql,$sql_table,$cols_searchable;

    $so=$sort;$si="";
    //$si='<i class="fas fa-sort-up"> </i>';
    if($order==$col) {
        $so=$rsort;
        $si=($sort=="asc")?'<i class="fas fa-sort-up"></i>':'<i class="fas fa-sort-down"></i>';
        //$si='<i class="fas fa-sort-up"> </i>';
        //print_r($si);
        }
    print("<th><a href=?search=$search&order=$col&sort=$so>".$cols[$col]['header']." $si".'</th>');
}

function draw_table($m=''){
    global $db,$cols;
    global $offset,$pageno,$no_of_records_per_page,$total_pages;
    global $orderBy,$cols_visible,$order;
    global $sort,$rsort;
    global $search,$sql,$sql_table,$cols_searchable;

    $rl=$db->getRecFrmQry("SELECT * $sql LIMIT $offset, $no_of_records_per_page");
    if($rl){
      print('<table class="table table-striped table-sm table-bordered border"><thead class="thead-dark"><tr>');
      foreach ($cols_visible as $col) {
        $so=$sort;$si="";
        //$si='<i class="fas fa-sort-up"> </i>';
        if($order==$col) {
            $so=$rsort;
            $si=($sort=="asc")?'<i class="fas fa-sort-up"></i>':'<i class="fas fa-sort-down"></i>';
            //$si='<i class="fas fa-sort-up"> </i>';
            //print_r($si);
            }
        print("<th><a href=?search=$search&order=$col&sort=$so>".$cols[$col]['header']." $si".'</th>');
        if(($m=='P')||($m=='B')) if($user->can(['admin','ekonomi'])) if($col=='balance') print('<th>Betalning</th>');
      }
      
      print('</tr></thead><tbody>'."\r\n");
      foreach ($rl as $i) {
        print('<tr>');
        foreach ($cols_visible as $col) {
            print("<td>".$i[$col]."</td>");
            if(($m=='P')||($m=='B')) if($user->can(['admin','ekonomi'])) if($col=='balance') {
                print("<td><span style=\"white-space:nowrap\">");
                if($i['balance']<0) {
                    if(($m=='P')){ 
                        $mod=json_encode(array_intersect_key($i, array_flip(["transaction_name","given_name","family_name","balance","transaction_reference","given_name","family_name"])));
                        print('<button type="button" data-target="#dynamicModal" onclick=\'doSwish('.$mod.')\' title="Swish" class="btn btn-sm btn-info" data-toggle="modal"><i class="fas fa-sync-alt"></i></button>');
                        print(" <a href=\"$_SERVER[REQUEST_URI]?cash&transaction_user_id=$i[user_id]&transaction_reference=$i[transaction_reference]&transaction_amount=".-$i['balance']."\"".confOp('cash'));
                        print(" <a href=\"$_SERVER[REQUEST_URI]?cash&transaction_user_id=$i[user_id]&transaction_reference=$i[transaction_reference]&transaction_amount=".-$i['balance']."\"".confOp('bank'));
                    }
                    if(($m=='B')) {
                        print("<a href='member_payment.php?target_id=$i[user_id]' title='Betala' class='btn btn-sm btn-success'><i class='fa fa-dollar'> </i></a>");
                    }
                }
                print("</span></td>");
            }
        }
        print('</tr>'."\r\n");
      }
      print('</tbody></table>');
      displayPagination($pageno,$total_pages,"&search=$search&order=$order&sort=$sort");
    } else{
        print("<p class='lead'><em>Hittar inget");
        if(!empty($search)) print(" som matchar: \"$search\"");
        print(".</em></p>");
    }
}
// ------------------------------------------------------

function displayPagination($pageno,$total_pages,$opt=""){
    global $total_rows;
    print("Sida $pageno av $total_pages");
    if(!empty($total_rows)) print(" (totalt $total_rows rader)");
    print("<br/>");
    if($total_pages>1) {
        print('
    <ul class="pagination" align-right>
        <li class="page-item"><a class="page-link" href="?pageno=1'.$opt.'" title="Gå till början" data-toggle="tooltip"><i class="fa fa-step-backward"></i></a></li>
        ');
    print('<li class="page-item ');
    print (($pageno <= 1)?'disabled':'');
    print('"><a class="page-link" href="');
    if($pageno <= 1) print('#'); else print ('?pageno='.($pageno - 1));
    print($opt);
    print('"><i class="fa fa-backward"></i></a></li>');
    print('<li class="page-item '); print(($pageno >= $total_pages)?'disabled':'');
    print('"><a class="page-link" href="');print(($pageno >= $total_pages)?'#':'?pageno='.($pageno + 1));
    print($opt);
    print('"><i class="fa fa-forward"></i></a></li>');
    print('<li class="page-item"><a class="page-link" href="?pageno='.$total_pages.$opt.'" title="Gå till slutet" data-toggle="tooltip"><i class="fa fa-step-forward"></i></a></li>
      </ul>
    ');
    }
    //print('<button onclick="goBack()" class="btn btn-secondary" title="Tillbaka"><i class="fa fa-undo"></i> Tillbaka</button></br>');
}

function title_search_export($m=''){
    global $page_title;
    if(!empty($_REQUEST['search'])) $ss=htmlspecialchars($_REQUEST['search']); else $ss="";
    if($m=='C') {
        print('
        <h1>'.$page_title.'</h1>
        <form action="" method="get">
            <div class="form-row">
                <div class="col-auto">
                ');
        print('
        </div>
        </div>');
        export_buttons();
        print('
        <div class="form-row">
                <div class="col-auto">
                    <input type="text" class="form-control" placeholder="Sök (tryck Enter)" name="search" value="'.$ss.'">
                </div>
                <div class="col">
                <a href="'.$_SERVER['SCRIPT_NAME'].'" class="btn btn-secondary ml-1" title="Återställ Tabell" data-toggle="tooltip"><i class="fa fa-undo"></i></a>
                </div>
            </div>
        </form>
        </br>
        ');
    } else {
        print('
        <form action="" method="get">
            <div class="form-row">
                <div class="col">
                    <h1>'.$page_title.'</h1>
                </div>
                <div class="col-auto">
                    <input type="text" class="form-control" placeholder="Sök (tryck Enter)" name="search" value="'.$ss.'">
                </div>
                <div class="col">
                <a href="'.$_SERVER['SCRIPT_NAME'].'" class="btn btn-secondary ml-1" title="Återställ Tabell" data-toggle="tooltip"><i class="fa fa-undo"></i></a>
        ');
        export_buttons();
        print('
                </div>
            </div>
        </form>
        ');
    }
}

function export_buttons(){
    global $export_enable;
    global $search,$order,$sort;
    if(strpos($export_enable,'A')!==false){
        print("<a href=\"?export&search=$search&order=$order&sort=$sort\" class=\"btn btn-primary float-right ml-2\" data-toggle='tooltip'".confOp('excel')."</a>");
    }
    if(strpos($export_enable,'E')!==false){
        print("<a href=\"?exportemail&search=$search&order=$order&sort=$sort\" class=\"btn btn-primary float-right ml-2\" data-toggle='tooltip'".confOp('mail')."</a>");
    }
}
                

function confOp($op='',$f='a>icon'){
    switch($op){
        case 'excel':
            $t="Exportera till Excel";
            $c='Vill du ladda ned en excel fil?';
            $i='<i class="fas fa-file-download"></i> <i class="fas fa-table"></i>';
            break;
        case 'exportall':
            $t="Exportera ALLA KLASSER till Excel";
            $c='Vill du ladda ned en excel fil?';
            $i='<i class="fas fa-file-download"></i> KLASSER <i class="fas fa-table"></i>';
            break;
        case 'mail':
            $t="Exportera email-adresser till en fil";
            $c='Vill du ladda ned email-adresserna till en fil?';
            $i='<i class="fas fa-file-download"></i> <i class="fas fa-at"></i>';
            break;
        case 'message':
            $t="Skicka ett meddelande";
            $c='Vill du skicka ett medelande till gruppen?';
//            $i='<i class="fa fa-send"></i>  <i class="fas fa-envelope"></i>';
            $i='<i class="fas fa-envelope"></i>';
            break;
        case 'send':
            $t="Skicka ett meddelande";
            $c='Vill du skicka ett medelande?';
            $i='<i class="fa fa-send"></i>';
            break;
        case 'delete':
            $t="Radera post";
            $c='Är du säker på att du vill RADERA denna post?';
            $i='<i class="fas fa-trash"></i>';
            break;
        case 'archive':
            $t="Arkivera post";
            $c='Är du säker på att du vill arkivera denna post?';
            $i='<i class="fas fa-archive"></i>';
            break;
        case 'show':
            $t="Visa post";
            $c='Är du säker på att du vill visa denna post?';
            $i='<i class="fas fa-eye"></i>';
            break;
        case 'restore':
            $t="Återställ post";
            $c='Är du säker på att du vill återställa denna post?';
            $i='<i class="fas fa-undo"></i>';
            break;
        

        case 'avsluta':
            $t="Avsluta medlemsskap";
            $c='Är du säker på att du vill markera medlemsskapet avslutat?';
            $i='<i class="fa fa-sign-out"></i>';
            break;

        case 'familydelete':
            $t="Avregistera Familj";
            $c='Är du RIKTIGT säker på att du vill Avregistrara familjen?\nVARNING! Alla familjemedlemmar kommer uteslutas och familjen kommer raderas';
            $i='<i class="fas fa-trash"></i>';
            break;
        case 'memberdelete':
            $t="Radera Medlem";
            $c='Är du RIKTIGT säker på att du vill RADERA medlemmen?????\nVARNING! T.ex. komer dennes transaktioner att raderas!\nRedigera medlemmen som ’Vilande/Avslutad’ istället.';
            $i='<i class="fas fa-trash"></i>';
            break;
        case 'wip':
            $t="Sidan är under utveckling";
            $c="$t. Vill du verkligen gå dit?";
            $i='<i class="fas fa-exclamation-triangle"></i> ';
            break;
        case 'cash':
            $t="Kontant betalning";
            $c="Vill du registrera full kontant betalning?";
            $i='<i class="btn btn btn-info fas fa-hand-holding-usd"></i> ';
            break;
        case 'bank':
            $t="Bank/Swish inbetalning";
            $c="Vill du registrera full bank/swish betalning?";
            $i='<i class="btn btn btn-info fas fa-university"></i> ';
            break;
        default:$t="Utför";$c='Är du säker?';break;
    }
    switch($f){
        case 'icon': return($i);break;
        case 'a>icon': return(confOp($op,'a').'>'.confOp($op,'icon'));break;
        case 'a': 
        default: 
        return("title=\"$t\" onclick=\"return confirm('$c');\"");break;
    }
}
function wip(){
    $t="Sidan är inte tillgänglig";
    $a="Tyvärr är sidan/länken under utveckling och inte tillgänglig just nu";
    $i='<i class="fas fa-exclamation-triangle"></i> ';
    print("title=\"$t\" onclick=\"alert('$a');return false;\">$i");
}

function pa($a,$callstack=false){
    print('<pre>');
    print_r($a);
    if($callstack) foreach (debug_backtrace() as $v) {
        print("Line $v[line] in ".basename($v['file'])." calls $v[function]\n");
    }
    //print_r(debug_backtrace());
    print('</pre>');
}

function pcols($a,$callstack=false){
    print('<pre>');
    $c=array_keys($a[0]);
    print('$cols='.json_encode($c).';');
    //print_r($a);
    if($callstack) foreach (debug_backtrace() as $v) {
        print("Line $v[line] in ".basename($v['file'])." calls $v[function]\n");
    }
    //print_r(debug_backtrace());
    print('</pre>');
}

function jj($a){
    print('<pre>');
//    print(json_encode($a,JSON_PRETTY_PRINT));
    print(json_encode($a));
    print('</pre>');
}


// ------------------------------------------------------
// handle configured input 
// ------------------------------------------------------
//$test_event_json='{"input_json":[{"name":"official","title":"Kan du ställa upp som funktionär?","required":1,"options":["Ja, jag kan ställa upp som funktionär","Nej, jag inte ställa upp som funktionär"]}]}';
//$test_event_json='{"input_json":[{"name":"funktionär","title":"Kan du ställa upp som funktionär?","options":["Ja, jag kan ställa upp som funktionär","Nej, jag inte ställa upp som funktionär"]}]}';
/*
$test_event_json='
{"input_json":[
    {"name":"funktionär","title":"Kan du ställa upp som funktionär?","required":1,"options":["Ja, jag kan ställa upp som funktionär","Nej, jag inte ställa upp som funktionär"]},
    {"name":"domare","title":"Kan du ställa upp som domare?","options":["Ja, jag kan ställa upp som domare","Nej, jag inte ställa upp som domare"]}
]}

{"input_json":[
    {"name":"funktionär","title":"Kan du ställa upp som funktionär?","checkbox":1,"options":["Ja, funktionär","Ja, domare"]}
]}

{"input_json":[
    {"name":"assistans","title":"Ange vad kan du hjälpa till med (om det behövs)","required":1,"options":[    
    "Jag tycker festen är toppenkul och kommer förståss att hjälpa till där det behövs",
    "Jag hjälper gärna till - ju fler vi blir desto lättare och roligare blir det",
    "Jag hjälper gärna till med lite av varje",
    "Jag kan sätta upp och ta ned bord och dukning",
    "Jag kan hjälpa till med underhållningen",
    "Jag kan hjälpa till med maten och kaffet",
    "Näe, jag vill inte hjälpa till - andra får fixa allt åt mig istället"
]}
]}

*/
//$reg_json
function event_json_input(){
    $iv='input_json';
    $r=null;
    if(!empty($_REQUEST[$iv])) {
        $r=json_encode($_REQUEST[$iv],JSON_UNESCAPED_UNICODE);
    }
    return($r);
}

function event_json_form($js=null){
    global $registration;
    global $ed;
//    global $test_event_json;
//    print_r($ed);
    if(empty($js)) {
        if(!empty($ed['event_json'])) $js=$ed['event_json'];
        else $js=$ed['et_event_json'];
        //$js=$ed['event_json_config'];
    }
    $o=json_decode($js,true);
    //print_r($ed);
    if(empty($o)) return;
    $iv='input_json';
    if(empty($o[$iv])) return;
    $rj=null;
    if (!empty($registration['registration_json'])) $rj=json_decode($registration['registration_json'],true);
    //if(!empty($_REQUEST[$iv])) print_r(json_encode($_REQUEST[$iv]));
    print('
    <hr>
    ');
    //print_r($o);
    //print_r($registration);
    //print_r($rj);
    foreach($o[$iv] as $i){
        $i['star']='';$i['req']='';
        if(empty($i['checkbox'])) if(!empty($i['required'])) {
            $i['star']='*';
            $i['req']='required';
        }

        print('
        <div class="form-group">
        <h6>'.$i['title'].$i['star'].'</h6>
        ');

        $ov=null;
        if(!empty($_REQUEST[$iv][$i['name']])) $ov=$_REQUEST[$iv][$i['name']];
        else if (!empty($rj[$i['name']])) $ov=$rj[$i['name']];
        //print_r($ov);
        foreach($i['options'] as $v) {
            $s='';
            if(!empty($i['checkbox'])) {
                //if($ov==="$v") $s='checked';
                if(!empty($ov)) if(in_array($v,$ov,true)) $s='checked';
                print('
                <div class="form-check">
                <input class="form-check-input" type="checkbox" name="'.$iv.'['.$i['name'].'][]" value="'.$v.'" '.$s.' >
                <label class="form-check-label">'.$v.'</label></div>
                ');
            } else {
                if(!empty($ov)) if($ov==="$v") $s='checked';
                print('
                <div class="form-check">
                <input class="form-check-input" type="radio" name="'.$iv.'['.$i['name'].']" value="'.$v.'" '.$s.' >
                <label class="form-check-label">'.$v.'</label></div>
                ');
            }
        }
        if(empty($i['required'])) print('
        <div class="form-check">
        <input class="form-check-input" type="radio" name="'.$iv.'['.$i['name'].']" value="">
        <label class="form-check-label">Inget av ovanstående</label></div>
        ');

        print('
        <div class="form-check">
        <input class="form-check-input d-none" type="radio" name="'.$iv.'['.$i['name'].']" value="DUMMY" '.$i['req'].'>
        <div class="invalid-feedback">Du måste välja ett alternativ</div>
        </div>
        <br/>
        ');
    }
}

function isYearJunior($by){
    global $membership_table;
    if(empty($by)) return false;
    return ((idate('Y')-intval($by))<=$membership_table['JUNIOR']['membership_max_age']); 
}


?>