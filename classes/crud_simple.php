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

    function __construct($title,$org_id,$classname=null){
        global $db;
        $this->db=$db;
        $this->page_title=$title;
        $this->org_id=$org_id;
        if(!empty($classname)) $this->base_on_class($classname);
    }

    function sql_prefix(){
        return "
        ,IF({$this->table_name}.{$this->table_key}_owner=$this->org_id,1,0) as OWNER
        ,IF({$this->table_name}.{$this->table_key}_owner=$this->org_id,NULL,org_name) as EXTERNAL
        ,{$this->table_name}.{$this->table_key}
        ";
    }
    function base_on_class($classname){
        $this->classname=$classname;
        $ci=$classname::classinfo();
        $this->table_list=str_replace("musa","musaMusic",$ci['TABLE_MAIN']);
        $this->table_name=$ci['TABLE_MAIN'];
        $this->table_key=$ci['TABLE_KEY'];
        $this->table_props=$ci['TABLE_PROPS'];
        $this->sql_body=$this->sql_prefix()."
        FROM {$this->table_name} 
        LEFT JOIN musaOrgs ON musaOrgs.org_id={$this->table_name}.{$this->table_key}_owner
        ";
        /*
        LEFT JOIN {$this->table_list} ON {$this->table_list}.{$this->table_key}={$this->table_name}.{$this->table_key}
        LEFT JOIN musaMusic ON musaMusic.music_id={$this->table_list}.music_id

        LEFT JOIN musaMusicComposers ON musaMusicComposers.person_id=musaPersons.person_id
        LEFT JOIN musaMusicArrangers ON musaMusicArrangers.person_id=musaPersons.person_id
        LEFT JOIN musaMusicAuthors ON musaMusicAuthors.person_id=musaPersons.person_id
        LEFT JOIN musaMusic ON (musaMusic.music_id=musaMusicArrangers.music_id) OR (musaMusic.music_id=musaMusicComposers.music_id)  OR (musaMusic.music_id=musaMusicAuthors.music_id)

        */
        $this->sql_group="";
        $this->cols_visible=array_merge($this->table_props,["OWNER","EXTERNAL",$this->table_key]);
        $this->cols_searchable=$this->table_props;
        $this->cols_edit=$this->cols_searchable;
        $this->order = $this->table_props[0];
        switch($classname){
            case 'Person':
                $this->table_list=['musaMusicComposers','musaMusicArrangers','musaMusicAuthors'];
                $this->sql_body=$this->sql_prefix()."
                FROM {$this->table_name} 
                LEFT JOIN musaOrgs ON musaOrgs.org_id={$this->table_name}.{$this->table_key}_owner
                LEFT JOIN musaGenderTypes ON musaGenderTypes.gender_id=musaPersons.gender_id
                LEFT JOIN musaCountries ON musaCountries.country_id=musaPersons.country_id
                ";
                break;
            }
        // ------------------------------------------------------
        $this->feature['create']=['button'=>New Button('create')];
    }
    function init(){
        $this->table=New Table('');
        $this->table->sql_select=$this->sql_select;
        $this->table->sql_body=$this->sql_body;
        $this->table->sql_group=$this->sql_group;
        if(empty($this->cols_visible)){
            $this->table->_info();
            die("missing cols_visible");
        }
        // get col info
        if(empty($this->cols)) $this->cols=Columns::cols($this->cols_visible);
        

        //page
        $this->table->pageno=(isset($_GET['pageno']))?$_GET['pageno']:1;

        //only own
        $this->table->own="\nAND $this->table_name.$this->table_key"."_owner=$this->org_id";
        //external = no limits
        if(!empty($_GET['ext'])) $this->table->own="";

        //search
        $this->table->cols_searchable=(!empty($this->cols_searchable))?$this->cols_searchable:$this->cols_visible;
        $this->table->search=(isset($_REQUEST['search']))?$_REQUEST['search']:"";

        //Column sorting on column name
        $this->table->order=(isset($_REQUEST['order']))?$_REQUEST['order']:"";
        if(!in_array($this->table->order, $this->cols_visible)) $this->table->order=$this->cols_visible[0];

        //Column sort order
        $sortBy = array('asc', 'desc'); 
        $this->table->sort=(isset($_REQUEST['sort']))?$_REQUEST['sort']:"";
        if(!in_array($this->table->sort, $sortBy)) $this->table->sort= $sortBy[0];
    }


    function header_col($col){
        $linkopt="";
        if(!empty($this->table->search)) $linkopt.="&search={$this->table->search}";
        $so=($this->table->sort=="desc")?"asc":"desc";
        $si="";
        if($this->table->order==$col) {
            $si=($this->table->sort=="desc")?"<i class='fas fa-sort-down'></i>":"<i class='fas fa-sort-up'></i>";
            }
        $r="<th><a href=?$this->page_uri&order=$col&sort=$so$linkopt>".$this->cols[$col]['header']." $si</th>";
        return $r;
    }
    function pagination(){
        $linkopt="";
        if(!empty($this->table->search)) $linkopt.="&search={$this->table->search}";
        if(!empty($this->table->order)) $linkopt.="&order={$this->table->order}";
        if(!empty($this->table->sort)) $linkopt.="&sort={$this->table->sort}";

        $r="";
        $r.="Sida {$this->table->pageno} av {$this->table->pages}".((!empty($this->table->rows))?" (totalt {$this->table->rows} rader)":"")."<br/>";
        if($this->table->pages>1) {
            $r.="<ul class='pagination' align-right>
            <li class='page-item".(($this->table->pageno <= 1)?" disabled":"")."'>
            <a class='page-link' href='?$this->page_uri&pageno=1$linkopt' title='Gå till början' data-toggle='tooltip'><i class='fa fa-step-backward'></i></a></li>
            <li class='page-item".(($this->table->pageno <= 1)?" disabled":"")."'>
            <a class='page-link' href='?$this->page_uri&pageno=".($this->table->pageno - 1)."$linkopt'><i class='fa fa-backward'></i></a></li>
            <li class='page-item".(($this->table->pageno >= $this->table->pages)?" disabled":"")."'>
            <a class='page-link' href='?$this->page_uri&pageno=".($this->table->pageno + 1)."$linkopt'><i class='fa fa-forward'></i></a></li>
            <li class='page-item".(($this->table->pageno >= $this->table->pages)?" disabled":"")."'>
            <a class='page-link' href='?$this->page_uri&pageno={$this->table->pages}$linkopt' title='Gå till slutet' data-toggle='tooltip'><i class='fa fa-step-forward'></i></a></li>
            </ul>";
        }
        return $r;
    }

    function header_table(){
        $r="";
        $r.="<form action='' method='get'>
        <div class='form-row'>
            <div class='col'><h1>$this->page_title</h1></div>
            <div class='col-auto'>
                <input type='text' class='form-control' placeholder='Sök (tryck Enter)' name='search' 
                value='".((!empty($this->table->search))?htmlspecialchars($this->table->search):"")."'>
            </div>
            <div class='col-auto'>
                ".(isset($_GET['list'])?"<input type='hidden' name='list' value='$_GET[list]'>":"")."
                <input type='hidden' name='ext' value='0'>
                <input type='checkbox' name='ext' value='1'".(!empty($_GET['ext'])?"checked":"")."> 
                <label >Sök externt</label>
            </div>
            <div class='col'>

            ";
        if(!empty($this->feature['create']['button'])) $r.=$this->feature['create']['button']->html();
        if(!empty($this->feature['export_excel']['button'])) $r.=$this->feature['export_excel']['button']->html();
        $r.="<a href='?$this->page_uri' class='btn btn-secondary ml-1' title='Återställ Tabell' data-toggle='tooltip'><i class='fa fa-undo'></i></a>
            </div>
        </div></form>";
        return $r;
    }

    function list(){
        $this->init();
        // do query with pagination limits for display
        $this->table->table();
        $r="";
        
        $deletemod=New Modal("confirmdelete".__LINE__);
        $r.=$deletemod->confirm_table_delete('?'.$this->page_uri.'&delete&id=${o.'.$this->table_key.'}',$this->table->table,$this->table_props[0]);
        $importmod=New Modal("confirmimport".__LINE__);
        $r.=$importmod->confirm_table_import('?'.$this->page_uri.'&import&id=${o.'.$this->table_key.'}',$this->table->table,$this->table_props[0]);

        $r.=$this->header_table();
        if($this->table->table){
            $r.="<table class='table table-striped table-sm table-bordered border'><thead class='thead-dark'><tr>";
            foreach ($this->cols_visible as $col) $r.=$this->header_col($col);
            $r.="<th style='width:10em;'>Action</th></tr></thead><tbody>";
            foreach ($this->table->table as $rid=>$i) {
                $r.="<tr>";
                foreach ($this->cols_visible as $col) {
                    $r.="<td>".$i[$col]."</td>";
                }
                $r.="<td><span>";
                if($i['OWNER']==1) $r.="&nbsp; <a href='?$this->page_uri&edit&id=".$i[$this->table_key]."' title='Redigera post' data-toggle='tooltip'><button class='btn-sm btn-primary'><i class='fa fa-edit'></i></button></a>";
                if($i['OWNER']==0) $r.="&nbsp; <button class='btn-sm btn-success' ".$importmod->trigger($rid)." title='Importera post'><i class='fa fa-copy'></i></button>";
                if($i['OWNER']==1) $r.="&nbsp; <button class='btn-sm btn-danger' ".$deletemod->trigger($rid)." title='Radera post'><i class='fa fa-trash'></i></button>";
                $r.="</span></td></tr>";
                /*
                $r.="<td>
                <a href='?$this->page_uri&edit&id=".$i[$this->table_key]."' title='Redigera' data-toggle='tooltip'><i class='fa fa-edit'></i></a>
                &nbsp| <a href='?$this->page_uri&delete&id=".$i[$this->table_key]."' data-toggle='tooltip' ".confOp('delete')."</a>
                &nbsp| <span ".$deletemod->trigger($rid)."><i class='fa fa-send'></i></span>
                </td></tr>";
                */
            }
            $r.="</tbody></table>";
            $r.=$this->pagination();
        } else{
            $r.="<p class='lead'><em>Hittar inget.</em></p>";
        }

        return $this->html=$r;
    }
    
    static function gen_input($c){
        //pa($c);
        if(empty($c['value'])) $c['value']=null;
        $req="";
        if(!empty($c['Null'])&&($c['Null']=='NO')) $req="required";
        if(!empty($c['req'])) $req="required";

        $dis=empty($c['dis'])?"":"disabled";
        $errmsg=(!empty($c['errmsg']))?$c['errmsg']:"";
        $t=$c['type'];
        $r="<div class='form-group'><label>$c[header]</label>";
        switch($t){
            case 't':
                $lines = substr_count($c['value'], "\n") + 1;
                //$lines=4;
                $r.="<textarea name='$c[name]' class='form-control' rows='$lines'>$c[value]</textarea>";
                break;
            case 'n':
                $r.="<input type='number' name='$c[name]' class='form-control' value='$c[value]' $req $dis>";
                break;
            case 's':
            default:
                $r.="<input type='text' name='$c[name]' class='form-control' value='$c[value]' $req $dis>";
                break;
        }
        $r.="<span class='form-text'>$errmsg</span></div>";
        return $r;
    }
    
    function view_form($cols){
        $r="<h2>$this->page_title</h2>";
        foreach($cols as $c) $r.=Crud::gen_input($c);
        return $r;
    }

    function cols_form($load=false){
        $cols=Columns::edit($this->cols_edit);
        if($load){
            $key=["$this->table_key"=>$_REQUEST['id']];
            $rec=$this->db->getUnique($this->table_name,$key);
            // get form values
            foreach($cols as $i=>$c) $cols[$i]['value']=(!empty($_REQUEST[$i]))?trim($_REQUEST[$i]):$rec[$i];
        }
        return $cols;
    }

    function create(){
        $this->init();
        $cols=$this->cols_form();
        
        $r="<form action='' method='post'  class='needs-validation' novalidate>";
        $r.=$this->view_form($cols);
        $r.="<input type='hidden' name='table_key' value='$this->table_key'/>";
        $r.="<button type='submit' name='bt_save' class='btn btn-success'><i class='fa fa-edit'></i> Skapa</button>";
        $r.="&nbsp;<a href='?$this->page_uri' class='btn btn-secondary fcommon' title='Avbryt'><i class='fa fa-undo'></i> Avbryt</a>
        </form>";
        return $this->html=$r;
    }
    function insert(){
        foreach($this->cols_form() as $i=>$ci) $rec[$i]=(!empty($_REQUEST[$i]))?trim($_REQUEST[$i]):null;
        $this->db->insert($this->table_name,$rec);
        setMessage('Skapad');
        header("location: ?$this->page_uri");
        exit;
    }

    

    function edit(){
        $this->init();
        $key=["$this->table_key"=>$_REQUEST['id']];
        $rec=$this->db->getUnique($this->table_name,$key);
        $cols=$this->cols_form(true);

        // get form values
        foreach($cols as $i=>$c) $cols[$i]['value']=(!empty($_REQUEST[$i]))?trim($_REQUEST[$i]):$rec[$i];
        
        $r="<form action='' method='post'  class='needs-validation' novalidate>";
        $r.=$this->view_form($cols);
        $r.="<input type='hidden' name='key' value='".json_encode($key)."'/>";
        $r.="<input type='hidden' name='id' value='$_REQUEST[id]'/>";
        $r.="<input type='hidden' name='table_key' value='$this->table_key'/>";
        $r.="<button type='submit' name='bt_update' class='btn btn-success'><i class='fa fa-edit'></i> Uppdatera</button>";
        $r.="&nbsp;<a href='?$this->page_uri' class='btn btn-secondary fcommon' title='Avbryt'><i class='fa fa-undo'></i> Avbryt</a>
        </form>";
        return $this->html=$r;
    }
    
    function update(){
        //pa($_POST);
        $key=["$this->table_key"=>$_REQUEST['id']];
        // get form values
        foreach($this->cols_form() as $i=>$c) $rec[$i]=(!empty($_REQUEST[$i]))?trim($_REQUEST[$i]):null;
        $this->db->update($this->table_name,$rec,$key);
        setMessage('Uppdaterad');
        header("location: ?$this->page_uri");
        exit;
    }

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
        $this->page_uri=null;
        if(!empty($_GET['list'])) $this->page_uri.="&list=$_GET[list]";
        if(isset($_GET['ext'])) {
            $this->page_uri.="&ext=$_GET[ext]";
            $_SESSION['external_enable']=$_GET['ext']; 
        }

        if(isset($_POST['bt_update'])) $this->update();
        else if(isset($_POST['bt_save'])) $this->insert();
        else if(isset($_REQUEST['edit'])) $this->edit();
        else if(isset($_REQUEST['create'])) $this->create();
        else if(isset($_REQUEST['import'])) $this->import();
        else if(isset($_REQUEST['delete'])) $this->delete();
        //if(isset($_REQUEST['delete']) $this->delete();
        //if(isset($_REQUEST['read']) $this->read();
        //if(isset($_REQUEST['create']) $this->create();
        else $this->list();
    }
}

class MusicCrud extends Crud {
    function __construct($title,$org_id){
        Crud::__construct($title,$org_id,'Music');
        $this->sql_body=$this->sql_prefix()."
        ,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',COMPOSER.first_name,COMPOSER.family_name) SEPARATOR ', ') as COMPOSER
        ,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',ARRANGER.first_name,ARRANGER.family_name) SEPARATOR ', ') as ARRANGER
        ,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',AUTHOR.first_name,AUTHOR.family_name) SEPARATOR ', ') as AUTHOR
        ,GROUP_CONCAT(DISTINCT musaHolidays.holiday_name SEPARATOR ', ') as HOLIDAYS
        FROM {$this->table_name} 
        LEFT JOIN musaOrgs ON musaOrgs.org_id=musaMusic.music_id_owner
        LEFT JOIN musaOrgStatusTypes ON musaOrgStatusTypes.org_status_code=musaOrgs.org_status_code
        LEFT JOIN musaChoirvoices ON musaChoirvoices.choirvoice_id=musaMusic.choirvoice_id
        LEFT JOIN musaStorages ON musaStorages.storage_id=musaMusic.storage_id
        LEFT JOIN musaMusicComposers ON musaMusicComposers.music_id=musaMusic.music_id
        LEFT JOIN musaPersons COMPOSER ON COMPOSER.person_id=musaMusicComposers.person_id
        LEFT JOIN musaMusicArrangers ON musaMusicArrangers.music_id=musaMusic.music_id
        LEFT JOIN musaPersons ARRANGER ON ARRANGER.person_id=musaMusicArrangers.person_id
        LEFT JOIN musaMusicAuthors ON musaMusicAuthors.music_id=musaMusic.music_id
        LEFT JOIN musaPersons AUTHOR ON AUTHOR.person_id=musaMusicAuthors.person_id

        LEFT JOIN musaMusicHolidays ON musaMusicHolidays.music_id=musaMusic.music_id
        LEFT JOIN musaHolidays ON musaHolidays.holiday_id=musaMusicHolidays.holiday_id
        ";
        $this->sql_where="WHERE 1";
        $this->sql_group="GROUP BY musaMusic.music_id";


    }

    function p2f($p){
        $o=$this->object->{$p};
        $c=$this->cols[$p];
        $r="<label>$c[header]</label>";
        switch(gettype($o)){
            case 'object':
                $r.=$o->form($c);
                break;
            case 'string':
            default:
                $r.="<input type='text' class='form-control' value='$o'  $c[req]>";
                break;
        }
        $r.="<div class='invalid-feedback'>$c[errmsg]</div>\n";
        //pa($r);
        return $r;
    }
    function view_form($cols=null){
        $r="";
        foreach($this->cols_layout as $h){
            $r.="<div class='form-row'>";
            foreach($h as $i=>$c){
                if(is_numeric($c)) {$w="-$c";$c=$i;} else $w="";
                $r.="<div class='form-group col-md$w'>".$this->p2f($c)."</div>";
            }
            $r.="</div>";
        }
        return $r;
    }


    function cols_form($music=null){
        $this->cols_edit=["title","subtitle","yearOfComp","movements","copies","notes","serial_number","publisher","identifier","storage_id","choirvoice_id","composers","arrangers","authors","categories","themes","languages","instruments","holidays","solovoices"];
        $this->cols_layout=[
            ["title","yearOfComp"=>2],
            ["subtitle","movements"=>3],
            ["notes"],
            ["choirvoice_id","languages"=>4],
            ["solovoices","instruments"],
            ["composers","arrangers","authors"],
            ["categories","holidays","themes"],
            ["serial_number"=>3,"publisher"],
            ["identifier","storage_id","copies"=>3],
        ];
        $cols=Columns::cols($this->cols_edit);
        return $this->cols=$cols;
    }
    

    function edit(){
        $this->init();
        $this->object=new Music($_REQUEST['id']);

        // cols and load form values
        $this->cols_form();
        //pa($cols);

        // view
        $r="<form action='' method='post'  class='needs-validation' novalidate>";
        $r.=$this->view_form();
        $key=["$this->table_key"=>$_REQUEST['id']];
        $r.="<input type='hidden' name='key' value='".json_encode($key)."'/>";
        $r.="<input type='hidden' name='id' value='$_REQUEST[id]'/>";
        $r.="<button type='submit' name='bt_update' class='btn btn-success'><i class='fa fa-edit'></i> Uppdatera</button>";
        $r.="&nbsp;<a href='?$this->page_uri' class='btn btn-secondary fcommon' title='Avbryt'><i class='fa fa-undo'></i> Avbryt</a>
        </form>";
        return $this->html=$r;
    }
    
}
