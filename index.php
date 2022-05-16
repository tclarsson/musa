<?php
require_once 'environment.php';
require_once 'crud_simple.php';
$user->admit();
//$page_nocontainer=true;

$card=new Card(APPLICATION_NAME);
$card->helpmodal=New Modal("helppage".__LINE__);
$card->helpmodal->body="
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
require_once 'header.php';
$card->render();
require_once 'footer.php';

?>