<?php
class Database{
 
    /**
     * database connection object
     * @var \PDO
     */
    protected $pdo = null;
    public $tables = [];
    public $columns = [];
 
    /**
     * Connect to the database
     */

    public function __construct($host,$port,$db,$user,$pass)
    {   
        try {
            //print(__FILE__."</br>");
            $this->pdo = new \PDO("mysql:host=$host;port=$port;charset=utf8mb4;dbname=$db",$user,$pass);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            // preserve datatypes INT in queries
            $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, FALSE);
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }



    /**
     * Return the pdo connection
     */
    public function getPdo()
    {
        return $this->pdo;
    }
 
    /**
     * Changes a camelCase table or field name to lowercase,
     * underscore spaced name
     *
     * @param  string $string camelCase string
     * @return string underscore_space string
     */
    protected function camelCaseToUnderscore($string)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
    }
 
    /**
     * Returns the ID of the last inserted row or sequence value
     *
     * @param  string $param Name of the sequence object from which the ID should be returned.
     * @return string representing the row ID of the last row that was inserted into the database.
     */
    public function lastInsertId($param = null)
    {
        return $this->pdo->lastInsertId($param);
    }
 
    /**
     * handler for dynamic CRUD methods
     *
     * Format for dynamic methods names -
     * Create:  insertTableName($arrData)
     * Retrieve: getTableNameByFieldName($value)
     * Update: updateTableNameByFieldName($value, $arrUpdate)
     * Delete: deleteTableNameByFieldName($value)
     *
     * @param  string     $function
     * @param  array      $arrParams
     * @return array|bool
     */
    public function __call($function, array $params = array())
    {
        if (! preg_match('/^(get|update|insert|delete)(.*)$/', $function, $matches)) {
            throw new \BadMethodCallException($function.' is an invalid method Call');
        }
 
        if ('insert' == $matches[1]) {
            if (! is_array($params[0]) || count($params[0]) < 1) {
                throw new \InvalidArgumentException('insert values must be an array');
            }
            return $this->insert($this->camelCaseToUnderscore($matches[2]), $params[0]);
        }
 
        list($tableName, $fieldName) = explode('By', $matches[2], 2);
        if (! isset($tableName, $fieldName)) {
            throw new \BadMethodCallException($function.' is an invalid method Call');
        }
         
        if ('update' == $matches[1]) {
            if (! is_array($params[1]) || count($params[1]) < 1) {
                throw new \InvalidArgumentException('update fields must be an array');
            }
            return $this->update(
                $this->camelCaseToUnderscore($tableName),
                $params[1],
                array($this->camelCaseToUnderscore($fieldName) => $params[0])
            );
        }
 
        //select and delete method
        return $this->{$matches[1]}(
            $this->camelCaseToUnderscore($tableName),
            array($this->camelCaseToUnderscore($fieldName) => $params[0])
        );
    }
 

 /**
     * Record retrieval WHERE condition generator
     */
    public function condition($whereAnd  =   array(), $whereOr   =   array(), $whereLike =   array())
    {
    $cond   =   '';
    $s=1;
    $params =   array();
    foreach($whereAnd as $key => $val)
    {
        if(!empty($cond)) $cond   .=  " And ";
        $cond   .=  $key." = :a".$s;
        $params['a'.$s] = $val;
        $s++;
    }
    foreach($whereOr as $key => $val)
    {
        if(!empty($cond)) $cond   .=  " OR ";
        $cond   .=  $key." = :a".$s;
        $params['a'.$s] = $val;
        $s++;
    }
    foreach($whereLike as $key => $val)
    {
        if(!empty($cond)) $cond   .=  " OR ";
        $cond   .=  $key." like '% :a".$s."%'";
        $params['a'.$s] = $val;
        $s++;
    }
    if(!empty($cond)) return ['where'=>"WHERE $cond",'params'=>$params];
    else return ['where'=>'','params'=>[]];

//    $stmt = $this->pdo->prepare("SELECT  $tableName.* FROM $tableName WHERE 1 ".$cond);
//      $stmt->execute($params);
    }
    
    /**
     * Record retrieval method
     *
     * @param  string     $tableName name of the table
     * @param  array      $where     (key is field name)
     * @return array (associative multidim array for multiple records)
     */
    public function get($tableName,  $whereAnd  =   array(), $whereOr   =   array(), $whereLike =   array())
    {
    $cond   =   '';
    $s=1;
    $params =   array();
    foreach($whereAnd as $key => $val)
    {
        $cond   .=  " And ".$key." = :a".$s;
        $params['a'.$s] = $val;
        $s++;
    }
    foreach($whereOr as $key => $val)
    {
        $cond   .=  " OR ".$key." = :a".$s;
        $params['a'.$s] = $val;
        $s++;
    }
    foreach($whereLike as $key => $val)
    {
        $cond   .=  " OR ".$key." like '% :a".$s."%'";
        $params['a'.$s] = $val;
        $s++;
    }
    $stmt = $this->pdo->prepare("SELECT  $tableName.* FROM $tableName WHERE 1 ".$cond);
        try {
            $stmt->execute($params);
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $res;
        } catch (\PDOException $e) {
            self::pa($stmt,true);
            throw new \RuntimeException("[".$e->getCode()."] : ". $e->getMessage());
        }
    }

        /**
     * Record retrieval method
     *
     * @param  string     $tableName name of the table
     * @param  array      $where     (key is field name)
     * @return array (associative  array for sigle record or FALSE)
     */
    public function getUnique($tableName,  $whereAnd  =   array(), $whereOr   =   array(), $whereLike =   array()){
        $a=$this->get($tableName, $whereAnd, $whereOr, $whereLike);
        if(count($a)==1) {
            return $a[0];
        } else return false;

    }

    public function getAllRecords($tableName, $fields='*', $cond='', $orderBy='', $limit='')
    {
        //echo "SELECT  $tableName.$fields FROM $tableName WHERE 1 ".$cond." ".$orderBy." ".$limit;
        //print "<br>SELECT $fields FROM $tableName WHERE 1 ".$cond." ".$orderBy." ".$limit;
        $stmt = $this->pdo->prepare("SELECT $fields FROM $tableName WHERE 1 ".$cond." ".$orderBy." ".$limit);
        //print "SELECT $fields FROM $tableName WHERE 1 ".$cond." ".$orderBy." " ;
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }
     
    public function getRecFrmQry($query)
    {
        //echo $query;
        $stmt = $this->pdo->prepare($query);
        //print("</br>");print_r($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }

    public function getUniqueFrmQry($query)
    {
        //echo $query;
        $stmt = $this->pdo->prepare($query);
        //print("</br>");print_r($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(count($rows)==1) {
            return $rows[0];
        } 
        else return [];
    }

    public function getColFrmQry($query)
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $rows;
    }
     
    
    public function getQueryCount($tableName, $field, $cond='')
    {
        $stmt = $this->pdo->prepare("SELECT count($field) as total FROM $tableName WHERE 1 ".$cond);
        try {
            $stmt->execute();
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
           
            if (! $res || count($res) != 1) {
               return $res;
            }
            return $res;
        } catch (\PDOException $e) {
            self::pa($stmt,true);
            throw new \RuntimeException("[".$e->getCode()."] : ". $e->getMessage());
        }
    }
     
    /**
     * Update Method
     *
     * @param  string $tableName
     * @param  array  $set       (associative where key is field name)
     * @param  array  $where     (associative where key is field name)
     * @return int    number of affected rows
     */
    public function update($tableName, array $set, array $where)
    {
        $arrSet = array_map(
           function($value) {
                return $value . '=:' . $value;
           },
           array_keys($set)
         );
             
         $arrWhere = array_map(
            function($value) {
                 return $value . '=:' . $value;
            },
            array_keys($where)
          );
              
         $sql="UPDATE $tableName SET ". implode(',', $arrSet).' WHERE '. implode(' AND ', $arrWhere);
         $stmt = $this->pdo->prepare($sql);
        try {
            //pa($sql);
            //pa(array_merge($set,$where));
            $stmt->execute(array_merge($set,$where));
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            self::pa($sql,true);
            throw new \RuntimeException("[".$e->getCode()."] : ". $e->getMessage());
        }
    }

    public function insertupdate($tableName, array $set, array $where)
    {
        $arrSet = array_map(
           function($value) {
                return $value . '=:' . $value;
           },
           array_keys($set)
        );
             
        $arrWhere = array_map(
        function($value) {
                return $value . '=:' . $value;
        },
        array_keys($where)
        );
            
        $sql="INSERT $tableName SET ".implode(',', array_merge($arrWhere, $arrSet))."
        ON DUPLICATE KEY UPDATE ".implode(',', $arrSet);
        $stmt = $this->pdo->prepare($sql);
        try {
            pa($sql);
            $stmt->execute(array_merge($set,$where));
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            self::pa($sql,true);
            throw new \RuntimeException("[".$e->getCode()."] : ". $e->getMessage());
        }
    }

 
    /**
     * Delete Method
     *
     * @param  string $tableName
     * @param  array  $where     (associative where key is field name)
     * @return int    number of affected rows
     */
    public function delete($tableName, array $where)
    {
        $arrWhere = array_map(
            function($value) {
                 return $value . '=:' . $value;
            },
            array_keys($where)
          );

        $sql="DELETE FROM $tableName WHERE ". implode(' AND ', $arrWhere);
        $stmt = $this->pdo->prepare($sql);
        //$stmt = $this->pdo->prepare("DELETE FROM $tableName WHERE ".key($where) . ' = ?');
        try {
            $stmt->execute($where);
 
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            self::pa($sql,true);
            throw new \RuntimeException("[".$e->getCode()."] : ". $e->getMessage());
        }
    }

    
     
    public function deleteFrmQry($query)
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->rowCount();
    }
 
    public function executeQry($query)
    {
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute();
    }
 
 
    /**
     * Insert Method
     *
     * @param  string $tableName
     * @param  array  $arrData   (data to insert, associative where key is field name)
     * @return int    number of affected rows
     */
    public function insert($tableName, array $data)
    {
        $stmt = $this->pdo->prepare("INSERT INTO $tableName (".implode(',', array_keys($data)).")
            VALUES (".implode(',', array_fill(0, count($data), '?')).")"
        );
        try{
            $stmt->execute(array_values($data));
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            self::pa($stmt,true);
            self::pa($data,true);
            throw new \RuntimeException("[".$e->getCode()."] : ". $e->getMessage());
        }
    }
    protected static function pa($a,$callstack=false){
        print('<pre>');
        print_r($a);
        if($callstack) foreach (debug_backtrace() as $v) {
            print("Line $v[line] in ".basename($v['file'])." calls $v[function]\n");
        }
        print('</pre>');
    }
    
    /**
     * Cache Method
     *
     * @param  string QUERY
     * @param  Int Time default 0 set 
     */
   public function getCache($sql,$filePath,$cache_min=0) {
	  $f = $filePath.md5($sql);
      if ( $cache_min!=0 and file_exists($f) and ( (time()-filemtime($f))/60 < $cache_min ) ) {
        $arr = unserialize(file_get_contents($f));
      }
      else {
        unlink($f);
        $arr = self::getRecFrmQry($sql);
        if ($cache_min!=0) {
          $fp = fopen($f,'w');
          fwrite($fp,serialize($arr));
          fclose($fp);
        }
      }
      return $arr;
    }

    //-------------------------------------------------------
    // get column info
    public function getColInfo($c) {
        if(empty($this->tables)){
            $this->tables= self::getColFrmQry("SHOW TABLES");
        }
        if(empty($this->columns)){
            foreach ($this->tables as $tn) {
                $ti = self::getRecFrmQry("SHOW COLUMNS IN $tn");
                foreach ($ti as $ci) {
                    //$ci=array_change_key_case($ci, CASE_LOWER);
                    $this->columns[$ci['Field']]=$ci;
                }
            }
            //pa($this->columns);
        }
        if(!empty($this->columns[$c])) {
            return($this->columns[$c]);
        }
        return null;
    }
}
?>