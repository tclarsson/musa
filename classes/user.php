<?php
/*
A class representing a user.
- login
- user info
 */
class User {
    public $id = null;
    private $db = null;
    private $pdo = null;
    protected $permissions=null;
    public $data=[];
    public $membership_table=[];
 
    public function __construct($db, $user_id=null) {
        // store database class instance
        $this->db = $db;
        $this->pdo = $db->getPdo();
        if(empty($user_id)) $this->check_signout();
        else {
            $this->id=$user_id;
            $this->fetchUser();
        }
    }
    //-------------------------------------------------------
    // helpers
    //-------------------------------------------------------
    public function redirect_to_target_uri(){
        // redirect to target uri
        if(!empty($_SESSION['uri_after_login'])) {
            header("Location:".$_SESSION['uri_after_login']);
            unset($_SESSION['uri_after_login']);
        } else {    
            // redirect to default/main page
            header("Location:".HOME_PAGE);
        }
        exit(0);
    }

    //-------------------------------------------------------
    // check signout
    //-------------------------------------------------------
    public function check_signout(){
        if($this->isLoggedIn()){
            // logged in
            if (isset($_REQUEST['signout'])) {
                // user wants to signout
                $this->logoutUser();
            }
        }
    }    
    //-------------------------------------------------------
    // check at signin
    //-------------------------------------------------------
    public function check_signin(){
        // already logged in - redirect to home page
        if($this->isLoggedIn()){
            // should not happen
            // redirect to default/main page
            header("Location:".HOME_PAGE);
            exit(0);
        }
        // check post/form
        $errors=[];
        if (isset($_POST['login'])) {
            if (empty($_POST['email'])) $errors['email'] = 'Email saknas!';
            if (empty($_POST['password'])) $errors['password'] = 'Lösenord saknas!';
            if(empty($errors)) {
                $sql="SELECT *
                FROM musaUsers
                WHERE email='$_POST[email]'";
                //pa($sql);
                $r=$this->db->getUniqueFrmQry($sql);
                //pa($r);
                if(empty($r)) $errors['register'] = 'Konto saknas. Registrera!';
                if(empty($errors)) {
                    if (!password_verify($_POST['password'], $r['password'])) $errors['missmatch'] = 'Fel email/lösenord!';
                    else {
                        // login ok
                        $this->loginUser($r['user_id']);
                        // create login cookie/token?
                        if (!empty($_POST["remember"])) {
                            // Set Cookie expiration for x months
                            $cookie_expiration_time = time() + (2* 30 * 24 * 60 * 60);  // for 2 months
                            $this->setAuthCookie($cookie_expiration_time);
                        } else {
                            $this->clearAuthCookie();
                        }
                        // redirect to target uri
                        $this->redirect_to_target_uri();
                    }
                } 
                //pa($errors);
                
            }
            return $errors;
        }
    }
    //-------------------------------------------------------
    // check if user is logged in , do some updates and direct to homepage
    //-------------------------------------------------------
    public function redirect_if_logged_in(){
        if($this->isLoggedIn()){
            // authenticated
            // update last_login
            $r=$this->updateLastLogin();
            // redirect to target uri
            $this->redirect_to_target_uri();
        }
    }

    //-------------------------------------------------------
    // set cookie
    //-------------------------------------------------------
    public function setAuthCookie($time){
        $expiry_date = date("Y-m-d H:i:s", $time);
        // Insert new token in table
        $auth_token=$this->insertAuthToken($this->id, $expiry_date);
        // Insert new token in users session
        setcookie("user_id", $this->id, $time);
        setcookie("auth_token", $auth_token, $time);
        return $auth_token;
    }
      
    //-------------------------------------------------------
    // admit
    //-------------------------------------------------------
    public function admit($perm=[],$and=true){
        if($this->isLoggedIn()){
            // user signed in
            if(!$this->can($perm,$and)) {
                // not allowed
                if(!empty($_SERVER['HTTP_REFERER'])) header("Location:".$_SERVER['HTTP_REFERER']); else header("Location: ".ROOT_URI."unauthorized.php");
                exit(0);
            }
        } else {
            // user need to sign in
            $_SESSION['uri_after_login']=$_SERVER['SCRIPT_NAME'];
            // redirect to signin page
            header("Location: ".ROOT_URI."signin.php");
            exit(0);
        }
        // user admitted!
    }

    //-------------------------------------------------------
    // check if a user_id is logged in or log in with cookies
    // -load user data if logged in
    //-------------------------------------------------------
    public function isLoggedIn():bool{
        if(empty($_SESSION['musa_loggedin_user_id'])) $this->check_cookie_session();
        if(!empty($_SESSION['musa_loggedin_user_id'])) {
            $this->id=$_SESSION['musa_loggedin_user_id'];
            // retrieve user
            $this->fetchUser();
        } 
        return(!empty($_SESSION['musa_loggedin_user_id']));
    }
    //-------------------------------------------------------
    // log in user
    //-------------------------------------------------------
    public function loginUser($user_id){
        $_SESSION['musa_loggedin_user_id']=$user_id;
        $this->id=$_SESSION['musa_loggedin_user_id'];
        // update last_login
        $r=$this->updateLastLogin();
    }
    //-------------------------------------------------------
    // log out user
    //-------------------------------------------------------
    public function logoutUser(){
        unset($_SESSION['musa_loggedin_user_id']);
        $this->id=null;
        if(!empty($_SESSION['uri_after_login'])) {
            header("Location:".$_SESSION['uri_after_login']);
            unset($_SESSION['uri_after_login']);
        } else {
            header("Location: ".ROOT_URI);
        }
        //Clear Session
        session_destroy();
        // clear cookies
        $this->clearAuthCookie();
        exit(0);
    }
    //-------------------------------------------------------
    // Check if cookie session
    //-------------------------------------------------------
    private function check_cookie_session(){
        if (empty($_SESSION['musa_loggedin_user_id'])) {
            // Check if COOKIE for authenticated session exists
            if (! empty($_COOKIE["user_id"]) && ! empty($_COOKIE["auth_token"])) {
                //pa($_COOKIE);exit;
                // Get token(s) for username/email/etc
                $tl = $this->getUserTokens(['user_id'=>$_COOKIE["user_id"]]);
                foreach ($tl as $tok) {
                    // check if token is not expired
                    $current_date = date("Y-m-d H:i:s", time());
                    if($tok["expiry_date"]>= $current_date) {
                        // Validate token cookie with database
                        if (password_verify($_COOKIE["auth_token"], $tok["token"])) {
                            // login user
                            $this->loginUser($_COOKIE["user_id"]);
                            //setMessage("Inloggad med COOKIE: ".$tok["expiry_date"]);    
                            break;
                        }
                    } else {
                        // clear cookies
                        //$this->clearAuthCookie();
                        // clear token
                        $this->deleteTokensById($tok['token_id']);
                    }
                }
            }
        }
    }



    private function addPerms($permission) {
        if(!is_array($permission)) $permission=[$permission];
        $up=json_decode($this->data['permissions'],true);
        if(!is_array($up)) $up=[];
        //print('<pre>');print_r($up);print('</pre>');
        //print_r($up);exit;
        $up=array_merge($up,$permission);
        $up=(array_keys(array_flip($up)));
        array_multisort($up);
        //print('<pre>');print_r($up);print('</pre>');
        $this->permissions=$up;    // update current user 
        // update role
        $key=['role_code' => $this->data['role_code']];
        $this->db->update('musaRoleTypes',['permissions'=>json_encode($up,JSON_UNESCAPED_UNICODE)],$key);
    }
 
    private function logPagePerms($permission) {
        return;
        if(!is_array($permission)) $permission=[$permission];
        $uri=basename($_SERVER['SCRIPT_NAME']);

        // log all permission in permission table tbPermissionTypes
        $permission_list=$this->db->getRecFrmQry("SELECT * FROM tbPermissionTypes");
        foreach($permission_list as $i) $pl[$i['pt_permission_code']]=$i;
        foreach($permission as $i){
            if(empty($pl[$i])) {
                // create new permission
                $uris=[$uri];
                $this->db->insert('tbPermissionTypes',['pt_permission_code'=>$i,'pt_permission_name'=>$i,'pt_permission_uris'=>json_encode($uris,JSON_UNESCAPED_UNICODE)]);
            } else {
                // update permission
                $uris=json_decode($pl[$i]['pt_permission_uris'],true);
                if(empty($uris)) $uris=[];
                $uris=array_merge($uris,[$uri]);
                $uris=(array_keys(array_flip($uris)));
                array_multisort($uris);
                $this->db->update('tbPermissionTypes',['pt_permission_uris'=>json_encode($uris,JSON_UNESCAPED_UNICODE)],['pt_permission_code'=>$i]);
                $key=['pt_permission_code'=>$i];
            }
        }

        // log all permission per page
        $key=['pc_page' => $uri];
        $a=$this->db->get('tbPageConfig',$key);
        if(!empty($a)) {
            $rp=json_decode($a[0]['pc_permissions_log'],true);
            if(!is_array($rp)) $rp=[];
            //print('<pre>');print_r($rp);print('</pre>');
            //print_r($rp);exit;
            $rp=array_merge($rp,$permission);
            $rp=(array_keys(array_flip($rp)));
            array_multisort($rp);
            //print('<pre>');print_r($rp);print('</pre>');
            $this->db->update('tbPageConfig',['pc_permissions_log'=>json_encode($rp,JSON_UNESCAPED_UNICODE)],$key);
        } else {
            $this->db->insert('tbPageConfig',array_merge(['pc_permissions_log'=>json_encode($permission,JSON_UNESCAPED_UNICODE)],$key));
        }
    }


    /**
     * returns true if the user represented by the object can do the action(s)
     * given as a param.
     * @param permission mixed a string holding a single permission name, or
            an array of strings, each holding a permission name
     * @param and_or bool if true all permissions must be granted to the user
            if false, any of them is sufficient
     * @return boolean true if the user can, false if he can't
     */
    public function can( $permission, $and_or = true ) {
        // fetch if needed
        $this->fetchUser();
        // if root -register all permissions
        if($this->data['role_code']=='ROOT') {
            $this->addPerms($permission);
            $this->logPagePerms($permission);
        }

        /*
          if an empty string or array is given, it may well be that an
          abstraction method was used to check for permission, and we're in the
          case where no permission is required to perform the subsequent action
          */
        if ( empty( $permission ) ) {
            return true;
        }
 
 
        /*
          If a single permission is requested, as a string, just check whether
          or not it's a key of the User::permissions array
          All keys in User::permissions have been granted to the user, and have
          1 as an attached value
          All ungranted permissions do not appear as keys of User::permissions
          */
        if ( is_string( $permission ) ) {
            return in_array( $permission, $this->permissions );
        }
 
        /*
          If not, we'll have to check for all of them, and AND/OR them,
          depending on the value of the second parameter.
          First, we'll need an array with requested permissions as keys, and 0s
          as values, in which we will set 1s for every granted permission
          Easy way to do so is to use array_intersect_key, getting the value of
          each key of User::$permissions that is present in $permission, then
          simply adding an array_fill_keys version of $permission
          */
        $checked_permissions = array_intersect_key(
                                    array_fill_keys( $this->permissions, 1 ),
                                    array_fill_keys( $permission, 1 )
                        ) + array_fill_keys( $permission, 0 );
 
        /*
          Now we will need array_sum if any of the requested permissions is
          sufficient (giving a non-null sum if one of them at least is granted)
          or array_product if all of them are needed (giving a null product if
          one of them at least is null)
          */
        return $and_or ? (bool) array_product( $checked_permissions ) : (bool) array_sum( $checked_permissions );
    }
 
    /**
     * Fetch permissions for this user from the database, and set the
     * User::$permissions property, to allow caching.
     * User::$permissions will be an array with permission names as keys.
     * @return bool true on success, false on failure


     */
    private function fetchUser($refresh = false) {
        // check if loaded - then return
        if(is_array( $this->permissions )&&!$refresh) return true;

        // load user
        $sql="SELECT * 
        FROM musaUsers
        LEFT JOIN musaRoleTypes ON musaRoleTypes.role_code=musaUsers.role_code
        LEFT JOIN musaUserStatus ON musaUserStatus.status_code=musaUsers.status_code
        LEFT JOIN musaOrgs ON musaOrgs.org_id=musaUsers.org_id
        WHERE musaUsers.user_id=$this->id
        ";        
        //pa($sql);
        try {
            $a=$this->db->getRecFrmQry($sql);
        } catch(Exception $e) {
            $a=[];
        }
        if(empty($a)) {
            $this->data=[];
            $this->permissions=[];
        } else {
            $this->data=$a[0];
            $this->permissions=json_decode($a[0]['permissions']);
        }
        if(!is_array($this->permissions)) $this->permissions=[$this->permissions];
        return is_array( $this->permissions );
    }
 
 
    /**
     * Lists permissions granted to the current user
     * @refresh bool true to refresh the permission cache for this user
     * @return array an id=>name array of all permissions of the current user
     */
    public function listPerms( $refresh = false ) {
        $this->fetchUser($refresh);
        return $this->permissions;
    }
 
    public function getUserData( $refresh = false ) {
        $this->fetchUser($refresh);
        return $this->data;
    }

        //-------------------------------------------------
    // @ return 1 ass array or false
    
    public function getUserUnique($whereAnd, $whereOr   =   array(), $whereLike =   array())
    {   
        $ma=$this->db->get('musaUsers',  $whereAnd,$whereOr,$whereLike);
        if(count($ma)==1) {
            // found single member token
            return $ma[0];
        } else return false;
    }
    //-------------------------------------------------
    

    public function getUserTokens($key)
    {   
        $m=$this->db->getUnique('musaUsers',$key);
        if($m) return $this->db->get('musaTokens',['user_id'=>$m['user_id']]);
        else return false;
    }
    
    public function updatePassword($user_id,$password)
    {   
        $m=$this->getMemberByValCol($user_id,'user_id');
        if(count($m)!=1) return false;
        print_r($m);

        $password_hash=password_hash($password,PASSWORD_DEFAULT);
        $password_updated=date("Y-m-d H:i:s");
        //$password_updated=time();
        $stmt = "UPDATE musaUsers SET password = '$password_hash' ,  password_updated = '$password_updated' WHERE user_id = ?";        
        try {
            $stmt = $this->pdo->prepare($stmt);
            $stmt->execute([$user_id]);
            $result = $stmt->rowCount();
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }
    
    public function updateLastLogin()
    {   
        $sql = "UPDATE musaUsers SET last_login = NOW() WHERE user_id = $this->id";        
        $r=$this->db->executeQry($sql);
        return $r;
    }
    
    public function getMemberByKeyVal($a)
    {   
        $col=array_keys($a)[0];
        $val=$a[$col];
        $stmt = "SELECT *,TIMESTAMPDIFF(SECOND,last_login,NOW()) as since_last_login FROM musaUsers WHERE $col = ?;";
        try {
            $stmt = $this->pdo->prepare($stmt);
            $stmt->execute(array($val));
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }
    
    public function getFamilies($par=[])
    {   
        $cond=$this->db->condition($par);

//SELECT * FROM musaUsers JOIN tbFamilies USING (memeber_id)
        $stmt = "SELECT * FROM musaUsers JOIN tbFamilies USING (user_id) $cond[where];";
        try {
            $stmt = $this->pdo->prepare($stmt);
            $stmt->execute($cond['params']);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }
    
    public function getMemberByValCol($val,$col='user_id')
    {
        $stmt = "SELECT * FROM musaUsers WHERE $col = ?;";
        try {
            $stmt = $this->pdo->prepare($stmt);
            $stmt->execute(array($val));
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function email2user($email){
        $sql="SELECT user_id
        FROM musaUsers
        WHERE musaUsers.email='$email'
        ";
        $r=$this->db->getColFrmQry($sql);
        if(empty($r)) return null;
        //pa($r);
        return $r[0];
    }
        
        
    public function getSelectMember(){
        //        $fs='user_id,given_name,family_name,email,membership_code,mobile,phone,family_family_name,level_name';
                $fs='user_id,given_name,family_name,email,membership_code,mobile,phone,family_family_name,level_name,role_code';
                $sql="
                SELECT $fs
                FROM musaUsers
                LEFT JOIN musaUsershipTypes ON musaUsershipTypes.mt_membership_code=musaUsers.membership_code
                LEFT JOIN tbFamilies ON tbFamilies.families_family_id=musaUsers.family_id
                LEFT JOIN tbLevelTypes ON tbLevelTypes.lt_level_code=musaUsers.level_code
                WHERE membership_hide is null 
                ORDER BY family_name ASC
                ";
                return $this->db->getRecFrmQry($sql);
            }
        
        
                    //-------------------------------------------------
/*
Array
(
    [iss] => accounts.google.com
    [azp] => 621106888130-jajol5rgih48pl5t04i51467q3p6f0fh.apps.googleusercontent.com
    [aud] => 621106888130-jajol5rgih48pl5t04i51467q3p6f0fh.apps.googleusercontent.com
    [sub] => 106189669172231393445
    [hd] => tclarsson.se
    [email] => thomas@tclarsson.se
    [email_verified] => 1
    [at_hash] => 0v7hF3BJxk57EsaxsVD63g
    [name] => Thomas Larsson
    [picture] => https://lh5.googleusercontent.com/-jMRAmmeNCI8/AAAAAAAAAAI/AAAAAAAAAAA/AMZuucnRpOjzp-ec7m8NtqqJme6L-4d82Q/s96-c/photo.jpg
    [given_name] => Thomas
    [family_name] => Larsson
    [locale] => en-GB
    [iat] => 1597314016
    [exp] => 1597317616
    [jti] => bbb1ea4b359154405124ea36934b9fca11b29896
)
 */ 
    
    public function insertMember($user)
    {
        $allowed=['given_name','family_name','email','email_verified','token','password','password_updated','last_login','mobile','address','birth_year','comment','membership_code','family_id','role_code','level_code','created','picture','google_id','city','zipcode','phone'];

        $user['token']= $this->getToken(); // generate unique token
        if(isset($user['sub'])) $user['google_id']=$user['sub'];    // rename field
        $data=array_intersect_key($user, array_flip($allowed));

        $stmt = $this->pdo->prepare("INSERT INTO musaUsers (".implode(',', array_keys($data)).")
            VALUES (".implode(',', array_fill(0, count($data), '?')).")"
        );
        try{
            $r=$stmt->execute(array_values($data));
            if($r) {
                $user_id= $this->db->lastInsertId(); 
                $result=$this->getMemberByValCol($user_id);
                $result=$result[0];
                return $result;
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }
    //-------------------------------------------------
    //     * @return int    number of affected rows

    public function updateUser($user_id,$user)
    {
        $allowed=['given_name','family_name','email','email_verified','token','password','password_updated','last_login','mobile','address','birth_year','comment','membership_code','family_id','role_code','level_code','created','picture','google_id','city','zipcode','phone'];
        if(isset($user['sub'])) $user['google_id']=$user['sub'];    // rename field
        $data=array_intersect_key($user, array_flip($allowed));
        return $this->db->update('musaUsers', $data, ['user_id'=>$user_id]);
    }

    //-------------------------------------------------
    //-------------------------------------------------
    public function verifyAuthToken($user_id, $token, $remove=false) 
    {
        $tl=$this->db->get('musaTokens',['user_id'=>$user_id]);
        //print_r($tl);
        foreach ($tl as $key => $tok) {
            // remove expired tokens
            if($tok["expiry_date"]< date("Y-m-d H:i:s")) {
                // expired 
                if($remove) {
                    //remove
                    $this->deleteTokensById($tok['token_id']);
                }
                continue;
            }
                
            // Validate token with database
            if (password_verify($token, $tok["token"])) {
                // match!
                if($remove) {
                    //remove
                    $this->deleteTokensById($tok['token_id']);
                }
                return true;
            }
        }
        return false;
    }



    public function getTokensByValCol($val,$col='token_id')
    {
        $stmt = "SELECT * FROM musaTokens WHERE $col = ?;";
        try {
            $stmt = $this->pdo->prepare($stmt);
            $stmt->execute(array($val));
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function deleteTokensById($val,$col='token_id')
    {
        $stmt = "DELETE FROM musaTokens WHERE $col = ?;";
        try {
            $stmt = $this->pdo->prepare($stmt);
            $stmt->execute(array($val));
            $result = $stmt->rowCount();
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }
    
    public function insertAuthToken($user_id,$expiry_date)
    {
        $token=$this->getToken();
        $token_hash=password_hash($token, PASSWORD_DEFAULT); //encrypt token
        $sql = "INSERT INTO musaTokens (user_id, token, expiry_date) values ($user_id,'$token_hash','$expiry_date')";  
        //$sql = "UPDATE musaUsers SET token='$token',token_expiry_date='$expiry_date' WHERE user_id=$user_id";  
        //pa($sql);
        $this->db->executeQry($sql);
        //$this->fetchUser(true);
        return $token;
    }
    

    //-------------------------------------------------
    //-------------------------------------------------

    public function getToken($length=60)
    {
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet .= "0123456789";
        $max = strlen($codeAlphabet) - 1;
        for ($i = 0; $i < $length; $i ++) {
            $token .= $codeAlphabet[$this->cryptoRandSecure(0, $max)];
        }
        return $token;
    }
    
    public function cryptoRandSecure($min, $max)
    {
        $range = $max - $min;
        if ($range < 1) {
            return $min; // not so random...
        }
        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
    }
    
    public function redirect($url) {
        header("Location:" . $url);
        exit;
    }
    
    public function clearAuthCookie() {
        if (isset($_COOKIE["user_id"])) {
            setcookie("user_id", "");
        }
        if (isset($_COOKIE["auth_token"])) {
            setcookie("auth_token", "");
        }
    }

/*
    //-------------------------------------------------
    //-------------------------------------------------

    function getAllMembers() {
        return $this->db->getAllRecords('musaUsers');
    }

    public function getMemberByEmail($email)
    {
//        $stmt = "SELECT * FROM musaUsers WHERE email = ?;";
        $stmt = "SELECT *,musaUsers.expiration_date as m_expiration_date,musaTokens.expiration_date as t_expiration_date  FROM musaUsers,musaTokens WHERE musaUsers.user_id=musaTokens.user_id AND email = ?;";
        try {
            $stmt = $this->pdo->prepare($stmt);
            $stmt->execute(array($email));
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }


    function getMemberByUsername($username) {
        print_r($db->getAllRecords('musaUsers'));
        $db_handle = new DBController();
        $query = "Select * from members where member_name = ?";
        $result = $db_handle->runQuery($query, 's', array($username));
        return $result;
    }
    
	function getTokenByUsername($username,$expired) {
	    $db_handle = new DBController();
	    $query = "Select * from tbl_token_auth where username = ? and is_expired = ?";
	    $result = $db_handle->runQuery($query, 'si', array($username, $expired));
	    return $result;
    }
    
    function markAsExpired($tokenId) {
        $db_handle = new DBController();
        $query = "UPDATE tbl_token_auth SET is_expired = ? WHERE id = ?";
        $expired = 1;
        $result = $db_handle->update($query, 'ii', array($expired, $tokenId));
        return $result;
    }
    
    function insertToken($username, $random_password_hash, $random_selector_hash, $expiry_date) {
        $db_handle = new DBController();
        $query = "INSERT INTO tbl_token_auth (username, password_hash, selector_hash, expiry_date) values (?, ?, ?,?)";
        $result = $db_handle->insert($query, 'ssss', array($username, $random_password_hash, $random_selector_hash, $expiry_date));
        return $result;
    }
    
    function update($query) {
        mysqli_query($this->conn,$query);
    }


/*
    function check_login(){
        $errors=[];
        if (empty($_POST['email'])) $errors['email'] = 'Email saknas!';
        if (empty($_POST['password'])) $errors['password'] = 'Lösenord saknas!';
        if(empty($errors)) {
            $sql="SELECT *
            FROM musaUsers
            LEFT JOIN musaPasswords ON musaPasswords.user_id=musaUsers.user_id
            WHERE email='$_POST[email]'";
            //pa($sql);
            $r=$this->db->getUniqueFrmQry($sql);
            //pa($r);
            if (empty($r)) $errors['register'] = 'Konto saknas. Registrera!';
            if(empty($errors)) {
                if (!password_verify($_POST['password'], $r['password'])) $errors['missmatch'] = 'Fel email/lösenord!';
                else {
                    // login ok
                    $_SESSION['user_id']=$r['user_id'];
                    $this->id=$_SESSION['user_id'];
                    // update last_login
                    $r=$this->updateLastLogin();
                    //$this->fetchUser();
                    // Set Auth Cookies if 'Remember Me' checked
                    //pa($_REQUEST);exit;
                    if (!empty($_POST["remember"])) {
                        // Set Cookie expiration for x months
                        $cookie_expiration_time = time() + (2* 30 * 24 * 60 * 60);  // for 2 months
                        $expiry_date = date("Y-m-d H:i:s", $cookie_expiration_time);
                        // Insert new token
                        $auth_token=$this->insertAuthToken($_SESSION['user_id'], $expiry_date);
                        setcookie("user_id", $_SESSION['user_id'], $cookie_expiration_time);
                        setcookie("auth_token", $auth_token, $cookie_expiration_time);
                    } else {
                        $this->clearAuthCookie();
                    }
                    // redirect to target uri
                    if(!empty($_SESSION['uri_after_login'])) {
                        header("Location:".$_SESSION['uri_after_login']);
                        unset($_SESSION['uri_after_login']);
                    } else {    
                        // redirect to default/main page
                        //header("Location:".HOME_PAGE);
                    }

                }
            } 
            //pa($errors);
            
        }
        return $errors;
    }
*/
    function check_register(){
        $errors=[];
        if(empty($_POST["acceptconditions"])) $errors['acceptconditions'] = 'Man måste godkänna villkoren!';
        if(empty($_POST['email'])) $errors['email'] = 'Email saknas!';
        if(empty($_POST['password'])) $errors['password'] = 'Lösenord saknas!';
        if(isset($_POST['password'])) {
            if ($_POST['password'] !== $_POST['confirm_password']) $errors['confirm_password'] = 'Lösenorden matchar inte!';
        }
        if(empty($errors)) {
            if(!empty($md)) {
              // existing user
              $user['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT); //encrypt password
              if(empty($md['role_code'])) $user['role_code']=DEFAULT_ROLETYPE_SELFREG;
              $r=$auth->updateUser($md['user_id'],$user);
              if($r!==false) {
                // updated!
                $_SESSION['musa_loggedin_user_id'] = $md['user_id'];
                $_SESSION['message'] = 'Du är inloggad!';
                $_SESSION['type'] = 'alert-success';
              } else {
                  $errors['database'] = 'Databasfel: Registrering misslyckades!';
              }
            } else {
              // new user
              $user['email'] = $_POST['email'];
              $user['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT); //encrypt password
              $user['role_code']=DEFAULT_ROLETYPE_SELFREG;
        
              $md=$auth->insertMember($user);
              if($md!==false) {
                // inserted!
                $_SESSION['musa_loggedin_user_id'] = $md['user_id'];
                $_SESSION['message'] = 'Du är inloggad!';
                $_SESSION['type'] = 'alert-success';
              } else {
                  $errors['database'] = 'Databasfel: Registrering misslyckades!';
              }
            }
          }
          return $errors;
        }
        
//        $errors['email'] = 'Ett konto finns redan med detta Email - Logga in istället!';


    /*
    $_POST['email']
    */
    function password_reset(){
        $errors=[];
        if (empty($_POST['email'])) {
            $errors['email'] = 'ERROR: Email saknas!';
        } else {
            $user_id=$this->email2user($_POST['email']);
            if(!empty($user_id)) {
                // create temp auth_token
                $expiry_date = date("Y-m-d H:i:s", time() + (24 * 60 * 60)); // 24h
                // Insert new token
                $token=$this->insertAuthToken($user_id, $expiry_date);

                // send email
                sendResetEmail(['email'=>$_POST['email'],'token'=>$token,'expires'=>$expiry_date]);
                setMessage("Återställningsinformation skickad till ".$_POST['email'],'success');
            } else {
                $errors['email'] = 'ERROR: Användare saknas!';                
            }
        }
        return $errors;
        // go to login page
        //exit(0);
    }
  

    // ------------------------------------------------------

  
}

?>
