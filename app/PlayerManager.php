<?php
namespace rbwebdesigns\cah_php;

class PlayerManager
{
    protected $players;
    protected $currentPlayer;

    public function __construct()
    {
        $this->players = [];
        $this->currentPlayer = null;
    }

    /**
     * Create a new player or re-connect one that has previously disconnected
     * 
     * @param array $data
     *  Message passed to the server from client
     * @param ConnectionInterface $conn
     *  Connection object from client
     * 
     * @return rbwebdesigns\cah_php\Player|boolean
     *   If username exists and is connected to game then returns false.
     *   Otherwise returns Player object
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
                    $this->currentPlayer = $this->players[$index + 1];
                    break;
                }
                $this->currentPlayer = $this->players[0];
                break;
            }
            $index++;
        }
        return $this->currentPlayer;
    }
}