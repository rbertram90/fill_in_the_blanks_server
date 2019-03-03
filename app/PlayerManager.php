<?php
namespace rbwebdesigns\cah_php;

class PlayerManager
{
    protected $players;
    protected $currentPlayer;

    protected static $answers = [
        'Goat', 'Cow', 'Sheep', 'Pig', 'Horse', 'Chicken', 'Fish',
        'Joey from Friends',
        'Michael Owens left foot',
        'Eternal happiness',
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
    protected static $availableCards = [];

    public function __construct()
    {
        $this->players = [];
        $this->currentPlayer = null;
        for ($c = 0; $c < 100; $c++) self::$availableCards[] = $c;
    }

    /**
     * Create a new player or re-connect one that has
     * previously disconnected
     * 
     * @param array $data
     *  Message passed to the server from client
     * @param ConnectionInterface $conn
     *  Connection object from client
     */
    public function connectPlayer($data, $conn)
    {
        $player = $this->getPlayerByUsername($data['username']);
        if (!is_null($player)) {
            if ($player->isActive) {
                return false;
            }
            $player->setConnection($conn);
            $player->isActive = true;
            return $player;
        }

        $player = new Player($conn);
        $player->username = $data['username'];
        $player->ip = $conn->remoteAddress;
        if (count($this->players) == 0) $player->isGameHost = true;

        $this->players[] = $player;
        return $player;
    }

    /**
     * Get a connected player by their username
     * 
     * @return rbwebdesigns\cah_php\Player|null
     */
    public function getPlayerByUsername($username)
    {
        foreach ($this->players as $player) {
            if ($player->username == $username) {
                return $player;
            }
        }
        return null;
    }

    public function getAnswerData($id) {
        return [
            'id' => $id,
            'text' => self::$answers[$id]
        ];
    }

    /**
     * Get a connected player by their resource (socket) Id
     * 
     * @return rbwebdesigns\cah_php\Player|null
     */
    public function getPlayerByResourceId($resourceId)
    {
        foreach ($this->players as $player) {
            if ($player->getConnection()->resourceId == $resourceId) {
                return $player;
            }
        }
        return null;
    }

    /**
     * Get all players in the game (active and not)
     * 
     * @return rbwebdesigns\cah_php\Player[]
     */
    public function getAllPlayers()
    {
        return $this->players;
    }

    /**
     * When a player disconnects we don't want to completely
     * remove them from the game as it could just be a temporary
     * network issue. There record is kept in game but marked as
     * inactive.
     * 
     * @param int $resourceId
     */
    public function markPlayerAsInactive($resourceId)
    {
        foreach ($this->players as $player) {
            if ($player->getConnection()->resourceId == $resourceId) {
                $player->isActive = false;
                return $player;
            }
        }
    }

    /**
     * Ensures each player has 8 cards
     */
    public function dealAnswerCards()
    {
        $playerNum = 1;
        foreach ($this->players as $player) {
            for ($c = count($player->cards); $c < 8; $c++) {
                $player->cards[] = $this->getRandomCard();
            }
            $playerNum++;
        }
    }

    protected function getRandomCard() {

        if (count(self::$availableCards) == 0) {
            throw new \Exception('No more cards!!!'); // @todo handle this
        }

        $random = rand(0, count(self::$availableCards) - 1);
        $return = self::$availableCards[$random];
        array_splice(self::$availableCards, $random, 1);
        
        return $this->getAnswerData($return);
    }

    /**
     * Get the judging player, generates if not set
     */
    public function getJudge()
    {
        if (is_null($this->currentPlayer)) {
            $this->currentPlayer = $this->players[0];
        }
        return $this->currentPlayer;
    }

    /**
     * Change the person judging
     */
    public function nextJudge()
    {
        $index = 0;
        foreach ($this->players as $player) {
            if ($player->username == $this->currentPlayer->username) {
                if (count($this->players) > $index + 1) {
                    $this->currentPlayer = $player;
                    break;
                }
                $this->currentPlayer = $this->players[0];
            }
            $index++;
        }
    }
}