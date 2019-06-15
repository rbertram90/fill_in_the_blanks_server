<?php
namespace rbwebdesigns\fill_in_the_blanks;

class PlayerManager
{
    /** @var rbwebdesigns\fill_in_the_blanks\Player[] */
    protected $players;

    /** @var rbwebdesigns\fill_in_the_blanks\Player */
    protected $currentPlayer;

    /** @var rbwebdesigns\fill_in_the_blanks\Game */
    protected $game;

    public function __construct($game)
    {
        $this->players = [];
        $this->currentPlayer = null;
        $this->game = $game;
    }

    /**
     * Create a new player or re-connect one that has previously disconnected
     * 
     * @param array $data
     *  Message passed to the server from client
     * @param ConnectionInterface $conn
     *  Connection object from client
     * 
     * @return rbwebdesigns\fill_in_the_blanks\Player|boolean
     *   If username exists and is connected to game then returns false.
     *   Otherwise returns Player object
     */
    public function connectPlayer($data, $conn)
    {
        $player = $this->getPlayerByUsername($data['username']);
        if (!is_null($player)) {
            if ($player->isActive) {
                throw new \Exception('Duplicate username', Game::E_DUPLICATE_USERNAME);
            }
            $player->setConnection($conn);
            $player->isActive = true;
            $player->status = $this->game::STATUS_CONNECTED;
            return $player;
        }

        $player = new Player($conn, $this->game);
        $player->username = $data['username'];
        $player->icon = $data['icon'];
        $player->ip = $conn->remoteAddress;
        if (count($this->players) == 0) $player->isGameHost = true;

        $this->players[] = $player;
        return $player;
    }

    /**
     * Get a connected player by their username
     * 
     * @return rbwebdesigns\fill_in_the_blanks\Player|null
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

    /**
     * Get a connected player by their resource (socket) Id
     * 
     * @return rbwebdesigns\fill_in_the_blanks\Player|null
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
     * @return rbwebdesigns\fill_in_the_blanks\Player[]
     */
    public function getAllPlayers()
    {
        return $this->players;
    }

    /**
     * Get all players currently connected
     */
    public function getActivePlayers()
    {
        $active = [];
        foreach ($this->players as $player) {
            if ($player->isActive) {
                $active[] = $player;
            }
        }
        return $active;
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
                $player->setInactive();
                return $player;
            }
        }
    }

    /**
     * Get the judging player, generates if not set
     */
    public function getJudge()
    {
        if (is_null($this->currentPlayer) && count($this->players) > 0) {
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
        $activePlayers = $this->getActivePlayers();

        if (count($activePlayers) == 0) return null;

        if (!$this->currentPlayer->isActive) {
            // Current judge has disconnected
            $this->currentPlayer = $activePlayers[0];
            $this->currentPlayer->status = Game::STATUS_JUDGE;
            return $this->currentPlayer;
        }

        foreach ($activePlayers as $player) $player->status = Game::STATUS_IN_PLAY;

        foreach ($activePlayers as $player) {
            if ($player->username == $this->currentPlayer->username) {
                if (count($activePlayers) > $index + 1) {
                    $this->currentPlayer = $activePlayers[$index + 1];
                    $this->currentPlayer->status = Game::STATUS_JUDGE;
                    break;
                }
                $this->currentPlayer = $activePlayers[0];
                $this->currentPlayer->status = Game::STATUS_JUDGE;
                break;
            }
            $index++;
        }
        return $this->currentPlayer;
    }

    /**
     * Reset player data
     */
    public function resetPlayers() {
        $activePlayers = $this->getActivePlayers();

        foreach ($activePlayers as $player) {
            $player->reset();
        }
        $this->currentPlayer = $activePlayers[0];
    }

    /**
     * Make sure all players have empty hand at round start
     */
    public function clearCardsInPlay() {
        foreach ($this->players as $player) {
            $player->cardsInPlay = [];
        }
    }
}