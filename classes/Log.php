<?php
class Log
{
    private static $timestamp;
    private static $text;
    private static $fileName;
    private static $DIRECTORY = "logs";

    public static function initialize()
    {
        if (!file_exists(self::$DIRECTORY)) {
            mkdir(self::$DIRECTORY, 0777, true);
        }
        self::$timestamp = new DateTime();
        $name = self::$timestamp->format("Y-m-d_H:i:s");
        self::$fileName = $name . ".log";
        self::$text = "";
    }

    public static function getTimestamp()
    {
        return self::$timestamp;
    }

    public static function getText()
    {
        return self::$text;
    }

    public static function getDirectory()
    {
        return self::$DIRECTORY;
    }

    public static function getFileName()
    {
        return self::$fileName;
    }

    public static function getFullPath()
    {
        return self::getDirectory() . "/" . self::getFileName();
    }

    public static function setText($text)
    {
        self::$text = $text;
    }

    private static function append($message, $type)
    {
        $time = self::getTimestamp()->format("M d H:i:s");
        $line = "$time [$type] $message\n";
        file_put_contents(self::getFullPath(), $line, FILE_APPEND);
    }

    public static function writeInfo($message)
    {
        self::append($message, "Info");
    }

    public static function writeError($message)
    {
        self::append($message, "Error");
    }

    public static function writeWarning($message)
    {
        self::append($message, "Warning");
    }
}
