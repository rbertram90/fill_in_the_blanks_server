<?php
namespace rbwebdesigns\cah_php;

/**
 * Class rbwebdesigns\cah_php\AnswerCardManager
 */
class AnswerCardManager
{
    protected static $answers = [
        'Goat', 'Cow', 'Sheep', 'Pig', 'Horse', 'Chicken', 'Fish',
        'Joey from Friends', 'Michael Owens left foot', 'Eternal happiness',
        'Card 11', 'Card 12', 'Card 13', 'Card 14', 'Card 15', 'Card 16', 'Card 17', 'Card 18', 'Card 19', 'Card 20',
        'Card 21', 'Card 22', 'Card 23', 'Card 24', 'Card 25', 'Card 26', 'Card 27', 'Card 28', 'Card 29', 'Card 30',
        'Card 31', 'Card 32', 'Card 33', 'Card 34', 'Card 35', 'Card 36', 'Card 37', 'Card 38', 'Card 39', 'Card 40',
        'Card 41', 'Card 42', 'Card 43', 'Card 44', 'Card 45', 'Card 46', 'Card 47', 'Card 48', 'Card 49', 'Card 50',
        'Card 51', 'Card 52', 'Card 53', 'Card 54', 'Card 55', 'Card 56', 'Card 57', 'Card 58', 'Card 59', 'Card 60',
        'Card 61', 'Card 62', 'Card 63', 'Card 64', 'Card 65', 'Card 66', 'Card 67', 'Card 68', 'Card 69', 'Card 70',
        'Card 71', 'Card 72', 'Card 73', 'Card 74', 'Card 75', 'Card 76', 'Card 77', 'Card 78', 'Card 79', 'Card 80',
        'Card 81', 'Card 82', 'Card 83', 'Card 84', 'Card 85', 'Card 86', 'Card 87', 'Card 88', 'Card 89', 'Card 90',
        'Card 91', 'Card 92', 'Card 93', 'Card 94', 'Card 95', 'Card 96', 'Card 97', 'Card 98', 'Card 99', 'Card 100',
    ];

    protected $availableCards = [];

    /**
     * AnswerCardManager constructor
     */
    public function __construct()
    {
        // Convert answers into cards
        $this->availableCards = [];
        $answerIndex = 0;
        foreach (self::$answers as $answerText) {
            $this->availableCards[] = $this->createCard($answerIndex);
            $answerIndex++;
        }
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
}