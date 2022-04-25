<?php

trait music_common {
    protected $db;
    protected $object_modified=true;

    protected function common_construct(){
        global $db;
        $this->db=$db;
        $this->object_modified=true;
    }

    static function p2a($o,$dump=false){
        $a=[];
        foreach ($o as $p=>$v) {
            $a[]=$p;
        }
        if($dump) pa('$props='.json_encode(($a)).';');
        return $a;
    }

    public function check_required($prop,$or) {
        $found=false;
        foreach ($or as $p) {
            if(!empty($prop[$p])) $found=true;
        }
        if(!$found) throw new \Exception("Missing one of properties: " . json_encode($or));
    }
  
    public function __set($name, $value) {
      throw new \Exception("Adding new properties is not allowed on " . __CLASS__);
    }
    public function pa($a,$callstack=false){
        print('<pre>');
        print_r($a);
        if($callstack) foreach (debug_backtrace() as $v) {
            print("Line $v[line] in ".basename($v['file'])." calls $v[function]\n");
        }
        print('</pre>');
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
    public function __construct($i) {
        self::common_construct();
        if(empty($i)) $i=[];
        else if(!is_array($i)) $i=['id'=>$i];
        if(empty($i['id'])) $i['id']='UNKNOWN';
        if(empty($i['name'])) $i['name']='Okänd';
        $this->gender_id=strtoupper($i['id']);
        $this->gender_name=$i['name'];
        switch($this->gender_id){
            case 'MALE':$this->gender_name='Man';break;
            case 'FEMALE':$this->gender_name='Kvinna';break;
        }
    }

    public function store(){
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

        if(empty($i)) $i=[];
        else if(!is_array($i)) $i=['name'=>$i];
        if(empty($i['name'])) $i['name']='Okänd';
        if(!empty($i['id'])) $this->country_id=$i['id'];
        $this->country_name=$i['name'];
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

        if(empty($i)) $i=[];
        else if(!is_array($i)) {
            if(is_numeric($i)) $i=['person_id'=>$i];
            else $i=['family_name'=>$i];
        }

        self::check_required($i,["person_id","family_name"]);
        $this->gender = New Gender($gender);
        $this->country = New Country($country);
        foreach ($this->p2a($this) as $p) {
            if(isset($i[$p])) $this->{$p}=$i[$p];
        }

        if(!empty($this->person_id)) $this->load();
    }
    

    public function store(){
        if(empty($this->person_id)){
            $this->gender->store();
            $this->country->store();
            self::pa($this);
            $sql="INSERT musaPersons SET
            family_name='$this->family_name',
            first_name='$this->first_name',
            gender_id='{$this->gender->gender_id}',
            country_id={$this->country->country_id}
            ";
            $this->db->executeQry($sql);
            $this->person_id=$this->db->lastInsertId();
            $this->object_modified=false;
        } else if($this->object_modified) { 
            // update
            $this->gender->store();
            $this->country->store();
            $sql="UPDATE musaPersons SET
            family_name='$this->family_name',
            first_name='$this->first_name',
            gender_id='$this->gender->gender_id',
            country_id=$this->country->country_id
            WHERE person_id=$this->person_id
            ";
            $this->db->executeQry($sql);
            $this->object_modified=false;
        }
    }

    public function load($id=null){
        self::pa("+++++++++++++++++++++++++",true);
        self::pa($id);
        self::pa($this);
        if(!empty($id)) $this->person_id=$id;
        if(!empty($this->person_id)){
            $sql="SELECT * 
            FROM musaPersons
            WHERE person_id=$this->person_id
            ";
            $r=$this->db->getUniqueFrmQry($sql);
            if(!empty($r)) {
                foreach ($this->p2a($this) as $p) {
                    if(isset($r[$p])) $this->{$p}=$r[$p];
                }
            }

            $this->gender->load($r['gender_id']);
            $this->country->load($r['country_id']);
            self::pa($this);
            $this->object_modified=false;
            return true;
        }
        return false;
    }
    public static function list($lid){
        pa($lid);
        $l=[];
        foreach($lid as $id){
            $i=New self($id);
            $i->load();
            $l[]=$i;
        }
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
/*
    public function __construct($i=null,$composers=null,$authors=null) {
        self::common_construct();

        if(empty($i)) $i=[];
        foreach (self::p2a($this) as $p) {
            if(!empty($i[$p])) $this->{$p}=$i[$p];
        }
        if(!empty($composers)) $this->composers = $composers;
        if(!empty($authors)) $this->authors = $authors;
        foreach ($this->p2a($this) as $p) {
            if(!empty(${$p})) $this->{$p} = ${$p};
        }

    }
*/
    private function link_person($tn,$o){
        $sql="INSERT $tn SET
        music_id=$this->music_id,
        person_id='$o->person_id'
        ";
        self::pa($sql);
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
            foreach($this->arrangers as $o){
                $o->store();
                $this->link_person('musaMusicArrangers',$o);
            }
            foreach($this->authors as $o){
                $o->store();
                $this->link_person('musaMusicAuthors',$o);
            }
            foreach($this->composers as $o){
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

}

//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------



?>
