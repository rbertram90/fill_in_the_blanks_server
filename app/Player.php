<?php
namespace rbwebdesigns\cah_php;

class Player
{

    public $username;
    public $ip;
    public $isGameHost = false;
    public $isActive;
    public $status;

    public $cards;
    protected $connection;
    public $cardsInPlay;

    public function __construct($connection)
    {
        $this->isActive = true;
        $this->connection = $connection;
        $this->cards = [];
        $this->status = 'connected';
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    public function removeCards($cards) {
        foreach ($cards as $card) {
            $c = 0;
            foreach ($this->cards as $playerCard) {
                if ($playerCard['id'] == $card['id']) {
                    array_splice($this->cards, $c, 1);
                    break;
                }
                $c++;
            }
        }
    }
}