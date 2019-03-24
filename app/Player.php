<?php
namespace rbwebdesigns\fill_in_the_blanks;

class Player
{

    public $username;
    public $ip;
    public $isGameHost = false;
    public $isActive; // has the player disconnected?
    public $status;
    public $score;

    /** @var rbwebdesigns\fill_in_the_blanks\Card[] white cards in hand */
    public $cards;

    /** @var int[] white cards that have been submitted for this round */
    public $cardsInPlay;

    /** @var rbwebdesigns\fill_in_the_blanks\Game */
    protected $game;

    /** @var ConnectionInterface */
    protected $connection;

    /**
     * class Player constructor
     */
    public function __construct($connection, $game)
    {
        $this->connection = $connection;
        $this->isActive = true;
        $this->status = Game::STATUS_CONNECTED;
        $this->game = $game;
        $this->reset();
    }

    /**
     * Get the connection object
     * 
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Change the connection object
     * 
     * @param ConnectionInterface $connection
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Remove cards from player hand
     * 
     * @param int[] $cards
     */
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

    /**
     * Player has submitted white cards - update
     * 
     * @param int[] $cards
     */
    public function playCards($cards) {
        $this->status = Game::STATUS_CARDS_CHOSEN;
        $this->cardsInPlay = $cards;
        $this->removeCards($cards);
    }

    /**
     * Reset player data for new game
     */
    public function reset() {
        $this->cards = [];
        $this->score = 0;
        $this->cardsInPlay = [];
    }

    /**
     * Set the player as inactive
     */
    public function setInactive() {
        $this->status = Game::STATUS_DISCONNECTED;
        $this->isActive = false;

        // Give the disconnected player their cards back incase they re-connect
        $this->returnCardsToPlayer();
    }

    /**
     * Return all the cards in play to the players hand
     */
    public function returnCardsToPlayer()
    {
        // This is a bit messy as we're currently storing cards
        // in play as an array of integers rather than full cards
        foreach ($this->cardsInPlay as $cardId) {
            $this->cards[] = $this->game->answerCardManager->getAnswerCard($cardId);
        }
        $this->cardsInPlay = [];
    }
}