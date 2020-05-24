<?php
class Log
{
    private $timestamp;
    private $text;
    private static $directory = "logs";
    private $fileName;

    public function __construct()
    {
        if (!file_exists(self::$directory)) {
            mkdir(self::$directory, 0777, true);
        }
        $this->timestamp = new DateTime();
        $name = $this->timestamp->format("Y-m-d_H:i:s");
        $this->fileName = $name . ".log";
        $this->text = "";
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getText()
    {
        return $this->text;
    }

    public static function getDirectory()
    {
        return self::$directory;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getFullPath()
    {
        return $this->getDirectory() . "/" . $this->getFileName();
    }

    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }

    private function append($message, $type)
    {
        $time = $this->getTimestamp()->format("M d H:i:s");
        $line = "$time [$type] $message\n";
        $this->setText($this->getText() . $line);
        return $this;
    }

    public function writeInfo($message)
    {
        $this->append($message, "Info");
    }

    public function writeError($message)
    {
        $this->append($message, "Error");
    }

    public function writeWarning($message)
    {
        $this->append($message, "Warning");
    }

    public function writeFile()
    {
        file_put_contents($this->getFullPath(), $this->getText());
    }
}
