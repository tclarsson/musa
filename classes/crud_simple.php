<?php 

trait crud_common {
    function render() {
        print($this->html());
    }
    function json() {
        return json_encode($this,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    }
    function html(){
        if(!empty($this->html)) return $this->html;
        return "<h4>".__CLASS__.":Not implemented.</h4>";
    }

}

class Button {
    use crud_common;
    function __construct($i){
        $this->format='default';
        $this->format=$i;
        $linkopt="";
        if(isset($_REQUEST['list'])) $linkopt.="&list=$_REQUEST[list]";
        if(isset($_REQUEST['search'])) $linkopt.="&search=$_REQUEST[search]";
        if(isset($_REQUEST['order'])) $linkopt.="&order=$_REQUEST[order]";
        if(isset($_REQUEST['sort'])) $linkopt.="&sort=$_REQUEST[sort]";

        switch($this->format){
            case 'export_excel':
                $this->html="<a href='?export$linkopt' class='btn btn-primary float-right ml-2' data-toggle='tooltip'".confOp('excel')."</a>";
                break;
            case 'export_email':
                $this->html="<a href='?exportemail$linkopt' class='btn btn-primary float-right ml-2' data-toggle='tooltip'".confOp('mail')."</a>";
                break;
            case 'info':
                $this->label="<i class='fa fa-plus'></i> <i class='fa fa-user'></i>";
                $this->html="<button type='button' data-target='#pageHelp' title='Information och Hjälp' class='btn btn-info float-right ml-2' data-toggle='modal'><i class='fa fa-info-circle'></i></button>";
                break;
            case 'export_excel':
                $this->html="<a href='?create$linkopt' class='btn btn-success float-right ml-2' title='Skapa ny post' data-toggle='tooltip'><i class='fa fa-plus'></i> <i class='fa fa-user'></i></a>";
                break;
            case 'create':
                //$this->label="<i class='fa fa-plus'></i> Skapa ny";
                $this->html="<a href='?create$linkopt' class='btn btn-success float-right ml-2' title='Skapa ny post' data-toggle='tooltip'><i class='fa fa-plus'></i> Skapa ny</a>";
                break;
            default:
                throw new Exception(__CLASS__.":ERROR. Format $this->format is not supported.", 1);
                break;
        }
    }
    function html(){
        return $this->html;
    }
}
class Card {
    use crud_common;

    function __construct($title){
        $this->title=$title;
    }

    function html(){
        $r="<div class='card bg-light'>
            <div class='card-header'>
            <div class='row card-header-title'> 
                <div class='col'>{$this->title}</div>";
        if(isset($this->helpmodal)) {
            if(empty($this->helpmodal->header)) 
                if(!empty($this->title)) $this->helpmodal->header=$this->title;
            $r.="<div class='col-auto'>
            <button type='button' data-target='#{$this->helpmodal->id}' 
            title='Information och Hjälp' class='btn btn-info float-right ml-2' data-toggle='modal'><i class='fa fa-info-circle'></i></button>
            </div>
            {$this->helpmodal->html()}
            ";
        }
        $r.="</div></div>";
        if(!empty($this->body)) $r.="<div class='card-body'>$this->body</div>";
        if(!empty($this->footer)) $r.="<div class='card-footer'>$this->footer</div>";
        $r.="</div></br>";
        return $r;
    }
}

class Modal {
    use crud_common;
    public $header=null;
    public $body=null;
    public $footer=null;

    public $header_class='bg-info';


    function __construct($id,$type=null){
        $this->id=$id;
        switch($type) {
            case 'confirmdelete':
                $this->footer="
                <form action='' method='post' id='form_$this->id' >
                <button type='button' class='btn btn-danger' data-dismiss='modal' onclick='submit_$this->id()'><i class='fa fa-trash'></i> Radera</a>
                <input type='hidden' name='".__CLASS__."_type' value='$type'>
                <input type='hidden' name='".__CLASS__."_id' value='$this->id'>
                <input type='hidden' name='".__CLASS__."_par' value='".'${i}'."'>
                ".'${form}'."
                </form>
                <button type='button' class='btn btn-secondary' data-dismiss='modal'><i class='fa fa-undo'></i> Avbryt</button>
                ";
        
            case 'info':
            default:
                $this->footer="<button type='button' class='btn btn-secondary' data-dismiss='modal'>Stäng</button>";
                break;
        }
    }

    function html(){
        $r="
        <div class='modal fade' id='$this->id' tabindex='-1' role='dialog'>
            <div class='modal-dialog' role='document'>
                <div class='modal-content'>
                <div class='modal-header modal-header-title text-light $this->header_class'>$this->header
                    <button type='button' class='close text-light' data-dismiss='modal'>&times;</button>
                </div>
                <div class='modal-body'>$this->body</div>
                <div class='modal-footer'>$this->footer</div>
                </div>
            </div>
        </div>
        ";
        return $r;
    }

   
    
    function dynamic($table=null){
        $v="";if(!empty($table)) $v="
        var table = ".json_encode($table, JSON_HEX_TAG)."
        o=table[i];
        console.log(o);
        ";
        $r="<div id='modal_$this->id'></div>
        <script>
        function modal_$this->id(i){
            console.log(i);
            $v
            var m=`".$this->html()."`;
            $('#modal_$this->id').html(m);
        }
        function submit_$this->id() {
            console.log('submitting..');
            document.getElementById('form_$this->id').submit();
        } 
        </script>
        ";
        return $r;
    }
    function trigger($i=0){
        $r="data-target='#$this->id' data-toggle='modal' onClick='modal_$this->id($i)'";
        return $r;
    }
    
    function confirmdelete($uri,$table=null){
        $this->footer="
        <form action='$uri' method='post' id='form_$this->id' >
        <button type='button' class='btn btn-danger' data-dismiss='modal' onclick='submit_$this->id()'><i class='fa fa-trash'></i> Radera</a>
        </form>
        <button type='button' class='btn btn-secondary' data-dismiss='modal'><i class='fa fa-undo'></i> Avbryt</button>
        ";
        return $this->dynamic($table);
    }

    function confirm_table_delete($uri,$table,$propname){
        $this->header='Radera ${o.'.$propname.'} ?';
        $this->body='<h4>Är du säker på att du vill radera:</h4>
        <h2>${o.'.$propname.'} ?</h2>';
        $this->footer="
        <form action='$uri' method='post' id='form_$this->id' >
        <input type='hidden' name='".__CLASS__."_id' value='$this->id'>
        <input type='hidden' name='".__CLASS__."_par' value='".'${i}'."'>
        <button type='button' class='btn btn-danger' data-dismiss='modal' onclick='submit_$this->id()'><i class='fa fa-trash'></i> Radera</a>
        </form>
        <button type='button' class='btn btn-secondary' data-dismiss='modal'><i class='fa fa-undo'></i> Avbryt</button>
        ";
        return $this->dynamic($table);
    }

    function confirm_table_import($uri,$table,$propname){
        $this->header='Importera ${o.'.$propname.'} ?';
        $this->body='<h4>Är du säker på att du vill importera:</h4>
        <h2>${o.'.$propname.'} ?</h2>';
        $this->footer="
        <form action='$uri' method='post' id='form_$this->id' >
        <input type='hidden' name='".__CLASS__."_id' value='$this->id'>
        <input type='hidden' name='".__CLASS__."_par' value='".'${i}'."'>
        <button type='button' class='btn btn-success' data-dismiss='modal' onclick='submit_$this->id()'><i class='fa fa-copy'></i> Importera</a>
        </form>
        <button type='button' class='btn btn-secondary' data-dismiss='modal'><i class='fa fa-undo'></i> Avbryt</button>
        ";
        return $this->dynamic($table);
    }



    
}

class Columns {
    const COLUMNS_FILENAME='columns.json';

    static function sqltype2code($sqltype){
        $t=explode("(",$sqltype);
        switch($t[0]){
            case 'float':
            case 'int':
            case 'year':
                return 'n';break;
            case 'text':
                return 't';break;
            case 'varchar':
            default:
                return 's';break;
        }
    }
    
    static function cols($lc,$noprimary=false){
        global $db;
        // get global columns info
        $columns=json_decode(file_get_contents(self::COLUMNS_FILENAME,true),true);
        $update=false;
        $cols=[];
        //pa($lc,true);
        foreach($lc as $i) {
            $col=[];
            $c=$db->getColInfo($i);
            if(!empty($c)) {
                if($noprimary) if($c['Key']=='PRI') continue;
                $col=array_merge($col,$c);
                $col['type']=self::sqltype2code($c['Type']);
            }
            if(!empty($columns[$i])) $col=array_merge($col,$columns[$i]);
            else $update=true;
            if(empty($col['header'])) $col['header']=$i;
            if(empty($col['type'])) $col['type']='s';
            if(!isset($col['errmsg'])) $col['errmsg']='';
            $col['name']=$i;
            if(!$update) if($columns[$i]!=$col) $update=true;
            $columns[$i]=$col;
            if(!isset($col['req'])) $col['req']='';
            $cols[$i]=$col;
        }
        if($update){
            // update file
            //$fn=realpath($columns_filename);
            //var_dump($columns_filename);var_dump($fn);exit();
            file_put_contents("./include/".self::COLUMNS_FILENAME,json_encode($columns,JSON_PRETTY_PRINT));
        }

        return $cols;
    }
    static function edit($lc){
        return self::cols($lc,true);
    }
}









class Crud {
    use crud_common;

    public $sql_select="*";

    function __construct($title,$classname){
        global $db;
        global $user;
        $this->db=$db;
        $this->page_title=$title;
        $this->org_id=$user->current_org_id();
        $this->classname=$classname;
        $this->_init_from_class();
    }

    function sql_prefix(){
        return "
        ,IF({$this->table_name}.{$this->table_key}_owner=$this->org_id,1,0) as OWNER
        ,IF({$this->table_name}.{$this->table_key}_owner=$this->org_id,NULL,org_name) as EXTERNAL
        ,{$this->table_name}.{$this->table_key}
        ";
    }
    function _init_from_class(){
        if(empty($this->classname)) return;
        $classname=$this->classname;
        $ci=$classname::classinfo();
        //$this->table_list=str_replace("musa","musaMusic",$ci['TABLE_MAIN']);
        $this->table_name=$ci['TABLE_MAIN'];
        $this->table_key=$ci['TABLE_KEY'];
        $this->table_props=$ci['TABLE_PROPS'];
        $this->cols_edit=$this->table_props;

        switch($classname){
            case 'Person':
                //$this->cols_edit=[];
                break;
        }
    }

    function _table_init(){
        $this->table->sql_body=$this->sql_prefix()."
        FROM {$this->table_name} 
        LEFT JOIN musaOrgs ON musaOrgs.org_id={$this->table_name}.{$this->table_key}_owner
        ";
        $this->table->sql_group="";
        $this->table->cols_visible=array_merge($this->table_props,[]);
        $this->table->cols_searchable=$this->table->cols_visible;
        $this->table->order = $this->table_props[0];
        //only own
        $this->table->own="\nAND $this->table_name.$this->table_key"."_owner=$this->org_id";
        //external = no limits
        if(!empty($_GET['ext'])) $this->table->own="";
        // ------------------------------------------------------
        $this->table->feature['create']=['button'=>New Button('create')];

        // ------------------------------------------------------
        if(empty($this->classname)) return;
        $classname=$this->classname;
        $ci=$classname::classinfo();
        switch($classname){
            case 'Person':
                $this->table->cols_visible=array_diff(array_merge(Person::cols_edit(),[Gender::TABLE_LIKE,Country::TABLE_LIKE]),['country','gender']);
                $this->table->sql_body=$this->sql_prefix()."
                FROM {$this->table_name} 
                LEFT JOIN musaOrgs ON musaOrgs.org_id={$this->table_name}.{$this->table_key}_owner
                LEFT JOIN musaGenderTypes ON musaGenderTypes.gender_id=musaPersons.gender_id
                LEFT JOIN musaCountries ON musaCountries.country_id=musaPersons.country_id
                ";
                break;
        }
        $this->table->cols_searchable=$this->table->cols_visible;
 
    }


    function list(){
        $this->table=New Table();
        $this->table->page_uri=$this->page_uri;
        $this->_table_init();
        $this->table->init();
        // do query with pagination limits for display
        $this->table->table();

        $r="";
        
        $deletemod=New Modal("confirmdelete".__LINE__);
        $r.=$deletemod->confirm_table_delete('?'.$this->page_uri.'&delete&id=${o.'.$this->table_key.'}',$this->table->table,$this->table_props[0]);
        $importmod=New Modal("confirmimport".__LINE__);
        $r.=$importmod->confirm_table_import('?'.$this->page_uri.'&import&id=${o.'.$this->table_key.'}',$this->table->table,$this->table_props[0]);

        $r.=$this->table->header_table($this->page_title);
        if($this->table->table){
            if(count($this->table->cols_visible)>=6) $this->nocontainer=true;
            $r.="<table class='table table-striped table-sm table-bordered border'><thead class='thead-dark'><tr>";
            foreach ($this->table->cols_visible as $col) $r.=$this->table->header_col($col);
            $r.="<th style='width:10em;'>Action</th></tr></thead><tbody>";
            foreach ($this->table->table as $rid=>$i) {
                $r.="<tr>";
                if($i['OWNER']==1) $r.="<a href='?$this->page_uri&edit&id=".$i[$this->table_key]."' title='Redigera post' data-toggle='tooltip'>";
                foreach ($this->table->cols_visible as $cid=>$col) {
                    $c=$i[$col];
                    if($cid==0) if($i['OWNER']==1) $c="<a href='?$this->page_uri&edit&id=".$i[$this->table_key]."' title='Redigera post' data-toggle='tooltip'>$c</a>";
                    $r.="<td>$c</td>";
                }
                if($i['OWNER']==1) $r.="</a>";
                $r.="<td><span>";
                if($i['OWNER']==1) $r.="&nbsp; <a href='?$this->page_uri&edit&id=".$i[$this->table_key]."' title='Redigera post' data-toggle='tooltip'><button class='btn-sm btn-primary'><i class='fa fa-edit'></i></button></a>";
                if($i['OWNER']==0) $r.="&nbsp; <button class='btn-sm btn-success' ".$importmod->trigger($rid)." title='Importera post'><i class='fa fa-copy'></i></button>";
                if($i['OWNER']==1) $r.="&nbsp; <button class='btn-sm btn-danger' ".$deletemod->trigger($rid)." title='Radera post'><i class='fa fa-trash'></i></button>";
                $r.="</span></td></tr>";
            }
            $r.="</tbody></table>";
            $r.=$this->table->pagination();
        } else{
            $r.="<p class='lead'><em>Hittar inget.</em></p>";
        }

        $this->page_title="Sök $this->page_title";
        return $this->html=$r;
    }

   
    function prop2inputhtml($p){
        $o=$this->object->{$p};
        $c=$this->cols[$p];
        //pa($p);
        //pa($c);
        $r="<label>$c[header]</label>";
        switch(gettype($o)){
            case 'object':
                $r.=$o->form($c);
                break;
            
            default: switch($c['type']){
                case 't':
                    $lines = substr_count($o, "\n") + 1;
                    //$lines=4;
                    $r.="<textarea class='form-control' name='$c[name]' rows='$lines'>$o</textarea>";
                    break;
                case 'n':
                    $r.="<input type='number' class='form-control' name='$c[name]' value='$o' $c[req]>";
                    break;
                case 's':
                default:
                    $r.="<input type='text' class='form-control' name='$c[name]' value='$o' $c[req]>";
                    break;
            }
            break;
        }
        $r.="<div class='invalid-feedback'>$c[errmsg]</div>\n";
        //pa($r);
        return $r;
    }
    //------------------------------------------------------------------------------
    function prop2searchhtml($p){
        $o=$this->object->{$p};
        $c=$this->cols[$p];
        //pa($p);
        //pa($c);
        $r="<label>$c[header]</label>";
        switch(gettype($o)){
            case 'object':
                $r.=$o->searchform($c);
                break;
            default: switch($c['type']){
                //case 'n':$r.="<input type='number' class='form-control' name='$c[name]' value='$o' $c[req]>";break;
                default:$r.="<input type='text' class='form-control' name='$c[name]' value='$o' $c[req]>";break;
            }
            break;
        }
        //$r.="<div class='invalid-feedback'>$c[errmsg]</div>\n";
        //pa($r);
        return $r;
    }


    function view_form($search=false){
        $r="";
        foreach($this->cols_layout as $h){
            $r.="<div class='form-row'>";
            if(!is_array($h)) $h=[$h];
            foreach($h as $i=>$c){
                if(is_numeric($c)) {$w="-$c";$c=$i;} else $w="";
                if($search) $r.="<div class='form-group col-md$w'>".$this->prop2searchhtml($c)."</div>";
                else $r.="<div class='form-group col-md$w'>".$this->prop2inputhtml($c)."</div>";
            }
            $r.="</div>";
        }
        return $r;
    }


//------------------------------------------------------------------------------
    function insert(){
        pa($_POST,true);
        $this->object=new $this->classname($_POST);
        // only set and store (new) owner id when creating new item!
        $this->object->set_owner();
        //pa($this->object,true);die;
        $this->object->store();
        setMessage('Skapad');
        header("location: ?$this->page_uri");
        exit;
    }

    
    function create(){
        // create empty object
        $this->object=new $this->classname();
        // cols and form layout
        $this->cols_edit=$this->classname::cols_edit();
        $this->cols_layout=$this->cols_edit;
        $this->cols=Columns::cols($this->cols_edit);
        $this->page_title="Skapa $this->page_title";
        //pa($this,true);die;

        // view
        $r="<form action='' method='post'  class='needs-validation' novalidate>
        ".$this->view_form()."
        <button type='submit' name='bt_save' class='btn btn-success'><i class='fa fa-edit'></i> Skapa</button>
        &nbsp;<a href='?$this->page_uri' class='btn btn-secondary fcommon' title='Avbryt'><i class='fa fa-undo'></i> Avbryt</a>
        </form>";

        return $this->html=$r;
    }
    //------------------------------------------------------------------------------
    function edit(){
        // create and load object
        $this->object=new $this->classname($_REQUEST['id']);

        // cols and form layout
        $this->cols_edit=$this->classname::cols_edit();
        $this->cols_layout=$this->cols_edit;
        $this->cols=Columns::cols($this->cols_edit);
        $this->page_title="Redigera $this->page_title";
        
        // view
        $r="<form action='' method='post'  class='needs-validation' novalidate>
        ".$this->view_form()."
        <input type='hidden' name='id' value='$_REQUEST[id]'/>
        <input type='hidden' name='$this->table_key' value='$_REQUEST[id]'/>
        <button type='submit' name='bt_update' class='btn btn-success'><i class='fa fa-edit'></i> Uppdatera</button>
        &nbsp;<a href='?$this->page_uri' class='btn btn-secondary fcommon' title='Avbryt'><i class='fa fa-undo'></i> Avbryt</a>
        </form>";
        return $this->html=$r;
    }

    
    function update(){
        //pa($_POST,true);
        $this->object=new $this->classname($_POST);
        //pa($this->object->json(),true);
        $this->object->store();
        //pa($this->object->json(),true);die;
        setMessage('Uppdaterad');
        header("location: ?$this->page_uri");
        exit;
    }

    //------------------------------------------------------------------------------
    function import(){
        //pa($_POST);
        $key=["$this->table_key"=>$_REQUEST['id']];
        // get record
        $rec=$this->db->getUnique($this->table_name,$key);
        //modify and write new record
        $rec[$this->table_key."_owner"]=$this->org_id;
        unset($rec[$this->table_key]);
        $this->db->insert($this->table_name,$rec);
        setMessage('Importerad');
        header("location: ?$this->page_uri");
        exit;
    }

    function delete(){
        //pa($_POST);
        $key=["$this->table_key"=>$_REQUEST['id']];
        $this->db->delete($this->table_name,$key);
        setMessage('Raderad');
        header("location: ?$this->page_uri");
        exit;
    }

    function controller(){
        $this->nocontainer=false;
        $this->page_uri=null;
        if(!empty($_GET['list'])) $this->page_uri.="&list=$_GET[list]";
        if(isset($_GET['ext'])) {
            $this->page_uri.="&ext=$_GET[ext]";
            $_SESSION['external_enable']=$_GET['ext']; 
        }
        //pa($_REQUEST);

        if(isset($_POST['bt_update'])) $this->update();
        else if(isset($_POST['bt_save'])) $this->insert();
        else if(isset($_REQUEST['edit'])) $this->edit();
        else if(isset($_REQUEST['create'])) $this->create();
        else if(isset($_REQUEST['import'])) $this->import();
        else if(isset($_REQUEST['delete'])) $this->delete();
        else $this->list();
        return $this->nocontainer;
    }
}

//-------------------------------------------------------------------------------
//-------------------------------------------------------------------------------
//-------------------------------------------------------------------------------
class MusicCrud extends Crud {
    const CONF_FIELDS_REGEXP=["title","subtitle","yearOfComp","notes",
    "serial_number","publisher","identifier"];

    const CONF_GROUP_ID=[
        'g_composer_id'=>['COMPOSER.person_id','composers'],
        'g_arranger_id'=>['ARRANGER.person_id','arrangers'],
        'g_author_id'=>['AUTHOR.person_id','authors'],
        'g_category_id'=>['musaCategories.category_id','categorys'],
        'g_theme_id'=>['musaThemes.theme_id','themes'],
        'g_language_id'=>['musaLanguages.language_id','languages'],
        'g_instrument_id'=>['musaInstruments.instrument_id','instruments'],
        'g_holiday_id'=>['musaHolidays.holiday_id','holidays'],
        'g_solovoice_id'=>['musaSolovoices.solovoice_id','solovoices'],
    ];

    const CONF_OBJECT_ID=[
        'storage_id'=>['','storage'],
        'choirvoice_id'=>['','choirvoice'],
    ];

    function __construct($title){
        Crud::__construct($title,'Music');
    }

    private static function _gidl2sql($gidl){
        if(!is_array($gidl)) $gidl=[$gidl];
        $r="";
        foreach($gidl as $gid) $r.="\n,CONCAT(',',GROUP_CONCAT(DISTINCT ".self::CONF_GROUP_ID[$gid][0]." SEPARATOR ','),',') as $gid";
        return $r;
    }
    function _table_init(){
        // table features
        $this->table->feature['nosearch']=true;
        $this->table->feature['create']=['button'=>New Button('create')];

        //only own
        $this->table->own="\nAND $this->table_name.$this->table_key"."_owner=$this->org_id";
        //external = no limits
        if(!empty($_GET['ext'])) $this->table->own="";
        $sql="
CREATE TEMPORARY TABLE mt0
SELECT musaMusic.*
,musaChoirvoices.choirvoice_name,musaStorages.storage_name
,IF(musaMusic.music_id_owner=$this->org_id,1,0) as OWNER
,IF(musaMusic.music_id_owner=$this->org_id,NULL,org_name) as EXTERNAL
,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',COMPOSER.first_name,COMPOSER.family_name) SEPARATOR ', ') as g_composer
,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',ARRANGER.first_name,ARRANGER.family_name) SEPARATOR ', ') as g_arranger
,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',AUTHOR.first_name,AUTHOR.family_name) SEPARATOR ', ') as g_author
".self::_gidl2sql(['g_composer_id','g_arranger_id','g_author_id'])."
FROM musaMusic 
LEFT JOIN musaOrgs ON musaOrgs.org_id=musaMusic.music_id_owner
LEFT JOIN musaOrgStatusTypes ON musaOrgStatusTypes.org_status_code=musaOrgs.org_status_code
LEFT JOIN musaChoirvoices ON musaChoirvoices.choirvoice_id=musaMusic.choirvoice_id
LEFT JOIN musaStorages ON musaStorages.storage_id=musaMusic.storage_id
LEFT JOIN musaMusicComposers ON musaMusicComposers.music_id=musaMusic.music_id
LEFT JOIN musaMusicAuthors ON musaMusicAuthors.music_id=musaMusic.music_id
LEFT JOIN musaMusicArrangers ON musaMusicArrangers.music_id=musaMusic.music_id
LEFT JOIN musaPersons COMPOSER ON COMPOSER.person_id=musaMusicComposers.person_id
LEFT JOIN musaPersons AUTHOR ON AUTHOR.person_id=musaMusicAuthors.person_id
LEFT JOIN musaPersons ARRANGER ON ARRANGER.person_id=musaMusicArrangers.person_id
WHERE 1 ".$this->table->own."
GROUP BY musaMusic.music_id;
";
        $r=$this->db->executeQry($sql);
        $sql="
CREATE TEMPORARY TABLE mt1
SELECT musaMusic.music_id
,GROUP_CONCAT(DISTINCT musaCategories.category_name SEPARATOR ', ') as g_category_name
,GROUP_CONCAT(DISTINCT musaThemes.theme_name SEPARATOR ', ') as g_theme_name
,GROUP_CONCAT(DISTINCT musaLanguages.language_name SEPARATOR ', ') as g_language_name
,GROUP_CONCAT(DISTINCT musaInstruments.instrument_name SEPARATOR ', ') as g_instrument_name
,GROUP_CONCAT(DISTINCT musaHolidays.holiday_name SEPARATOR ', ') as g_holiday_name
,GROUP_CONCAT(DISTINCT musaSolovoices.solovoice_name SEPARATOR ', ') as g_solovoice_name
".self::_gidl2sql(['g_category_id','g_theme_id','g_language_id','g_instrument_id','g_holiday_id','g_solovoice_id'])."

FROM musaMusic 
LEFT JOIN musaMusicCategories ON musaMusicCategories.music_id=musaMusic.music_id
LEFT JOIN musaMusicThemes ON musaMusicThemes.music_id=musaMusic.music_id
LEFT JOIN musaMusicLanguages ON musaMusicLanguages.music_id=musaMusic.music_id
LEFT JOIN musaMusicInstruments ON musaMusicInstruments.music_id=musaMusic.music_id
LEFT JOIN musaMusicSolovoices ON musaMusicSolovoices.music_id=musaMusic.music_id
LEFT JOIN musaCategories ON musaCategories.category_id=musaMusicCategories.category_id
LEFT JOIN musaThemes ON musaThemes.theme_id=musaMusicThemes.theme_id
LEFT JOIN musaLanguages ON musaLanguages.language_id=musaMusicLanguages.language_id
LEFT JOIN musaInstruments ON musaInstruments.instrument_id=musaMusicInstruments.instrument_id
LEFT JOIN musaMusicHolidays ON musaMusicHolidays.music_id=musaMusic.music_id
LEFT JOIN musaHolidays ON musaHolidays.holiday_id=musaMusicHolidays.holiday_id
LEFT JOIN musaSolovoices ON musaSolovoices.solovoice_id=musaMusicSolovoices.solovoice_id
WHERE 1 ".$this->table->own."
GROUP BY musaMusic.music_id;
";

        $r=$this->db->executeQry($sql);
        

        $this->table->sql_body="
        FROM mt0 musaMusic
        LEFT JOIN mt1 ON musaMusic.music_id=mt1.music_id
        ";
        $this->table->sql_group="";
        $this->table->cols_visible=[
            "title","subtitle","yearOfComp","movements","copies","notes",
            "storage_name","choirvoice_name",
            "g_composer","g_arranger","g_author",
            "g_category_name","g_theme_name","g_language_name","g_instrument_name","g_holiday_name","g_solovoice_name",
            "serial_number","publisher","identifier",
        ];
        $this->table->cols_searchable=$this->table->cols_visible;
        //$this->table->cols_searchable=$this->table_props;
        $this->table->order = $this->table_props[0];
        // ------------------------------------------------------
        //$this->table->_info();
    }


    private function _edit_layout(){
        // cols and load form values
        $this->cols_edit=["title","subtitle","yearOfComp","movements","copies","notes",
        "serial_number","publisher","identifier","storage","choirvoice",
        "composers","arrangers","authors","categories","themes","languages","instruments","holidays","solovoices"];
        $this->cols_layout=[
            ["title","yearOfComp"=>2],
            ["subtitle","movements"=>3],
            ["notes"],
            ["choirvoice","languages"=>4],
            ["solovoices","instruments"],
            ["composers","arrangers","authors"],
            ["categories","holidays","themes"],
            ["serial_number"=>3,"publisher"],
            ["identifier","storage","copies"=>3],
        ];
        $this->cols=Columns::cols($this->cols_edit);
    }

    function create(){
        // create empty object
        $this->object=new Music();
        $this->page_title="Skapa $this->page_title";
        // cols and form layout
        $this->_edit_layout();

        //pa($this,true);die;
        // view
        $r="<form action='' method='post'  class='needs-validation' novalidate>
        ".$this->view_form()."
        <button type='submit' name='bt_save' class='btn btn-success'><i class='fa fa-edit'></i> Skapa</button>
        &nbsp;<a href='?$this->page_uri' class='btn btn-secondary fcommon' title='Avbryt'><i class='fa fa-undo'></i> Avbryt</a>
        </form>";
        return $this->html=$r;
    }
    //------------------------------------------------------------------------------
    function edit(){
        $this->object=new Music($_REQUEST['id']);
        $this->page_title="Redigera $this->page_title";

        // cols and form layout
        $this->_edit_layout();
        // view
        $r="<form action='' method='post'  class='needs-validation' novalidate>
        <button type='submit' name='bt_update' class='btn btn-success'><i class='fa fa-edit'></i> Uppdatera</button>
        &nbsp;<a href='?$this->page_uri' class='btn btn-secondary fcommon' title='Avbryt'><i class='fa fa-undo'></i> Avbryt</a>";
        $r.=$this->view_form();
        $key=["$this->table_key"=>$_REQUEST['id']];
        $r.="<input type='hidden' name='key' value='".json_encode($key)."'/>";
        $r.="<input type='hidden' name='id' value='$_REQUEST[id]'/>";
        $r.="<input type='hidden' name='$this->table_key' value='$_REQUEST[id]'/>";
        $r.="<button type='submit' name='bt_update' class='btn btn-success'><i class='fa fa-edit'></i> Uppdatera</button>";
        $r.="&nbsp;<a href='?$this->page_uri' class='btn btn-secondary fcommon' title='Avbryt'><i class='fa fa-undo'></i> Avbryt</a>
        </form>";
        return $this->html=$r;
    }


    private function _search_layout(){
        // cols and load form values
        $this->cols_edit=["title","subtitle","yearOfComp","notes",
        "serial_number","publisher","identifier","storage","choirvoice",
        "composers","arrangers","authors","categories","themes","languages","instruments","holidays","solovoices"];
        $this->cols_layout=[
            ["title","subtitle","yearOfComp"],
            ["choirvoice","solovoices","instruments","languages"=>4],
            ["composers","arrangers","authors"],
            ["categories","holidays","themes","storage"],
            ["serial_number"=>3,"publisher","identifier","notes"],
        ];
        $this->cols=Columns::cols($this->cols_edit);
    }

    private static function _gida2search($ida,$del=''){
        $r="";
        foreach($ida as $id=>$p) if(!empty($_POST[$p[1]])) $r.="\nAND ($id REGEXP '$del".implode("$del|$del",$_POST[$p[1]])."$del')";
        return $r;
    }

    private static function _fl2search($fl){
        $r="";
        foreach($fl as $fid) {
            $v=trim($_POST[$fid]);
            if(!empty($v)) {
                $c=Columns::cols([$fid])[$fid];
                switch($c['type']){
                    case 'n':
                        if(strpos($v,'-')!==false) {
                            $nr=explode('-',$v);
                            if(count($nr)==2) {
                                if(empty($nr[1])) $r.="\nAND ($fid >= $nr[0])";
                                elseif(empty($nr[0])) $r.="\nAND ($fid <= $nr[1])";
                                else $r.="\nAND ($fid >= $nr[0] AND $fid <= $nr[1])";
                            }
                        } else {
                            $r.="\nAND ($fid REGEXP '$v')";break;        
                        }
                        break;
                    default:
                        $r.="\nAND ($fid REGEXP '$v')";break;
                }
            }
        }
        return $r;
    }


    function list(){
        // create empty object
        $this->object=new Music();
        $this->page_title="Sök $this->page_title";
        // cols and form layout
        $this->_search_layout();

        $this->table=New Table();
        $this->table->page_uri=$this->page_uri;
        $this->_table_init();
        $this->table->init();

        //pa($this,true);die;

        $asearch=New Card("Sökning");
        $asearch->helpmodal=New Modal("helppage".__LINE__);
        $asearch->helpmodal->body="<p>Här kan du göra enkel/avancerad sökning i musikarkivet</p>";
        // view
        $asearch->body="
        <form action='' method='post'>
        <div class='form-row'>
            <div class='col-auto'>
                <input type='text' class='form-control' placeholder='Sök (tryck Enter)' name='search' 
                value='".((!empty($this->search))?htmlspecialchars($this->search):"")."'>
            </div>
            <div class='col-auto'>
                <a href='?".$this->table->page_uri."' class='btn btn-secondary' title='Återställ Tabell' data-toggle='tooltip'><i class='fa fa-undo'></i></a>
            </div>
            <div class='col-auto'>
                <input type='hidden' name='ext' value='0'>
                <input type='checkbox' name='ext' value='1'".(!empty($_GET['ext'])?"checked":"")."> 
                <label >Sök externt</label>
            </div>
            <div class='col-auto'>
                <input type='hidden' name='asearch' value='0'>
                <input type='checkbox' id='chk_asearch' onclick='ShowHideDiv(this)' name='asearch' value='1'".(!empty($_REQUEST['asearch'])?"checked":"")."> 
                <label >Avancerad Sökning</label>
            </div>
        </div>
        </form>
        <div id='adv_search_input' style='display: none'>
        <form action='' method='post'  class='needs-validation' novalidate>
        ".$this->view_form(true)."
        <button type='submit' name='bt_asearch' class='btn btn-primary'><i class='fa fa-search'></i> Sök</button>
        &nbsp;<a href='?$this->page_uri' class='btn btn-secondary fcommon' title='Avbryt'><i class='fa fa-undo'></i> Avbryt</a>
        </form>
        </div>
        <script type='text/javascript'>
        function ShowHideDiv(o) {
            var divTarget = document.getElementById('adv_search_input');
            divTarget.style.display = o.checked ? 'block' : 'none';
        }
        </script>        
        ";


        // check and add advanced search
        if(isset($_POST['bt_asearch'])) {
            //pa($_POST);
            // reset simple search
            $this->table->search="";
            $s="";

            // regexp field
            $s.=self::_fl2search(self::CONF_FIELDS_REGEXP);

            // multiselect object
            $s.=self::_gida2search(self::CONF_OBJECT_ID);

            // multiselect list-object
            $s.=self::_gida2search(self::CONF_GROUP_ID,',');
            //if(!empty($_POST['languages'])) $s.="\nAND (g_language_id REGEXP ',".implode(',|,',$_POST['languages']).",')";

            $this->table->asearch=$s;
        }
        // $this->search=(isset($_REQUEST['search']))?$_REQUEST['search']:"";

        // do query with pagination limits for display
        $this->table->table();

        $r=$asearch->html();
        
        $deletemod=New Modal("confirmdelete".__LINE__);
        $r.=$deletemod->confirm_table_delete('?'.$this->page_uri.'&delete&id=${o.'.$this->table_key.'}',$this->table->table,$this->table_props[0]);
        $importmod=New Modal("confirmimport".__LINE__);
        $r.=$importmod->confirm_table_import('?'.$this->page_uri.'&import&id=${o.'.$this->table_key.'}',$this->table->table,$this->table_props[0]);

        $r.=$this->table->header_table($this->page_title);
        if($this->table->table){
            if(count($this->table->cols_visible)>=6) $this->nocontainer=true;
            $r.="<table class='table table-striped table-sm table-bordered border'><thead class='thead-dark'><tr>";
            foreach ($this->table->cols_visible as $col) $r.=$this->table->header_col($col);
            $r.="<th style='width:10em;'>Action</th></tr></thead><tbody>";
            foreach ($this->table->table as $rid=>$i) {
                $r.="<tr>";
                if($i['OWNER']==1) $r.="<a href='?$this->page_uri&edit&id=".$i[$this->table_key]."' title='Redigera post' data-toggle='tooltip'>";
                foreach ($this->table->cols_visible as $cid=>$col) {
                    $c=$i[$col];
                    if($cid==0) if($i['OWNER']==1) $c="<a href='?$this->page_uri&edit&id=".$i[$this->table_key]."' title='Redigera post' data-toggle='tooltip'>$c</a>";
                    $r.="<td>$c</td>";
                }
                if($i['OWNER']==1) $r.="</a>";
                $r.="<td><span>";
                if($i['OWNER']==1) $r.="&nbsp; <a href='?$this->page_uri&edit&id=".$i[$this->table_key]."' title='Redigera post' data-toggle='tooltip'><button class='btn-sm btn-primary'><i class='fa fa-edit'></i></button></a>";
                if($i['OWNER']==0) $r.="&nbsp; <button class='btn-sm btn-success' ".$importmod->trigger($rid)." title='Importera post'><i class='fa fa-copy'></i></button>";
                if($i['OWNER']==1) $r.="&nbsp; <button class='btn-sm btn-danger' ".$deletemod->trigger($rid)." title='Radera post'><i class='fa fa-trash'></i></button>";
                $r.="</span></td></tr>";
            }
            $r.="</tbody></table>";
            $r.=$this->table->pagination();
        } else{
            $r.="<p class='lead'><em>Hittar inget.</em></p>";
        }

        $this->page_title="Sök $this->page_title";
        return $this->html=$r;
    }
    
}
