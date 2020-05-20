<?php
require_once __DIR__ . '/data.php';

$cinemaData = new CinemaData();
echo "Emptying tables...\n";
$tables = $cinemaData->getTables();
foreach($tables as $table) {
    echo ">>$table\n";
    $cinemaData->emptyTable($table);
}