<?php

// ------------------------------------------------------
// Messaging
// ------------------------------------------------------

function ulistMessages($errors){
    if(!is_array($errors)) $errors=[$errors];
    print('<ul>');
    if(isset($errors['desc'])) print('<h6>'.$errors['desc'].'</h6><ul>');
    if(isset($errors['list'])) foreach ($errors['list'] as $li) print('<li>'.$li.'</li>');
    else foreach ($errors as $li) print('<li>'.$li.'</li>');
    print('</ul>');
}

function displayMessages($errors){
    if ((!empty($errors))||(!empty($_SESSION['message']))) {
        print('</br>');
    }
    if (!empty($errors)) {
        print('<div class="alert alert-danger">');
        ulistMessages($errors);
        print('</div>');
    }
    if (!empty($_SESSION['message'])) {
        print('<div class="alert '.$_SESSION['type'].'">');
        if(is_array($_SESSION['message'])) {
            print('<ul>');
            foreach($_SESSION['message'] as $i){
                print('<li>'.$i.'</li>');    
            }
            print('</ul>');
        } else print $_SESSION['message'];
        print('</div>');
        unset($_SESSION['message']);
        unset($_SESSION['type']);
    }
}

function setMessage($m,$type='success'){
    if(!is_array($m)) $m=[$m];
    if(empty($_SESSION['message'])) $_SESSION['message']=[];
    if(!is_array($_SESSION['message'])) $_SESSION['message']=[$_SESSION['message']];
    $_SESSION['message']=array_merge($_SESSION['message'],$m);
    $_SESSION['type']="alert-$type";
    //print_r($_SESSION['message']);
}


function modalErrors($errors){
    //pa($errors);exit;
    if (!empty($errors)) {
        print('<script type="text/javascript">$(document).ready(function(){$("#errorMessages").modal(\'show\');});</script>
        <div class="modal" tabindex="-1" id="errorMessages" class="modal fade">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header  bg-danger">
            <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Error</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          </div>
          <div class="modal-body">');
          ulistMessages($errors);
          print('</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Stäng</button></div></div></div></div>');
    }
}


?>