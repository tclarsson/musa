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
    protected static function ass($exp){
        if($exp===false) $exp="false";
        eval('$t='."$exp;");
        if(!$t) {
            $v=debug_backtrace()[0];
            print("<h1>Assert of [$exp] failed at line: $v[line] in ".basename($v['file'])."</h1>
            ");
        }
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
    //-----------------------------------------------------------------
    //-----------------------------------------------------------------
    //-----------------------------------------------------------------
    public function _params2props($i=null) {
        if(!empty($i)) {
            if(!is_array($i)) {if(is_numeric($i)) $i=[self::TABLE_KEY=>$i]; else $i=[self::TABLE_LIKE=>$i];}
            foreach ($this->p2a($this) as $p) if(isset($i[$p])) $this->{$p}=$i[$p];
            if(empty($this->{self::TABLE_KEY})) $this->like();            
            if(!empty($this->{self::TABLE_KEY})) $this->load();
        }
    }
    public function key(){
        return [self::TABLE_KEY=>$this->{self::TABLE_KEY}];
    }
    public function like(){
        if(!empty($this->{self::TABLE_LIKE})) {
            $sql="SELECT ".self::TABLE_KEY."
            FROM ".self::TABLE_MAIN."
            WHERE ".self::TABLE_LIKE." LIKE '".$this->{self::TABLE_LIKE}."'";      
            $r=$this->db->getColFrmQry($sql);  
            if(!empty($r[0])) $this->{self::TABLE_KEY}=$r[0];
        }
    }

    public function store($force=false){
        if(empty($this->{self::TABLE_KEY})) $this->like();
        $rec=[];
        foreach (self::TABLE_PROPS as $p) if(!empty($this->{$p})) $rec[$p]=$this->{$p};
        if(!empty($rec)) {
            if(empty($this->{self::TABLE_KEY})){
                $this->db->insert(self::TABLE_MAIN,$rec);
                $this->{self::TABLE_KEY}=$this->db->lastInsertId();
            } else if($this->object_modified||$force) { 
                $this->db->update(self::TABLE_MAIN,$rec,[self::TABLE_KEY=>$this->{self::TABLE_KEY}]);
            }
        }
        $this->object_modified=false;
        return $this->key();

    }

    public function load($id=null){
        if(!empty($id)) $this->{self::TABLE_KEY}=$id;
        if(!empty($this->{self::TABLE_KEY})){
            $r=$this->db->getUnique(self::TABLE_MAIN,[self::TABLE_KEY=>$this->{self::TABLE_KEY}]);
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
            $r=$db->delete(self::TABLE_MAIN,[self::TABLE_KEY=>$id]);
            return true;
        }
        return false;
    }    
    public static function list_all(){
        global $db;
        $r=$db->getAllRecords(self::TABLE_MAIN);
        return $r;

    }
    public static function music_list($tn,$mid){
        global $db;
        $sql="SELECT ".self::TABLE_KEY." FROM $tn WHERE music_id=$mid";
        $r=$this->db->getColFrmQry($sql);
        if(!empty($r)) {
            $l=[];
            foreach($lid as $id) $l[]=New self($id);
            return $l;
        }
        return null;
    }

    private static function _test_std(){
        self::pa("---------------------------------------------------------------------------\n".__CLASS__." class test started.");
        //self::pa(self::list_all());
        $o=New self();self::ass($o->{self::TABLE_KEY}==null);self::pa($o->json());
        $o=New self(__CLASS__.__CLASS__);self::ass($o->{self::TABLE_KEY}==null);self::pa($o->json());
        $o->store();self::ass($o->{self::TABLE_KEY}!=null);self::pa($o->json());
        $o2=New self($o->{self::TABLE_KEY});self::pa($o2->json());
        $o3=New self(__CLASS__.__CLASS__);self::ass($o3->{self::TABLE_KEY}!=null);self::pa($o3->json());
        //self::pa(self::list_all());
        self::delete($o->{self::TABLE_KEY});
        //self::pa(self::list_all());
        self::pa(__CLASS__." class test performed.\n---------------------------------------------------------------------------");
    }


}

//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Gender{
    use music_common;
    const TABLE_MAIN='musaGenderTypes';
    const TABLE_KEY='gender_id';
    const TABLE_LIKE='gender_name';
    const TABLE_PROPS=['gender_name'];

    //const TABLE_MAIN='musaGenderTypes';

    public $gender_id=null;
    public $gender_name=null;
    public function __construct($i=null) {
        self::common_construct();
        $this->load($i);
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

    public static function delete($id){
        // disable delete
        return;
    }

    public function store(){
        $this->object_modified=false;
        return [self::TABLE_KEY=>$this->{self::TABLE_KEY}];
    }

    public static function _test(){
        //self::_test_std();

        self::pa("---------------------------------------------------------------------------\n".__CLASS__." class test started.");
        $o=New self();self::pa($o->json());self::ass($o->gender_id==null);self::ass($o->gender_name==null);
        $o=New self('Man');self::pa($o->json());self::ass($o->gender_id=='M');self::ass($o->gender_name=='Man');
        $o=New self('kVinna');self::ass($o->gender_id=='F');self::ass($o->gender_name=='Kvinna');
        self::delete('F');
        $o=New self('kjsdhgfna');self::ass($o->gender_id==null);self::ass($o->gender_name==null);
        $o=New self();self::ass(empty($o->gender_id));self::ass(empty($o->gender_name));
        self::pa(__CLASS__." class test performed.\n---------------------------------------------------------------------------");
    }

    
}

//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Country{
    use music_common;
    const TABLE_MAIN='musaCountries';
    const TABLE_KEY='country_id';
    const TABLE_LIKE='country_name';
    const TABLE_PROPS=['country_name'];

    public $country_id=null;
    public $country_name=null;
    public function __construct($i=null) {
        self::common_construct();
        $this->_params2props($i);
    }

    public static function _test(){
        self::_test_std();
    }
}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Holiday{
    use music_common;
    const TABLE_MAIN='musaHolidays';
    const TABLE_KEY='holiday_id';
    const TABLE_LIKE='holiday_name';
    const TABLE_PROPS=['holiday_name'];

    public $holiday_id=null;
    public $holiday_name=null;
    
    public function __construct($i=null) {
        self::common_construct();
        $this->_params2props($i);
    }

    public static function _test(){
        self::_test_std();
    }

}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Category{
    use music_common;
    const TABLE_MAIN='musaCategories';
    const TABLE_KEY='category_id';
    const TABLE_LIKE='category_name';
    const TABLE_PROPS=['category_name'];

    public $category_id=null;
    public $category_name=null;
    
    public function __construct($i=null) {
        self::common_construct();
        $this->_params2props($i);
    }

    public static function _test(){
        self::_test_std();
    }

}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Instrument{
    use music_common;
    const TABLE_MAIN='musaInstruments';
    const TABLE_KEY='instrument_id';
    const TABLE_LIKE='instrument_name';
    const TABLE_PROPS=['instrument_name'];

    public $instrument_id=null;
    public $instrument_name=null;
    
    public function __construct($i=null) {
        self::common_construct();
        $this->_params2props($i);
    }

    public static function _test(){
        self::_test_std();
    }

}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Language{
    use music_common;
    const TABLE_MAIN='musaLanguages';
    const TABLE_KEY='language_id';
    const TABLE_LIKE='language_name';
    const TABLE_PROPS=['language_name'];

    public $language_id=null;
    public $language_name=null;
    
    public function __construct($i=null) {
        self::common_construct();
        $this->_params2props($i);
    }

    public static function _test(){
        self::_test_std();
    }

}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class ChoirVoice{
    use music_common;
    const TABLE_MAIN='musaChoirVoices';
    const TABLE_KEY='choir_voice_id';
    const TABLE_LIKE='choir_voice_name';
    const TABLE_PROPS=['choir_voice_name'];

    public $choir_voice_id=null;
    public $choir_voice_name=null;
    
    public function __construct($i=null) {
        self::common_construct();
        $this->_params2props($i);
    }

    public static function _test(){
        self::_test_std();
    }

}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class SoloVoice{
    use music_common;
    const TABLE_MAIN='musaSoloVoices';
    const TABLE_KEY='solo_voice_id';
    const TABLE_LIKE='solo_voice_name';
    const TABLE_PROPS=['solo_voice_name'];

    public $solo_voice_id=null;
    public $solo_voice_name=null;
    
    public function __construct($i=null) {
        self::common_construct();
        $this->_params2props($i);
    }

    public static function _test(){
        self::_test_std();
    }

}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Theme{
    use music_common;
    const TABLE_MAIN='musaThemes';
    const TABLE_KEY='theme_id';
    const TABLE_LIKE='theme_name';
    const TABLE_PROPS=['theme_name'];

    public $theme_id=null;
    public $theme_name=null;
    
    public function __construct($i=null) {
        self::common_construct();
        $this->_params2props($i);
    }

    public static function _test(){
        self::_test_std();
    }

}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Storage{
    use music_common;
    const TABLE_MAIN='musaStorages';
    const TABLE_KEY='storage_id';
    const TABLE_LIKE='storage_name';
    const TABLE_PROPS=['org_id','storage_name'];

    public $storage_id=null;
    public $org_id=null;
    public $storage_name=null;
    
    public function __construct($i=null) {
        self::common_construct();
        $this->_params2props($i);
    }

    public static function _test(){
        self::_test_std();
    }

}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Person {
    use music_common;
    const TABLE_MAIN='musaPersons';
    const TABLE_KEY='person_id';
    const TABLE_LIKE='family_name';
    const TABLE_PROPS=['family_name', 'first_name', 'date_born', 'date_dead'];
    const TABLE_COLS_PROPS=['gender_id', 'country_id', 'family_name', 'first_name', 'date_born', 'date_dead'];


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
        $this->_params2props($i);
    }

    private static function lh($p,$c){
        if(!empty($p)) return "AND IF(ISNULL($c),1,$c LIKE '%$p%') ";      
        return "";
    }
    private function like(){
        $sql="SELECT ".self::TABLE_KEY." 
        FROM ".self::TABLE_MAIN."
        WHERE 1 ";
        foreach (self::TABLE_PROPS as $p) $sql.=self::lh($this->{$p},$p);
        $sql.=self::lh($this->gender->gender_id,'gender_id');
        $sql.=self::lh($this->country->country_id,'country_id');
        //pa($sql);
        $r=$this->db->getColFrmQry($sql);  
        //pa($r);
        if(!empty($r[0])) $this->{self::TABLE_KEY}=$r[0];
    }

    public function store($force=false){
        if(empty($this->{self::TABLE_KEY})) $this->like();
        $rec=[];
        $rec['gender_id']=$this->gender->store();
        $rec['country_id']=$this->country->store();
        foreach (self::TABLE_PROPS as $p) if(!empty($this->{$p})) $rec[$p]=$this->{$p};
        if(!empty($rec)) {
            if(empty($this->{self::TABLE_KEY})){
                $this->db->insert(self::TABLE_MAIN,$rec);
                $this->{self::TABLE_KEY}=$this->db->lastInsertId();
            } else if($this->object_modified||$force) { 
                $this->db->update(self::TABLE_MAIN,$rec,[self::TABLE_KEY=>$this->{self::TABLE_KEY}]);
            }
        }
        $this->object_modified=false;
        return $this->key();
    }

    public function load($id=null){
        if(!empty($id)) $this->{self::TABLE_KEY}=$id;
        if(!empty($this->{self::TABLE_KEY})){
            $r=$this->db->getUnique(self::TABLE_MAIN,[self::TABLE_KEY=>$this->{self::TABLE_KEY}]);
            if(!empty($r)) foreach ($this->p2a($this) as $p) if(isset($r[$p])) $this->{$p}=$r[$p];
            if(!empty($r['gender_id'])) $this->gender->load($r['gender_id']);
            if(!empty($r['country_id'])) $this->country->load($r['country_id']);
            $this->object_modified=false;
            return true;
        }
        return false;
    }

    public static function list($lid){
        $l=[];
        foreach($lid as $id) $l[]=New self($id);
        return $l;
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
    const TABLE_KEY='music_id';
    const TABLE_LIKE='title';
    const TABLE_PROPS=['org_id', 'storage_id', 'choir_parts', 'solo_parts', 'title', 'subtitle', 'yearOfComp', 'movements', 'notes', 'serial_number', 'publisher', 'identifier'];

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
    //relations
    public $storage_id=null;
    public $choir_parts=null;
    public $solo_parts=null;
    // lists
    public $arrangers;
    public $authors;
    public $composers;
    public $categories;
    public $themes;
    public $languages;
    public $instruments;
    public $holidays;
    public $songsolos;


    
    public function __construct($i=null) {
        self::common_construct();
        $this->_params2props($i);
    }

    private function list_store($tn,$l){
        if(!empty($l)) foreach($l as $o){
            $rec=[self::TABLE_KEY=>$this->{self::TABLE_KEY}];
            $rec+=$o->store();
            $this->db->insert($tn,$rec);
        }        
    }

    public function store(){
        $rec=[];
        $rec+=$this->storage_id->store();
        $rec+=$this->choir_parts->store();
        $rec+=$this->solo_parts->store();

        foreach (self::TABLE_PROPS as $p) if(!empty($this->{$p})) $rec[$p]=$this->{$p};
        if(!empty($rec)) {
            if(empty($this->{self::TABLE_KEY})){
                $this->db->insert(self::TABLE_MAIN,$rec);
                $this->{self::TABLE_KEY}=$this->db->lastInsertId();
            } else if($this->object_modified||$force) { 
                $this->db->update(self::TABLE_MAIN,$rec,[self::TABLE_KEY=>$this->{self::TABLE_KEY}]);
            }
            $this->object_modified=false;
        }
        // store lists
        $this->list_store('musaMusicComposers',$this->composers);
        $this->list_store('musaMusicArrangers',$this->arrangers);
        $this->list_store('musaMusicAuthors',$this->authors);
        $this->list_store('musaMusicCategories',$this->categories);
        $this->list_store('musaMusicThemes',$this->themes);
        $this->list_store('musaMusicLanguages',$this->languages);
        $this->list_store('musaMusicInstruments',$this->instruments);
        $this->list_store('musaMusicHolidays',$this->holidays);
        $this->list_store('musaMusicSongsolos',$this->songsolos);
    
        return $this->key();

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

    private function list_load($tn,$on,$ln){
        $key={$on}::keyname();
        $sql="SELECT $key FROM $tn WHERE ".self::TABLE_KEY."=".$this->{self::TABLE_KEY};
        $r=$this->db->getColFrmQry($sql);
        if(!empty($r)) {

        if(!empty($l)) foreach($l as $o){
            $rec=[self::TABLE_KEY=>$this->{self::TABLE_KEY}];
            $rec+=$o->store();
            $this->db->insert($tn,$rec);
        }        
    }


    public function load($id=null){
        if(!empty($id)) $this->{self::TABLE_KEY}=$id;
        if(!empty($this->{self::TABLE_KEY})){
            $r=$this->db->getUnique(self::TABLE_MAIN,[self::TABLE_KEY=>$this->{self::TABLE_KEY}]);
            if(!empty($r)) foreach ($this->p2a($this) as $p) if(isset($r[$p])) $this->{$p}=$r[$p];
            // load links
            $this->storage_id->load($r['storage_id']);
            $this->choir_parts->load($r['choir_parts']);
            $this->solo_parts->load($r['solo_parts']);
            //if(!empty($r['country_id'])) $this->country->load($r['country_id']);

            // load lists
            $this->composers=Person::music_list('musaMusicComposers',$this->{self::TABLE_KEY});
            $this->arrangers=Person::music_list('musaMusicArrangers',$this->{self::TABLE_KEY});
            $this->authors=Person::music_list('musaMusicAuthors',$this->{self::TABLE_KEY});
            $this->categories=Categories::music_list('musaMusicCategories',$this->{self::TABLE_KEY});
            $this->themes=Themes::music_list('musaMusicThemes',$this->{self::TABLE_KEY});
            $this->languages=Languages::music_list('musaMusicLanguages',$this->{self::TABLE_KEY});
            $this->instruments=Instruments::music_list('musaMusicInstruments',$this->{self::TABLE_KEY});
            $this->holidays=Holidays::music_list('musaMusicHolidays',$this->{self::TABLE_KEY});
            $this->songsolos=Songsolos::music_list('musaMusicSongsolos',$this->{self::TABLE_KEY});

            $this->object_modified=false;
            return true;
        }
        return false;
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

    public static function _test(){
        self::pa("---------------------------------------------------------------------------\n".__CLASS__." class test started.");
        $o=New Music;
        $o->org_id=2;
        $o->title="TestSong";
        $o->subtitle="TestSub";
        $o->yearOfComp=1987;
        $o->movements=null;
        $o->notes=null;
        $o->serial_number=null;
        $o->publisher=null;
        $o->identifier=null;
        $o->storage_id=null;
        $o->choir_parts=null;
        $o->solo_parts=null;

        $o->arrangers=[New Person("arr1"),New Person("arr2",'MALE')];
        $p=New Person("Person1",'kvinna','testland');
        $o->composers=[$p];
        $o->authors=[New Person("Larsson",'MALE','Sverige'),New Person("Erik"),$p];
        $o->categories;
   
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
