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
    'musaHolidays',
    'musaInstruments',
    'musaLanguages',
    'musaStorages',
    'musaThemes'
];
foreach($tables as $t) {
    $sql="DELETE FROM musa.$t";$db->deleteFrmQry($sql);pa($sql);
}
