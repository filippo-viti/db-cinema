<?php

class ExportHandler
{
    public static function getExport($date, $type)
    {
        $hour = (int) (new DateTime())->format("H");
        // ID file exports are not published by TMDB until 8 AM
        if ($hour < 8) {
            $date->sub(new DateInterval('P1D'));
        }
        $date = $date->format("m_d_Y");

        $url = "http://files.tmdb.org/p/exports/$type" . "_ids_$date.json.gz";
        $gz = $type . "_data/" . basename($url);
        if (!file_exists($type . "_data")) {
            mkdir($type . "_data", 0777, true);
        }
        self::getFile($url, $gz);
        self::unzip($gz, $type . "_data/$type" . "_id_list.json");
    }

    private static function getFile($url, $dest)
    {
        $fileName = basename($url);
        if (file_exists($dest)) {
            echo "File $dest already exists, skipping download\n";
            return;
        }
        if (!file_put_contents($dest, file_get_contents($url))) {
            throw new Exception("Failed to download list file $fileName");
        }
    }

    private static function unzip($gz, $dest)
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

    public static function parseJSON($json, $maxLines)
    {
        foreach ($json as $key => $line) {
            if ($key == $maxLines) {
                break;
            }
            $object = json_decode($line, true);
            $objectArray[] = $object;
        }
        return $objectArray;
    }
}