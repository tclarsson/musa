<?php
require_once 'environment.php';
require_once 'logging.php';
require_once './vendor/autoload.php';

// Create the Transport
$transport = (new Swift_SmtpTransport(SENDER_SMTP, SENDER_PORT, 'ssl'))
    ->setUsername(SENDER_EMAIL)
    ->setPassword(SENDER_PASSWORD);


// Create the Mailer using your created Transport
$mailer = new Swift_Mailer($transport);



//-------------------------------------------------------
function do_email_verification(&$user_data){
  global $db,$auth,$errors;
  $rec=[];

  //print_r($user_data);exit;
  if(empty($user_data['email'])) return;
  if($user_data['email_verified']=='VERIFIED') return;

  
  // make token if missing
  if(empty($user_data['token'])) {
    $user_data['token']= $auth->getToken(); // generate unique token
    $rec['token']=$user_data['token'];
  } else if($user_data['email_verified']=='PENDING') return;


  if(sendVerificationEmail($user_data)) {
    // successful
    $user_data['email_verified']='PENDING';
    $rec['email_verified']=$user_data['email_verified'];
    try{
        $db->update('tbMembers',$rec,['user_id'=>$user_data['user_id']]);
    } catch (\RuntimeException $e) {
        $errors['database'] = 'Någonting gick fel: '.$e->getMessage();
    }
  }
}



function sendInviteEmail($mess)
{
    global $mailer;
    if(!isset($mess['name'])) $mess['name']='';
    $mess['subj']=APPLICATION_NAME.': Inbjudan att registrera konto';

    $link=ROOT_URL.'verify_email.php?email=' . $mess['email'].'&token=' . $mess['token'];
    $body = '<!DOCTYPE html>
    <html lang="en">

    <head>
      <meta charset="UTF-8">
      <title>Inbjudan till '.APPLICATION_NAME.'</title>
      <style>
        .wrapper {
          padding: 20px;
          color: #444;
          font-size: 1.3em;
        }
        .button {
          background: blue;
          text-decoration: none;
          padding: 8px 15px;
          border-radius: 5px;
          color: white;
        }
      </style>
    </head>

    <body>
      <div class="wrapper">
        <img src="'.ROOT_URL.'images/web-form-header.png"/>      
        <p>Hej '.$mess['name'].',</p>
        <p>Här kommer en inbjudan från din organisation att registrera ett konto i '.APPLICATION_NAME.'</p>
        <p>Om du vill registrera ett konto, klicka på "Registrera konto"!</p>
        <p><a class="button" style="color: white" href="'.$link.'">Registrera konto</a></p>
        <p>MVH</p><p><a class="link" href="'.ROOT_URL.'">'.APPLICATION_NAME.'</a></p>
      </div>
    </body>

    </html>';


    // Create a message
    $message = (new Swift_Message($mess['subj']))
        ->setFrom(SENDER_EMAIL)
        ->setTo($mess['email'])
        ->setBody($body, 'text/html');

    // Send the message
    $result = $mailer->send($message);
    dolog($mess['email'].': '.$mess['subj']);

    if ($result > 0) {
        return true;
    } else {
        return false;
    }
}

function sendVerificationEmail($mess)
{
    global $mailer;
    if(!isset($mess['name'])) $mess['name']='';
    $mess['subj']=APPLICATION_NAME.': Verifiera din email!';

    //print_r($mess);exit;
    $link=ROOT_URL.'verify_email.php?email=' . $mess['email'].'&token=' . $mess['token'];
    $body = '<!DOCTYPE html>
    <html lang="en">

    <head>
      <meta charset="UTF-8">
      <title>Verifiera din email</title>
      <style>
        .wrapper {
          padding: 20px;
          color: #444;
          font-size: 1.3em;
        }
        .button {
          background: blue;
          text-decoration: none;
          padding: 8px 15px;
          border-radius: 5px;
          color: white;
        }
      </style>
    </head>

    <body>
      <div class="wrapper">
        <img src="'.ROOT_URL.'images/web-form-header.png"/>      
        <p>Hej '.$mess['name'].',</p>
        <p>Tack för att du registrerat dig hos '.APPLICATION_NAME.'!</p>
        <p>Klicka på "Verifiera" för att verifiera din email adress:</p>
        <p><a class="button" style="color: white" href="'.$link.'">Verifiera</a></p>
        <p>MVH</p><p><a class="link" href="'.ROOT_URL.'">'.APPLICATION_NAME.'</a></p>
      </div>
    </body>

    </html>';

    // Create a message
    $message = (new Swift_Message($mess['subj']))
        ->setFrom(SENDER_EMAIL)
        ->setTo($mess['email'])
        ->setBody($body, 'text/html');

    // Send the message
    $result = $mailer->send($message);
    dolog($mess['email'].': '.$mess['subj']);

    if ($result > 0) {
        return true;
    } else {
        return false;
    }
}

/*
// send mail to confirm that user is not approved by board
function sendApprovedEmail($mess)
{
    global $mailer;
    if(empty($mess['email'])) return false;
    if(!isset($mess['given_name'])) $mess['given_name']='';
    $mess['subj']='Välkommen till '.APPLICATION_NAME.'!';

    //print_r($mess);exit;
    $body = '<!DOCTYPE html>
    <html lang="en">

    <head>
      <meta charset="UTF-8">
      <title>Medlemskap Godkänt</title>
      <style>
        .wrapper {
          padding: 20px;
          color: #444;
          font-size: 1.3em;
        }
        .button {
          background: blue;
          text-decoration: none;
          padding: 8px 15px;
          border-radius: 5px;
          color: white;
        }
      </style>
    </head>

    <body>
      <div class="wrapper">
        <img src="'.ROOT_URL.'images/web-form-header.png"/>      
        <p>Hej '.$mess['given_name'].',</p>
        <p>Välkommen till '.APPLICATION_NAME.'! Ditt medlemskap är nu godkänt.</p>
        <p>MVH</p><p><a class="link" href="'.ROOT_URL.'">'.APPLICATION_NAME.'</a></p>
      </div>
    </body>

    </html>';

    // Create a message
    $message = (new Swift_Message($mess['subj']))
        ->setFrom(SENDER_EMAIL)
        ->setTo($mess['email'])
        ->setBody($body, 'text/html');

    // Send the message
    $result = $mailer->send($message);
    dolog($mess['email'].': '.$mess['subj']);

    if ($result > 0) {
        return true;
    } else {
        return false;
    }
}

*/

function sendMaintenanceEmail($mess)
{
    global $mailer;
    if(!isset($mess['subj'])) $mess['subj']=APPLICATION_NAME.': Underhållsrapport';
    if(!isset($mess['mess'])) $mess['mess']='';
    $body = '<!DOCTYPE html>
    <html lang="en">

    <head>
      <meta charset="UTF-8">
      <title>ATK Maintenance</title>
      <style>
        .wrapper {
          padding: 20px;
          color: #444;
          font-size: 1.3em;
        }
        .button {
          background: blue;
          text-decoration: none;
          padding: 8px 15px;
          border-radius: 5px;
          color: white;
        }
      </style>
    </head>

    <body>
      <div class="wrapper">
        <div>'.$mess['mess'].'</div>
        <p>MVH</p><p><a class="link" href="'.ROOT_URL.'">'.APPLICATION_NAME.'</a></p>
        <img src="'.ROOT_URL.'images/web-form-header.png"/>      
      </div>
    </body>

    </html>';

    // Create a message
    $message = (new Swift_Message($mess['subj']))
        ->setFrom(SENDER_EMAIL)
        ->setTo($mess['email'])
        ->setBody($body, 'text/html');

    // Send the message
    $result = $mailer->send($message);
    dolog($mess['email'].': '.$mess['subj']);


    if ($result > 0) {
        return true;
    } else {
        return false;
    }
}

function sendResetEmail($mess)
{
    global $mailer;
    if(!isset($mess['name'])) $mess['name']='';
    $mess['subj']=APPLICATION_NAME.': Återställ ditt lösenord';
    
    $link=ROOT_URL.'reset_password.php?email=' . $mess['email'].'&resettoken=' . $mess['token'];
    if(!empty($mess['expires'])) $expires="<p>Denna länk kan användas endast EN GÅNG och fungerar  bara fram till $mess[expires].</p> ";
      else $expires="";
    $body = '<!DOCTYPE html>
    <html lang="en">

    <head>
      <meta charset="UTF-8">
      <title>Återställ ditt lösenord</title>
      <style>
        .wrapper {
          padding: 20px;
          color: #444;
          font-size: 1.3em;
        }
        .button {
          background: blue;
          text-decoration: none;
          padding: 8px 15px;
          border-radius: 5px;
          color: white;
        }

      </style>
    </head>

    <body>
      <div class="wrapper">
      <img src="'.ROOT_URL.'images/web-form-header.png"/>      
      <p>Hej '.$mess['name'].',</p>
      <p>Du har bett om att få byta lösenord. För att sätta ett nytt lösenord, klicka på "Ändra lösenord" och följ instruktionerna.</p>
      <p><a class="button" style="color: white" href="'.$link.'">Ändra lösenord</a></p>
      '.$expires.'
      <p>Om du inte bett om att byta ditt lösenord kan du bortse från denna e-post.</p>
      <p>MVH</p><p><a class="link" href="'.ROOT_URL.'">'.APPLICATION_NAME.'</a></p>
      </div>
    </body>

    </html>';

    // Create a message
    $message = (new Swift_Message($mess['subj']))
        ->setFrom(SENDER_EMAIL)
        ->setTo($mess['email'])
        ->setBody($body, 'text/html');

    // Send the message
    $result = $mailer->send($message);
    dolog($mess['email'].': '.$mess['subj']);

    if ($result > 0) {
        return true;
    } else {
        return false;
    }
}