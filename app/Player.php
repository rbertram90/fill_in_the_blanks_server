<?php
namespace rbwebdesigns\cah_php;

class Player
{

    public $username;
    public $ip;
    public $isGameHost = false;
    public $resourceID;
    public $isActive;

    public $cards;

    public function __construct()
    {
        $this->isActive = true;
    }
}