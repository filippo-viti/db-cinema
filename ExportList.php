<?php
class ExportList
{
    protected $list;
    protected $timestamp;

    public function __construct()
    {
        $this->timestamp = new DateTime();
    }

    public function getList()
    {
        return $this->list;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }
}