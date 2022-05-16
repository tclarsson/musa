<?php

trait music_common {
    protected $db;
    protected $object_modified=true;
    protected $owner=null;

    protected function common_construct($owner=null){
        global $db;
        $this->db=$db;
        $this->object_modified=true;
        if(!empty($owner)) $this->{self::TABLE_KEY."_owner"}=$owner;
        //pa($this,true);

        //pa("common_construct:".get_called_class());
    }
    public static function get_constants(){
        $oClass = new ReflectionClass(get_called_class());
        $i=$oClass->getConstants();
        return $i;
    }
    public static function classinfo(){
        $i=self::get_constants();
        if(empty($i['CLASS_TITLE'])) $i['CLASS_TITLE']=$i['TABLE_MAIN'];
        return $i;
    }
    public function __set($name, $value) {
        throw new \Exception("Adding new properties is not allowed on " . __CLASS__);
    }
    public function json() {
        return json_encode($this,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    }
    public function _cols() {
        $r=[];
        foreach($this as $c=>$v) $r[]=$c;
        return('$cols_edit='.json_encode($r).";");
    }
    public function mod() {
        $this->object_modified=true;
    }

    // pl('ObjList');
    protected function proplist($class='all'){
        $a=[];
        foreach ($this as $p=>$v) if(is_object($v)) {
            $c=get_class($v);
            if(($c=='all')||($c==$class)) $a[]=$p;
        }
        return $a;
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
    protected static function ass($exp,$errmsg=""){
        if($exp===false) $exp="false";
        eval('$t='."$exp;");
        if(!$t) {
            $v=debug_backtrace()[0];
            print("
            <hr>
            <h2>Assert of [$exp] failed at line: $v[line] in ".basename($v['file'])."</h2>
            <pre>$errmsg</pre>
            <hr>
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
    public function _like_load() {
        if(empty($this->{self::TABLE_KEY})) $this->like();            
        if(!empty($this->{self::TABLE_KEY})) $this->load();
    }
    
    public function _params2props($i=null) {
        if(!empty($i)) {
            if(!is_array($i)) {if(is_numeric($i)) $i=[self::TABLE_KEY=>$i]; else $i=[self::TABLE_LIKE=>$i];}
            foreach ($this->p2a($this) as $p) if(isset($i[$p])) $this->{$p}=$i[$p];
        }
    }
    public static function tabkey(){
        return self::TABLE_KEY;
    }
    public function key(){
        if(empty($this->{self::TABLE_KEY})) return [];
        return [self::TABLE_KEY=>$this->{self::TABLE_KEY}];
    }
    public function like(){
        if(!empty($this->{self::TABLE_LIKE})) {
            $sql="SELECT ".self::TABLE_KEY."
            FROM ".self::TABLE_MAIN."
            WHERE ".self::TABLE_LIKE." LIKE '".$this->{self::TABLE_LIKE}."'";      
            //pa(get_called_class());
            //pa($sql);
            $r=$this->db->getColFrmQry($sql);  
            if(!empty($r[0])) {
                $this->{self::TABLE_KEY}=$r[0];
                //pa("Found id:".$r[0],true);
                //pa($this);
            }
        }
    }

    public function store($force=false){
        if(empty($this->{self::TABLE_KEY})) $this->like();
        $rec=[];
        foreach(self::TABLE_PROPS as $p) if(!empty($this->{$p})) $rec[$p]=$this->{$p};
        if(!empty($rec)) {
            if(empty($this->{self::TABLE_KEY})){
                // only set and store (new) owner id when creating new item!
                $p=self::TABLE_KEY."_owner";
                if(!empty($this->{$p})) $rec[$p]=$this->{$p};
                //pa($this,true);
                //pa($rec,true);
                try {
                    $this->db->insert(self::TABLE_MAIN,$rec);
                    $this->{self::TABLE_KEY}=$this->db->lastInsertId();
                } catch (\Throwable $th) {
                    // error in storing - e.g. missing mandatory info
                    return [];
                }
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
                //pa($r,true);
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
    public static function list_all($cond=null,$field='*',$orderby=null){
        global $db;
        if(!empty($cond)) $cond="AND $cond";
        $r=$db->getAllRecords(self::TABLE_MAIN,$field,$cond,$orderby);
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
    //-----------------------------------------------------------------
    //-----------------------------------------------------------------
    /* called from p2f - single select */
    function form($c=null){
        if(empty($c)) return '</br>function form($c=null) not supported by class: '.__CLASS__." Line:".__LINE__;
        $c['value']=$this->{self::TABLE_KEY};
        $r=$this->_form_select($c,self::list_select());
        return $r;
    }
    /* called from ObjList  - multiselect */
    static function listform($c,$lo){
        $c['value']=[];
        foreach($lo as $o) $c['value'][]=$o->{self::TABLE_KEY};
        return self::_form_mselect($c,self::list_select());
    }
    //-----------------------------------------------------------------
    /* used to generate possible select options */
    static function list_select_field(){
        return self::TABLE_KEY." as  id,".self::TABLE_LIKE." as text";
    }
    static function list_select(){
        $l=self::list_all(null,self::list_select_field(),'order by text');
        return $l;
    }

    //-----------------------------------------------------------------
    function _form_select($c,$l){
        if(!empty($l)){
            $r="<select class='form-control' name='$c[name]'>
            <option value=''>Välj ett alternativ:</option>";
            foreach($l as $v) 
                $r.="<option value='$v[id]' ".($v['id']==$c['value']?"selected":"").">$v[text]</option>";
            $r.="</select>";
        } else {
            $r="<select class='form-control' name='$c[name]' disabled>
            <option value=''>Det finns inget att välja på</option>";
            $r.="</select>";
        }
        return $r;
    }

    static function _form_mselect($c,$l){
        if(!empty($l)){
            $r=" (Välj ett eller flera alternativ)<select class='form-control' name='$c[name]' multiple>";
            
            foreach($l as $v) 
                $r.="<option value='$v[id]' ".(in_array($v['id'],$c['value'])?"selected":"").">$v[text]</option>";
            $r.="</select>";
        } else {
            $r="<select class='form-control' name='$c[name]' disabled>
            <option value=''>Det finns inget att välja på</option>";
            $r.="</select>";
        }
        return $r;
    }



    //-----------------------------------------------------------------
    //-----------------------------------------------------------------

    private static function _test_std(){
        self::pa("---------------------------------------------------------------------------\n".__CLASS__." class test started.");
        $o=New self();self::ass($o->{self::TABLE_KEY}==null);self::pa($o->json());
        $o=New self(__CLASS__.__CLASS__);self::ass($o->{self::TABLE_KEY}==null);self::pa($o->json());
        $o->store();self::ass($o->{self::TABLE_KEY}!=null);self::pa($o->json());
        $o2=New self($o->{self::TABLE_KEY});self::pa($o2->json());
        $o3=New self(__CLASS__.__CLASS__);self::ass($o3->{self::TABLE_KEY}!=null);self::pa($o3->json());
        self::delete($o->{self::TABLE_KEY});
        self::pa(__CLASS__." class test performed.\n---------------------------------------------------------------------------");
    }


}

//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
/*
Gender('M')
Gender('Man')
Gender(['gender_name'=>'Man'])
Gender(['gender_id'=>'F'])
*/
class Gender{
    use music_common;
    const CLASS_TITLE='Kön';
    const TABLE_MAIN='musaGenderTypes';
    const TABLE_KEY='gender_id';
    const TABLE_LIKE='gender_name';
    const TABLE_PROPS=['gender_name'];

    //const TABLE_MAIN='musaGenderTypes';

    public $gender_id=null;
    public $gender_name=null;
    public function __construct($i=null,$owner=null) {
        self::common_construct($owner);
        $this->_params2props($i);
        $this->_like_load();
        $this->object_modified=false;
    }

    public function like(){
        if(!empty($this->{self::TABLE_LIKE})) {
            $i=$this->{self::TABLE_LIKE};
            if(in_array(strtolower($i),['m','man','male','mr','herr','herre','pojke'])) {
                $this->{self::TABLE_KEY}='M';
            } else if(in_array(strtolower($i),['f','k','woman','female','mrs','fru','kvinna','dam','flicka'])) {
                $this->{self::TABLE_KEY}='F';
            }
        }
    }
    public function load($id=null){
        if(!empty($id)) $this->{self::TABLE_KEY}=strtoupper($id);
        if(!empty($this->{self::TABLE_KEY})){
            $i=$this->{self::TABLE_KEY};
            if(in_array(strtolower($i),['m','man','male','mr','herr','herre','pojke'])) {
                $this->{self::TABLE_KEY}='M';
                $this->{self::TABLE_LIKE}='Man';
            } else if(in_array(strtolower($i),['f','k','woman','female','mrs','fru','kvinna','dam','flicka'])) {
                $this->{self::TABLE_KEY}='F';
                $this->{self::TABLE_LIKE}='Kvinna';
            }
            $this->object_modified=false;
        }
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
    const CLASS_TITLE='Land';
    const TABLE_MAIN='musaCountries';
    const TABLE_KEY='country_id';
    const TABLE_LIKE='country_name';
    const TABLE_PROPS=['country_name'];

    public $country_id=null;
    public $country_name=null;
    public function __construct($i=null) {
        self::common_construct();
        $this->_params2props($i);
        $this->_like_load();
    }

    public static function _test(){
        self::_test_std();
    }
}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Holiday{
    use music_common;
    const CLASS_TITLE='Högtid';
    const TABLE_MAIN='musaHolidays';
    const TABLE_LIST='musaMusicHolidays';
    const TABLE_KEY='holiday_id';
    const TABLE_LIKE='holiday_name';
    const TABLE_PROPS=['holiday_name'];

    public $holiday_id=null;
    public $holiday_id_owner=null;
    public $holiday_name=null;
    
    public function __construct($i=null,$owner=null) {
        self::common_construct($owner);
        $this->_params2props($i);
        //pa($i);pa($this);
        $this->_like_load();
    }

    public static function _test(){
        self::_test_std();
    }

}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Category{
    use music_common;
    const CLASS_TITLE='Kategori';
    const TABLE_MAIN='musaCategories';
    const TABLE_LIST='musaMusicCategories';
    const TABLE_KEY='category_id';
    const TABLE_LIKE='category_name';
    const TABLE_PROPS=['category_name'];

    public $category_id=null;
    public $category_id_owner=null;
    public $category_name=null;
    
    public function __construct($i=null,$owner=null) {
        self::common_construct($owner);
        $this->_params2props($i);
        $this->_like_load();
    }

    public static function _test(){
        self::_test_std();
    }

}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Instrument{
    use music_common;
    const CLASS_TITLE='Instrument';
    const TABLE_MAIN='musaInstruments';
    const TABLE_LIST='musaMusicInstruments';
    const TABLE_KEY='instrument_id';
    const TABLE_LIKE='instrument_name';
    const TABLE_PROPS=['instrument_name'];

    public $instrument_id=null;
    public $instrument_id_owner=null;
    public $instrument_name=null;
    
    public function __construct($i=null,$owner=null) {
        //pa($i,true);
        self::common_construct($owner);
        $this->_params2props($i);
        $this->_like_load();
    }

    public static function _test(){
        self::_test_std();
    }

}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Language{
    use music_common;
    const CLASS_TITLE='Språk';
    const TABLE_MAIN='musaLanguages';
    const TABLE_LIST='musaMusicLanguages';
    const TABLE_KEY='language_id';
    const TABLE_LIKE='language_name';
    const TABLE_PROPS=['language_name'];

    public $language_id=null;
    public $language_id_owner=null;
    public $language_name=null;
    
    public function __construct($i=null,$owner=null) {
        self::common_construct($owner);
        $this->_params2props($i);
        $this->_like_load();
    }

    public static function _test(){
        self::_test_std();
    }

}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Choirvoice{
    use music_common;
    const CLASS_TITLE='Körstämmor';
    const TABLE_MAIN='musaChoirvoices';
    const TABLE_LIST='musaMusicChoirvoices';
    const TABLE_KEY='choirvoice_id';
    const TABLE_LIKE='choirvoice_name';
    const TABLE_PROPS=['choirvoice_name'];

    public $choirvoice_id=null;
    public $choirvoice_id_owner=null;
    public $choirvoice_name=null;
    
    public function __construct($i=null,$owner=null) {
        self::common_construct($owner);
        $this->_params2props($i);
        $this->_like_load();
    }

    public static function _test(){
        self::_test_std();
    }

}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Solovoice{
    use music_common;
    const CLASS_TITLE='Solostämma';
    const TABLE_MAIN='musaSolovoices';
    const TABLE_LIST='musaMusicSolovoices';
    const TABLE_KEY='solovoice_id';
    const TABLE_LIKE='solovoice_name';
    const TABLE_PROPS=['solovoice_name'];

    public $solovoice_id=null;
    public $solovoice_id_owner=null;
    public $solovoice_name=null;
    
    public function __construct($i=null,$owner=null) {
        self::common_construct($owner);
        $this->_params2props($i);
        $this->_like_load();
    }

    public static function _test(){
        self::_test_std();
    }

}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Theme{
    use music_common;
    const CLASS_TITLE='Tema';
    const TABLE_MAIN='musaThemes';
    const TABLE_LIST='musaMusicThemes';
    const TABLE_KEY='theme_id';
    const TABLE_LIKE='theme_name';
    const TABLE_PROPS=['theme_name'];


    public $theme_id=null;
    public $theme_id_owner=null;
    public $theme_name=null;
    
    public function __construct($i=null,$owner=null) {
        self::common_construct($owner);
        $this->_params2props($i);
        $this->_like_load();
    }


    public static function _test(){
        self::_test_std();
    }

}

//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Storage{
    use music_common;
    const CLASS_TITLE='Lagringsplats';
    const TABLE_MAIN='musaStorages';
    const TABLE_KEY='storage_id';
    const TABLE_LIKE='storage_name';
    const TABLE_PROPS=['storage_name'];


    public $storage_id=null;
    public $storage_id_owner=null;
    public $storage_name=null;
    
    public function __construct($i=null,$owner=null) {
        self::common_construct($owner);
        $this->_params2props($i);
        $this->_like_load();
    }



    public static function _test(){
        self::_test_std();
    }

}


//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Person {
    use music_common;
    const CLASS_TITLE='Person';
    const TABLE_MAIN='musaPersons';
    const TABLE_KEY='person_id';
    const TABLE_LIKE='family_name';
    const TABLE_PROPS=['family_name', 'first_name', 'date_born', 'date_dead','gender_name','country_name','person_id_owner'];
    const TABLE_COLS_PROPS=['gender_id', 'country_id', 'family_name', 'first_name', 'date_born', 'date_dead'];


    public $person_id = null;
    public $person_id_owner = null;
    public $family_name = null;
    public $first_name = null;
    public $date_born = null;
    public $date_dead = null;
    public $gender=null;
    public $country=null;

    public function __construct($i=null,$owner=null){
        //pa($i);
        self::common_construct();
        if(!empty($owner)) $this->person_id_owner=$owner;
        $this->_params2props($i);
        $this->gender = New Gender($this->gender);
        $this->country = New Country($this->country);
        $this->_like_load();
    }

    private static function lh($p,$c){
        if(!empty($p)) return "AND IF(ISNULL($c),1,$c LIKE '%$p%') ";      
        return "";
    }
    private function like(){
        $sql="SELECT ".self::TABLE_KEY." 
        FROM ".self::TABLE_MAIN."
        LEFT JOIN musaGenderTypes ON musaGenderTypes.gender_id=musaPersons.gender_id
        LEFT JOIN musaCountries ON musaCountries.country_id=musaPersons.country_id
        WHERE 1 ";
        foreach (['family_name', 'first_name', 'date_born', 'date_dead'] as $p) $sql.=self::lh($this->{$p},$p);
        $sql.=self::lh($this->gender->gender_name,'gender_name');
        $sql.=self::lh($this->country->country_name,'country_name');
        //pa($sql);
        $r=$this->db->getColFrmQry($sql);  
        //pa($r);
        if(!empty($r[0])) {
            $this->{self::TABLE_KEY}=$r[0];
            //pa($r[0]);
        }
    }

    public function store($force=false){
        if(empty($this->{self::TABLE_KEY})) $this->like();
        $rec=[];
        $rec+=$this->gender->store();
        $rec+=$this->country->store();
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
/*
    static function list_select(){
        global $db;
        $sql="
        SELECT person_id as  id, CONCAT_WS(',',family_name,first_name,musaCountries.country_name,CONCAT('(',date_born,'-','date_dead',')')) as text 
        FROM musa.musaPersons
        LEFT JOIN musaCountries ON musaCountries.country_id=musaPersons.country_id
        ORDER BY text
        ";
        $l=$db->getRecFrmQry($sql);
        return $l;
    }
*/

    static function list_select_field(){
        $sql=self::TABLE_KEY.
        " as  id, CONCAT_WS(',',family_name,first_name,CONCAT('(',date_born,'-','date_dead',')')) as text";
        return $sql;
    }

    public static function _test(){
        self::pa("---------------------------------------------------------------------------\n".__CLASS__." class test started.");
        $o=New self(__CLASS__.__CLASS__);self::ass($o->date_born!=1965,$o->json());
        $o->store();//self::pa($o);
        $id=$o->person_id;
        $o=New Person($id);$o->date_born=1965;$o->store(true);
        $o=New Person($id);self::ass($o->date_born==1965,$o->json());
        
        $o=New Person(["person_id"=>3,"family_name"=>"Larsson"],'F','Norge');self::ass($o->country->country_name=='Norge',$o->json());
        //$o=New Person("Erik");self::pa($o);
        $o=New Person("Larsson",'MALE','Sverige');self::ass($o->country->country_name=='Sverige',$o->json());
        //$o=New Person("Erik");

        self::delete($id);

        //$o=New self('GGGG');$o->store();self::ass($o->person_id!=null);self::pa($o->json());
        //$o=New self('Sverige');self::ass($o->person_id!=null);self::pa($o->json());
        //$o=New self('SvErige');self::ass($o->person_id!=null);self::pa($o->json());
        self::pa(__CLASS__." class test performed.\n---------------------------------------------------------------------------");
    }

}
//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Composer extends Person {
    const TABLE_LIST='musaMusicComposers';
}
//------------------------------------------------------------------------------------
class Author extends Person {
    const TABLE_LIST='musaMusicAuthors';
}
//------------------------------------------------------------------------------------
class Arranger extends Person {
    const TABLE_LIST='musaMusicArrangers';
}
//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
/*
New ObjList([New Instrument('Tuba'),New Instrument('Fiol'))
New ObjList('Instrument',['Tuba','Fiol'])
New ObjList('Arranger',['mozart','benny'])
New ObjList('Arranger',[{'family_name'=>'mozart'},{'family_name'=>'andersson','first_name'=>'benny'}])
*/
class ObjList {
    protected $db;
    protected $class;
    protected $linktable;

    function __construct($o,$i=null,$owner=null){
        global $db;
        $this->db=$db;
        if(!empty($o)){
            if(is_array($o)){
                if(is_object($o[0])) {
                    // create list of objects
                    $this->class=get_class($o[0]);
                    foreach($o as $o) $this->objects[]=$o;
                }
            } else if (is_string($o)) {
                // set object type
                $this->class=$o;
                if(!empty($i)){
                    // create list of objects with init-data
                    if(!is_array($i)) $i=[$i];
                    foreach($i as $idx=>$v) $this->objects[]=New $this->class($v,$owner);
                } else $this->objects=[];
            }   
            $this->linktable=$this->class::TABLE_LIST;
        } else {
            throw new Exception("Missing object type or list of objects", 1);
        }
    }
    function key(){
        return $this->class::tabkey();
    }
    function list(){
        return $this->objects;
    }
    function linkstore($key){
        foreach($this->objects as $o){
            $rec=$key;
            $rec+=$o->store();
            $this->db->insert($this->linktable,$rec);
        }
    }
    function linkload($key){
        $sql="SELECT ".$this->key()." FROM $this->linktable WHERE ".array_keys($key)[0]."=".$key[array_keys($key)[0]];
        //pa($sql);
        $r=$this->db->getColFrmQry($sql);
        $this->objects=[];
        if(!empty($r)) foreach($r as $id) $this->objects[]=New $this->class($id);
        return $this->objects;
    }

    function set($l){
        if(!empty($l)){
            if(!is_array($l)) $l=[$l];
            $c=get_class($l[0]);
            if($c==$this->class) $this->objects=$l;
            else throw new Exception("Object must be of class: $this->class", 1);
        }
    }

    /* return html for input form */
    function form($c){
        $lo=$this->objects;
        $r=$this->class::listform($c,$lo);
        return $r;
    }
    
}

//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------
class Music {
    use music_common;
    const CLASS_TITLE='Musik';
    const TABLE_MAIN='musaMusic';
    const TABLE_KEY='music_id';
    const TABLE_LIKE='title';
    const TABLE_PROPS=['music_id_owner', 'storage_id', 'choirvoice_id', 'title', 'subtitle', 'yearOfComp', 'movements', 'notes', 'serial_number', 'publisher', 'identifier'];

    public $music_id=null;
    public $music_id_owner=null;
    public $title=null;
    public $subtitle=null;
    public $yearOfComp=null;
    public $movements=null;
    public $copies=null;
    public $notes=null;
    public $serial_number=null;
    public $publisher=null;
    public $identifier=null;
    //relations (to single objects)
    public $storage;
    public $choirvoice;
    // lists  (of multiple objects)
    public $composers=null;
    public $arrangers=null;
    public $authors=null;
    public $categories=null;
    public $themes=null;
    public $languages=null;
    public $instruments=null;
    public $holidays=null;
    public $solovoices=null;


    
    public function __construct($i=null,$owner=null) {
        self::common_construct($owner);
        $this->_params2props($i);
        // attach objects
        //relations
        $this->storage = New Storage($i,$owner);
        $this->choirvoice = New Choirvoice($i,$owner);
        //lists
        $this->composers = New ObjList('Composer',$this->composers,$owner);
        $this->arrangers = New ObjList('Arranger',$this->arrangers,$owner);
        $this->authors = New ObjList('Author',$this->authors,$owner);
        $this->categories = New ObjList('Category',$this->categories,$owner);
        $this->themes = New ObjList('Theme',$this->themes,$owner);
        $this->languages = New ObjList('Language',$this->languages,$owner);
        $this->instruments = New ObjList('Instrument',$this->instruments,$owner);
        $this->holidays = New ObjList('Holiday',$this->holidays,$owner);
        $this->solovoices = New ObjList('Solovoice',$this->solovoices,$owner);
    
        $this->_like_load();
    }

    public function store($force=false){
        $rec=[];
        //relations
        $rec+=$this->storage->store($force);
        $rec+=$this->choirvoice->store($force);
        //$rec+=$this->solovoice_id->store($force);

        foreach (self::TABLE_PROPS as $p) if(!empty($this->{$p})) if(is_object($this->{$p})) $rec+=$this->{$p}->key(); else $rec[$p]=$this->{$p};
            
        if(!empty($rec)) {
            if(empty($this->{self::TABLE_KEY})){
                $this->db->insert(self::TABLE_MAIN,$rec);
                $this->{self::TABLE_KEY}=$this->db->lastInsertId();
            } else if($this->object_modified||$force) { 
                $this->db->update(self::TABLE_MAIN,$rec,[self::TABLE_KEY=>$this->{self::TABLE_KEY}]);
            }
            // store lists (when music_id is available)
            foreach($this->proplist('ObjList') as $p) $this->{$p}->linkstore($this->key());
            $this->object_modified=false;
            //pa($this);
        }
        return $this->key();
    }


    public function load($id=null){
        if(!empty($id)) $this->{self::TABLE_KEY}=$id;
        if(!empty($this->{self::TABLE_KEY})){
            $r=$this->db->getUnique(self::TABLE_MAIN,[self::TABLE_KEY=>$this->{self::TABLE_KEY}]);
            if(!empty($r)) foreach ($this->p2a($this) as $p) if(isset($r[$p])) $this->{$p}=$r[$p];
            
            //relations
            $this->storage->load($r['storage_id']);
            $this->choirvoice->load($r['choirvoice_id']);
            //$this->solovoice_id->load($r['solovoice_id']);

            // load lists
            foreach($this->proplist('ObjList') as $p) $this->{$p}->linkload($this->key());

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

    public static function _test(){
        self::pa("---------------------------------------------------------------------------\n".__CLASS__." class test started.");
        $o=New Music(__CLASS__.__CLASS__);
        $o->music_id_owner=2;
        $o->subtitle="TestSub";
        $o->yearOfComp=1987;
        //$o->storage=New Storage(__CLASS__);

        $o->arrangers->set([New Person("arr1"),New Person("arr2",'MALE')]);
        $o->composers->set(New Person("Person1",'kvinna','testland'));
        $o->authors->set([New Person("Larsson",'MALE','Sverige'),New Person("Erik")]);
        self::pa($o->json());
        $o->store();self::pa($o->json());
        self::delete($o->music_id);
        $o->title='Kopia1';$o->music_id=null;$o->store(true);
        $o->title='Kopia2';$o->music_id=null;$o->store(true);
        $o->title='Kopia3';$o->music_id=null;$o->store(true);

        self::pa(__CLASS__." class test performed.\n---------------------------------------------------------------------------");
    }
}

//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------



?>
