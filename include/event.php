<?php 
$event= new EventLib($db);


// $member_data['user_id'],$ed['event_id']
function event_payment($member_data,$ed){
    global $db;
    $payment=false;
    // get transactions
    $sql="
    SELECT *,SUM(transaction_amount) as balance
    FROM tbTransactions
    WHERE transaction_user_id=$member_data[user_id] AND transaction_event_id=$ed[event_id]
    ";
    $tl=$db->getRecFrmQry($sql);
    

    if(count($tl)>0) {
        print('<br>');
        foreach($tl as $t){
            if($t['balance']!=0) {
                print('<h5>Avgift = '.-$t['balance'].' kr. Ref# '.$t['transaction_reference'].'</h5>');
                $payment=true;
            }
        }
        if($payment) {
            if(count($tl)==1) print('
            <p>Betala via BG '.PAYMENT_BG.' eller Swisha till '.PAYMENT_SWISH.' och ange ref#, alternativt skanna koden med Swish:</p>
            <img width="300" src="swish_generate_qr.php?payee='.PAYMENT_SWISH.'&amount='.-$t['balance'].'&message=ref '.$t['transaction_reference'].','.$member_data['given_name'].' '.$member_data['family_name'].','.$ed['event_name'].'">
            <p>(...väldigt enkelt om du surfar med datorn...)</p>
            <p>OBS: Det tar några dagar innan inbetalningen bokförs på ditt konto.</p>

            ');
            else print('<p>Betala via BG '.PAYMENT_BG.' eller Swisha till '.PAYMENT_SWISH.' och ange ref#.</p>');
        }
    }
}

function is_event($ed,$status){
    // default
    $prop="AVR";
    if(!empty($ed['est_property'])) $prop=$ed['est_property']; 
    switch($status){
        case 'active': return (strpos($prop,'A')!==FALSE);
        case 'open': return (strpos($prop,'R')!==FALSE);
        case 'visible': return (strpos($prop,'V')!==FALSE);
        case 'cancelled': return (strpos($prop,'C')!==FALSE);
        default: return false;
    }
}

function event_registration_status($member_data,$ed,$entries,$categories){
    //<!--  Card with registration status -->
//    pa($entries);pa($categories);exit;
    print(cardHeader($ed,'status'));
    if(count($entries)>0) {
        print('<h5>'.$member_data['nick_name'].' är anmäld i följande klasser:</h5><ol class="list-group">');
        foreach($entries as $ei => $i){
            $c=$categories[$i['entry_category_id']];
            print('<li class="list-group-item d-flex justify-content-between align-items-center">['.$c['category_code'].'] '.$c['category_name']);
            if(!empty($c['category_description'])) print(': '.$c['category_description']);
            print('<span class="badge badge-primary badge-pill" title="Antal anmälningar i klassen">'.$c['category_participants'].'</span>
            </li>');
        }
    } else {
        print('<h5>'.$member_data['nick_name'].' är anmäld.</h5>');
    }
    
    event_payment($member_data,$ed);
    print('
    <a href="'.ROOT_URI.'" class="btn btn-info" title="Avbryt" data-toggle="tooltip"><i class="fa fa-undo"></i> Perfekt, Så skall det vara! <i class="fa fa-thumbs-up"></i></a>        
    </div>
    </div>');
}

// ------------------------------------------------------
// event helpers
// ------------------------------------------------------

function get_eventlist($m=''){
    global $db;
    $w='WHERE 1';
    // show only after registration start
    if(strpos($m,'ARS')!==FALSE) $w.=" AND registration_start<=DATE(NOW())";
    // show only registration before registration end
    if(strpos($m,'BRE')!==FALSE) $w.=" AND registration_stop>=DATE(NOW())";
    // (not) show membership
    if(strpos($m,'MEMB')===FALSE) $w.=" AND NOT tbEvents.event_code = '".EVENTTYPE_MEMBERSHIP."'";
    $w.=" ORDER BY date_start";
    // newest first
    if(strpos($m,'DESC')!==FALSE) $w.=" DESC";
    $sql_events="SELECT tbEvents.*,tbEventTypes.*,tbEventStatusTypes.*
    ,date(date_start) as date_date_start
    ,IF(time(date_start)!='00:00',date_format(date_start,'%H:%i'),'') as time_date_start
    ,datediff(registration_stop,NOW()) as days_registration_stop
    ,datediff(registration_start,NOW()) as days_registration_start
    ,CONCAT_WS(', ',CONCAT_WS(' ',tbMembers.given_name,tbMembers.family_name),tbMembers.mobile,tbMembers.phone) as contact
    FROM tbEvents
    LEFT JOIN tbEventTypes ON tbEventTypes.et_event_code=tbEvents.event_code
    LEFT JOIN tbEventStatusTypes ON tbEventStatusTypes.est_event_status_code=tbEvents.event_status_code
    LEFT JOIN tbMembers ON tbMembers.user_id=tbEvents.event_contact_id
    $w
    ";
    $r=$db->getRecFrmQry($sql_events);
    $eventlist=[];
    foreach($r as $i) {
        $eventlist[$i['event_id']]=$i;
        $eventlist[$i['event_id']]['wd_date_start']=dt2date($i['date_start'],'wltr');  // add weekday format
    }
    return $eventlist;
}

function get_event($eid){
    global $db,$errors,$event_year;
    $sql_event = "SELECT * 
    ,datediff(registration_stop,NOW()) as days_registration_stop
    ,IF(time(date_start)!='00:00',date_format(date_start,'%H:%i'),'') as time_date_start
    ,IFNULL(tbEvents.event_json,tbEventTypes.et_event_json) as event_json_config
    FROM tbEvents 
    LEFT JOIN tbEventTypes ON tbEvents.event_code=tbEventTypes.et_event_code
    LEFT JOIN tbEventStatusTypes ON tbEventStatusTypes.est_event_status_code=tbEvents.event_status_code
    LEFT JOIN tbMembers ON tbMembers.user_id=tbEvents.event_contact_id
    WHERE event_id=$_SESSION[event_id]
    ";
    $el=$db->getRecFrmQry($sql_event); 
    if(count($el)>0) {
        $ed=$el[0]; 
        $event_year = strftime('%Y',strtotime($ed['date_start']));
        if(empty($ed['event_categories_max'])) $ed['event_categories_max']=1; // default to one category
        if(empty($ed['event_fee'])) $ed['event_fee']=0;
    } else {
        $errors['noevent']="Hittade ingen aktivitet";
        $ed=[]; 
    }
    return $ed;
}



function tab_event_head($m=''){
    print('
    <table class="table  table-striped table-sm border">
    <thead class="thead-dark">
    <tr>
    <th>Aktivitet</th>
    <th>Status</th>
    <th>Dagar kvar</th>');
    if(strpos($m,'A')!==FALSE) print("<th>Anmälan*</th>");
    if(strpos($m,'E')!==FALSE) print("<th>Redigera*</th>");
    if(strpos($m,'R')!==FALSE) print("<th>Rapport</th>");
    if(strpos($m,'B')!==FALSE) print("<th>Saldo</th>");
    if(strpos($m,'W')!==FALSE) print("<th>Varning</th>");
    if(strpos($m,'I')!==FALSE) print('
    <th>Info</th>
    <th>Deltagande</th>');
    print('</tr></thead>');
}
function tab_event_row($e,$m='S'){
    // print row
    if(is_event($e,'active')) $a=" btn-info"; else $a=" btn-danger";
//    <td>[$e[event_id]] <strong>$e[event_name]</strong></br>$e[date_date_start] $e[time_date_start]</td>
    if(strpos($m,'S')!==FALSE) print("
    <tr>
    <td><strong>$e[event_name]</strong></br>$e[date_date_start] $e[time_date_start]</td>
    <td>$e[event_status_name]</td>
    <td>$e[days_registration_stop]</td>");
    if(strpos($m,'I')!==FALSE) {
        print('<td><button type="button" data-target="#dynamicModal" onclick="doModal('.$e['event_id'].')" title="Detaljer för '.$e['event_name'].'" class="btn btn-sm'.$a.'" data-toggle="modal"><i class="fa fa-search-plus"></i> </button></td>');
        print('<td><a href="event_status.php?event_id='.$e['event_id'].'" type="button" title="Deltagande för '.$e['event_name'].'" class="btn btn-sm'.$a.'" ><i class="fa fa-user-friends"></i> </a></td>');
    }
}

function make_event_info_modal_script($eventlist){
    print('
<script>
var eventlist = '.json_encode($eventlist, JSON_HEX_TAG).'
function doModal(i){
    e=eventlist[i];
    console.log(e);
    var desc="";if(e.event_description) desc=`<p>${e.event_description}</p>`;
    var info="";if(e.event_note) info=`<p>${e.event_note}</p>`;
    var event_status_name="";if(e.event_status_name) event_status_name=`<h6>Aktivitetsstaus</h6><p>${e.event_status_name}: ${e.event_status_description}</p>`;
    var contact="";if(e.contact) contact=`<h6>Kontaktperson</h6><p>${e.contact}</p>`;
    var event_contact_info="";if(e.event_contact_info) event_contact_info=`<h6>Kontaktinformation</h6><p>${e.event_contact_info}</p>`;
    var fee="";if(e.event_fee>0) fee=`<h6>Avgift</h6><p>Grundavgiften för ${e.event_name} är ${e.event_fee} kr.</p>`;
    var m=`
    <div class="modal fade" id="dynamicModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header modal-header-title bg-info">${e.event_name} ${e.date_date_start} ${e.time_date_start}
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          ${desc}
          ${info}
          ${event_status_name}
          <h6>Start</h6>
          <p>${e.wd_date_start}</p>
          <h6>Anmälan</h6>
          <p>Första anmälningsdag: ${e.registration_start}</br>Sista anmälningsdag: ${e.registration_stop}</p>
          ${fee}
          ${contact}
          ${event_contact_info}
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Stäng</button></div>
      </div>
    </div>
  </div>
    `;
    $("#dynamicModalFrame").html(m);
}
</script>
');
}

/*
function pEE($h,$v){
    if(!empty($v)) print('<div class="row border-bottom"><div class="col-4">'.$h.'</div><div class="col eventinfo">'.$v.'</div></div>');
}
function old_printEventInfoCard($ed){
    global $db;
    if(!empty($ed['event_fee'])) $f='Grundavgiften för '.$ed['event_name'].' är '.$ed['event_fee'].' kr.'; else $f='';
    if(!empty($ed['event_contact_id'])) $c=contact_info($db->getUnique('tbMembers',['user_id'=>$ed['event_contact_id']])); else $c='';
    print('<div class="card bg-light">
  <div class="card-header card-header-title">'.$ed['event_name'].' '.dt2date($ed['date_start'],'').'</div>
  <div class="card-body"><div class="container">');
  pEE('Beskrivning',$ed['event_description']);
  pEE('Startdatum',dt2date($ed['date_start'],'d'));
  pEE('Sista anmälningsdag',dt2date($ed['registration_stop'],'d'));
  pEE('Avgifter',$f);
  pEE('Information',$ed['event_note']);
  pEE('Kontakt',$c);
print('</div></div></div>');
}
*/
function sEE($h,$v){
    if(!empty($h)) $h="<h6 class='eventinfoheader'>$h</h6>"; else $h="";
    if(!empty($v)) $r="$h<p class='eventinfotext'>$v</p>"; else $r="";
    return $r;
}



function cardHeader($ed,$mode=''){
    $i=$ed['event_name'].' '.dt2date($ed['date_start'],'dt');
    if(!empty($ed['event_status_name'])) $i.=' är '.$ed['event_status_name'];
    if(is_event($ed,'active')) {
        $a=" bg-success";
    } else {
        $a=" bg-danger";
        $i.='!';
    }
    switch($mode){
        case 'status':
            $i='Status: '.$i;
        case 'back':
            $r='
<div class="row">
<div class="col">'.$i.'</div>
<div class="col-1"><button type="button" onclick="goBack()" title="Tillbaka" class="btn btn-secondary float-right" ><i class="fa fa-undo"></i> </button></div>
</div>
            ';
            break;
        default:
            $r=$i;
            break;
    }
    $h='<div class="card bg-light"><div class="card-header card-header-title'.$a.'" >'.$r.'</div><div class="card-body">';
    return $h;
}


function printEventInfoCard($ed){
    if(!empty($ed['event_fee'])) $f='Grundavgiften för '.$ed['event_name'].' är '.$ed['event_fee'].' kr.'; else $f='';
    if(!empty($ed['event_contact_id'])) $cp=contact_info($ed); else $cp='';
    if(!empty($ed['event_status_name'])) $es="$ed[event_status_name]: $ed[event_status_description]"; else $es='';
    print(cardHeader($ed,'back'));
    print(
    sEE('',$ed['event_description']).
    sEE('',$ed['event_note']).
    sEE('Aktivitetsstatus',$es).
    sEE('Start',dt2date($ed['date_start'],'wltr')).
    sEE('Sista anmälningsdag',dt2date($ed['registration_stop'],'wlr')).
    sEE('Avgifter',$f).
    sEE('Kontaktperson',$cp).
    sEE('Kontaktinformation',$ed['event_contact_info']).
    '</div></div>');
}



?>