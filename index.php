<?php
require_once 'environment.php';
$user->admit();
//$page_nocontainer=true;
require_once 'header.php';

//Card: Header
print("
<div class='card bg-light'>
    <div class='card-header'>
    <div class='row card-header-title'> 
        <div class='col'>".APPLICATION_NAME."</div>
        <div class='col-auto'>
        <button type='button' data-target='#welcomeModal' title='Information och Hjälp' class='btn btn-info float-right ml-2' data-toggle='modal'><i class='fa fa-info-circle'></i> Välkommen</button>
        <a href='mailto:".EMAIL_CONTACT."?subject=MUSA Kontakt' class='btn btn-primary float-right ml-2' title='Skicka ett meddelande support'><i class='fas fa-envelope'></i> Kontakta support</a>
        </div>
    </div>
    </div>
</div>
");

pa($user->data);
require_once 'footer.php';
//------------------------------------------------
// modals:
//------------------------------------------------
$m['id']="welcomeModal";
$m['head']=APPLICATION_NAME;
$m['body']="
<h3>Välkommen {$user->data['name']},<h3> 
<h4>till ".APPLICATION_NAME."!</h4>
<p>Här kan du </p>
<ul>
  <li>Söka i ditt notregister</li>
  <li>Hitta kontaktuppgifter</li>
</ul>
<p>
Längst uppe till höger (<i class='fa fa-user'></i>) hittar du dina kontouppgifter och där kan du också logga ut. 
</p>
<p>Vår hemsida hittar du genom att klicka längst upp till vänster på vår logga: <a href='".URL_SUPPORT."'><img src='".ROOT_URI."images/logo.png' width='50' height='50' alt='MUSA' title='MUSA'></a>.</p>
<p>Du kan läsa våra vilkor och hur vi hanterar dina uppgifter <a href='conditions.php'>här.</a></p>
<p>Om du har några frågor eller synpunkter, kan du skicka ett mail till <a href='mailto:".EMAIL_SUPPORT."?subject=MUSA Feedback'>".EMAIL_SUPPORT."</a></p>
";
make_modal($m)
?>