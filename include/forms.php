<?php 
// ------------------------------------------------------
// get global columns info
$columns=json_decode(file_get_contents('columns.json',true),true);
// ------------------------------------------------------
function get_col_info($c){
    global $columns,$db;
    $i=$columns[$c];
    if(empty($i['errmsg'])) $i['errmsg']='';
    if(empty($i['sqltype'])) $i['sqltype']=$db->getColInfo($c)['Type'];
    if(empty($i['name'])) $i['name']=$db->getColInfo($c)['Field'];
    if(empty($i['header'])) $i['header']=$db->getColInfo($c)['Field'];
    if(empty($i['required'])) $i['required']=($db->getColInfo($c)['Null']=='NO')?1:0;
    if(empty($i['sqltype'])) {
        print("</br>Missing information about column: $c</br>");
    }
    return($i);
}

function gen_input($c){
    $i=get_col_info($c);
    if(!empty($i['required'])) $rec="required"; else $rec="";
    $t=explode("(",$i['sqltype']);
    switch($t[0]){
        case 'text':
            $lines = substr_count($i['value'], "\n") + 1;
            print('
            <div class="form-group">
            <label>'.$i['header'].'</label>
            <textarea name="'.$i['name'].'" class="form-control" rows="'.$lines.'">'.$i['value'].'</textarea>
            <span class="form-text">'.$i['errmsg'].'</span>
            </div>');
            break;
        case 'float':
        case 'int':
            print('
            <div class="form-group">
            <label>'.$i['header'].'</label>
            <input type="number" name="'.$i['name'].'" class="form-control" value="'.$i['value'].'" '.$rec.'>
            <span class="form-text">'.$i['errmsg'].'</span>
            </div>');
            break;
        case 'varchar':
        default:
            print('
            <div class="form-group">
            <label>'.$i['header'].'</label>
            <input type="text" name="'.$i['name'].'" class="form-control" value="'.$i['value'].'" '.$rec.'>
            <span class="form-text">'.$i['errmsg'].'</span>
            </div>');
            break;
    }
}

function gen_select($c){
    $i=get_col_info($c);
    if(!empty($i['required'])) $rec="required"; else $rec="";
    print('
    <div class="form-group">
    <label>'.$i['header'].'</label>
    <select class="form-control" name="'.$i['name'].'" '.$rec.'>
    <option value="">Välj från listan:</option>');
    foreach($i['select'] as $k => $v){
        if($i['value']==$k) $sel='selected'; else $sel='';
        print("<option value='$k' $sel>$v</option>");
    }
    print('</select></div><div id="post_select_'.$i['name'].'"></div>');
}

function gen_mselect($c){
    $i=get_col_info($c);
    print('
    <div class="form-group">
    <label>'.$i['header'].' (flerval)</label>
    <select multiple class="form-control" name="'.$i['name'].'[]" size="'.count($i['select']).'">');
    foreach($i['select'] as $k => $v){
        if(in_array($k,$i['value'])) $sel='selected'; else $sel='';
        print("<option value='$k' $sel>$v</option>");
    }
    print('</select></div>');
}

function gen_checks($c){
    $i=get_col_info($c);
    $cv=$i['value'];
    $mc="";if(count($i['select'])>1) $mc=" (flerval)";
    if(!is_array($cv)) $cv=[$cv];
    if(empty($i['errmsg'])) $i['errmsg']='';
    print('
    <div class="form-group">
    <label>'.$i['header'].$mc.'</label>');
    foreach($i['select'] as $k => $v){
        $sel='';
        if(!empty($i['value'])) if(in_array($k,$cv)) $sel='checked';
        print('
        <div class="form-check">
        <input class="form-check-input" type="checkbox" name="'.$i['name'].'[]" value="'.$k.'" '.$sel.' >
        <label class="form-check-label">'.$v.'</label></div>');
    }
    print('</div>');
}

// ------------------------------------------------------
// Restore form with old data
// ------------------------------------------------------
function rqv($name,$data=null) {
    if (isset($_REQUEST[$name])) $r=$_REQUEST[$name];
    else $r='';
    return htmlspecialchars($r);
}

/*
function fv($data,$name) {
    $r='';
    if (isset($_REQUEST[$name])) $r=$_REQUEST[$name];
    else if($data) if (!empty($data[$name])) $r=$data[$name];
    return htmlspecialchars(trim($r));
}
*/

?>