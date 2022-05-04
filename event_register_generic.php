<?php 
require_once 'config.php';
require_once 'authentication.php'; 
require_once 'event.php'; 
require_once 'utils.php'; 
admit();

// ------------------------------------------------------
// External Call: 
// event_AO.php?edit&event_id=1&target_id=1
// event_PRO.php?register&event_id=5&target_id=1
// ------------------------------------------------------

// ------------------------------------------------------
// get (target) member
// ------------------------------------------------------
if(!empty($_REQUEST['target_id'])) {
    $_SESSION['target_id']=$_REQUEST['target_id'];
}

if(!empty($_SESSION['target_id'])) {
    $member_data=get_member_record($_SESSION['target_id']);
    // authorize operation on target_id
    member_authorized_only($member_data);
} else {
    // missing target id - return
    header("Location:".$_SERVER['HTTP_REFERER']);
}
// ------------------------------------------------------
// get event 
// ------------------------------------------------------
if(!empty($_REQUEST['event_id'])) $_SESSION['event_id']=$_REQUEST['event_id'];
// get active events of type
$ed=get_event($_SESSION['event_id']);

$display_registration_data=0;
if($ed['days_registration_stop']>=0) $display_registration_data=1;
// if admin - show anyway
if(!empty($_SESSION['admin'])) $display_registration_data=1;

// ------------------------------------------------------
// get categories in event + participants
$sql = "
SELECT tbCategories.*,tbCategoryTypes.*,COUNT(entry_id) as category_participants FROM tbCategories 
LEFT JOIN tbCategoryTypes ON tbCategories.categories_category_code=tbCategoryTypes.category_code 
LEFT JOIN tbEntries ON tbEntries.entry_category_id=tbCategories.category_id   
WHERE tbCategories.categories_event_id=$_SESSION[event_id] 
GROUP BY tbCategories.category_id
ORDER BY tbCategories.categories_category_code ASC
";
$catlist=$db->getRecFrmQry($sql);
foreach($catlist as $i) $categories[$i['category_id']]=$i;
//pa($categories);exit;

// get all members for partner selections etc
$all_members=$auth->getSelectMember();
// ------------------------------------------------------


// ------------------------------------------------------
// DELETE Registration to event
// ------------------------------------------------------
if (isset($_REQUEST['bt_delete'])) {
	if (count($errors) === 0) {
        // delete Entry(s)
        $key=['entry_member_id'=>$member_data['member_id'],'entry_event_id'=>$ed['event_id']];
        $db->delete('tbEntries',$key);
        // delete registration
        $key=['registration_member_id'=>$member_data['member_id'],'registration_event_id'=>$ed['event_id']];
        $db->delete('tbRegistrations',$key);
        // delete Transaction
        delete_transaction_eventtype($member_data['member_id'],$ed);

        header("Location:".$_SERVER['SCRIPT_NAME']);
    }

}
// ------------------------------------------------------


// ------------------------------------------------------
// Update Memeber and General data
// ------------------------------------------------------
if ((isset($_REQUEST['bt_register']))||(isset($_REQUEST['bt_update']))) {
    if(empty($_REQUEST['given_name'])) $errors['given_name'] = 'Du måste ange förnamn.';
    if(empty($_REQUEST['family_name'])) $errors['family_name'] = 'Du måste ange efternamn.';
    if((empty($_REQUEST['mobile']))&&empty($_REQUEST['phone'])) $errors['addnumber'] = 'Du måste ange minst ett mobil eller telefonnummer.';
    if(empty($_REQUEST['birth_year'])) $errors['birth_year'] = 'Du måste ange födelseår.';
  

    //pa($_REQUEST);exit;
	if (count($errors) === 0) {
        // update member
        $key=['member_id'=>$member_data['member_id']];
        $rec=[];
        $cols=['given_name', 'family_name', 'mobile', 'phone', 'address', 'zipcode', 'city', 'birth_year','level_code'];
        foreach($cols as $k) {
            if(isset($_REQUEST[$k])) {
                $rec[$k]=trim($_REQUEST[$k]); 
                if(empty($rec[$k])) $rec[$k]=null;
                $member_data[$k]=$rec[$k];  //TODO: what if update fails....
            }
        }

        $db->update('tbMembers',$rec,$key);
    }
}


// ------------------------------------------------------
// DELETE entries to event
// removes empty removed categories
// ------------------------------------------------------
if (isset($_REQUEST['bt_register'])||isset($_REQUEST['bt_update'])) {
    if(isset($_REQUEST['entries'])) foreach($_REQUEST['entries'] as $ei=>$i){
        if(isset($i['deleted'])) {
            if(!empty($i['entry_id'])) {
                $key=['entry_id'=>$i['entry_id']];
                // delete existing antry    
                $db->delete('tbEntries',$key);
            }
            unset($_REQUEST['entries'][$ei]);
        }
    }
}
    

// ------------------------------------------------------
// Validation
// ------------------------------------------------------
if (isset($_REQUEST['bt_register'])||isset($_REQUEST['bt_update'])) {
   
    if(empty($_REQUEST['entries'])) {
        $errors['category_id'] = 'Du måste ange minst en klass.';
        //$errors['internal'] = 'Internal Error.';
    } else {
        foreach($_REQUEST['entries'] as $ei=>$i){
            if(empty($i['deleted'])) {
                if(empty($i['entry_category_id'])) {
                    $errors['entry_category_id'] = 'Du måste ange klass.';
                } else {
                    $c=$categories[$i['entry_category_id']];
                    if(isset($categories[$i['entry_category_id']]['selected'])) $errors['multiple_category_entries'] = $c['category_code'].': Du kan bara vara med i varje klass en gång.';
                    $categories[$i['entry_category_id']]['selected']=1;
                    // age
                    if(!empty($c['age_max'])) if($member_data['birth_year']<($event_year-$c['age_max'])) $errors['birth_year'] = $c['category_code'].': Du måste vara född '.($event_year-$c['age_max']).' eller senare.';
                    if(!empty($c['age_min'])) if($member_data['birth_year']>($event_year-$c['age_min'])) $errors['birth_year'] = $c['category_code'].': Du måste vara född '.($event_year-$c['age_min']).' eller tidigare.';
                    //$errors['debug']=json_encode([$c['age_min'],$c['age_max'],$member_data['birth_year'],$event_year]);
                    // partner
                    if(isset($i['partner']['players'])){
                        $okf=false;
                        if(!empty($i['partner']['notmember'])) $okf=true;
                        else if(!empty($i['partner']['member'])) $okf=true;
                        if(!$okf) $errors['partner'] = $c['category_code'].': Du måste ange en partner';
                    }
                }
            }
        }
    }
    //pa($errors);pa($categories);exit;
}

// ------------------------------------------------------
// NEW Registration to event
// ------------------------------------------------------
if (isset($_REQUEST['bt_register'])) {
    if (count($errors) === 0) {
        // create registration
        $rec=['registration_member_id'=>$member_data['member_id'],'registration_event_id'=>$ed['event_id']];
        $rec['registration_note']=$_REQUEST['registration_note'];
        $rec['registration_json']=event_json_input();
    //pa($rec);exit;

        $db->insert('tbRegistrations',$rec);
        // create Entry(s)
        foreach($_REQUEST['entries'] as $ei=>$i){
            $rec=['entry_member_id'=>$member_data['member_id'],'entry_event_id'=>$ed['event_id'],'entry_category_id'=>$i['entry_category_id'],'preference_order'=>$i['prio']];
            if(isset($i['partner'])){
                if(isset($i['partner']['notmember'])){
                    $rec['partner_notmember_json']=json_encode($i['partner']['notmember'],JSON_UNESCAPED_UNICODE);
                } else {
                    $rec['partner_member_id']=$i['partner']['member'];
                }
            }
            $db->insert('tbEntries',$rec);
        }
        // make Transaction
        make_transaction_eventtype($member_data['member_id'],$ed,-$_REQUEST['total_fee']);
        header("Location:".$_SERVER['SCRIPT_NAME']);
    }

}
// ------------------------------------------------------

// ------------------------------------------------------
// UPDATE Registration to event
// ------------------------------------------------------
if (isset($_REQUEST['bt_update'])) {
	if (count($errors) === 0) {
        // update registration
        $key=['registration_member_id'=>$member_data['member_id'],'registration_event_id'=>$ed['event_id']];
        $rec=['registration_note'=>$_REQUEST['registration_note']];
        $rec['registration_json']=event_json_input();
        $db->update('tbRegistrations',$rec,$key);
        // update Entry(s)
        foreach($_REQUEST['entries'] as $ei=>$i){
            $rec=['entry_member_id'=>$member_data['member_id'],'entry_event_id'=>$ed['event_id'],'entry_category_id'=>$i['entry_category_id'],'preference_order'=>$i['prio']];
            $rec['partner_member_id']=null;
            $rec['partner_notmember_json']=null;
            if(isset($i['partner'])){
                if(isset($i['partner']['notmember'])){
                    $rec['partner_notmember_json']=json_encode($i['partner']['notmember'],JSON_UNESCAPED_UNICODE);
                } else {
                    $rec['partner_member_id']=$i['partner']['member'];
                }
            }
            if(!empty($i['entry_id'])) {
                $key=['entry_id'=>$i['entry_id']];
                if(!empty($i['deleted'])) {
                    // delete existing antry    
                    $db->delete('tbEntries',$key);
                } else {
                    // update existing antry
                    $db->update('tbEntries',$rec,$key);
                }
            } else {
                // insert new entry
                $db->insert('tbEntries',$rec);
            }
        }

        // make Transaction
        make_transaction_eventtype($member_data['member_id'],$ed,-$_REQUEST['total_fee']);
        
        header("Location:".$_SERVER['SCRIPT_NAME']);
    }

}

// ------------------------------------------------------
// ------------------------------------------------------
// get user registration data
// ------------------------------------------------------
$registrations=$db->get('tbRegistrations',['registration_member_id'=>$member_data['member_id'],'registration_event_id'=>$ed['event_id']]);
if(count($registrations)==1) $registration=$registrations[0]; else $registration=[];
// get entries
$sql='SELECT * FROM tbEntries WHERE entry_member_id='.$member_data['member_id'].' AND entry_event_id='.$ed['event_id'].'
ORDER BY preference_order ASC';
$entrylist=$db->getRecFrmQry($sql);
$entries=[];
foreach($entrylist as $i) {
    $entries[$i['entry_id']]=$i;
    if(!empty($i['partner_notmember_json'])) $entries[$i['entry_id']]['partner_notmember_json']=json_decode($i['partner_notmember_json'],true);
}

// ------------------------------------------------------


// ------------------------------------------------------
// Restore form with old data
// ------------------------------------------------------
function fv($name) {
    global $member_data,$registration,$entrylist;

    if (isset($_REQUEST[$name])) $r=$_REQUEST[$name];
    else if (!empty($registration[$name])) $r=$registration[$name];
    else if (!empty($entrylist[0][$name])) $r=$entrylist[0][$name];
    else if (!empty($member_data[$name])) $r=$member_data[$name];
    else $r='';

    return htmlspecialchars($r);
}

function fnv($name){
	print('name="'.$name.'" value="'.fv($name).'"');
}
function fnvd($name){
    global $member_data;
    if(!empty($member_data[$name])) $d='readonly'; else $d='';
    print('name="'.$name.'" value="'.fv($name).'" '.$d);
}



?>
<!doctype html>
<html lang="en">
<head>
  <?php include 'header.inc';?>
  <title>Anmälan</title>
  <style type="text/css">
   .eventinfo {
    font-weight: bold;
   } 
   </style>
</head>
<body>
<?php include 'navbar.php';?>
<div class="container">
<?php displayMessages($errors);?>

</br>
<?php if(!empty($registration)) event_registration_status($member_data,$ed,$entries,$categories); ?>

<!--  INFO Card  -->
</br>
<?php  printEventInfoCard($ed); ?>

<?php if($display_registration_data): ?>
<!--  Card with registration form -->
</br>
<div class="card bg-light">
  <h1 class="card-header card-header-title" >Anmälan: <?php print($ed['event_name'].' '.dt2date($ed['date_start'],''));?></h1>
  <div class="card-body">
  <form  action="" method="post" class="needs-validation" novalidate>

<h2>Deltagare</h2> 
<input type="hidden" name="member_id" value="<?php print($member_data['member_id']);?>">
<input type="hidden" name="event_id" value="<?php print($ed['event_id']);?>">
    <div class="form-row">
        <div class="form-group col-md" >
        <label>Förnamn*</label>
        <input type="text" class="form-control" <?php fnvd("given_name");?> placeholder="Förnamn" required>
        <div class="invalid-feedback">Ange Förnamn</div>        
        </div>
        <div class="form-group col-md">
        <label>Efternamn*</label>
        <input type="text" class="form-control"  <?php fnvd("family_name");?> placeholder="Efternamn" required>
        <div class="invalid-feedback">Ange Efternamn</div>        
        </div>
        <div class="form-group col-md">
        <label>Email*</label>
        <input type="email" class="form-control" <?php fnvd("email");?> placeholder="Email" required>
        <div class="invalid-feedback">Ange godkänd emailadress</div>        
        </div>
    </div>

    <div><label >(**) Minst ett telefonnummer krävs:</label></div>
    <div class="form-row">
        <div class="form-group col-md">
        <label>Mobil**</label>
        <input type="tel" pattern="^0[0-9]{9}$" class="form-control <?php if(isset($errors['addnumber'])) print('is-invalid');print('" ');fnv("mobile");?> placeholder="Mobil">
        <div class="invalid-feedback">Ange bara siffror</div>        
        <?php if(isset($errors['addnumber'])) print('<div class="invalid-feedback">Minst ett telefonnummer krävs!</div>');?>
        </div>
        <div class="form-group col-md">
        <label>Telefon**</label>
        <input type="tel" pattern="^[+0][0-9]{7,15}$" class="form-control <?php if(isset($errors['addnumber'])) print('is-invalid');print('" ');fnv("phone");?> placeholder="Telefon">
        <div class="invalid-feedback">Ange bara siffror, inkl riktnummer</div>        
        <?php if(isset($errors['addnumber'])) print('<div class="invalid-feedback">Minst ett telefonnummer krävs!</div>');?>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-md-3">
        <label>Född År*</label>
        <input type="text" pattern="^(?:19|20)[0-9]{2}$" class="form-control" <?php fnvd("birth_year");?> placeholder="ÅÅÅÅ" required>
        <div class="invalid-feedback">Ange födelseår, ÅÅÅÅ</div>        
        </div>
        <div class="form-group col-md">
        <label>Spelstyrka</label>
        <select class="form-control" name="level_code">
            <option value="">Välj ett alternativ i listan som stämmer bäst:</option>
            <?php
                $levelTypes=$db->get('tbLevelTypes');
                foreach($levelTypes as $i){
                    if($i['lt_level_code']==fv('level_code')) $sel='selected'; else $sel='';
                    print("<option value='$i[lt_level_code]' $sel>$i[level_name]: $i[level_description]</option>");
                }
            ?>
        </select>
        <div class="invalid-feedback">Ange spelstyrka</div>        
        </div>
    </div>

    <hr>
    <?php 
    if($ed['event_categories_max']>1) print('
        <h2>Välj klasser</h2>
        <p>Du kan välja upp till '.$ed['event_categories_max'].' klasser. Välj dessa i prioritetsordning.</p>
        ');
    else print('
        <h2>Välj klass</h2>
        <p>Du kan välja '.$ed['event_categories_max'].' klass.</p>
    ');
    ?>
    <div id="class_inputs"></div>

    <div class="form-group">
    <button type="button" id="bt_add_category" class="btn btn-success" title='Lägg till klass' onclick="addCat(this);"><i class='fa fa-plus'></i> Lägg till klass</button>      
    </div>

    <?php event_json_form();?>
    <hr>
    <div class="form-group">
        <label>Övrig information</label>
        <textarea rows = "5"  class="form-control" name="registration_note" placeholder="Förbehåll, Meddelande, eller Kommentarer"><?php print(fv("registration_note"));?></textarea>
    </div>
    <div class="form-group">
        <label>Avgift</label>
        <label type="text" class="form-control fee_info" readonly></label>
    </div>

    <?php if(empty($registration)): ?>
    <div class="form-group">
        <button type="submit" name="bt_register" class="btn btn-success fcommon" title="Sparar och skickar in anmälan" onclick="saveEntries()"><i class="fa fa-send"></i> Skicka in Anmälan</button>
        <a href="<?php print(ROOT_URI);?>" class="btn btn-secondary" title='Avbryt' data-toggle='tooltip'><i class='fa fa-undo'></i> Avbryt</a>        
    </div>
    <?php else:?>
    <div class="form-group">
        <button type="submit" name="bt_update" class="btn btn-success fcommon" title="Ändrar din tidigare anmälan" onclick="return confirm('Är du säker på att du vill ändra din anmälan?');"><i class="fa fa-edit"></i> Ändra Anmälan</button>
        <a href="<?php print(ROOT_URI);?>" class="btn btn-secondary" title='Avbryt' data-toggle='tooltip'><i class='fa fa-undo'></i> Avbryt</a>        
    </div>
    <div class="form-group">
        <button type="submit" name="bt_delete" class="btn btn-danger fcommon" title="Raderar din tidigare anmälan" onclick="return confirm('Är du säker på att du vill ta bort anmälan?');"><i class="fa fa-trash"></i> Ta Bort Anmälan</button>
    </div>
    <?php endif?>
    <input type="hidden" name="total_fee" id="total_fee">
    <input type="hidden" name="entriesObject" id="entriesObject">
    </form>
    </div>
</div>
<!--  END: Card with registration form -->
    <?php endif;?>

</div>

<?php include 'footer.php';?>

<script>
var event_year = <?php print(strftime('%Y',strtotime($ed['date_start']))); ?>;
// event categories
var categories = <?php echo json_encode($categories, JSON_HEX_TAG); ?>;
// all members
var all_members = <?php echo json_encode($all_members, JSON_HEX_TAG); ?>;

var categories_max = <?php print($ed['event_categories_max']);?>;
var num_entries=0;
var entries=[];
var total_fee=0;

function nn(s){if(s===  null) return "";  else return s;}
function a2ay(a){return a+" år (född "+(event_year-a)+")";}
function isset (accessor) {
  try { return (typeof accessor() !== 'undefined');} 
  catch (e) {return false;}
}

function saveEntries() {
    updateFee();
    document.getElementById('entriesObject').value=JSON.stringify(entries);
}



function updateFee() {
    total_fee=<?php print($ed['event_fee']);?>;
    for (i = 0; i < entries.length; i++) if(isset(()=>entries[i].cat.category_fee)) total_fee+= Number(entries[i].cat.category_fee);
    console.log(total_fee);
    $(".fee_info").html("Summa avgifter: " +total_fee+" kr");
    document.getElementById('total_fee').value=total_fee;
}

function fv(o,item){
    try {
        return o[item];
    } catch (error) {
        return '';
    }
}

function changePartner(o){
    console.log(o);
//    console.log(o.name);
    console.log(o.value);
//    console.log(o.id);
console.log('-------changePartner---------');

   var eid=o.id;

   if(o.value=="notfound") {
        console.log(fv(o.partner_notmember_json,'given_name'));
        $("#"+eid+"_partner_notfound").html(`
        <div class="form-group">
            <label>Fyll i din partners uppgifter nedan om du inte hittar denne i listan:</label>
            </div>
            <div class="form-row">
                <div class="form-group col" >
                <label>Förnamn*</label>
                <input type="text" class="form-control" name="entries[${eid}][partner][notmember][given_name]" value="${fv(o.partner_notmember_json,'given_name')}" placeholder="Förnamn" required="required">
                </div>
                <div class="form-group col">
                <label>Efternamn*</label>
                <input type="text" class="form-control"  name="entries[${eid}][partner][notmember][family_name]" value="${fv(o.partner_notmember_json,'family_name')}" placeholder="Efternamn" required="required">
                </div>
                <div class="form-group col">
                <label>Email*</label>
                <input type="email" class="form-control" name="entries[${eid}][partner][notmember][email]" value="${fv(o.partner_notmember_json,'email')}" placeholder="email" required="required">
                </div>
            </div>
            `);

   } else $("#"+eid+"_partner_notfound").html('');

}

function changeCat(o) {
    console.log(o);
//    console.log(o.name);
//    console.log(o.value);
//    console.log(o.id);
console.log('-------changeCat---------');
    var eid=o.id;
    var idx=-1;
    for (i = 0; i < entries.length; i++) {
        if(entries[i].id==o.id) {idx=i;break;}
    }
    console.log(entries);
    console.log(idx);
    if(o.value!='UNSELECTED') {

    cat=categories[o.value];
    entries[idx].cat=cat;
    updateFee();
    cat.category_info_name=nn(cat.category_name)+ " " + nn(cat.category_description);
    var info=`<div class="form-group">
    <input type="hidden" name="entries[${eid}][entry_category_id]" value="${cat.category_id}">
    </br>
    <h2>${cat.category_info_name}</h2> 
    `;
    if(cat.category_fee>0) {
        info+=`<p>Avgift: ${cat.category_fee}kr per spelare</p>`;
    }
    if(cat.age_min>0) {
        info+="<p>Minsta åldersgräns: "+a2ay(cat.age_min)+"</p>";
    }
    if(cat.age_max>0) {
        info+="<p>Högsta åldersgräns: "+a2ay(cat.age_max)+"</p>";
    }
    info+="</div>";
    var partner='';
    if(cat.num_players_required==2) {
        var opt='';
        var sel='';
        all_members.forEach(function(mem,i){
            if(mem.member_id==o.partner_member_id) sel='selected'; else sel='';
            if(!(mem.mobile)) mem.mobile='';
            if(!(mem.phone)) mem.phone='';
            if(!(mem.email)) mem.email='';
            opt+=`<option value="${mem.member_id}" ${sel}>[${mem.member_id}]: ${mem.given_name} ${mem.family_name} (${mem.mobile}, ${mem.phone}, ${mem.email})</option>`;
        });
        if(o.partner_notmember_json) sel='selected';
        opt+=`<option value="notfound" ${sel}>Hittar inte min partner i listan...</option>`

        partner=`
        <div class="form-group">
        <input type="hidden" name="entries[${eid}][partner][players]" value="${cat.num_players_required}">
        <label>Ange din dubbelpartner (som måste vara medlem)</label>
        <select class="custom-select" id="${eid}" name="entries[${eid}][partner][member]" onchange="changePartner(this)">
        <option value="" selected>Välj Dubbelpartner...</option>
        ${opt}
        </select>
        </div>
        <div id="${eid}_partner_notfound">
        </div>

        `;
    }

    $("#"+eid+"_info").html(`
    ${info}
    ${partner}
    `);
    } else {
        $("#"+eid+"_info").html('');
    }

}

function removeCat(o){
    console.log(o);
    console.log(o.name);
    var idx=-1;
    for (i = 0; i < entries.length; i++) {
        if(entries[i].id==o.name) {idx=i;break;}
    }
    if(idx>=0) {
        console.log(idx);
        var id=entries[i].id;
        $("#"+id+"_element").html(`<input type="hidden" name="entries[${id}][deleted]" value="${o.entry_id}">`);
        entries.splice(idx, 1);
        //o.parentElement.parentElement.parentElement.remove();
        for (i = 0; i < entries.length; i++) {
            $("#"+entries[i].id+"_label").html(i+1);
            document.getElementById(entries[i].id+"_prio").value = i+1;
            //$("#"+entries[i].id+"_prio").value=(i+1);
            console.log("#"+entries[i].id+"_prio");
        }
        console.log(entries);
    }
    if(entries.length>=categories_max) $("#bt_add_category").addClass('d-none'); else $("#bt_add_category").removeClass('d-none'); 

}

function addCat(o){
    console.log(o);
    console.log('-------addCat---------');

    if(entries.length<categories_max) {
        num_entries+=1;
        var id="e"+num_entries;
        entries.push({'id':id});
        if(entries.length>=categories_max) $("#bt_add_category").addClass('d-none'); else $("#bt_add_category").removeClass('d-none'); 

        var opt='';
        var sel='';
        for(const i in categories){
            var cat=categories[i];
            if(cat.category_id==o.entry_category_id) sel='selected'; else sel='';
            var fee='';if(cat.category_fee>0) fee=`(Avgift: ${cat.category_fee} kr)`;
            opt+=`<option value="${cat.category_id}" ${sel}>[${cat.category_code}] ${cat.category_name} ${fee}</option>`;
        };

        var hidden_entry_id='';
        if(isset(()=>o.entry_id)) hidden_entry_id=`<input type="hidden" name="entries[${id}][entry_id]" value="${o.entry_id}">`;

        $("#class_inputs").append(`
        <div class="form-group" id="${id}_element">
        <input type="hidden" id="${id}_prio" name="entries[${id}][prio]" value="${entries.length}">

        <div class="input-group" id="cat_ig_${num_entries}">
        <div class="input-group-prepend">
        <label class="input-group-text bg-success"><strong class="text-white" id="${id}_label">${entries.length}</strong></label>
        </div>
        <select class="custom-select" id="${id}" name="entries[${id}][category]" onchange="changeCat(this)">
        <option value="UNSELECTED" selected>Välj Klass...</option>
        ${opt}
        </select>
        <div class="input-group-append">
        <button class="btn btn-danger" type="button" name="${id}" onclick="removeCat(this);"><i class="fa fa-trash"></i></button>
        </div>
        </div>
        <div class="form-group"  id="${id}_info">
        </div>
  <hr>

</div>
${hidden_entry_id}
`);
        return id;
    }
    return false;
}

function populate_form(){
    // current registered entries
    var entries = <?php echo json_encode($entries, JSON_HEX_TAG); ?>;
    console.log(entries);
    for(const i in entries){
        var o=entries[i];
        o.id=addCat(o);
        o.value=entries[i].entry_category_id;
        changeCat(o);
        if(!(o.partner_member_id>0)) o.value='notfound';
        changePartner(o);
    };
}

function init(){
    console.log(categories);
    populate_form();
}
window.onload = init;

</script>
</body>
    <!-- Error messages   -->
    <?php modalErrors($errors);?>

</html>