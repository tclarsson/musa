<?php 
// uncomment to disable site
//header("Location: " .ROOT_URL."service_disabled.php");
//header("Location: https://medlem.aspotennisklubb.se/");


$auth= new Auth($db);
require_once "utils.php";

// Get Current date, time
$current_date = date("Y-m-d H:i:s", time());

//-------------------------------------------------------
// Check if cookie session
//-------------------------------------------------------
if (empty($_SESSION['user_id'])) {
    // Check if COOKIE for authenticated session exists
    if (! empty($_COOKIE["user_id"]) && ! empty($_COOKIE["auth_token"])) {
        //pa($_COOKIE);exit;
        // Get token(s) for username/email/etc
        $tl = $auth->getAuthTokenByMember(['user_id'=>$_COOKIE["user_id"]]);
        foreach ($tl as $tok) {
            // check if token is not expired
            if($tok["expiry_date"]>= $current_date) {
                // Validate token cookie with database
                if (password_verify($_COOKIE["auth_token"], $tok["token"])) {
                    // login user
                    $_SESSION['user_id']=$_COOKIE["user_id"];
                    //setMessage("Inloggad med COOKIE: ".$tok["expiry_date"]);    
                    break;
                }
            } else {
                // clear cookies
                //$auth->clearAuthCookie();
                // clear token
                $auth->deleteAuthTokensById($tok['token_id']);
            }
        }
    }
}


$membership_table=get_membership_table();
$user_data=[];
//-------------------------------------------------------
// session settings for authenticated users
//-------------------------------------------------------
if (! empty($_SESSION['user_id'])) {
    //pa($_SESSION);
    $currentUser = new User($db,$_SESSION['user_id'],$membership_table);
    $user_data=$currentUser->getUserData();
    //$user_data=get_member_record($_SESSION['user_id']);
    //pa($user_data);
    if(empty($user_data)) unset($_SESSION['user_id']);    // dont allow login for non existing memberships
    
    // dont allow login for hidden memberships, except INVITED
    if((!empty($user_data['membership_hide']))&&($user_data['membership_code']!=DEFAULT_MEMBERSHIPTYPE_INVITED)) unset($_SESSION['user_id']);    
    //pa($_SESSION);exit;
}

// prepare authenticated users
if (! empty($_SESSION['user_id'])) {
    // check athority/role
    $_SESSION["ROLE"]=$user_data['role_code'];
    $_SESSION["name"]=$user_data['nick_name'];
    
    // check number of unread info
    if(empty($user_data['mc_tst_infoboard'])) $user_data['mc_tst_infoboard']=0;
    $sql="SELECT count(*) as unread FROM tbInfoboard where  IFNULL(ib_hide,0)=0 AND ib_created > '$user_data[mc_tst_infoboard]'";
    $_SESSION['unread_info']=$db->getColFrmQry($sql)[0];

    // check number of unread ladder
    if(empty($user_data['mc_tst_ladder'])) $user_data['mc_tst_ladder']=0;
    $sql="SELECT count(*) as unread FROM tbLadderPlayers where lp_updated > '$user_data[mc_tst_ladder]'";
    $_SESSION['unread_ladder']=$db->getColFrmQry($sql)[0];
    
    // check number ladder challenges
    if(!empty($user_data['lp_id'])) {
        $sql="SELECT count(*) as active FROM tbLadderChallenges WHERE (lc_challenger_id = '$user_data[lp_id]' OR lc_challenged_id = '$user_data[lp_id]') AND lc_result_reported IS NULL";
        $_SESSION['ladder_challenges']=$db->getColFrmQry($sql)[0];
    } else $_SESSION['ladder_challenges']=0;
}

//-------------------------------------------------------
// user wants to sign in or sign out
//-------------------------------------------------------
if (empty($_SESSION['user_id'])) {
    // not logged in
    if(isset($_REQUEST['signin'])) {
        // save target uri
        $_SESSION['uri_after_login']=$_SERVER['SCRIPT_NAME'];
        // redirect to signin page
        header("Location: ".ROOT_URI."signin.php");
        exit(0);
    }
} else {
    // logged in
    if(isset($_REQUEST['signout'])) {
        // save target uri
        $_SESSION['uri_after_login']=$_SERVER['SCRIPT_NAME'];
        // redirect to logout page
        header("Location: ".ROOT_URI."logout.php");
        exit(0);
    }
}

//-------------------------------------------------------

//-------------------------------------------------------
function get_membership_table(){
    global $db;
    $sql="SELECT * 
    FROM  tbMembershipTypes ";
    $a=$db->getRecFrmQry($sql);
    foreach($a as $k=>$v) $r[$v['mt_membership_code']]=$v;
    return($r);
}

//-------------------------------------------------------
function get_sql_members_base(){
    $sql_members="
    FROM tbMembers
    LEFT JOIN tbMembershipTypes ON tbMembershipTypes.mt_membership_code=tbMembers.membership_code
    LEFT JOIN tbRoleTypes ON tbRoleTypes.rt_role_code=tbMembers.role_code
    LEFT JOIN tbFamilies ON tbFamilies.families_family_id=tbMembers.family_id
    LEFT JOIN tbLevelTypes ON tbLevelTypes.lt_level_code=tbMembers.level_code
    ";
    return($sql_members);
}

function get_sql_members(){
    global $membership_table;
    $sql_members="
    ,IF(family_admin_id=user_id,family_family_name,null) as family_admin
    ,IF((YEAR(NOW()) - birth_year)<=".$membership_table['JUNIOR']['membership_max_age'].",'JUNIOR','SENIOR') as  age_group
    ,IF(family_admin_id=user_id,'".$membership_table['FAMILJEANSVARIG']['membership_name']."',IF(tbMembers.family_id is null,IF((YEAR(NOW()) - birth_year)<=".$membership_table['JUNIOR']['membership_max_age'].",'".$membership_table['JUNIOR']['membership_name']."',membership_name),'".$membership_table['FAMILJEMEDLEM']['membership_name']."')) as  member_membership
    ,IF(family_admin_id=user_id,".$membership_table['FAMILJEANSVARIG']['membership_fee'].",IF(tbMembers.family_id is null,IF((YEAR(NOW()) - birth_year)<=".$membership_table['JUNIOR']['membership_max_age'].",".$membership_table['JUNIOR']['membership_fee'].",membership_fee),".$membership_table['FAMILJEMEDLEM']['membership_fee'].")) as  member_fee
    ,IFNULL(given_name,IFNULL(family_name,email)) as nick_name
    ".get_sql_members_base();
    return($sql_members);
}

function get_member_record($user_id){
    global $db;
//    global $membership_table;
    $sql_member="SELECT * ".get_sql_members()." WHERE membership_hide is null AND tbMembers.user_id=".$user_id;
//    print($sql_member);exit;
    $a=$db->getRecFrmQry($sql_member);
    if(empty($a)) return([]);
    $r=$a[0];
    return($r);
}

function get_family_record($family_id){
    global $db;
    if(empty($family_id)) return [];
    $sql="
    SELECT *
    FROM tbFamilies
    JOIN tbMembers ON tbFamilies.family_admin_id=tbMembers.user_id 
    LEFT JOIN tbMembershipTypes ON tbMembershipTypes.mt_membership_code=tbMembers.membership_code 
    WHERE tbFamilies.families_family_id=$family_id
    ";
    $a=$db->getRecFrmQry($sql);
    if(empty($a)) return([]);
    return $a[0];
}



function change_member_membership($user_id,$mc){
    global $db;
    $member_data=get_member_record($user_id);
    if(!empty($member_data)){
        $rec=[];
        // if not NORMAL then no family
        if($mc!=DEFAULT_MEMBERSHIPTYPE_NORMAL) {
            // Delete Family if admin/needed
            if(!empty($member_data['family_id'])) {
                if(!empty($member_data['family_admin'])) {
                    $dbkey=['families_family_id'=>$member_data['family_id']];
                    $db->delete('tbFamilies',$dbkey);
                    setMessage("Familj och Familjeansvarig avregistrerad.");
                }
                $rec['family_id']=null;
                setMessage("Familjemedlemskap avregistrerat.");
            }
        }
        $key=['user_id'=>$_REQUEST['user_id']];
        $rec['membership_code']=$mc;
        $db->update('tbMembers',$rec,$key);        
        setMessage("Medlemskap ändrat till: $mc");
    }
}

//-------------------------------------------------------

// ------------------------------------------------------
// family
// if $exclude_id is set - exclude member with $exclude_id
function get_family_members($family_id,$exclude_id=NULL){
    global $db;
    global $membership_table;

    $sql_list_family_members="SELECT * 
    ,IFNULL(given_name,IFNULL(family_name,email)) as nick_name
    ,IF((YEAR(NOW()) - birth_year)<=".$membership_table['JUNIOR']['membership_max_age'].",'JUNIOR','SENIOR') as  age_group
    FROM tbMembers 
    LEFT JOIN tbMembershipTypes ON tbMembershipTypes.mt_membership_code=tbMembers.membership_code
    LEFT JOIN tbFamilies ON tbFamilies.families_family_id=tbMembers.family_id
    WHERE membership_hide is null AND tbMembers.family_id=".$family_id;
    if(!empty($exclude_id)) $sql_list_family_members.=" AND NOT tbMembers.user_id=".$exclude_id;
    $sql_list_family_members.=". ORDER BY given_name";
    $a=$db->getRecFrmQry($sql_list_family_members);
    return($a);
}

// ------------------------------------------------------
// get membership 
/*
function get_membership($user_id){
    global $db;
    $sql_list_family_members="SELECT * 
    FROM tbMembers 
    LEFT JOIN tbMembershipTypes ON tbMembershipTypes.mt_membership_code=tbMembers.membership_code
    LEFT JOIN tbFamilies ON tbFamilies.user_id=tbMembers.user_id
    WHERE membership_hide is null AND tbMembers.user_id=".$user_id;
}
*/
// ------------------------------------------------------
function allowed($perm=[],$and=true){
    global $currentUser;
    if (empty($_SESSION['user_id'])) {
        return false;
    }
    if($currentUser->can($perm,$and)) return true;
    //if($currentUser->can("admin")) return true; // temporary testing
    return false;
}

function admit($perm=[],$and=true){
    global $currentUser;
    //pa('admit');pa($_SESSION);


    // ------------------------------------------------------
    // check and redirect if needed

    // user need to sign in
    if (empty($_SESSION['user_id'])) {
        //pa($_SERVER);pa($_SESSION);exit;
        // save target uri
        //pa($_SERVER);exit;
        //TODO// if(empty($_SESSION['uri_after_login'])) $_SESSION['uri_after_login']=$_SERVER['REQUEST_URI'];
        if(empty($_SESSION['uri_after_login'])) $_SESSION['uri_after_login']=$_SERVER['SCRIPT_NAME'];
        // redirect to signin page
        header("Location: ".ROOT_URI."signin.php");
        exit(0);
    } else {
        // clear target uri if user signed in
        unset($_SESSION['uri_after_login']);
    }

    // force redirect to update membership codes for New/incomplete members =NULL including INVITED
    if(empty($currentUser->user_data['membership_code'])||($currentUser->user_data['membership_code']==DEFAULT_MEMBERSHIPTYPE_INVITED)) {
        if(basename($_SERVER['SCRIPT_NAME'])!='member_update.php') {
            // force membership update
            header("Location:member_update.php");
            setMessage('Fyll i och spara dina medlemsuppgifter','info');
            exit(0);
        } else {
            //pa($_SERVER);exit;
        }
    } 
    
    if(!allowed($perm,$and)) {
        //pa($_SESSION);exit;
        // redirect to main page
        //header("Location:".HOME_PAGE);
        //return
        if(!empty($_SERVER['HTTP_REFERER'])) header("Location:".$_SERVER['HTTP_REFERER']); else header("Location: ".ROOT_URI."signin.php");
        exit(0);
    }

  
    //setMessage(basename($_SERVER['SCRIPT_NAME']).', admit: '.json_encode($perm).', '.($and?'AND':'OR'));
}

// ------------------------------------------------------


function member_authorized_only($target_data){
    global $currentUser;
    // user is target
    if($currentUser->user_data['user_id']==$target_data['user_id']) return;
    if(!empty($currentUser->user_data['family_admin'])) {
        // user is representing family member
        if($target_data['family_id']==$currentUser->user_data['family_id']) return;
    }
    // user may represent any other user
    if($currentUser->can(['onbehalf'])) return;
    // redirect to main page
    header("Location:".HOME_PAGE);
    exit(0);
}


// check if user is logged in , do some updates and direct to homepage
function redirect_if_logged_in(){
    global $auth;
    if (!empty($_SESSION['user_id'])) {
        // authenticated
        // update last_login
        $r=$auth->updateLastLogin($_SESSION['user_id']);
        // redirect to target uri
        if(!empty($_SESSION['uri_after_login'])) {
            header("Location:".$_SESSION['uri_after_login']);
            unset($_SESSION['uri_after_login']);
            exit(0);
        }

        // redirect to default/main page
        header("Location:".HOME_PAGE);
        exit(0);
    }
}



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