<?php
namespace rbwebdesigns\cah_php;

class PlayerManager
{
    protected $players;

    public function __construct()
    {
        $this->players = [];
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
            $player->resourceID = $conn->resourceId;
            $player->isActive = true;
            return $player;
        }

        $player = new Player();
        $player->username = $data['username'];
        $player->resourceID = $conn->resourceId;
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
     * @param int $resourceID
     */
    public function markPlayerAsInactive($resourceID)
    {
        foreach ($this->players as $player) {
            if ($player->resourceID == $resourceID) {
                $player->isActive = false;
                return $player;
            }
        }
    }

}