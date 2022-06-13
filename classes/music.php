<?php

trait music_common {
    protected $db;
    protected $object_modified=true;
    protected $user_org_id=null;
    //protected $owner=null;

    protected static function pa($a,$callstack=false){
        print('<pre>');
        print_r($a);
        if($callstack) foreach (debug_backtrace() as $v) {
            print("Line $v[line] in ".basename($v['file'])." calls $v[function]\n");
        }
        print('</pre>');
    }

    protected static function _debug($a,$callstack=false){
        return;
        self::pa($a,$callstack);
    }

    protected function common_construct(){
        global $db;
        global $user;
        self::_debug("common_construct Class:".get_called_class()." CLASS:".__CLASS__);
        $this->db=$db;
        $this->object_modified=true;
        $this->user_org_id=$user->current_org_id();
        $this->set_owner();
    }
    public function set_owner(){
        if($this->{self::TABLE_KEY."_owner"}!=$this->user_org_id) $this->object_modified=true;
        $this->{self::TABLE_KEY."_owner"}=$this->user_org_id;
    }
    public static function get_constants(){
        $oClass = new ReflectionClass(get_called_class());
        $i=$oClass->getConstants();
        return $i;
    }
    public function edit_allowed(){
        if(empty($this->{self::TABLE_KEY."_owner"})) return true;
        return $this->{self::TABLE_KEY."_owner"}==$this->user_org_id;
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
    public static function props_pub() {
        $reflect = new ReflectionClass(get_called_class());
        $props   = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
        $r=array_column($props,'name');
        return $r;
    }
    public static function cols_edit() {
        // remove key/hidden fields
        $c=array_diff(self::props_pub(),[self::TABLE_KEY,self::TABLE_KEY."_owner"]);
        return $c;
    }
    public function _cols() {
        $r=[];
        foreach($this as $c=>$v) $r[]=$c;
        return('$cols_edit='.json_encode($r).";");
    }
    public function mod() {
        $this->object_modified=true;
    }
    public function is_mod() {
        return $this->object_modified;
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
        if($dump) self::_debug('$props='.json_encode(($a)).';');
        return $a;
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
    public function set($i) {
        self::_debug("set Class:".get_called_class()." CLASS:".__CLASS__);
        if(!empty($i)) {
            self::_debug($i,true);
            if(!is_array($i)) {
                if(is_numeric($i)) $i=[self::TABLE_KEY=>($i+0)]; else $i=[self::TABLE_LIKE=>$i];
            }
            // load if own key available
            if(!empty($i[self::TABLE_KEY])) {
                $this->object_modified=$this->load($i[self::TABLE_KEY]);
                self::_debug("set unset: [".self::TABLE_KEY."]=".$i[self::TABLE_KEY]);
                unset($i[self::TABLE_KEY]);
            }
            //self::_debug($i,true);
            //self::_debug(self::props_pub());
            // check all properties
            foreach (self::props_pub() as $p) {
                if(!empty($i[$p])) {
                    if(is_object($this->{$p})){
                        // send the class specific params, let object decide what to set
                        self::_debug("Setting object $p\n",true);
                        $this->{$p}->set($i[$p]);
                    } else {
                        // is current user allowed to change property?
                        if($this->edit_allowed()) {
                            // make numeric
                            if(is_numeric($i[$p])) $v=$i[$p]+0; else $v=$i[$p];
                            // update property
                            if($this->{$p}!=$v) $this->object_modified=true;
                            $this->{$p}=$v;
                            self::_debug("$p=$v (Mod:$this->object_modified)");
                        }
                    }
                }
            }
            // check for existing likes
            if(empty($this->{self::TABLE_KEY})) {
                $this->like();            
                if(!empty($this->{self::TABLE_KEY})) $this->load();
            }
        }
        //self::_debug("set END: ".get_called_class());
        self::_debug($this->json(),true);
        self::_debug("Mod: $this->object_modified\n");
    }
    public static function tabkey(){
        return self::TABLE_KEY;
    }
    public function key(){
        if(empty($this->{self::TABLE_KEY})) return [];
        return [self::TABLE_KEY=>$this->{self::TABLE_KEY}];
    }
    
    // like: check if same data already exist by owner
    public function like(){
        if(!empty($this->{self::TABLE_LIKE})) {
            $sql="SELECT ".self::TABLE_KEY."
            FROM ".self::TABLE_MAIN."
            WHERE ".self::TABLE_LIKE." LIKE '".$this->{self::TABLE_LIKE}."'
            AND ".self::TABLE_KEY."_owner=$this->user_org_id";
            $r=$this->db->getColFrmQry($sql);  
            if(!empty($r[0])) {
                $this->{self::TABLE_KEY}=$r[0];
            }
        }
    }

    public function store($force=false){
        if(empty($this->{self::TABLE_KEY})) $this->like();
        $rec=[];
        // store all object properties
        foreach (self::props_pub() as $p) if(is_object($this->{$p})) {
            $rec+=$this->{$p}->store();
        }
        self::_debug("store Class: ".get_called_class()."  CLASS:".__CLASS__."---------------------------------------------------------------");
        self::_debug("Modified: $this->object_modified");
        // store all local properties
        foreach (self::TABLE_PROPS as $p) if(!empty($this->{$p})) $rec[$p]=$this->{$p};
        if(!empty($rec)) {
            self::_debug($rec);
            self::_debug($this->json());
            if(empty($this->{self::TABLE_KEY})){
                // only set and store (new) owner id when creating new item!
                $p=self::TABLE_KEY."_owner";
                if(!empty($this->{$p})) $rec[$p]=$this->{$p};
                try {
                    $this->db->insert(self::TABLE_MAIN,$rec);
                    $this->{self::TABLE_KEY}=$this->db->lastInsertId();
                    self::_debug("store/insert Class: ".get_called_class()."  CLASS:".__CLASS__);
                    self::_debug($this->key());
                } catch (\Throwable $th) {
                    // error in storing - e.g. missing mandatory info
                    return [];
                }
            } else if($this->object_modified||$force) { 
                $this->db->update(self::TABLE_MAIN,$rec,[self::TABLE_KEY=>$this->{self::TABLE_KEY}]);
                self::_debug("store/update Class: ".get_called_class()."  CLASS:".__CLASS__);
            }
        }
        $this->object_modified=false;
        self::_debug("store/end Class: ".get_called_class()."  CLASS:".__CLASS__."---------------------------------------------------------------");
        return $this->key();
    }

    public function load($id=null){
        $object_is_new=false;
        self::_debug("load Class: ".get_called_class()."  CLASS:".__CLASS__);
        if(!empty($id)) {
            $object_is_new=($this->{self::TABLE_KEY}!=$id);
            $this->{self::TABLE_KEY}=$id;
        }
        if(!empty($this->{self::TABLE_KEY})){
            $r=$this->db->getUnique(self::TABLE_MAIN,[self::TABLE_KEY=>$this->{self::TABLE_KEY}]);
            if(!empty($r)) {
                unset($r[self::TABLE_KEY]);
                $this->set($r);
            }
            // load lists pointing to object
            foreach($this->proplist('ObjList') as $p) $this->{$p}->linkload($this->key());

            $this->object_modified=false;
            self::_debug($this->json(),true);
            return $object_is_new;
        }
        return $object_is_new;
    }

    public static function delete($id){
        global $db;
        if(!empty($id)) {
            $r=$db->delete(self::TABLE_MAIN,[self::TABLE_KEY=>$id]);
            return true;
        }
        return false;
    }    
    public static function delete_unconstrained($org_id){
        global $db;
        $ci=self::get_constants();
        $k=$ci['TABLE_KEY'];
        //self::_debug(self::TABLE_MAIN);
        $sql="DELETE m
        FROM $ci[TABLE_MAIN] m\n";
        if(!empty($ci['TABLE_LIST'])) $sql.="LEFT JOIN $ci[TABLE_LIST] l ON l.$k=m.$k\n";
        $sql.="WHERE m.{$k}_owner=$org_id\n";
        if(!empty($ci['TABLE_LIST'])) $sql.="AND l.$k IS NULL\n";
        $r=$db->deleteFrmQry($sql);
        return $r;
    }    
    public static function list_all($cond=null,$field='*',$orderby=null){
        global $db;
        if(!empty($cond)) $cond="AND $cond";
        $r=$db->getAllRecords(self::TABLE_MAIN,$field,$cond,$orderby);
        return $r;

    }
/*
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
*/    
    //-----------------------------------------------------------------
    //-----------------------------------------------------------------
    /* called from p2f - single select */
    function form($c=null){
        if(empty($c)) return '</br>function form($c=null) not supported by class: '.__CLASS__." Line:".__LINE__;
        $c['name']=self::TABLE_KEY;
        $c['value']=$this->{self::TABLE_KEY};
        $r=self::_form_select($c,self::list_select());
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
        /*
        global $db;
        if(!empty($cond)) $cond="AND $cond";
        $r=$db->getAllRecords(self::TABLE_MAIN,$field,$cond,$orderby);
        return $r;
*/
        return $l;
    }

    //-----------------------------------------------------------------
    //-----------------------------------------------------------------
    private static function _form_select($c,$l){
        if(!empty($l)){
            $r="<select class='form-control' name='$c[name]'>
            <option value=''>Välj ett alternativ:</option>";
            foreach($l as $v) if($v['id']==$c['value']) {
                $r.="<option value='$v[id]' selected>$v[text]</option>";    
            }
            foreach($l as $v) if($v['id']!=$c['value']) {
                $r.="<option value='$v[id]'>$v[text]</option>";    
            }
            $r.="</select>";
        } else {
            $r="<select class='form-control' name='$c[name]' disabled>
            <option value=''>Det finns inget att välja på</option>";
            $r.="</select>";
        }
        return $r;
    }

    private static function _form_mselect($c,$l){
        if(!empty($l)){
            $r=" (Välj ett eller flera alternativ)<select class='form-control' name='$c[name][]' multiple>";
            foreach($l as $v) if(in_array($v['id'],$c['value'])) {
                $r.="<option value='$v[id]' selected>$v[text]</option>";    
            }
            foreach($l as $v) if(!in_array($v['id'],$c['value'])) {
                $r.="<option value='$v[id]'>$v[text]</option>";    
            }
            $r.="</select>";
        } else {
            $r="<select class='form-control' name='$c[name][]' disabled>
            <option value=''>Det finns inget att välja på</option>";
            $r.="</select>";
        }
        return $r;
    }


    //-----------------------------------------------------------------
    static function _sql_list_join($t){
        $sql="";
        $cn=get_called_class();
        //self::_debug($cn);return "";
        if(!empty($cn::TABLE_LIST)){
            $mt=$cn::TABLE_MAIN;
            $lt=$cn::TABLE_LIST;
            $k=$cn::TABLE_KEY;
            $l=$cn::TABLE_LIKE;
            switch($t){
                case 'v':
                $sql="g_$l";                
                break;
                case 'g':
                $sql=",GROUP_CONCAT(DISTINCT $mt.$l SEPARATOR ', ') as g_$l\n";                
                break;
                case 'j': 
                $sql="LEFT JOIN $lt ON $lt.music_id=musaMusic.music_id
                LEFT JOIN $mt ON $mt.$k=$lt.$k
                ";
                break;
            }
        }
        return $sql;
    }

    //-----------------------------------------------------------------
    /* set object based on form 
    $_REQUEST['theme_id']=45;
    $_REQUEST['theme_id']=[45,56];
    
    */
    function parse_form(){
        $this->__construct($_REQUEST);
    }

    //-----------------------------------------------------------------



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
    const TABLE_LIST='musaPersons';
    const TABLE_KEY='gender_id';
    const TABLE_LIKE='gender_name';
    const TABLE_PROPS=['gender_name'];

    //const TABLE_MAIN='musaGenderTypes';

    public $gender_id=null;
    public $gender_id_owner=null;
    public $gender_name=null;
    
    public function __construct($i=null) {
        self::common_construct();
        $this->set($i);
        $this->object_modified=false;
    }

    public function edit_allowed(){
        return false;
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
    const TABLE_LIST='musaPersons';
    const TABLE_KEY='country_id';
    const TABLE_LIKE='country_name';
    const TABLE_PROPS=['country_name'];

    public $country_id=null;
    public $country_id_owner=null;
    public $country_name=null;
    public function __construct($i=null) {
        self::common_construct();
        $this->set($i);
    }

    public function edit_allowed(){
        return false;
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
    
    public function __construct($i=null) {
        self::common_construct();
        $this->set($i);
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
    
    public function __construct($i=null) {
        self::common_construct();
        $this->set($i);
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
    
    public function __construct($i=null) {
        self::common_construct();
        $this->set($i);
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
    
    public function __construct($i=null) {
        self::common_construct();
        $this->set($i);
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
    
    public function __construct($i=null) {
        self::common_construct();
        $this->set($i);
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
    
    public function __construct($i=null) {
        self::common_construct();
        $this->set($i);
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
    const TABLE_LIST='musaMusic';
    const TABLE_KEY='storage_id';
    const TABLE_LIKE='storage_name';
    const TABLE_PROPS=['storage_name'];


    public $storage_id=null;
    public $storage_id_owner=null;
    public $storage_name=null;
    
    public function __construct($i=null) {
        self::common_construct();
        $this->set($i);
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
    const TABLE_LIST='musaMusic';
    const TABLE_KEY='choirvoice_id';
    const TABLE_LIKE='choirvoice_name';
    const TABLE_PROPS=['choirvoice_name'];

    public $choirvoice_id=null;
    public $choirvoice_id_owner=null;
    public $choirvoice_name=null;
    
    public function __construct($i=null) {
        self::common_construct();
        $this->set($i);
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
    const TABLE_PROPS=['family_name', 'first_name', 'date_born', 'date_dead','gender_id','country_id'];
    const TABLE_COLS_PROPS=['gender_id', 'country_id', 'family_name', 'first_name', 'date_born', 'date_dead'];


    public $person_id = null;
    public $person_id_owner = null;
    public $family_name = null;
    public $first_name = null;
    public $date_born = null;
    public $date_dead = null;
    public $gender=null;
    public $country=null;

    public function __construct($i=null){
        self::common_construct();
        $this->gender = New Gender();
        $this->country = New Country();
        $this->set($i);
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
        WHERE 1 
        AND ".self::TABLE_KEY."_owner=$this->user_org_id
        ";
        foreach (['family_name', 'first_name', 'date_born', 'date_dead'] as $p) $sql.=self::lh($this->{$p},$p);
        $sql.=self::lh($this->gender->gender_name,'gender_name');
        $sql.=self::lh($this->country->country_name,'country_name');
        self::_debug($sql);
        $r=$this->db->getColFrmQry($sql);  
        //self::_debug($r);
        if(!empty($r[0])) {
            $this->{self::TABLE_KEY}=$r[0];
            //self::_debug($r[0]);
        }
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
        " as  id, CONCAT_WS(',',family_name,first_name,CONCAT('(',date_born,'-',date_dead,')')) as text";
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
New ObjList([New Instrument('Tuba')])
New ObjList([New Instrument('Tuba'),New Instrument('Fiol')])
New ObjList('Instrument')
*/
class ObjList {
    protected $db;
    protected $class;
    protected $linktable;

    function __construct($o){
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
                $this->objects=[];
            } else throw new Exception("Missing object type or list of objects", 1);
            $this->linktable=$this->class::TABLE_LIST;
        } else {
            throw new Exception("Missing object type or list of objects", 1);
        }
    }
    /*
    ->set('Tuba');
    ->set(['Tuba','Fiol']);
    ->set([{"family_name":"Ulveaus","first_name":"Bj\u00f6rn"},{"family_name":"Rice","first_name":"Tim"}])
    */
    function set($i){
        $this->objects=[];
        if(!empty($i)){
            // create list of objects with init-data
            if(!is_array($i)) $i=[$i];
            foreach($i as $idx=>$v) $this->objects[]=New $this->class($v);
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
        //self::_debug($sql);
        $r=$this->db->getColFrmQry($sql);
        $this->objects=[];
        if(!empty($r)) foreach($r as $id) $this->objects[]=New $this->class($id);
        return $this->objects;
    }

    /*
    function _set($l){
        if(!empty($l)){
            if(!is_array($l)) $l=[$l];
            $c=get_class($l[0]);
            if($c==$this->class) $this->objects=$l;
            else throw new Exception("Object must be of class: $this->class", 1);
        }
    }
    */

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
    const TABLE_OBJLISTS=[
    'composers' => 'Composer',
    'arrangers' => 'Arranger',
    'authors' => 'Author',
    'categories' => 'Category',
    'themes' => 'Theme',
    'languages' => 'Language',
    'instruments' => 'Instrument',
    'holidays' => 'Holiday',
    'solovoices' => 'Solovoice',
    ];

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
    public $storage=null;
    public $choirvoice=null;
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


    
    public function __construct($i=null) {
        self::common_construct();
        //relations
        $this->storage = New Storage();
        $this->choirvoice = New Choirvoice();
        // attach objects
        //lists
        foreach(self::TABLE_OBJLISTS as $p=>$on) $this->{$p} = New ObjList($on);
        // set & load
        $this->set($i);
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
            //self::_debug($this);
        }
        return $this->key();
    }

    public static function delete($id){
        global $db;
        if(!empty($id)) {
            $sql="DELETE FROM musaMusic
            WHERE music_id=$id
            ";
            $r=$db->deleteFrmQry($sql);
            self::_debug($r);
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
    public static function _test_new(){
        self::pa("---------------------------------------------------------------------------\n".__CLASS__." class test started.");
        $i=json_decode('
        {"title":"Anthem","subtitle":"","movements":"","copies":"42","notes":"Musikal","choirvoice":"SSAATBB","composers":[{"family_name":"Andersson","first_name":"Benny"}],"arrangers":[{"family_name":"Arnberg","first_name":"G\u00f6ran"}],"authors":[{"family_name":"Ulveaus","first_name":"Bj\u00f6rn"},{"family_name":"Rice","first_name":"Tim"}],"solovoices":["R\u00f6st"],"instruments":["Orgel"],"languages":["Engelska"],"themes":["K\u00e4rlek"],"holidays":"","categories":""}
        ',true);
        $o=New Music($i);
        self::pa($o->json());
        $o->store();self::pa($o->json());
        self::delete($o->music_id);
        self::pa(__CLASS__." class test performed.\n---------------------------------------------------------------------------");
    }

}

//------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------



?>
