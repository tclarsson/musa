<?php
function infomodal(){
    global $title;
    $p=basename($_SERVER['SCRIPT_NAME']);
    //print_r($_SERVER);
    //print_r($p);
    $h='';
    $cjson='
    <h6>Anpassning JSON</h6>
    Detta är en JSON formaterad sträng som styr anpassningar av <b>registreringsformuläret till aktiviteten</b>, tex när man vill registrera information utöver standardinformationen. 
    <br/>OBS! Om aktiviteten har en angiven "Anpassning JSON" används INTE aktivitetstypens "Anpassning JSON".
    <br/>OBS! "Anpassning JSON" används inte i registreringar med aktivitetstyp "TENNISSKOLAN" (eftersom dessa redan är anpassade).
    <p/>Exempel: För att fråga efter frivilliga, där användaren kan välja 1 alternativ eller inget alls, ser den ut så här:
    <br/><i>{"input_json":[{"name":"funktionär","title":"Vill du vara funtionär?","options":["Ja","Nej"]}]}</i>
    <p/>Exempel: Lägger man till <i>"required":1</i> MÅSTE användaren välja ett alternativ - så här:
    <br/><i>{"input_json":[{"name":"funktionär","title":"Vill du vara funtionär?"<b>,"required":1</b>,"options":["Ja","Nej","Kanske"]}]}</i>
    <p/>Exempel: För frivilligt flerval används <i>"checkbox":1</i> - så här:
    <br/><i>{"input_json":[{"name":"favoritfärger","title":"Välj färg(er)"<b>,"checkbox":1</b>,"options":["Röd","Grön","Blå","Gul"]}]}</i>
    <p/>Exempel: Man kan kombinera flera val så här:
    <br/><i>{"input_json":[<br/>
    {"name":"fruktval","title":"Välj frukt","options":["Äpple","Banan","Päron"]},<br/>
    {"name":"funktionär","title":"Vill du vara funtionär?","required":1,"options":["Ja","Nej","Kanske"]},<br/>
    {"name":"favoritfärger","title":"Välj färg(er)","checkbox":1,"options":["Röd","Grön","Blå","Gul"]}<br/>
    ]}</i>
    ';
    switch($p){
        case 'eventtypes_update.php':
            $h=$title;
            $b='
            <p>Här skapas eller uppdateras en aktivitetstyp</p>
            <p>Aktivitetstyp används i en eller flera specifika aktiviteter. Aktivitetstyp bidrar med en standard beskrivning av aktiviteten, grundavgift och anpassning:</p>
            <ul>
            <li>Aktivitetstyp: Aktivitetstypens referenskod. Använd en kort och unik kod utan mellanrum och i VERSALER.</li>
            <li>Aktivitet: Aktivitetens namn.</li>
            <li>Beskrivning: En generell beskrivning av aktivitetstypen.</li>
            <li>Avgift: Grundavgiften för att deltaga i aktiviteten. Normalt tillkommer aktivitetens avgifter för valda klasser.</li>
            </ul>'.$cjson;
            break;

        case 'events_update.php':
          $h=$title;
          $b='
          <p>Här skapas en ny eller uppdateras en specifik aktivitet</p>
          <ul>
          <li>Aktivitetstyp: Välj en aktivitetstyp för aktiviteten. Aktivitetstyp bidrar med en standard beskrivning av aktiviteten, avgift och anpassning. Saknas lämplig aktivitetstyp, måste denna skapas separat.</li>
          <li>Aktivitetsstatus: Styr om aktiviteten är synlig eller om anmälan eller redigering är blockerad för användarna.</li> 
          <li>Start: Detta är aktivitetens start datum och tid, tex "2021-04-17 14:00:00"</li>
          <li>Registrering öppnar: Detta datum avgör när medlemmar kan anmäla sig till en aktivitet.</li>
          <li>Registrering stängs: Detta datum avgör sista dag som medlemmar kan registrera/anmäla sig till en aktivitet. Efter det tas ingen ny/ändring av anmälan emot. Detta datum är också förfallodatum för ev inbetalning av aktivitetens avgift.</li>
          <li>Max antal klasser: Anger max antal klasser som aktiviteten tillåter medlemmen att anmäla sig till. Anges inget värde är default 1 klass.</li>
          <li>Information: Aktivitetsspecifik text som visas i anmälninsformuläret. Kan lämnas tomt.</li>
          <li>Kontaktperson: Välj en medlem som skall vara kontaktperson för aktiviteten. Kan lämnas tomt.</li>
          <li>Kontaktinformation: Text om hur man tar kontakt. Används som alternativ till kontaktperson. Kan lämnas tomt.</li>
          <li>Klasstyp: Välj de klasser som skall vara valbara/tillgängliga i aktiviteten. Alla tillgängliga klasser is systemet visas. Man kan välja inga, en eller flera. Finns inte lämplig klass, måste den skapas i systemet först.</li>
          </ul>'.$cjson;
          break;

      }
    if(!empty($h)) print('
    <div class="modal fade" id="infoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header modal-header-title bg-info">'.$h.' 
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">'.$b.'</div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Stäng</button></div>
      </div>
    </div>
  </div>
      ');

}
infomodal();
?>


