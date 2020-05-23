<?php
require_once __DIR__ . '/classes/CinemaData.php';

echo "Downloading valid ID list...\n";
$cinemaData = new CinemaData();
echo "Emptying tables...\n";
$tables = $cinemaData->getTables();
foreach ($tables as $table) {
    echo ">>$table\n";
    $cinemaData->emptyTable($table);
}
echo "Inserting into table Generi...\n";
$cinemaData->insertDataGeneri();
echo "Inserting into table Film...\n";
$cinemaData->insertDataFilm();
echo "Inserting into table Attori...\n";
$cinemaData->insertDataAttori();