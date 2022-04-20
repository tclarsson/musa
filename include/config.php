<?php
// system settings
const ROOT_URL=ROOT_HOST.ROOT_URI;
const HOME_PAGE=ROOT_URI."index.php";
setlocale(LC_TIME, "sv_SE.UTF-8");


// application settings
const APPLICATION_NAME='Musikarkivet';
const LINES_PER_PAGE = 20;

//default access when: selfregistered, invited, verified,
const DEFAULT_ROLETYPE_SELFREG='REG';
const DEFAULT_ROLETYPE_INVITED='MEDLEM';
const DEFAULT_ROLETYPE_VERIFIED='MEDLEM';

// email
const EMAIL_CONTACT='musa@tclarsson.se';
const EMAILLIST_NOTIFY_NEW_MEMBERS=['thomas@tclarsson.se'];
const EMAIL_SUPPORT='support@enfast.se';

const URL_SUPPORT='https://support.enfast.se';



// start session
session_start();
$db = new Database(DB_HOST,DB_PORT,DB_DATABASE,DB_USERNAME,DB_PASSWORD);
//$link = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE); 

// mysql settings
// SET time_zone = '+2:00';
// SET time_zone = 'Europe/Stockholm';
if(!empty(MYSQL_TIMEZONE)) $db->executeQry("SET time_zone = ".MYSQL_TIMEZONE);

// global variable for validation errors
$errors=[];

require_once 'utils.php';
require_once 'messages.php';

$user= new User($db);

?>