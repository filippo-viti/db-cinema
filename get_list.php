<?php

class MovieList
{
    private $list;
    private $timestamp;

    public function __construct()
    {
        $this->timestamp = new DateTime();
        $this->getExport();
        // list is an array of json objects
        $file = file("movie_data/movie_id_list.json");
        $this->list = $this->parseJSON($file);
    }

    public function getList()
    {
        return $this->list;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    private function getExport()
    {
        $currentDate = $this->getTimestamp();
        $hour = (int) (new DateTime())->format("H");
        // ID file exports are not published by TMDB until 8 AM
        if ($hour < 8) {
            $currentDate->sub(new DateInterval('P1D'));
        }
        $currentDate = $currentDate->format("m_d_Y");

        $url = "http://files.tmdb.org/p/exports/movie_ids_$currentDate.json.gz";
        $gz = "movie_data/" . basename($url);
        $this->getFile($url, $gz);
        $this->unzip($gz, 'movie_data/movie_id_list.json');
    }

    private function getFile($url, $dest)
    {
        // TODO check if file is outdated
        $fileName = basename($url);
        if (file_exists($dest)) {
            echo "File $dest already exists, skipping download\n";
            return;
        }
        if (!file_put_contents($dest, file_get_contents($url))) {
            throw new Exception("Failed to download movie list $fileName");
        }
    }

    private function unzip($gz, $dest)
    {
        if (file_exists($dest)) {
            echo "File $dest already exists, skipping decompression\n";
            return;
        }
        $gz = gzopen($gz, 'rb');
        if (!$gz) {
            throw new UnexpectedValueException('Could not open gzip file');
        }

        $dest = fopen($dest, 'wb');
        if (!$dest) {
            gzclose($gz);
            throw new UnexpectedValueException(
                'Could not open destination file'
            );
        }

        while (!gzeof($gz)) {
            fwrite($dest, gzread($gz, 4096));
        }

        gzclose($gz);
        fclose($dest);
    }

    private function parseJSON($json)
    {
        $objectArray = [];
        foreach ($json as $line) {
            $object = json_decode($line, true);
            $objectArray[] = $object;
        }
        return $objectArray;
    }
}
