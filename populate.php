<?php
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/get_list.php';

echo "Downloading valid ID list...\n";
$movieList = new MovieList();
$cinemaData = new CinemaData();
echo "Emptying tables...\n";
$tables = $cinemaData->getTables();
foreach ($tables as $table) {
    echo ">>$table\n";
    $cinemaData->emptyTable($table);
}
