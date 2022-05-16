<?php
require_once 'environment.php';
require_once 'music.php';

pa("Deleting contents from tables");
$tables=[
    'musaMusic',
    'musaPersons',
    'musaCategories',
    'musaChoirvoices',
    'musaCountries',
    'musaGenderTypes',
    'musaHolidays',
    'musaInstruments',
    'musaLanguages',
    'musaStorages',
    'musaThemes'
];
foreach($tables as $t) {
    $sql="DELETE FROM musa.$t";$db->deleteFrmQry($sql);pa($sql);
}
$o=New Holiday('Midsommar',3);$o->store();
$o=New Holiday('Midsommar',3);$o->store();
$o=New Holiday('Midsommar',3);$o->store();
$o=New Holiday('Midsommar',3);$o->store();
$o=New Holiday('Midsommar',3);$o->store();
$sql="SELECT * from musaHolidays";
$r=$db->getRecFrmQry($sql);
pa($r);

$o=New Composer('Mozart',3);$o->store();
$o=New Composer('Mozart',3);$o->store();
$o=New Composer('Mozart',3);$o->store();
$o=New Composer('Mozart',3);$o->store();
$o=New Composer('Mozart',3);$o->store();
$sql="SELECT * from musaPersons";
$r=$db->getRecFrmQry($sql);
pa($r);

$o=New Instrument('Tuba',3);$o->store();
$o=New Instrument('Tuba',3);$o->store();
$o=New Instrument('Tuba',3);$o->store();
$o=New Instrument('Tuba',3);$o->store();
$o=New Instrument('Tuba',3);$o->store();
$sql="SELECT * from musaInstruments";
$r=$db->getRecFrmQry($sql);
pa($r);
die;
pa(Composer::get_constants());die;

$m=New Music(8);
pa($m->json());
die;
Music::_test();
// tests
Gender::_test();
Holiday::_test();
Country::_test();
Person::_test();
Music::_test();
die;


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

