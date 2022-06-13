<?php
require_once 'environment.php';
require_once 'music.php';
//$r=$db->executeQry("DROP TABLE IF EXISTS mt0");
$sql="CREATE TEMPORARY TABLE mt0
SELECT musaMusic.*
,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',COMPOSER.first_name,COMPOSER.family_name) SEPARATOR ', ') as COMPOSER
,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',ARRANGER.first_name,ARRANGER.family_name) SEPARATOR ', ') as ARRANGER
,GROUP_CONCAT(DISTINCT CONCAT_WS(' ',AUTHOR.first_name,AUTHOR.family_name) SEPARATOR ', ') as AUTHOR
FROM musaMusic 
LEFT JOIN musaOrgs ON musaOrgs.org_id=musaMusic.music_id_owner
LEFT JOIN musaOrgStatusTypes ON musaOrgStatusTypes.org_status_code=musaOrgs.org_status_code
LEFT JOIN musaChoirvoices ON musaChoirvoices.choirvoice_id=musaMusic.choirvoice_id
LEFT JOIN musaStorages ON musaStorages.storage_id=musaMusic.storage_id
LEFT JOIN musaMusicComposers ON musaMusicComposers.music_id=musaMusic.music_id
LEFT JOIN musaPersons COMPOSER ON COMPOSER.person_id=musaMusicComposers.person_id
LEFT JOIN musaMusicAuthors ON musaMusicAuthors.music_id=musaMusic.music_id
LEFT JOIN musaPersons AUTHOR ON AUTHOR.person_id=musaMusicAuthors.person_id
LEFT JOIN musaMusicArrangers ON musaMusicArrangers.music_id=musaMusic.music_id
LEFT JOIN musaPersons ARRANGER ON ARRANGER.person_id=musaMusicArrangers.person_id
GROUP BY musaMusic.music_id;";
$r=$db->executeQry($sql);
$r=$db->getRecFrmQry("select * from mt0");
pa($r);
$r=$db->getRecFrmQry("select * from mt0");
pa($r);
die;



$v="34";
if(is_numeric($v)) pa("Numeric");
pa(($v +0));
die;

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

