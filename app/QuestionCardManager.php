<?php

namespace rbwebdesigns\fill_in_the_blanks;

/**
 * Class rbwebdesigns\fill_in_the_blanks\QuestionCardManager
 */
class QuestionCardManager
{
    /** @var string[] */
    protected static $questions = [];

    /** @var \rbwebdesigns\fill_in_the_blanks\Card[] */
    protected $availableCards;

    /** @var \rbwebdesigns\fill_in_the_blanks\Card */
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
     * @return \rbwebdesigns\fill_in_the_blanks\Card
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
     * @return \rbwebdesigns\fill_in_the_blanks\Card
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
        $blackCards = file_get_contents(CARDS_PATH . '/standard/black.txt');
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

            // Double check it's got blank in it
            if (strpos($questionText, '____') === FALSE) {
                continue;
            }

            $this->availableCards[] = $this->createCard($questionIndex);
            $questionIndex++;
        }
    }

}
