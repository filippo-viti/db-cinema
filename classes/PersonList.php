<?php
require_once __DIR__ . '/ExportHandler.php';
require_once __DIR__ . '/ExportList.php';

class PersonList extends ExportList
{
    // set to negative if you want to get all movies
    private $MAX_PEOPLE = 100;

    public function __construct()
    {
        parent::__construct();
        ExportHandler::getExport($this->timestamp, "person");
        // list is an array of json objects
        $json = "person_data/person_id_list.json";
        $file = file($json);
        Log::writeInfo("Getting " . $this->MAX_PEOPLE . "movies from $json");
        $this->list = ExportHandler::parseJSON($file, $this->MAX_PEOPLE);
    }
}
