<?php
namespace rbwebdesigns\fill_in_the_blanks;

class Player
{

    public $username;
    public $ip;
    public $isGameHost = false;
    public $isActive;
    public $status;
    public $score;

    public $cards;
    protected $connection;
    public $cardsInPlay;

    public function __construct($connection)
    {
        $this->connection = $connection;
        $this->isActive = true;
        $this->status = 'Connected';
        $this->reset();
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
                if ($playerCard->id == $card) {
                    array_splice($this->cards, $c, 1);
                    break;
                }
                $c++;
            }
        }
    }

    public function reset() {
        $this->cards = [];
        $this->score = 0;
        $this->cardsInPlay = [];
    }
}