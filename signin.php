<?php 
require_once 'environment.php';

// ------------------------------------------------------
// SIGN IN USER by FORM submission
// ------------------------------------------------------
$errors=$user->check_signin();

// ------------------------------------------------------
// Restore form with old data
// ------------------------------------------------------
function fv($name) {
  global $user_data,$rd,$rl;

  if (isset($_REQUEST[$name])) $r=$_REQUEST[$name];
  else if (isset($_SESSION[$name])) $r=$_SESSION[$name];
  else $r='';

  return htmlspecialchars($r);
}





//require_once 'header.php';

?>


<!doctype html>
<html lang="en">
  <head>
    <?php require_once 'header.inc';?>
    <title>Logga in</title>
<style>
.login-form {
    width: 340px;
    margin: 30px auto;
    font-size: 15px;
}
.login-form form {
    margin-bottom: 15px;
    background: #f7f7f7;
    box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.3);
    padding: 30px;
}
.login-form h2 {
    margin: 0 0 15px;
}
.login-form .hint-text {
    color: #777;
    padding-bottom: 15px;
    text-align: center;
    font-size: 13px; 
}
.form-control, .btn {
    min-height: 38px;
    border-radius: 2px;
}
.login-btn {        
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
<!-- Display messages -->
<?php //displayMessages($errors);?>

<div class="login-form" >
    <form action="" method="post" class="needs-validation" novalidate>
      <img class="mb-4" width="100%" src="images/web-form-header.png"/>
      <h2 class="text-center text-error">Logga in</h2>
        <div class="form-group">
          <div class="input-group">                
                <div class="input-group-prepend">
                    <span class="input-group-text">
                        <span class="fa fa-user"></span>
                    </span>                    
                </div>
                <input type="email" class="form-control" name="email" value="<?php print(fv('email'));?>" placeholder="email@email.se" required="required">
                <div class="invalid-feedback">Ange din emailadress</div>                        
            </div>
        </div>
    <div class="form-group">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">
                        <i class="fa fa-lock"></i>
                    </span>                    
                </div>
                <input type="password" class="form-control" name="password" value="<?php print(fv('password'));?>" placeholder="Lösenord" required="required">
                <div class="invalid-feedback">Ange ditt lösenord</div>                        
            </div>
        </div>        
        <div class="clearfix">
            <label class="float-left form-check-label"><input type="checkbox" name="remember" id="remember"
              <?php if(! empty($_COOKIE["user_id"])) { ?> checked <?php }?> value="remember"
              > Håll mig inloggad på denna enhet!</label>          
        </div>        
        <div class="form-group">
            <button type="submit" name="login" class="btn btn-success btn-block login-btn"><i class="fa fa-sign-in"></i> Logga in</button>
        </div>
        <div class="clearfix">
            <a href="reset_password.php" class="float-right text-success">Glömt lösenordet?</a>
        </div>  
        
    </form>
    <div class="hint-text">Inget konto? <a href="signup.php" class="text-success">Registrera här!</a></div>
    <div class="hint-text">Har du problem att logga in? Skicka ett mail till: <a href="mailto:<?php print(EMAIL_SUPPORT);?>?subject=ATK Login" class="text-success"><?php print(EMAIL_SUPPORT);?></a></div>
</div>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>


  </body>
    <!-- Error messages   -->
    <?php modalErrors($errors);?>

</html>