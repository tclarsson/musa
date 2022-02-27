<?php 
require_once 'environment.php';
require_once 'authentication.php'; 
require_once 'event.php'; 
require_once 'utils.php'; 
admit();

// clear potetial admin flag (set by some admin operation) - this is user managing himself!
if(isset($_SESSION['admin'])) unset($_SESSION['admin']);

// ------------------------------------------------------
// check and verify email
require_once 'sendEmails.php';
do_email_verification($user_data);
// ------------------------------------------------------


// ------------------------------------------------------
if($user_data['family_admin']) {
  $family=get_family_members($user_data['family_id'],$user_data['user_id']);
} else $family=null;

function dd($purl){
  global $family;
  $r="";
  foreach($family as $i) {
    //$r.='<a class="dropdown-item button" href="'.$purl.'&target_id='.$i['user_id'].'" title="Anmäl '.$i['given_name'].' till aktivitet"><i class="fa fa-plus"></i> '.$i['given_name'].'</a>';
    $r.='<a class="dropdown-item"  href="'.$purl.'&target_id='.$i['user_id'].'" type="button" title="Anmäl '.$i['given_name'].' till aktivitet" ><i class="fa fa-plus"></i> '.$i['given_name'].'</a>';    
  }
  return($r);
}

$eventlist=get_eventlist('BRE');


$sql_registrations="SELECT * 
    , date(date_start) as date_date_start
    , datediff(registration_stop,NOW()) as days_registration_stop
    , datediff(date_start,NOW()) as days_date_start 
    ,IF(time(date_start)!='00:00',date_format(date_start,'%H:%i'),'') as time_date_start
    FROM tbEvents
    LEFT JOIN tbEventTypes ON tbEventTypes.et_event_code=tbEvents.event_code
    LEFT JOIN tbEventStatusTypes ON tbEventStatusTypes.est_event_status_code=tbEvents.event_status_code
    LEFT JOIN tbRegistrations ON registration_event_id = event_id
    LEFT JOIN tbMembers ON user_id = registration_user_id
    LEFT JOIN tbMembershipTypes ON tbMembershipTypes.mt_membership_code=tbMembers.membership_code
    WHERE membership_hide is null AND LOCATE('D',est_property)=0
    ";
    // AND date_start>=DATE(NOW())
    if($user_data['family_admin']) $sql_registrations.=" AND family_id=".$user_data['family_id']; else $sql_registrations.=" AND user_id=".$user_data['user_id'];
    $sql_registrations.=" ORDER BY date_start, event_id";
    //pa($sql_registrations);exit;
    $reglist=$db->getRecFrmQry($sql_registrations);

// ------------------------------------------------------
// check balance
$sql_where="JOIN tbMembers ON tbMembers.user_id=tbTransactions.transaction_user_id ";
if(!empty($user_data['family_admin'])) $sql_where.="WHERE family_id=$user_data[family_id]"; 
else $sql_where.="WHERE user_id=$user_data[user_id]"; 

$sql="
SELECT SUM(transaction_amount) as balance
FROM tbTransactions
$sql_where
";
$balance=$db->getRecFrmQry($sql);
if($balance[0]['balance']!=0) $balance=-$balance[0]['balance']; else $balance=0;
// ------------------------------------------------------



?>
<!doctype html>
<html lang="en">
  <head>
	<?php require_once 'header.inc';?>
    <title>ATK</title>
  </head>
  <body>
  <?php require_once 'navbar.php';?>
  <div class="container">
  <?php displayMessages($errors);?>

<?php if ($user_data['email_verified']=='PENDING'): ?>
  <div class="alert alert-warning alert-dismissible fade show" role="alert">
    Du måste verifiera din email-adress genom att klicka på länken vi skickat till:
    <strong><?php echo $user_data['email']; ?></strong>
    <div class="hint-text">Inget mail? <a href="verify_email.php?verifyemail=<?php print($user_data['email']);?>" class="text-success">Skicka länk igen!</a></div>            
  </div>
<?php endif;?>


<!--  Header -->
<div class="card bg-light">
    <div class="card-header">
    <div class="row card-header-title"> 
        <div class="col">Aspö Tennisklubbs Medlemsservice</div>
        <div class="col-auto">
        <button type="button" data-target="#welcomeModal" title="Information och Hjälp" class="btn btn-info float-right ml-2" data-toggle="modal"><i class="fa fa-info-circle"></i> Välkommen</button>
        <a href="mailto:<?php print(EMAIL_CONTACT);?>?subject=ATK Kontakt" class="btn btn-primary float-right ml-2" title='Skicka ett meddelande till klubben'><i class="fas fa-envelope"></i> Kontakta klubben</a>
        </div>
    </div>
    </div>
</div>

<!--  Messages -->
<?php if($balance>0):?>    
</br>
<div class="card bg-light">
  <div class="card-header alert-warning">
    <div class="row card-header-title"> 
      <div class="col">Obetalda avgifter</div>
    </div>
  </div>
  <div class="card-body">
    <a href="member_account.php#saldo">
    <p>Du har <?php print($balance);?> kr i obetalda avgifter.</p>   <button  title="Gå till ditt saldo" class="btn btn-info"><i class="fas fa-piggy-bank"></i>  Gå till Saldo</button></a> 
    <p>(Du kan bortse från denna information om du nyligen gjort en inbetalning.)</p>
  </div>

</div>
<?php endif; // no items

?>


<!--  Regstration list -->
<?php if(!empty($reglist)):?>    
</br>
<div class="card bg-light">
    <div class="card-header">
    <div class="row card-header-title"> 
        <div class="col">Aktiva Anmälningar</div>
        <div class="col">
        <button type="button" data-target="#registrationModal" title="Information och Hjälp" class="btn btn-info float-right" data-toggle="modal"><i class="fa fa-info-circle"></i> </button>
        </div>
    </div>
    </div>
    <div class="card-body">
    <div class="table-responsive">
<?php
    tab_event_head('EI');
    foreach ($reglist as $i) if(is_event($i,'visible')) {
      tab_event_row($i,'S');
      $uri="?edit&event_code=$i[event_code]&event_id=$i[event_id]"; 
      $fn="event_register_$i[event_code].php";
      if(file_exists($fn)) $purl="$fn$uri"; 
      else $purl="event_register_generic.php$uri"; 
      print('<td>');


      if(is_event($i,'active')) {
        print('
        <a href="'.$purl.'&target_id='.$i['user_id'].'" type="button" title="Redigera anmälan för '.$i['given_name'].'" class="btn btn-sm btn-success"><i class="fa fa-edit"></i> '.$i['given_name'].'</a>
        ');
      } else {
        print("Redigering är stängd.");
      }
      print('</td>');
      tab_event_row($i,'I');
      print('</tr>');

    }
?>
</table></div>
<p>* Klicka den anmälan som du vill redigera.</p>

</div>
</div>
<?php 
endif; // no items
?>

<!--  Event list -->
</br>
<div class="card bg-light" id="activeEvents">
    <div class="card-header">
    <div class="row card-header-title"> 
        <div class="col">Aktuella Aktiviteter</div>
        <div class="col">
        <button type="button" data-target="#eventModal" title="Information och Hjälp" class="btn btn-info float-right" data-toggle="modal"><i class="fa fa-info-circle"></i> </button>
        </div>
    </div>
    </div>
    <div class="card-body">
<?php if(!empty($eventlist)):?>    
<form  action="" method="post" class="needs-validation" novalidate>
<div class="table-responsive"><table class="table  table-striped table-sm border">
<?php
  tab_event_head('AI');
  foreach ($eventlist as $i) if(is_event($i,'visible')) {
      tab_event_row($i,'S');
      $uri="?register&event_code=$i[event_code]&event_id=$i[event_id]"; 
      $fn="event_register_$i[event_code].php";
      if(file_exists($fn)) $purl="$fn$uri"; 
      else $purl="event_register_generic.php$uri"; 
      print('<td>');
      if(is_event($i,'open')) {
        // open for registration
        if($i['days_registration_start']<=0) {
          // registration is allowed
          if(empty($family)) {
            print('
            <a href="'.$purl.'&target_id='.$user_data['user_id'].'" type="button" title="Anmäl '.$user_data['given_name'].' till aktivitet" class="btn btn-sm btn-success" ><i class="fa fa-plus"></i> '.$user_data['given_name'].'</a>
            ');
          } else {
            print('
          <div class="btn-group">
            <a href="'.$purl.'&target_id='.$user_data['user_id'].'" type="button" title="Anmäl '.$user_data['given_name'].' till aktivitet" class="btn btn-sm btn-success" ><i class="fa fa-plus"></i> '.$user_data['given_name'].'</a>
            <button type="button" title="Anmäl Familjemedlemmar till aktivitet" class="btn btn-sm btn-success dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <span class="sr-only">Toggle Dropdown</span>
            </button>
            <div class="dropdown-menu">'.dd($purl).'</div>
          </div>
          ');
          }
        } else {
          print("Öppnas $i[registration_start]</br>($i[days_registration_start] dagar kvar)");
        }
      } else {
        print("Anmälan är stängd.");
      }
      print('</td>');
      tab_event_row($i,'I');
      print('</tr>');
  }
?>
</table></div>
</form>

<?php 
if(!empty($family)) print('<p>* Om aktiviteten är öppen för anmälan kan du klicka ditt namn för att anmäla dig själv eller klicka på pilen för att anmäla en familjemedlem.</p>');
?>
<?php else:  // empty  ?>
Det finns inga aktuella aktiviteter.

<?php 
endif; // no items
?>

</div>
</div>
</div>
</div>
</div>



<?php require_once 'footer.php'; ?>


<!-- Modals -->

<div class="modal fade" id="welcomeModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header modal-header-title ">Medlemsservice
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div><img src="<?php print(ROOT_URI);?>images/web-form-footer.png" width="100%"></div>
      <div class="modal-body">
      <h3>Välkommen <?php print($user_data['nick_name']);?>,<h3> 
    <h4>till Aspö Tennisklubbs medlemsservice!</h4>
    <p>Här kan du </p>
    <ul>
      <li>Sköta ditt medlemskap</li>
      <li>Anmäla dig till klubbens aktiviteter, tex Aspö Open</li> 
      <li>Kolla och uppdatera anmälningar</li>
      <li>Hitta kontaktuppgifter och medspelare</li>
  </ul>
  <p>
    Bara registrerade medlemmar kan komma in på denna sida. 
  Längst uppe till höger (<i class="fa fa-user"></i>) hittar du dina kontouppgifter och där kan du också logga ut. 
  Tillgång till fler funktioner, tex medlemsregister, får du efter att styrelsen verifierat ditt medlemskap.
</p>
  <p>Vår öppna hemsida hittar du genom att klicka längst upp till vänster på vår logga, eller klickar du <a href="https://www.aspotennisklubb.se/">här</a>. 
  På vår öppna hemsida's meny finns länken "medlemsservice" tillbaka hit.</p>
  <p>Du kan läsa våra vilkor och hur vi hanterar dina uppgifter <a href="conditions.php">här.</a></p>
  <p>Om du har några frågor eller synpunkter, kan du skicka ett mail till <a href="mailto:support@aspotennisklubb.se?subject=ATK Feedback">support@aspotennisklubb.se</a></p>

      </div>
      <div class="modal-footer">
        
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Stäng</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="eventModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header modal-header-title bg-info">Aktiviteter
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>Här ser du alla framtida aktiviteter. Vissa kan du anmäla dig till.</p>
        <p>Aktivitetens namn och startdatum visas och dessutom hur många dagar som är kvar tills sista dag för anmälan.</p>
        <p>Infoknappen visar lite detaljer kring aktiviteten. Statusknappen visa hur många som anmält sig hittils i olika klasser</p>
        <h6>Aktiviteter som är öppna för anmälan</h6>
        <p>Om aktivitet är öppen för anmälan, finns en grön knapp</p>
        <p>Du anmäler dig genom att klicka på ditt namn till höger om den aktivitet du är intresserad av.</p>
        <?php if(!empty($family)) print('<p>Du kan också anmäla en familjemedlem genom att istället välja namn i listan som kommer fram om man trycker på pilen (<i class="fa fa-caret-down"></i>).</p>'); ?>
        <h6>Aktiviteter som ännu inte är öppna för anmälan</h6>
        <p>Om aktivitet inte öppen för anmälan, visas datumet när anmälan är möjlig längst till höger</p>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Stäng</button></div>
    </div>
  </div>
</div>

<div class="modal fade" id="registrationModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header modal-header-title bg-info">Anmälningar
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>Här ser du alla anmälningar som är aktiva.</p>
        <p>Aktivitetens namn och startdatum visas och dessutom hur många dagar som är kvar tills sista dag för anmälan.</p>
        <p>Du kan redigera anmälan genom att klicka namnet till höger om den aktivitet/anmälan du är intresserad av.</p>
        <?php if(!empty($family)) print('<p>Du kan även redigera en familjemedlems anmälan.</p>'); ?>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Stäng</button></div>
    </div>
  </div>
</div>


  </body>
<?php make_event_info_modal_script($eventlist);?>


</html>