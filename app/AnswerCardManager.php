<?php
namespace rbwebdesigns\cah_php;

/**
 * Class rbwebdesigns\cah_php\AnswerCardManager
 */
class AnswerCardManager
{
    protected static $answers = [];

    protected $availableCards = [];

    /**
     * AnswerCardManager constructor
     */
    public function __construct()
    {
        // Convert answers into cards
        $whiteCards = file_get_contents(CARDS_ROOT.'/standard/white.txt');
        self::$answers = explode(PHP_EOL, $whiteCards);
        
        $this->resetDeck();
    }

    /**
     * Get a random answer card from the ones that have not been played yet
     * 
     * @return rbwebdesigns\cah_php\Card
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
     * @return rbwebdesigns\cah_php\Card
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
    public function resetDeck() {
        $this->availableCards = [];
        $answerIndex = 0;
        foreach (self::$answers as $answerText) {
            $this->availableCards[] = $this->createCard($answerIndex);
            $answerIndex++;
        }
    }
}