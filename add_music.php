<?php
require_once 'environment.php';
require_once 'music.php';


Music::_test();
// tests
Gender::_test();
Holiday::_test();
Country::_test();
Person::_test();
Music::_test();
//die;

//pa(Music::delete(1));
//$m= new Music(3);
//pa(json_encode($m,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
//pa($m->json());
//pa($m);
//pa($db->getColInfo(null));
//pa($m->json());
//die;
//$m->music_id_owner=$user->current_org_id();
$m= new Music;
$m->music_id_owner=2;
$m->title="Happy";
$m->arrangers=[New Person("Thomas"),New Person("Erik",'MALE')];
$m->composers=[New Person(["person_id"=>3,"family_name"=>"Larsson"],'MALE','Sverige'),New Person("Erik")];
$m->composers=[New Person("Larsson",'MALE','Sverige'),New Person("Erik")];
$m->store();
pa($m);
$m= new Music;
$m->music_id_owner=2;
$m->title="Dancing Queen";
$m->arrangers=[New Person("Thomas"),New Person("Erik",'MALE')];
$m->composers=[New Person(["person_id"=>3,"family_name"=>"Larsson"],'MALE','Sverige'),New Person("Erik")];
$m->composers=[New Person("Larsson",'MALE','Sverige'),New Person("Erik")];
$m->store();
pa($m);
//pa(Person::delete(21));

