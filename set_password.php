<?php 
require_once 'environment.php';
require_once 'sendEmails.php';
// ------------------------------------------------------
$errors = [];
// ------------------------------------------------------

// ------------------------------------------------------
// Set passwaord by FORM submission
// ------------------------------------------------------
if (isset($_POST['setpassword'])) {
  if (empty($_SESSION['set_password'])) {
      $errors['user_id'] = 'Okänt konto!';
      //$_SESSION['set_password']=$_SESSION['user_id'];
  }
  if (empty($_POST['password'])) {
      $errors['missingpassword'] = 'Lösenord saknas!';
  }
  if (isset($_POST['password']) && $_POST['password'] !== $_POST['confirm_password']) {
      $errors['notmatching'] = 'Lösenorden matchar inte!';
  }

  if (count($errors) === 0) {
    // update password
    $rec['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT); //hash password
    $n=$user->updateUser($_SESSION['set_password'],$rec);
    if($n==1) {
      // updated!
      setMessage('Lösenordet är ändrat!');
      // log in
      $user->loginUser($_SESSION['set_password']);
      unset ($_SESSION['set_password']);
      //unset($_SESSION['uri_after_login']);
    } else {
      $errors['database'] = 'Databasfel: Byte av lösenord misslyckades!';
    }
  }
}

// login ok?
//unset($_SESSION['uri_after_login']);
$user->redirect_if_logged_in();

?>

<!doctype html>
<html lang="en">
  <head>
    <?php require_once 'header.inc';?>
    <title>Ange nytt lösenord</title>
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
    <form action="" method="post" class="needs-validation" novalidate>
      <img class="mb-4" width="100%" src="images/web-form-header.png"/>
      <h2 class="text-center">Ange nytt lösenord</h2>    
      <div class="form-group">
        <div class="input-group">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-lock"></i></span></div>
          <input type="password" class="form-control input-lg<?php if(!empty($errors)) print(' is-invalid');?>" name="password" placeholder="Lösenord" required="required" >
          <?php if(empty($errors)) print('<div class="invalid-feedback">Lösenord saknas</div>');
          else foreach($errors as $e) print('<div class="invalid-feedback">'.$e.'</div>');
          ?>
        </div>
      </div>
      <div class="form-group">
        <div class="input-group">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-lock"></i></span></div>
          <input type="password" class="form-control input-lg<?php if(!empty($errors['notmatching'])) print(' is-invalid');?>" name="confirm_password" placeholder="Repetera Lösenord" required="required">
          <?php if(empty($errors)) print('<div class="invalid-feedback">Lösenord saknas</div>');
          else foreach($errors as $e) print('<div class="invalid-feedback">'.$e.'</div>');
          ?>
        </div>  
      </div>
      <div class="form-group">
        <button type="submit" name="setpassword" class="btn btn-success btn-block signup-btn"><i class="fa fa-edit"></i> Spara</button>
      </div>
    </form>
    <div class="hint-text">Javisst ja... <a href="signin.php" class="text-success">Jag kom på mitt lösenord!</a></div>

</div>
<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>


  </body>
</html>