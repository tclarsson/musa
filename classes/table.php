<?php 
trait table_common {
    function render() {
        print($this->html());
    }
    function json() {
        return json_encode($this,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    }
    function html(){
        return "<h4>".__CLASS__.":Not implemented.</h4>";
    }
    function _test(){
        print("<h4>Test of: ".__CLASS__.".</h4>");
        print($this->json());
    }

}

// ------------------------------------------------------
// ------------------------------------------------------


class Table {
    use table_common;
    const LINES_PER_PAGE=20;
    protected $db;

    public $no_of_records_per_page = self::LINES_PER_PAGE;
    public $pageno=1;
    public $pages;

    public $sql_select="*";
    public $sql_body="";
    public $sql_group="";
    public $sql_where="WHERE 1";

    function __construct(){
        global $db;
        $this->db=$db;
    }

    function _info(){
        $sql = "SELECT * $this->sql_body LIMIT 1,1";
        $r=$this->db->getRecFrmQry($sql);
        if(!empty($r)) {
            pa('$crud->cols_visible='.json_encode(array_keys($r[0])).';');
        }
    }
    function init(){
        // get col info
        if(empty($this->cols)) $this->cols=Columns::cols($this->cols_visible);
       

        //page
        $this->pageno=(isset($_GET['pageno']))?$_GET['pageno']:1;


        //search
        $this->cols_searchable=(!empty($this->cols_searchable))?$this->cols_searchable:$this->cols_visible;
        $this->search=(isset($_REQUEST['search']))?$_REQUEST['search']:"";

        //Column sorting on column name
        $this->order=(isset($_REQUEST['order']))?$_REQUEST['order']:"";
        if(!in_array($this->order, $this->cols_visible)) $this->order=$this->cols_visible[0];

        //Column sort order
        $sortBy = array('asc', 'desc'); 
        $this->sort=(isset($_REQUEST['sort']))?$_REQUEST['sort']:"";
        if(!in_array($this->sort, $sortBy)) $this->sort= $sortBy[0];
    }


    function header_col($col){
        $linkopt="";
        if(!empty($this->search)) $linkopt.="&search={$this->search}";
        $so=($this->sort=="desc")?"asc":"desc";
        $si="";
        if($this->order==$col) {
            $si=($this->sort=="desc")?"<i class='fas fa-sort-down'></i>":"<i class='fas fa-sort-up'></i>";
            }
        $r="<th><a href=?$this->page_uri&order=$col&sort=$so$linkopt>".$this->cols[$col]['header']." $si</th>";
        return $r;
    }
    function pagination(){
        $linkopt="";
        if(!empty($this->search)) $linkopt.="&search={$this->search}";
        if(!empty($this->order)) $linkopt.="&order={$this->order}";
        if(!empty($this->sort)) $linkopt.="&sort={$this->sort}";

        $r="";
        $r.="Sida {$this->pageno} av {$this->pages}".((!empty($this->rows))?" (totalt {$this->rows} rader)":"")."<br/>";
        if($this->pages>1) {
            $r.="<ul class='pagination' align-right>
            <li class='page-item".(($this->pageno <= 1)?" disabled":"")."'>
            <a class='page-link' href='?$this->page_uri&pageno=1$linkopt' title='Gå till början' data-toggle='tooltip'><i class='fa fa-step-backward'></i></a></li>
            <li class='page-item".(($this->pageno <= 1)?" disabled":"")."'>
            <a class='page-link' href='?$this->page_uri&pageno=".($this->pageno - 1)."$linkopt'><i class='fa fa-backward'></i></a></li>
            <li class='page-item".(($this->pageno >= $this->pages)?" disabled":"")."'>
            <a class='page-link' href='?$this->page_uri&pageno=".($this->pageno + 1)."$linkopt'><i class='fa fa-forward'></i></a></li>
            <li class='page-item".(($this->pageno >= $this->pages)?" disabled":"")."'>
            <a class='page-link' href='?$this->page_uri&pageno={$this->pages}$linkopt' title='Gå till slutet' data-toggle='tooltip'><i class='fa fa-step-forward'></i></a></li>
            </ul>";
        }
        return $r;
    }

    function header_table($title){
        $r="";
        $r.="<form action='' method='get'>
        <div class='form-row'>
            <div class='col'><h1>$title</h1></div>
            <div class='col-auto'>
                <input type='text' class='form-control' placeholder='Sök (tryck Enter)' name='search' 
                value='".((!empty($this->search))?htmlspecialchars($this->search):"")."'>
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


    function table(){
        $search = "";
        // only show own?
        if(!empty($this->own)) $search .= $this->own;

        if(!empty($this->search)) {
            $search .= "\nAND CONCAT_WS('#',".implode(",", $this->cols_searchable).") LIKE '%$this->search%'";
        }
       

        // calculate num rows & pages
        $sql = "SELECT COUNT(*) as 'numrows'\n$this->sql_body\n$this->sql_where $search $this->sql_group";
        //pa($sql);
        $r=$this->db->getRecFrmQry($sql);
        if(empty($this->sql_group)) {
            $this->rows = $r[0]['numrows'];
        } else {
            $this->rows = count($r);
        }
        $this->pages = ceil($this->rows / $this->no_of_records_per_page);


        // paging and grouping
        $offset = ($this->pageno-1) * $this->no_of_records_per_page;
        $sql = "SELECT $this->sql_select\n$this->sql_body\n$this->sql_where $search $this->sql_group\nORDER BY $this->order $this->sort\nLIMIT $offset, $this->no_of_records_per_page";
        $this->sql=$sql;

        // final query
        //pa($sql);
        $this->table=$this->db->getRecFrmQry($sql);
        return $this->table;
    }

    
    function _test(){
        print("<h4>Test of: ".__CLASS__.".</h4>");
        //pa($this->rows());
        pa($this->table());
        pa($this->cols());
        pa($this->json());
    }

}



?>