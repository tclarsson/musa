<?php
require_once 'environment.php';
require_once 'music.php';
require_once 'crud_simple.php';
$user->admit();

//$page_nocontainer=true;

// check import
if(!empty($_REQUEST['type'])&&!empty($_REQUEST['cols'])&&!empty($_REQUEST['data'])){
    //$sql="DELETE FROM musa.musaMusic";$db->deleteFrmQry($sql);pa($sql);
    //$sql="DELETE FROM musa.musaInstruments";$db->deleteFrmQry($sql);pa($sql);
    
    $cols=json_decode($_REQUEST['cols'],true);
    $num_import=0;
    foreach(json_decode($_REQUEST['data'],true) as $i=>$r){
        $rec=[];
        foreach($cols as $c=>$ci) $rec[$c]=$r[$ci['odx']];
        //print("<p>".json_encode($rec)."</p>");
        switch($_REQUEST['type']){
            case 'music':
                $o=New Music($rec);
                break;
            case 'person':
                $o=New Person($rec);
                break;
        }
        //pa($o);
        if($o->is_mod()) $num_import++;
        $o->store();
        //if($i>=50) break;
    }
    setMessage("Analyserat $i poster.");
    setMessage("Importerat $num_import poster.");
}

$cpi=new Card('Import av personer eller musik');
$cpi->helpmodal=New Modal("helppage".__LINE__);
$cpi->helpmodal->body="
<h3>Import från fil<h3> 
<p>Här kan du importera data från en komma-separerad (.CSV) fil.</p>
<p>Följande kan importeras:</p>
<ul>
<li>Musik (*)</li>
<li>Personer</li>
</ul>
<p>Not(*): Importera denna data sist om du har olika data att importera.</p>
";
$cpi->body="
<form>
    <div class='row'>
    <label for='files'>Välj CSV-fil för import:</label>
    <input type='file' id='files'  class='form-control' accept='.csv' required />
    </div>
    </br>
    <div class='row'>
    <button type='submit' id='submit-file' class='btn btn-primary'>Läs fil</button>
    </div>
</form>
";

require_once 'header.php';
$cpi->render();
print("<div id='html_info'></div>");
print("<div id='html_result'></div>");
require_once 'footer.php';
?>
<script src="./js/papaparse.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
  
  $('#submit-file').on("click",function(e){
    e.preventDefault();
    $('#files').parse({
        config: {
            delimiter: ";",
            header:false,
            complete: display_results,
        },
        before: function(file, inputElem)
        {
            console.log("Parsing file...", file);
        },
        error: function(err, file)
        {
            console.log("ERROR:", err, file);
        },
        complete: function()
        {
            console.log("Done with all files");
        }
    });
  });
  var cols;
  //------------------------------------------------------------------------------
  function set_cols_info(header){
    var info=`<p>Följande information hittades:</p>
    <table class='table'><thead><tr><th>Hittad</th><th>Namn</th><th>Beskrivning</th></tr>`;
    var odx=0;
    for (const c in cols) {
        cols[c].description=c;
        var i=header.indexOf(c);
        if(i==-1) if(cols[c].alias) for(const a of cols[c].alias) {
            i=header.indexOf(a);
            //console.log(`matching of ${a} results in ${i}`);
            if(i!=-1) break;
        }
        if(i==-1) console.log("Did not find "+c);
        cols[c].idx=i;
        cols[c].odx=odx++;
        if(i!=-1) info+=`<tr><td><i class='fa fa-check'></i></td>`;
        else info+=`<tr><td><i class='fa fa-question'></i></td>`;
        info+=`<td>${c}</td><td>${cols[c].description}</td></tr>`;
        if(i!=-1) delete header[i]; else delete cols[c];
    }
    console.log(header);
    var notused='';
    var as=`alias:[`;
    for (const h in header) {
        notused+=`<li>${header[h]}</li>`;
        as+=`'${header[h]}',`;
    }
    if(notused) info+=`</tr></table><p>Följande information används inte (eller är okänd):</p><ul>${notused}</ul></br>`;
    //as+=`]`;info+=`<p>${as}</p>`;
    return info;
  }

  //------------------------------------------------------------------------------
  function p_all(v){
      if(v) {
        if(v=='#VALUE!') v='';
      } else v='';
    return v.trim();
  }

  function p_person(v){
    const l=p_csl(v);
    const a= l.map(v=>p_usl(v));
    return a; 
  }
  function p_usl(v){
    const p=(v.split('_')); 
    var r={};
    if(p[0]) r.family_name=p[0];
    if(p[1]) r.first_name=p[1];
    return r; 
  }
  function p_csl(v){
    const a=(v.split(',')); 
    //return JSON.stringify(a); 
    return a; 
  }


  //------------------------------------------------------------------------------
  function import_result(type,header,data){
    $("#html_info").html(set_cols_info(header));
    var html='';
    html+=`<form action='' method='post'>
    <button type='submit' id='submit-file' class='btn btn-success'>Importera</button>
    <p>Antal lästa rader: ${data.length}</p>
    <table class='table table-striped table-sm table-bordered border'>
    `;
    html+=`<thead class='thead-dark'><tr>`;
    for (const c in cols) html+=`<th>${c}</th>`;
    html+= "</tr></thead>";
    var res=[];
    var html_row='';
    var item=[];
    var valid=true;
    num_rows=0;
    for(i=1;i<data.length;i++){
        html_row='';
        item=[];
        valid=true;
        for (const c in cols) {
            var v=p_all(data[i][cols[c].idx]);
            if(v) if(cols[c].parse) v=cols[c].parse(v);
            if(cols[c].req) if(!v) valid=false;
            item[cols[c].odx]=v;
            html_row+=`<td>${JSON.stringify(v)}</td>`;
        }
        if(valid) {
            res.push(item);
            html+= `<tr>${html_row}</tr>`;
            num_rows++;
        } else console.log(`Missing required information, dropping record: ${i}`);
        //for(c=0;c<header.length;c++) html+=`<td>${row[c]}</td>`;
    }
    html+= `</table>
    <p>Antal rader som kommer importeras: ${num_rows}</p>
    <input type='hidden' name='type' value='${type}'>
    <input type='hidden' name='cols' value='${JSON.stringify(cols)}'>
    <input type='hidden' name='data' value='${JSON.stringify(res)}'>
    </form>`;
    $("#html_result").html(html);
    console.log(res);
  }
  //------------------------------------------------------------------------------
  function parse_unknown(header,data){
    $("#html_info").html(`
    <h4>Importfilen har okänt innehåll</h4>
    <p>Följande kan importeras:</p>
    <ul>
    <li>Musik (*)</li>
    <li>Personer</li>
    </ul>
    <p>Not(*): Importera denna data sist om du har olika data att importera.</p>
    `);
  }

  //------------------------------------------------------------------------------
  function parse_persons(header,data){
    cols={
        family_name:{req:true},
        first_name:{},
        gender:{},
        country:{alias:['country_name',]},
        date_born:{},
        date_dead:{},
    };
    import_result('person',header,data);
  }

  //------------------------------------------------------------------------------
  function parse_music(header,data){
    cols={
        title:{req:true},
        subtitle:{},
        yearofcomp:{},
        movements:{},
        copies:{},
        notes:{},
        serialnumber:{},
        publisher:{},
        identifier:{},
        storage:{},
        choirvoice:{alias:['choirvoice_name']},

        composers:{parse:p_person,alias:['composer',]},
        arrangers:{parse:p_person,alias:['arranger',]},
        authors:{parse:p_person,alias:['author']},
        solovoices:{parse:p_csl,alias:['solovoice_name']},
        instruments:{parse:p_csl,alias:['instrument_name']},
        languages:{parse:p_csl,alias:['language_name']},
        themes:{parse:p_csl,alias:['theme_name']},
        holidays:{parse:p_csl,alias:['holiday_name']},
        categories:{parse:p_csl,alias:['category','category_name',]},
    };
    import_result('music',header,data);
  }
  
  function display_results(results){
    var data = results.data;
    var header=data[0].map(e => {return e.toLowerCase();});
    if(header.includes('title')) parse_music(header,data);
    else if(header.includes('family_name')) parse_persons(header,data);
    else parse_unknown(header,data);
  }
});
</script>