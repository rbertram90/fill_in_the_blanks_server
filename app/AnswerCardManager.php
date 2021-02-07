<?php
namespace rbwebdesigns\fill_in_the_blanks;

/**
 * Class rbwebdesigns\fill_in_the_blanks\AnswerCardManager
 */
class AnswerCardManager
{
    /** @var string[] */
    protected static $answers = [];

    /** @var \rbwebdesigns\fill_in_the_blanks\Card[]  */
    protected $availableCards = [];

    /** @var \rbwebdesigns\fill_in_the_blanks\Game */
    protected $game;

    /**
     * AnswerCardManager constructor
     * 
     * @param \rbwebdesigns\fill_in_the_blanks\Game $game
     */
    public function __construct($game)
    {
        $this->game = $game;        
    }

    /**
     * Get the question cards from text files
     */
    public function buildDeck()
    {
        // Get card data from file(s)
        $answers = [];
        foreach ($this->game->activeCardPacks as $pack) {
            print "Adding answer card pack - " . $pack . PHP_EOL;
            $cards = file_get_contents(CARDS_PATH .'/'. $pack .'/white.txt');
            $answers = array_merge($answers, explode(PHP_EOL, $cards));
        }
        
        self::$answers = $answers;

        $this->resetDeck();
    }

    /**
     * Get a random answer card from the ones that have not been played yet
     * 
     * @return rbwebdesigns\fill_in_the_blanks\Card
     */
    protected function takeRandomCard()
    {
        if (count($this->availableCards) == 0) {
            throw new \Exception('No more answer cards!!!'); // @todo handle this
        }

        $randomIndex = rand(0, count($this->availableCards) - 1);
        $removed = array_splice($this->availableCards, $randomIndex, 1);
        
        return $removed[0];
    }

    /**
     * Create a new card instance for answer
     * 
     * @param int $answerID
     *  Index of the answer text
     * 
     * @return rbwebdesigns\fill_in_the_blanks\Card
     */
    protected function createCard($answerID)
    {
        $card = new Card();
        $card->id = $answerID;
        $card->text = self::$answers[$answerID];
        return $card;
    }

    /**
     * Ensure each player has 8 cards
     * 
     * @param Player[] $players
     */
    public function dealAnswerCards($players)
    {
        foreach ($players as $player) {
            for ($c = count($player->cards); $c < 8; $c++) {
                $player->cards[] = $this->takeRandomCard();
            }
        }
    }

    /**
     * Get a card by answer index
     */
    public function getAnswerCard($index)
    {
        return $this->createCard($index);
    }

    /**
     * Return all cards to available
     */
    public function resetDeck()
    {
        $this->availableCards = [];
        $answerIndex = 0;
        foreach (self::$answers as $answerText) {
            $this->availableCards[] = $this->createCard($answerIndex);
            $answerIndex++;
        }
    }

}
