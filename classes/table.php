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