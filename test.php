<?php
require_once 'environment.php';
require_once 'music.php';

//pa(Music::delete(1));
pa(Music::list_all('erik'));
$m= new Music(3);
//pa(json_encode($m,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
//pa($m->json());
//pa($m);
//pa($db->getColInfo(null));
pa($m->json());
//die;
//$m->org_id=$user->current_org_id();
$m= new Music;
$m->org_id=2;
$m->title="Happy";

$m->arrangers=[New Person("Thomas"),New Person("Erik",'MALE')];
$m->composers=[New Person(["person_id"=>3,"family_name"=>"Larsson"],'MALE','Sverige'),New Person("Erik")];
$m->composers=[New Person("Larsson",'MALE','Sverige'),New Person("Erik")];
pa($m);
$mid=$m->store();
print($mid);
pa($m);
pa(Person::delete(21));
