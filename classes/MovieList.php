<?php
require_once __DIR__ . '/ExportHandler.php';
require_once __DIR__ . '/ExportList.php';

class MovieList extends ExportList
{
    // set to negative if you want to get all movies
    private $MAX_MOVIES = 100;

    public function __construct()
    {
        parent::__construct();
        ExportHandler::getExport($this->timestamp, "movie");
        // list is an array of json objects
        $json = "movie_data/movie_id_list.json";
        $file = file($json);
        Log::writeInfo("Getting " . $this->MAX_MOVIES . "movies from $json");
        $this->list = ExportHandler::parseJSON($file, $this->MAX_MOVIES);
    }
}
