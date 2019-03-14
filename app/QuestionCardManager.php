<?php
namespace rbwebdesigns\cah_php;

/**
 * Class rbwebdesigns\cah_php\QuestionCardManager
 */
class QuestionCardManager
{

    protected static $questions = [];

    protected $availableCards;
    public $currentQuestion;

    /**
     * QuestionCardManager constructor
     */
    public function __construct()
    {
        // Convert answers into cards
        $this->populateCards();

        // Convert question text into cards
        $this->resetDeck();
    }

    /**
     * @return rbwebdesigns\cah_php\Card
     */
    public function getRandomQuestion()
    {
        if (count($this->availableCards) == 0) {
            throw new \Exception('No more question cards!!!'); // @todo handle this
        }

        $randomIndex = rand(0, count($this->availableCards) - 1);
        $removed = array_splice($this->availableCards, $randomIndex, 1);

        $this->currentQuestion = $removed[0];
        
        return $this->currentQuestion;
    }

    /**
     * Create a new question card instance
     * 
     * @param int $questionIndex
     *   Index of the question text
     * 
     * @return rbwebdesigns\cah_php\Card
     */
    protected function createCard($questionIndex)
    {
        $card = new Card();
        $card->id = $questionIndex;
        $card->text = self::$questions[$questionIndex];
        return $card;
    }

    /**
     * Get the cards from text files
     * 
     * @todo add UI to choose packs!
     */
    protected function populateCards()
    {
        // Convert answers into cards
        $blackCards = file_get_contents(CARDS_ROOT.'/standard/black.txt');
        self::$questions = explode(PHP_EOL, $blackCards);
    }

    /**
     * Restore all cards
     */
    public function resetDeck()
    {
        // Convert question text into cards
        $this->availableCards = [];
        $questionIndex = 0;
        foreach (self::$questions as $questionText) {
            $this->availableCards[] = $this->createCard($questionIndex);
            $questionIndex++;
        }
    }

}