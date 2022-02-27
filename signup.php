<?php 
require_once 'environment.php';
require_once 'sendEmails.php';
// ------------------------------------------------------
// Restore form with old data
// ------------------------------------------------------
function fv($name) {
  if (isset($_REQUEST[$name])) $r=$_REQUEST[$name];
  else if (isset($_SESSION[$name])) $r=$_SESSION[$name];
  else $r='';
  return htmlspecialchars($r);
}


// ------------------------------------------------------
// SIGN UP USER by FORM submission
// ------------------------------------------------------
if (isset($_POST['signup'])) {
  $errors=$user->check_register();

// login ok?
redirect_if_logged_in();

?>

<!doctype html>
<html lang="en">
  <head>
    <?php require_once 'header.inc';?>
    <meta name="google-signin-client_id" content="<?php print(GOOGLE_CLIENT_ID);?>">
    <title>Registrera</title>
<style>
.signup-form {
    width: 340px;
    margin: 30px auto;
    font-size: 15px;
}
.signup-form form {
    margin-bottom: 15px;
    background: #f7f7f7;
    box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.3);
    padding: 30px;
}
.signup-form h2 {
    margin: 0 0 15px;
}
.signup-form .hint-text {
    color: #777;
    padding-bottom: 15px;
    text-align: center;
    font-size: 13px; 
}
.form-control, .btn {
    min-height: 38px;
    border-radius: 2px;
}
.signup-btn {        
    font-size: 15px;
    font-weight: bold;
}
.or-seperator {
    margin: 20px 0 10px;
    text-align: center;
    border-top: 1px solid #ccc;
}
.or-seperator i {
    padding: 0 10px;
    background: #f7f7f7;
    position: relative;
    top: -11px;
    z-index: 1;
}
.social-btn .btn {
    margin: 10px 0;
    font-size: 15px;
    text-align: left; 
    line-height: 24px;       
}
.social-btn .btn i {
    float: left;
    margin: 4px 15px  0 5px;
    min-width: 15px;
}
.input-group-addon .fa{
    font-size: 18px;
}
.form-check-label {
    margin-bottom: 10px;
}

</style>
</head>
<body>
<?php displayMessages($errors);?>
<div class="signup-form">
    <form action="" method="post" id="signupform" class="needs-validation" novalidate>
      <img class="mb-4" width="100%" src="images/web-form-header.png"/>
      <h2 class="text-center">Nytt Konto</br>för Medlemskap</h2>    
      <p class="text-center"><a href="info_membership.php">Information om Medlemskap</a></p>
      <div class="form-check">
          <input type="checkbox" class="form-check-input" name="acceptconditions" value="checked" <?php print(fv('acceptconditions'));?> required>
          <label class="form-check-label"> Jag godkänner <a href="conditions.php">ATKs villkor</a></label>
          <div class="invalid-feedback">Du måste godkänna villkoren</div>
      </div>  
    <div class="or-seperator"><i>Registrera med google</i></div>
        <div class="text-center social-btn">
          <div class="g-signin2" data-onsuccess="onSignIn"></div>
        </div>
    <div class="or-seperator"><i>eller email address</i></div>
    <div class="form-group">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">
                        <i class="fa fa-user"></i>
                    </span>                    
                </div>

          <input type="email" class="form-control input-lg" name="email" value="<?php print(fv('email'));?>" placeholder="Email adress" required="required">
          <div class="invalid-feedback">Email saknas eller har felaktigt format</div>

        </div>
      </div>
    <div class="form-group">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-lock"></i></span>                    
                </div>
            <input type="password" minlength="3" class="form-control input-lg" name="password" value="<?php print(fv('password'));?>" placeholder="Lösenord" required="required">
            <div class="invalid-feedback">Minst 3 tecken</div>
        </div>
      </div>
    <div class="form-group">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">
                        <i class="fa fa-lock"></i>
                    </span>                    
                </div>
            <input type="password" minlength="3" class="form-control input-lg" name="confirm_password" value="<?php print(fv('confirm_password'));?>" placeholder="Repetera Lösenord" required="required">
            <div class="invalid-feedback">Minst 3 tecken</div>
        </div>  
      </div>

      <div class="clearfix">
          <label class="float-left form-check-label"><input type="checkbox" name="remember" value="checked" <?php print(fv('remember'));?>> Håll mig inloggad på denna enhet!</label>
      </div>  
      <div class="form-group">
          <button type="submit" name="signup" class="btn btn-success btn-block signup-btn"><i class="fa fa-user-plus"></i> Registrera</button>
      </div>
  	<input type="hidden" name="googleidtoken" id="googleidtoken" value="">      
    </form>
    <div class="hint-text">Har redan ett konto? <a href="signin.php" class="text-success">Logga in här!</a></div>
</div>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

<script src="https://apis.google.com/js/platform.js" async defer></script>
<script type="text/javascript">
  function onSignIn(googleUser) {
    var profile = googleUser.getBasicProfile();
    console.log('ID: ' + profile.getId()); // Do not send to your backend! Use an ID token instead.
    console.log('Name: ' + profile.getName());
    console.log('Image URL: ' + profile.getImageUrl());
    console.log('Email: ' + profile.getEmail()); // This is null if the 'email' scope is not present.

    var id_token = googleUser.getAuthResponse().id_token;
//    document.googleid.googleidtoken.value=id_token;
    document.getElementById("googleidtoken").value=id_token;
    console.log('id_token: ' +id_token);
    signOut();
    document.getElementById("signupform").submit();
/*   
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'signup.php');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
      console.log('Signed in as: ' + xhr.responseText);
      window.location.replace("home.php");
    };
    xhr.send('googleidtoken=' + id_token);  
  */
  }  

  function signOut() {
    var auth2 = gapi.auth2.getAuthInstance();
    auth2.signOut().then(function () {
      console.log('User signed out.');
    });
  }


</script>
<!-- Debug   -->
<?php 
    if(!empty($_SESSION['debug'])) {
        print('<pre>');
        print_r($_REQUEST);
        print('</pre>');
    }
?>
<!-------------------------->

  </body>
      <!-- Error messages   -->
      <?php modalErrors($errors);?>

</html>