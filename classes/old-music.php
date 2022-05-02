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
    public function mod() {
        $this->object_modified=true;
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
    protected static function ass($t){
        if(!$t) self::pa('Test failed.',true);
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
    //const TABLE_MAIN='musaGenderTypes';

    public $gender_id=null;
    public $gender_name=null;
    public function __construct($i=null) {
        self::common_construct();
        $this->load($i);
        $this->json();
    }

    public function load($i=null){
        $this->gender_id=null;
        $this->gender_name=null;
        if(!empty($i)) {
            if(in_array(strtolower($i),['m','man','male','mr','herr','herre','pojke'])) {
                $this->gender_id='M';
                $this->gender_name='Man';
            } else if(in_array(strtolower($i),['f','woman','female','mrs','fru','kvinna','dam','flicka'])) {
                $this->gender_id='F';
                $this->gender_name='Kvinna';
            }
        }
        $this->object_modified=false;
    }

    public function store(){
        $this->object_modified=false;
    }

    public static function _test(){
        self::pa("---------------------------------------------------------------------------\n".__CLASS__." class test started.");
        $o=New self();self::pa($o->json());self::ass($o->gender_id==null);self::ass($o->gender_name==null);
        $o=New self('Man');self::pa($o->json());self::ass($o->gender_id=='M');self::ass($o->gender_name=='Man');
        $o=New self('kVinna');self::ass($o->gender_id=='F');self::ass($o->gender_name=='Kvinna');
        $o=New self('kjsdhgfna');self::ass($o->gender_id=='F');self::ass($o->gender_name=='Kvinna');
        $o=New self();self::ass(empty($o->gender_id));self::ass(empty($o->gender_name));
        self::pa(__CLASS__." class test performed.\n---------------------------------------------------------------------------");
    }

    
}

//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Country{
    use music_common;
    const TABLE_MAIN='musaCountries';
    const TABLE_PROPS=['country_name'];


    public $country_id=null;
    public $country_name=null;
    public function __construct($i) {
        self::common_construct();
        if(!empty($i)) {
            if(!is_array($i)) {if(is_numeric($i)) $i=['country_id'=>$i]; else $i=['country_name'=>$i];}
            foreach ($this->p2a($this) as $p) if(isset($i[$p])) $this->{$p}=$i[$p];
            if(empty($this->country_id)) $this->like();            
            if(!empty($this->country_id)) $this->load();
        }
    }
    private function like(){
        if(!empty($this->country_name)) {
            $sql="SELECT country_id 
            FROM ".self::TABLE_MAIN."
            WHERE country_name LIKE '%$this->country_name%'
            ";      
            $r=$this->db->getColFrmQry($sql);  
            if(!empty($r[0])) $this->country_id=$r[0];
        }
    }

    public function store($force=false){
        if(empty($this->country_id)) $this->like();
        $rec=[];foreach (self::TABLE_PROPS as $p) if(!empty($this->{$p})) $rec[$p]=$this->{$p};
        if(!empty($rec)) {
            if(empty($this->country_id)){
                $this->db->insert(self::TABLE_MAIN,$rec);
                $this->country_id=$this->db->lastInsertId();
            } else if($this->object_modified||$force) { 
                $this->db->update(self::TABLE_MAIN,$rec,['country_id'=>$this->country_id]);
            }
        }
        $this->object_modified=false;
    }

    public function load($id=null){
        if(!empty($id)) $this->country_id=$id;
        if(!empty($this->country_id)){
            $r=$this->db->getUnique(self::TABLE_MAIN,['country_id'=>$this->country_id]);
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

    public static function delete($id){
        global $db;
        if(!empty($id)) {
            $r=$db->delete(self::TABLE_MAIN,['country_id'=>$id]);
            return true;
        }
        return false;
    }

    public static function list_all(){
        global $db;
        $r=$db->getAllRecords(self::TABLE_MAIN);
        return $r;
    }

    public static function _test(){
        self::pa("---------------------------------------------------------------------------\n".__CLASS__." class test started.");
        $o=New self('GGGG');$o->store();self::ass($o->country_id!=null);self::pa($o);
        self::delete($o->country_id);
        $o=New self('GGGG');self::ass($o->country_id==null);
        $o=New self('Sverige');self::ass($o->country_id!=null);
        $o=New self('SvErige');self::ass($o->country_id!=null);self::pa($o->json());
        //self::pa(self::list_all());
        self::pa(__CLASS__." class test performed.\n---------------------------------------------------------------------------");
    }

}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Person {
    use music_common;
    const TABLE_MAIN='musaPersons';
    const TABLE_COLS_PROPS=['gender_id', 'country_id', 'family_name', 'first_name', 'date_born', 'date_dead'];
    const TABLE_PROPS=['family_name', 'first_name', 'date_born', 'date_dead'];


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
            if(empty($this->person_id)) $this->like();            
            if(!empty($this->person_id)) $this->load();
        }
    }

    private static function lh($p,$c){
        if(!empty($p)) return "AND IF(ISNULL($c),1,$c LIKE '%$p%') ";      
        return "";
    }
    private function like(){
        $sql="SELECT person_id 
        FROM ".self::TABLE_MAIN."
        WHERE 1 ";
        foreach (self::TABLE_PROPS as $p) $sql.=self::lh($this->{$p},$p);
        $sql.=self::lh($this->gender->gender_id,'gender_id');
        $sql.=self::lh($this->country->country_id,'country_id');
        //pa($sql);
        $r=$this->db->getColFrmQry($sql);  
        //pa($r);
        if(!empty($r[0])) $this->person_id=$r[0];
    }

    public function store($force=false){
        if(empty($this->person_id)) $this->like();
        $this->gender->store();
        $this->country->store();
        $rec=[];foreach (self::TABLE_PROPS as $p) if(!empty($this->{$p})) $rec[$p]=$this->{$p};
        $rec['gender_id']=$this->gender->gender_id;
        $rec['country_id']=$this->country->country_id;
        if(!empty($rec)) {
            if(empty($this->person_id)){
                $this->db->insert(self::TABLE_MAIN,$rec);
                $this->person_id=$this->db->lastInsertId();
            } else if($this->object_modified||$force) { 
                $this->db->update(self::TABLE_MAIN,$rec,['person_id'=>$this->person_id]);
            }
        }
        $this->object_modified=false;
    }

    public function load($id=null){
        if(!empty($id)) $this->person_id=$id;
        if(!empty($this->person_id)){
            $r=$this->db->getUnique(self::TABLE_MAIN,['person_id'=>$this->person_id]);
            if(!empty($r)) foreach ($this->p2a($this) as $p) if(isset($r[$p])) $this->{$p}=$r[$p];
            if(!empty($r['gender_id'])) $this->gender->load($r['gender_id']);
            if(!empty($r['country_id'])) $this->country->load($r['country_id']);
            $this->object_modified=false;
            return true;
        }
        return false;
    }
    public static function delete($id){
        global $db;
        if(!empty($id)) {
            $r=$db->delete(self::TABLE_MAIN,['person_id'=>$id]);
            return $r;
        }
        return false;
    }

    public static function list($lid){
        $l=[];
        foreach($lid as $id) $l[]=New self($id);
        return $l;
    }
    public static function list_all(){
        global $db;
        $r=$db->getAllRecords(self::TABLE_MAIN);
        return $r;
    }

    public static function _test(){
        self::pa("---------------------------------------------------------------------------\n".__CLASS__." class test started.");
        $o=New self('Nisse');$o->store();self::pa($o);
        $id=$o->person_id;
        $o=New Person($id);$o->date_born=1965;$o->store(true);self::pa($o->json());
        $o=New Person($id);self::pa($o->json());self::ass($o->date_born==1965);
        
        $o=New Person(["person_id"=>3,"family_name"=>"Larsson"],'F','Norge');self::pa($o->json());
        //$o=New Person("Erik");self::pa($o);
        $o=New Person("Larsson",'MALE','Sverige');self::pa($o->json());
        //$o=New Person("Erik");

        self::delete($id);

        //$o=New self('GGGG');$o->store();self::ass($o->person_id!=null);self::pa($o->json());
        //$o=New self('Sverige');self::ass($o->person_id!=null);self::pa($o->json());
        //$o=New self('SvErige');self::ass($o->person_id!=null);self::pa($o->json());
        //self::pa(self::list_all());
        self::pa(__CLASS__." class test performed.\n---------------------------------------------------------------------------");
    }

}

//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Music {
    use music_common;
    const TABLE_MAIN='musaMusic';


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
    public $choirvoice_id=null;
    public $solovoice_id=null;
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
        $cols=["music_id","org_id","storage_id","choirvoice_id","solovoice_id","title","subtitle","yearOfComp","movements","notes","serial_number","publisher",
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
        LEFT JOIN musaStatusTypes ON musaStatusTypes.user_status_code=musaOrgs.org_status_code
        LEFT JOIN musaStorages ON musaStorages.storage_id=musaMusic.storage_id
        LEFT JOIN musaMusicComposers ON musaMusicComposers.music_id=musaMusic.music_id
        LEFT JOIN musaPersons comp ON comp.person_id=musaMusicComposers.person_id
        LEFT JOIN musaMusicArrangers ON musaMusicArrangers.music_id=musaMusic.music_id
        LEFT JOIN musaPersons arr ON arr.person_id=musaMusicArrangers.person_id
        LEFT JOIN musaMusicAuthors ON musaMusicAuthors.music_id=musaMusic.music_id
        LEFT JOIN musaPersons auth ON auth.person_id=musaMusicAuthors.person_id
        WHERE musaStatusTypes.user_status_hidden=0
        ";
        if(!empty($search)) $sql.="AND CONCAT_WS('#',$sc) LIKE '%$search%' ";
        $sql.="GROUP BY musaMusic.music_id";
        //pa($sql);
        $r=$db->getRecFrmQry($sql);
        //pcols($r);
        return $r;
    }

    public static function _test(){
        self::pa("---------------------------------------------------------------------------\n".__CLASS__." class test started.");
        $p=New Person("Person1",'kvinna','testland');
        $o=New Music;
        $o->org_id=2;
        $o->title="Happy";$o->mod();
        $o->arrangers=[New Person("arr1"),New Person("arr2",'MALE')];
        $o->composers=[$p];
        $o->authors=[New Person("Larsson",'MALE','Sverige'),New Person("Erik")];
        self::pa($o->json());
        $o->store();self::pa($o->json());
        self::delete($o->music_id);
        
        $o->title="Happy Again";
        self::pa($o->json());
        $o->store(true);

        self::pa(__CLASS__." class test performed.\n---------------------------------------------------------------------------");
    }
}

//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------



?>
