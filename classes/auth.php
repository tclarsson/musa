<?php


class Auth {

    private $db = null;
    private $pdo = null;

    public function __construct($db)
    {
        // store database class instance
        $this->db = $db;
        // store handle
        $this->pdo = $db->getPdo();
    }

    
    //-------------------------------------------------
    // @ return 1 ass array or false
    
    public function getMemberUnique($whereAnd, $whereOr   =   array(), $whereLike =   array())
    {   
        $ma=$this->db->get('tbMembers',  $whereAnd,$whereOr,$whereLike);
        if(count($ma)==1) {
            // found single member token
            return $ma[0];
        } else return false;
    }
    //-------------------------------------------------
    

    public function getAuthTokenByMember($key)
    {   
        $m=$this->db->getUnique('tbMembers',$key);
        if($m) return $this->db->get('tbAuthTokens',['user_id'=>$m['user_id']]);
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
        $stmt = "UPDATE tbMembers SET password = '$password_hash' ,  password_updated = '$password_updated' WHERE user_id = ?";        
        try {
            $stmt = $this->pdo->prepare($stmt);
            $stmt->execute([$user_id]);
            $result = $stmt->rowCount();
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }
    
    public function updateLastLogin($user_id)
    {   
        $last_login=date("Y-m-d H:i:s");
        $stmt = "UPDATE tbMembers SET last_login = '$last_login' WHERE user_id = ?";        
        try {
            $stmt = $this->pdo->prepare($stmt);
            $stmt->execute([$user_id]);
            $result = $stmt->rowCount();
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }
    
    public function getMemberByKeyVal($a)
    {   
        $col=array_keys($a)[0];
        $val=$a[$col];
        $stmt = "SELECT *,TIMESTAMPDIFF(SECOND,last_login,NOW()) as since_last_login FROM tbMembers WHERE $col = ?;";
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

//SELECT * FROM tbMembers JOIN tbFamilies USING (memeber_id)
        $stmt = "SELECT * FROM tbMembers JOIN tbFamilies USING (user_id) $cond[where];";
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
        $stmt = "SELECT * FROM tbMembers WHERE $col = ?;";
        try {
            $stmt = $this->pdo->prepare($stmt);
            $stmt->execute(array($val));
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function getSelectMember(){
//        $fs='user_id,given_name,family_name,email,membership_code,mobile,phone,family_family_name,level_name';
        $fs='user_id,given_name,family_name,email,membership_code,mobile,phone,family_family_name,level_name,role_code';
        $sql="
        SELECT $fs
        FROM tbMembers
        LEFT JOIN tbMembershipTypes ON tbMembershipTypes.mt_membership_code=tbMembers.membership_code
        LEFT JOIN tbFamilies ON tbFamilies.families_family_id=tbMembers.family_id
        LEFT JOIN tbLevelTypes ON tbLevelTypes.lt_level_code=tbMembers.level_code
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

        $stmt = $this->pdo->prepare("INSERT INTO tbMembers (".implode(',', array_keys($data)).")
            VALUES (".implode(',', array_fill(0, count($data), '?')).")"
        );
        try{
            $r=$stmt->execute(array_values($data));
            if($r) {
                $user_id= $this->pdo->lastInsertId(); 
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

    public function updateMember($user_id,$user)
    {
        $allowed=['given_name','family_name','email','email_verified','token','password','password_updated','last_login','mobile','address','birth_year','comment','membership_code','family_id','role_code','level_code','created','picture','google_id','city','zipcode','phone'];
        if(isset($user['sub'])) $user['google_id']=$user['sub'];    // rename field
        $data=array_intersect_key($user, array_flip($allowed));
        return $this->db->update('tbMembers', $data, ['user_id'=>$user_id]);
    }

    //-------------------------------------------------
    //-------------------------------------------------
    public function verifyAuthToken($user_id, $token, $remove=false) 
    {
        $tl=$this->db->get('tbAuthTokens',['user_id'=>$user_id]);
        foreach ($tl as $key => $tok) {
            // remove expired tokens
            if($tok["expiry_date"]< date("Y-m-d H:i:s")) {
                // expired 
                if($remove) {
                    //remove
                    $this->deleteAuthTokensById($tok['token_id']);
                }
                continue;
            }
                
            // Validate token with database
            if (password_verify($token, $tok["token"])) {
                // match!
                if($remove) {
                    //remove
                    $this->deleteAuthTokensById($tok['token_id']);
                }
                return true;
            }
        }
        return false;
    }



    public function getAuthTokensByValCol($val,$col='token_id')
    {
        $stmt = "SELECT * FROM tbAuthTokens WHERE $col = ?;";
        try {
            $stmt = $this->pdo->prepare($stmt);
            $stmt->execute(array($val));
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function deleteAuthTokensById($val,$col='token_id')
    {
        $stmt = "DELETE FROM tbAuthTokens WHERE $col = ?;";
        try {
            $stmt = $this->pdo->prepare($stmt);
            $stmt->execute(array($val));
            $result = $stmt->rowCount();
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }
    
    public function insertAuthToken($user_id,$token,$expiry_date)
    {
        $stmt = "INSERT INTO tbAuthTokens (user_id, token, expiry_date) values (?, ?, ?)";        
        try {
            $stmt = $this->pdo->prepare($stmt);
            $stmt->execute([$user_id,$token,$expiry_date]);
            $result = $stmt->rowCount();
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
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

    //-------------------------------------------------
    //-------------------------------------------------

    function getAllMembers() {
        return $this->db->getAllRecords('tbMembers');
    }

    public function getMemberByEmail($email)
    {
/*        
SELECT *,tbMembers.expiration_date as m.expiration_date FROM tbMembers,tbAuthTokens WHERE tbMembers.user_id=tbAuthTokens.user_id AND email = 'thomas@tclarsson.se'        
*/
//        $stmt = "SELECT * FROM tbMembers WHERE email = ?;";
        $stmt = "SELECT *,tbMembers.expiration_date as m_expiration_date,tbAuthTokens.expiration_date as t_expiration_date  FROM tbMembers,tbAuthTokens WHERE tbMembers.user_id=tbAuthTokens.user_id AND email = ?;";
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
        print_r($db->getAllRecords('tbMembers'));
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

    
    //----------------------------------------------------------------------------------------

}
?>