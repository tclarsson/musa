<?php 
//------------------------------------------------
// logging functions
//------------------------------------------------
function dolog($s){
    return;
    $bsn=basename($_SERVER['SCRIPT_NAME']);
    switch($bsn){
        case 'maintenance_cron.php':
            $LOGFILE=LOG_FILE_CRON;
            break;
        default:
            $LOGFILE=LOG_FILE_ACTIVITY;
            break;
    }
    
	$ts=date("Y-m-d H:i:s");	// current time stamp
    if(!empty($_SESSION['user_id'])) $user_id=$_SESSION['user_id']; else $user_id='U';
    $log="$ts|$bsn|$user_id|$s".PHP_EOL;
	//print("$log");
	//file_put_contents($LOGFILE, $log, FILE_APPEND);
}

?>