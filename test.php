<?php
require_once 'environment.php';
require_once 'music.php';

$m= new Music(3);
print(json_encode($m));
die;
//$m->org_id=$user->current_org_id();
$m->org_id=2;
$m->title="SingSingSing";

$m->arrangers=[New Person("Thomas"),New Person("Erik",'Male')];
$m->composers=[New Person(["person_id"=>3,"family_name"=>"Larsson"],'Male','Sverige'),New Person("Erik")];
$m->composers=[New Person("Larsson",'MALE','Sverige'),New Person("Erik")];
pa($m);
$mid=$m->store();
print($mid);
pa($m);

