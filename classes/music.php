<?php

trait music_common {
    protected $db;
    protected $object_modified=true;

    protected function common_construct(){
        global $db;
        $this->db=$db;
        $this->object_modified=true;
    }
    public function __set($name, $value) {
        throw new \Exception("Adding new properties is not allowed on " . __CLASS__);
    }
    public function json() {
        return json_encode($this,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    }

  
    protected static function p2a($o,$dump=false){
        $a=[];
        foreach ($o as $p=>$v) {
            $a[]=$p;
        }
        if($dump) pa('$props='.json_encode(($a)).';');
        return $a;
    }
    protected static function pa($a,$callstack=false){
        print('<pre>');
        print_r($a);
        if($callstack) foreach (debug_backtrace() as $v) {
            print("Line $v[line] in ".basename($v['file'])." calls $v[function]\n");
        }
        print('</pre>');
    }

    public function check_required($prop,$or) {
        $found=false;
        foreach ($or as $p) {
            if(!empty($prop[$p])) $found=true;
        }
        if(!$found) throw new \Exception("Missing one of properties: " . json_encode($or));
    }
  
    public function testSetProps() {
        foreach (self::p2a($this) as $p) {
            $this->{$p}=$p;
        }
    }
}

//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Gender{
    use music_common;

    public $gender_id=null;
    public $gender_name=null;
    public function __construct($i=null) {
        self::common_construct();

        if(!empty($i)) {
            if(!is_array($i)) {$i=['gender_id'=>$i];}
            foreach ($this->p2a($this) as $p) if(isset($i[$p])) $this->{$p}=$i[$p];
            if(!empty($this->gender_id)) $this->load();
        }
    }

    public function store(){
        return;
        if(empty($this->gender_id)){
            $this->gender_id=strtoupper($this->gender_name);
            $sql="INSERT musaGenderTypes SET
            gender_id='$this->gender_id',
            gender_name='$this->gender_name'
            ";
            $this->db->executeQry($sql);
            $this->gender_id=$this->db->lastInsertId();
            $this->object_modified=false;
        } else if($this->object_modified) { 
            // update
            $sql="UPDATE musaGenderTypes SET
            gender_name='$this->gender_name'
            WHERE gender_id='$this->gender_id'
            ";
            $this->db->executeQry($sql);
            $this->object_modified=false;
        }
    }
    public function load($id=null){
        if(!empty($id)) $this->gender_id=$id;
        if(!empty($this->gender_id)){
            $sql="SELECT * 
            FROM musaGenderTypes
            WHERE gender_id='$this->gender_id'
            ";
            $r=$this->db->getUniqueFrmQry($sql);
            if(!empty($r)) {
                foreach ($this->p2a($this) as $p) {
                    if(isset($r[$p])) $this->{$p}=$r[$p];
                }
            }
            $this->object_modified=false;
            return true;
        }
        return false;
    }

}

//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Country{
    use music_common;

    public $country_id=null;
    public $country_name=null;
    public function __construct($i) {
        self::common_construct();
        if(!empty($i)) {
            if(!is_array($i)) {if(is_numeric($i)) $i=['country_id'=>$i]; else $i=['country_name'=>$i];}
            foreach ($this->p2a($this) as $p) if(isset($i[$p])) $this->{$p}=$i[$p];
            if(empty($this->country_id)) $this->find_id();
            if(!empty($this->country_id)) $this->load();
        }
    }
    public function find_id(){
        if(!empty($this->country_name)) {
            $sql="SELECT * 
            FROM musaCountries
            WHERE country_name LIKE '%$this->country_name%'
            ";      
            //pa($sql);
            $r=$this->db->getRecFrmQry($sql);  
            //$r=$this->db->get('musaCountries',null,null,['country_name'=>$this->country_name]);
            if(!empty($r[0]['country_id'])) $this->country_id=$r[0]['country_id'];
        }
    }

    public function store(){
        if(empty($this->country_id)){
            $sql="INSERT musaCountries SET
            country_name='$this->country_name'
            ";
            $this->db->executeQry($sql);
            $this->country_id=$this->db->lastInsertId();
            $this->object_modified=false;
        } else if($this->object_modified) { 
            // update
            $sql="UPDATE musaCountries SET
            country_name='$this->country_name'
            WHERE country_id=$this->country_id
            ";
            $this->db->executeQry($sql);
            $this->object_modified=false;
        }
    }
    public function load($id=null){
        if(!empty($id)) $this->country_id=$id;
        if(!empty($this->country_id)){
            $sql="SELECT * 
            FROM musaCountries
            WHERE country_id=$this->country_id
            ";
            $r=$this->db->getUniqueFrmQry($sql);
            if(!empty($r)) {
                foreach ($this->p2a($this) as $p) {
                    if(isset($r[$p])) $this->{$p}=$r[$p];
                }
            }
            $this->object_modified=false;
            return true;
        }
        return false;
    }


}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Person {
    use music_common;

    public $person_id = null;
    public $family_name = null;
    public $first_name = null;
    public $date_born = null;
    public $date_dead = null;
    public $gender;
    public $country;

    public function __construct($i=null,$gender=null,$country=null){
        self::common_construct();

        $this->gender = New Gender($gender);
        $this->country = New Country($country);
        if(!empty($i)) {
            if(!is_array($i)) {if(is_numeric($i)) $i=['person_id'=>$i]; else $i=['family_name'=>$i];}
            foreach ($this->p2a($this) as $p) if(isset($i[$p])) $this->{$p}=$i[$p];
            if(!empty($this->person_id)) $this->load();
        }
    }
    

    public function store(){
        $this->gender->store();
        $this->country->store();
        $rec=[];
        $rec['family_name']=$this->family_name;
        $rec['first_name']=$this->first_name;
        $rec['gender_id']=$this->gender->gender_id;
        $rec['country_id']=$this->country->country_id;
        if(empty($this->person_id)){
            $this->db->insert('musaPersons',$rec);
            $this->person_id=$this->db->lastInsertId();
            $this->object_modified=false;
        } else if($this->object_modified) { 
            // update
            $this->db->update('musaPersons',$rec,['person_id'=>$this->person_id]);
            $this->object_modified=false;
        }
    }

    public function load($id=null){
        if(!empty($id)) $this->person_id=$id;
        if(!empty($this->person_id)){
            $r=$this->db->getUnique('musaPersons',['person_id'=>$this->person_id]);
            if(!empty($r)) {
                foreach ($this->p2a($this) as $p) {
                    if(isset($r[$p])) $this->{$p}=$r[$p];
                }
            }

            $this->gender->load($r['gender_id']);
            $this->country->load($r['country_id']);
            //self::pa($this);
            $this->object_modified=false;
            return true;
        }
        return false;
    }
    public static function delete($id){
        global $db;
        if(!empty($id)) {
            $r=$db->delete('musaMusic',['music_id'=>$id]);
            self::pa($r);
            return $r;
        }
        return false;
    }

    public static function list($lid){
        $l=[];
        foreach($lid as $id) $l[]=New self($id);
        return $l;
    }
}

//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Music {
    use music_common;

    public $music_id=null;
    public $org_id=null;
    public $title=null;
    public $subtitle=null;
    public $yearOfComp=null;
    public $movements=null;
    public $notes=null;
    public $serial_number=null;
    public $publisher=null;
    public $identifier=null;
    public $storage_id=null;
    public $choir_parts=null;
    public $solo_parts=null;
    public $arrangers;
    public $authors;
    public $categories;
    public $composers;
    
    public function __construct($id=null) {
        self::common_construct();
        if(!empty($id)) $this->load($id);
    }

    private function link_person($tn,$o){
        $sql="INSERT $tn SET
        music_id=$this->music_id,
        person_id='$o->person_id'
        ";
        //self::pa($sql);
        $this->db->executeQry($sql);
    }

    public function store(){
        if(empty($this->music_id)){
            $sql="INSERT musaMusic SET
            org_id=$this->org_id,
            title='$this->title'
            ";
            $this->db->executeQry($sql);
            $this->music_id=$this->db->lastInsertId();
            if(!empty($this->arrangers)) foreach($this->arrangers as $o){
                $o->store();
                $this->link_person('musaMusicArrangers',$o);
            }
            if(!empty($this->authors)) foreach($this->authors as $o){
                $o->store();
                $this->link_person('musaMusicAuthors',$o);
            }
            if(!empty($this->composers)) foreach($this->composers as $o){
                $o->store();
                $this->link_person('musaMusicComposers',$o);
            }
            $this->object_modified=false;
        } else if($this->object_modified) { 
            // update
            $sql="UPDATE musaMusic SET
            org_id=$this->org_id,
            title='$this->title'
            WHERE music_id=$this->music_id
            ";
            $this->db->executeQry($sql);
            $this->object_modified=false;
        }
        return $this->music_id;
    }

    private function list_persons($tn){
        $sql="SELECT person_id
        FROM $tn
        WHERE music_id=$this->music_id
        ";
        $r=$this->db->getColFrmQry($sql);
        if(!empty($r)) {
            return Person::list($r);
        }
        return [];
    }


    public function load($id=null){
        if(!empty($id)) $this->music_id=$id;
        if(!empty($this->music_id)){
            $sql="SELECT * 
            FROM musaMusic
            WHERE music_id=$this->music_id
            ";
            $r=$this->db->getUniqueFrmQry($sql);
            if(!empty($r)) {
                foreach ($this->p2a($this) as $p) {
                    if(isset($r[$p])) $this->{$p}=$r[$p];
                }
            }
            $this->arrangers=$this->list_persons('musaMusicArrangers');
            $this->authors=$this->list_persons('musaMusicAuthors');
            $this->composers=$this->list_persons('musaMusicComposers');
            $this->object_modified=false;
            return true;
        }
        return false;
    }

    public static function delete($id){
        global $db;
        if(!empty($id)) {
            $sql="DELETE FROM musaMusic
            WHERE music_id=$id
            ";
            $r=$db->deleteFrmQry($sql);
            self::pa($r);
            return $r;
        }
        return false;
    }

    public static function list_all($search=null){
        global $db;
        $cols=["music_id","org_id","storage_id","choir_parts","solo_parts","title","subtitle","yearOfComp","movements","notes","serial_number","publisher",
        "identifier",
        "person_id","gender_id","country_id","family_name","first_name","date_born","date_dead"];
        $cols=["title","subtitle","yearOfComp","movements","notes","publisher",
        "comp.family_name","comp.first_name",
        "arr.family_name","arr.first_name",
        "auth.family_name","auth.first_name",
        ];
        //$cols=["title"];
        //,"person_id","gender_id","country_id","family_name","first_name","date_born","date_dead"];
        //$sql="SELECT *,musaMusic.*,musaOrgs.*,musaStorages.*
        $sc=implode(",", $cols);
        $sql="SELECT musaMusic.*
        ,CONCAT_WS('#',$sc) as search_field
        FROM musaMusic
        LEFT JOIN musaOrgs ON musaOrgs.org_id=musaMusic.org_id
        LEFT JOIN musaStatusTypes ON musaStatusTypes.status_code=musaOrgs.status_code
        LEFT JOIN musaStorages ON musaStorages.storage_id=musaMusic.storage_id
        LEFT JOIN musaMusicComposers ON musaMusicComposers.music_id=musaMusic.music_id
        LEFT JOIN musaPersons comp ON comp.person_id=musaMusicComposers.person_id
        LEFT JOIN musaMusicArrangers ON musaMusicArrangers.music_id=musaMusic.music_id
        LEFT JOIN musaPersons arr ON arr.person_id=musaMusicArrangers.person_id
        LEFT JOIN musaMusicAuthors ON musaMusicAuthors.music_id=musaMusic.music_id
        LEFT JOIN musaPersons auth ON auth.person_id=musaMusicAuthors.person_id
        WHERE musaStatusTypes.status_hidden=0
        ";
        if(!empty($search)) $sql.="AND CONCAT_WS('#',$sc) LIKE '%$search%' ";
        $sql.="GROUP BY musaMusic.music_id";
        //pa($sql);
        $r=$db->getRecFrmQry($sql);
        //pcols($r);
        return $r;
    }
}

//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------



?>
