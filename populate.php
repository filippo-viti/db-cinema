<?php
require_once __DIR__ . '/classes/CinemaData.php';
require_once __DIR__ . "/classes/Log.php";

Log::initialize();
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
echo "Inserting into table Film, Recita_In and Colonne_Sonore...\n";
$cinemaData->insertDataFilm();
echo "Inserting into table Attori...\n";
$cinemaData->insertDataAttori();
echo "Inserting into table Ha_Vinto...\n";
$cinemaData->insertDataHaVinto();
echo "Inserting into table Premi...\n";
$cinemaData->insertDataPremi();
echo "Inserting into table Musicisti...\n";
$cinemaData->insertDataMusicisti();
// TODO reset FOREIGN_KEY_CHECKS
echo "Done\n";