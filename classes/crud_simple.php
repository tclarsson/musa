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
        if(isset($this->helpmodal)) 
            if(empty($this->helpmodal->head)) 
                if(!empty($this->title)) $this->helpmodal->head=$this->title;
        
        $r="<div class='card bg-light'>
            <div class='card-header'>
            <div class='row card-header-title'> 
                <div class='col'>{$this->title}</div>";
        if(isset($this->helpmodal)) 
            $r.="<div class='col-auto'>
            <button type='button' data-target='#{$this->helpmodal->id}' 
            title='Information och Hjälp' class='btn btn-info float-right ml-2' data-toggle='modal'><i class='fa fa-info-circle'></i></button>
            </div>
            {$this->helpmodal->html()}
            ";
        $r.="</div></div>";
        if(!empty($this->body)) $r.="<div class='card-body'>$this->body</div>";
        if(!empty($this->footer)) $r.="<div class='card-footer'>$this->footer</div>";
        $r.="</div></br>";
        return $r;
    }
}

class Modal {
    use crud_common;
    function __construct($i){
        $this->id=$i;
    }
    function html(){
        $r="
        <div class='modal fade' id='$this->id' tabindex='-1' role='dialog'>
            <div class='modal-dialog' role='document'>
                <div class='modal-content'>
                <div class='modal-header modal-header-title bg-info text-light'>$this->head
                    <button type='button' class='close text-light' data-dismiss='modal'>&times;</button>
                </div>
                <div class='modal-body'>$this->body</div>
                <div class='modal-footer'><button type='button' class='btn btn-secondary' data-dismiss='modal'>Stäng</button></div>
                </div>
            </div>
        </div>
        ";
        return $r;
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
            case 'varchar':
            case 'text':
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
            $col=['header'=>$i,'type'=>'s'];
            $c=$db->getColInfo($i);
            if($noprimary) if($c['Key']=='PRI') continue;
            if(!empty($c)) {
                $col=array_merge($col,$c);
                $col['type']=self::sqltype2code($c['Type']);
            }
            if(!empty($columns[$i])) {
                $col=array_merge($col,$columns[$i]);
            } else {
                $columns[$i]=$col;
                $update=true;
            }
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
    function __construct($title,$org_id,$id=""){
        global $db;
        $this->db=$db;
        $this->page_title=$title;
        $this->org_id=$org_id;
        $this->id=$id;
    }
    function set_singleprop($basename,$props,$key){
        $this->table_name="musa$basename";
        $this->table_list=str_replace("musa","musaMusic",$this->table_name);
        $this->table_key=$key;
        if(!is_array($props)) $props=[$props];
        $this->table_props=$props;
        $this->sql_select="musa{$this->table_basename}.*,musaMusic.*,musaOrgs.*";
        $this->sql_body="
        ,IF(musaMusic.org_id={$this->org_id},1,0) as owner
        FROM {$this->table_name} 
        LEFT JOIN {$this->table_list} ON {$this->table_list}.{$this->table_key}={$this->table_name}.{$this->table_key}
        LEFT JOIN musaMusic ON musaMusic.music_id={$this->table_list}.music_id
        LEFT JOIN musaOrgs ON musaOrgs.org_id=musaMusic.org_id
        ";
        $this->sql_group="";
        $this->cols_visible=array_merge($this->table_props,["owner"]);
        //$this->cols_visible=["{$this->table_props}",{$this->table_props},"music_id","org_id","storage_id","choirvoice_id","solovoice_id","title","subtitle","yearOfComp","movements","notes","serial_number","publisher","identifier","org_name","org_info","org_status_code","org_created","owner"]
        $this->cols_searchable=$this->table_props;
        $this->cols_edit=$this->cols_searchable;
        $this->order = $this->table_props[0];
        // ------------------------------------------------------
        //$this->format=['container'];
        $this->feature['create']=['button'=>New Button('create')];
        //$this->feature['export_excel']=['button'=>New Button('export_excel')];
    }
    function base_on_class($classname){
        $ci=$classname::classinfo();
        $this->table_list=str_replace("musa","musaMusic",$ci['TABLE_MAIN']);
        $this->table_name=$ci['TABLE_MAIN'];
        $this->table_key=$ci['TABLE_KEY'];
        $this->table_props=$ci['TABLE_PROPS'];
        $this->sql_select="{$this->table_name}.*,musaMusic.*,musaOrgs.*";
        $this->sql_body="
        ,IF(musaMusic.org_id={$this->org_id},1,0) as owner
        FROM {$this->table_name} 
        LEFT JOIN {$this->table_list} ON {$this->table_list}.{$this->table_key}={$this->table_name}.{$this->table_key}
        LEFT JOIN musaMusic ON musaMusic.music_id={$this->table_list}.music_id
        LEFT JOIN musaOrgs ON musaOrgs.org_id=musaMusic.org_id
        ";
        $this->sql_group="";
        $this->cols_visible=array_merge($this->table_props,["owner"]);
        $this->cols_searchable=$this->table_props;
        $this->cols_edit=$this->cols_searchable;
        $this->order = $this->table_props[0];
        switch($classname){
            case 'Person':
                $this->table_list=['musaMusicComposers','musaMusicArrangers','musaMusicAuthors'];
                $this->sql_body=",IF(musaMusic.org_id={$this->org_id},1,0) as owner
FROM musaPersons 
LEFT JOIN musaMusicComposers ON musaMusicComposers.person_id=musaPersons.person_id
LEFT JOIN musaMusicArrangers ON musaMusicArrangers.person_id=musaPersons.person_id
LEFT JOIN musaMusicAuthors ON musaMusicAuthors.person_id=musaPersons.person_id
LEFT JOIN musaMusic ON (musaMusic.music_id=musaMusicArrangers.music_id) OR (musaMusic.music_id=musaMusicComposers.music_id)  OR (musaMusic.music_id=musaMusicAuthors.music_id)
LEFT JOIN musaOrgs ON musaOrgs.org_id=musaMusic.org_id
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
            <div class='col'>";
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
        //pa($this->table->table);

        $r=$this->header_table();;
        if($this->table->table){
            $r.="<table class='table table-striped table-sm table-bordered border'><thead class='thead-dark'><tr>";
            foreach ($this->cols_visible as $col) $r.=$this->header_col($col);
            $r.="<th style='width:5em;'>Action</th></tr></thead><tbody>";
            foreach ($this->table->table as $i) {
                $r.="<tr>";
                foreach ($this->cols_visible as $col) {
                    $r.="<td>".$i[$col]."</td>";
                }
                $r.="<td>
                <a href='?$this->page_uri&edit&id=".$i[$this->table_key]."' title='Redigera' data-toggle='tooltip'><i class='fa fa-edit'></i></a>
                &nbsp| <a href='?$this->page_uri&delete&id=".$i[$this->table_key]."' data-toggle='tooltip' ".confOp('delete')."</a>
                </td></tr>";
            }
            $r.="</tbody></table>";
            $r.=$this->pagination();
        } else{
            $r.="<p class='lead'><em>Hittar inget.</em></p>";
        }
        return $this->html=$r;
    }
    
    static function gen_input($c){
        if(empty($c['value'])) $c['value']=null;
        $req=($c['Null']=='NO')?"required":"";
        $dis=($c['Key']=='PRI')?"disabled":"";
        $errmsg=(!empty($c['errmsg']))?$c['errmsg']:"";
        $t=explode("(",$c['Type']);
        $r="<div class='form-group'><label>$c[header]</label>";
        switch($t[0]){
            case 'text':
                $lines = substr_count($c['value'], "\n") + 1;
                //$lines=4;
                $r.="<textarea name='$c[Field]' class='form-control' rows='$lines'>$c[value]</textarea>";
                break;
            case 'float':
            case 'int':
                $r.="<input type='number' name='$c[Field]' class='form-control' value='$c[value]' $req $dis>";
                break;
            case 'varchar':
            default:
                $r.="<input type='text' name='$c[Field]' class='form-control' value='$c[value]' $req $dis>";
                break;
        }
        $r.="<span class='form-text'>$errmsg</span></div>";
        return $r;
    }
    

    function create(){
        $this->init();
        $cols=Columns::edit($this->cols_edit);
        
        $r="<h2></h2>
        <form action='' method='post'  class='needs-validation' novalidate>
        ";
        foreach($cols as $c) $r.=Crud::gen_input($c);
        $r.="<input type='hidden' name='table_key' value='$this->table_key'/>";
        $r.="<button type='submit' name='bt_save' class='btn btn-success'><i class='fa fa-edit'></i> Spara</button>";
        $r.="&nbsp;<a href='?$this->page_uri' class='btn btn-secondary fcommon' title='Avbryt'><i class='fa fa-undo'></i> Avbryt</a>
        </form>";
        return $this->html=$r;
    }
    function insert(){
        foreach($this->cols_edit as $i) $rec[$i]=(!empty($_REQUEST[$i]))?trim($_REQUEST[$i]):null;
        $this->db->insert($this->table_name,$rec);
        setMessage('Skapad');
        header("location: ?$this->page_uri");
        exit;
    }

    

    function edit(){
        $this->init();
        $key=["$this->table_key"=>$_REQUEST['id']];
        $rec=$this->db->getUnique($this->table_name,$key);
        //$this->cols_edit=array_keys($rec);
        //pa($this->cols_edit);
        $cols=Columns::edit($this->cols_edit);
        //pa($cols);

        // get form values
        foreach($cols as $i=>$c) $cols[$i]['value']=(!empty($_REQUEST[$i]))?trim($_REQUEST[$i]):$rec[$i];
        
        $r="<h2></h2>
        <form action='' method='post'  class='needs-validation' novalidate>
        ";

        foreach($cols as $c) $r.=Crud::gen_input($c);
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
        foreach($this->cols_edit as $i) $rec[$i]=(!empty($_REQUEST[$i]))?trim($_REQUEST[$i]):null;
        $this->db->update($this->table_name,$rec,$key);
        setMessage('Uppdaterad');
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
        $this->page_uri=(!empty($_GET['list']))?"list=$_GET[list]":null;

        if(isset($_POST['bt_update'])) $this->update();
        else if(isset($_POST['bt_save'])) $this->insert();
        else if(isset($_REQUEST['edit'])) $this->edit();
        else if(isset($_REQUEST['create'])) $this->create();
        else if(isset($_REQUEST['delete'])) $this->delete();
        //if(isset($_REQUEST['delete']) $this->delete();
        //if(isset($_REQUEST['read']) $this->read();
        //if(isset($_REQUEST['create']) $this->create();
        else $this->list();
    }
}

