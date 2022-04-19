<div id="dynamicModalFrame"></div>
<hr>
<?php 
print('<button onclick="goBack()" class="btn btn-secondary" title="Tillbaka"><i class="fa fa-undo"></i> Tillbaka</button></br>');
if(!empty($user->data['org_name'])) print("
</br><div class='bg-dark text-light'><strong  class='ml-2'>".$user->data['org_name']."</strong></div>
          ");

?>
</br><a href="<?php print(ROOT_URI);?>"><img src="<?php print(ROOT_URI);?>images/web-form-footer.png" width="300"></a>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
<?php
function v($v){if(empty($v)) return ''; else return $v;}
require_once 'logging.php';
$ld="";
if(!empty($user_data)) $ld.=v($user_data['given_name'])." ".v($user_data['family_name']);
dolog($ld);


require_once 'infomodal.php';
?>
