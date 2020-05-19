<?php
require_once __DIR__ . '/data.php';

$cinemaData = new CinemaData();
echo "Emptying tables...\n";
$tables = ["Attori", "Colonne_Sonore", "Film", "Genere", "Ha_Vinto", "Musicisti", "Premi", "Recita_In"];
foreach($tables as $table) {
    echo ">>$table\n";
    $cinemaData->emptyTable($table);
}