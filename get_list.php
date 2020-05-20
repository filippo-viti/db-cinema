<?php

function getMovieList()
{
    $currentDate = new DateTime();
    $hour = (int) (new DateTime())->format("H");
    // ID file exports are not published by TMDB until 8 AM
    if ($hour < 8) {
        $currentDate->sub(new DateInterval('P1D'));
    }
    $currentDate = $currentDate->format("m_d_Y");

    $url = "http://files.tmdb.org/p/exports/movie_ids_$currentDate.json.gz";
    $gz = "movie_data/" . basename($url);
    getFile($url, $gz);
    unzip($gz, 'movie_data/movie_id_list.json');
}

function getFile($url, $dest)
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

function unzip($gz, $dest)
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
        throw new UnexpectedValueException('Could not open destination file');
    }

    while (!gzeof($gz)) {
        fwrite($dest, gzread($gz, 4096));
    }

    gzclose($gz);
    fclose($dest);
}

getMovieList();
