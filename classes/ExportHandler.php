<?php

require_once __DIR__ . "/Log.php";

class ExportHandler
{
    public static function getExport($date, $type)
    {
        $hour = (int) (new DateTime())->format("H");
        // ID file exports are not published by TMDB until 8 AM
        if ($hour < 8) {
            $date->sub(new DateInterval('P1D'));
        }
        $dateString = $date->format("m_d_Y");

        $url = "http://files.tmdb.org/p/exports/$type" . "_ids_$dateString.json.gz";
        $gz = $type . "_data/" . basename($url);
        if (!file_exists($type . "_data")) {
            Log::writeInfo("Creating directory $type" . "_data");
            mkdir($type . "_data", 0777, true);
        }
        try {
            self::getFile($url, $gz);
        } catch (\Exception $e) {
            $date->sub(new DateInterval('P1D'));
            $dateString = $date->format("m_d_Y");
            $url = "http://files.tmdb.org/p/exports/$type" . "_ids_$dateString.json.gz";
            Log::writeWarning($e->getMessage() . ", trying to get $url");
            $gz = $type . "_data/" . basename($url);
            self::getFile($url, $gz);
        }
        
        self::unzip($gz, $type . "_data/$type" . "_id_list.json");
    }

    private static function getFile($url, $dest)
    {
        $fileName = basename($url);
        if (file_exists($dest)) {
            Log::writeInfo("File $dest already exists, skipping download");
            return;
        }
        Log::writeInfo("Getting file $url");
        $export = file_get_contents($url);
        if (!$export) {
            throw new Exception("Failed to download today's export");
        }
        if (!file_put_contents($dest, $export)) {
            $message = "Failed to download list file $fileName";
            Log::writeError($message);
            throw new Exception($message);
        }
    }

    private static function unzip($gz, $dest)
    {
        if (file_exists($dest)) {
            Log::writeInfo("File $dest already exists, skipping decompression");
            return;
        }
        $gz = gzopen($gz, 'rb');
        if (!$gz) {
            $message = "Could not open gzip file";
            Log::writeError($message);
            throw new UnexpectedValueException($message);
        }

        $dest = fopen($dest, 'wb');
        if (!$dest) {
            gzclose($gz);
            $message = "Could not open destination file";
            Log::writeError($message);
            throw new UnexpectedValueException($message);
        }

        Log::writeInfo("Unzipping file $gz");
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
