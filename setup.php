<?php
// https://sqldbm.com/Project/Dashboard/All/

require_once 'environment.php';
//------------------------------------------------------------------------------------------

// https://www.tclarsson.se/musa/setup.php?showtables
if(isset($_REQUEST['showtables'])){
    $sql="show tables;
";
    pa($sql);
    $a=$db->getRecFrmQry($sql);
    pa($a);
}

// http://swing/musa/setup.php?setpassword=musa&email=thomas@tclarsson.se
/*
https://musa.enfast.se/setup.php?setpassword=musa&email=thomas@tclarsson.se&org_id=1
*/

if(isset($_REQUEST['setpassword'])&&isset($_REQUEST['email'])){
    $rec['user_id']=$user->email2user($_REQUEST['email']);
    $rec['email']=$_REQUEST['email'];
    $rec['password'] = password_hash($_REQUEST['setpassword'], PASSWORD_DEFAULT); //encrypt password
    $sql="INSERT INTO musaUsers SET user_id=$rec[user_id],email='$rec[email]',password='$rec[password]',org_id=$_REQUEST[org_id] 
    ON DUPLICATE KEY UPDATE password='$rec[password]',email='$rec[email]',org_id=$_REQUEST[org_id]";
    pa($sql);
    $r=$db->executeQry($sql);
    pa($r);
}

/*
https://www.tclarsson.se/musa/setup.php?changepassword=musa&email=thomas@tclarsson.se
https://musa.enfast.se/setup.php?changepassword=musa&email=thomas@tclarsson.se
*/

if(isset($_REQUEST['changepassword'])&&isset($_REQUEST['email'])){
    $rec['user_id']=$user->email2user($_REQUEST['email']);
    $rec['email']=$_REQUEST['email'];
    $rec['password'] = password_hash($_REQUEST['changepassword'], PASSWORD_DEFAULT); //encrypt password
    $sql="UPDATE musaUsers SET email='$rec[email]',password='$rec[password]'
    WHERE user_id=$rec[user_id]";
    pa($sql);
    $r=$db->executeQry($sql);
    pa($r);
    if (!password_verify($_REQUEST['changepassword'], $rec['password'])) print('ERROR: lösenord!'); else print('Lösenord ok!');

}

?>