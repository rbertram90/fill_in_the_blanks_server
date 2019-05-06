<?php
namespace rbwebdesigns\fill_in_the_blanks;

class Messenger
{

    protected $game;

    public function __construct($game) {
        $this->game = $game;
    }

    /**
     * Send a message to a single client
     * 
     * @param ConnectionInterface $client
     * @param array $data
     */
    public function sendMessage($client, $data)
    {
        print "Sending message to ({$client->resourceId}): {$data['type']}".PHP_EOL;
        $msg = json_encode($data);
        $client->send($msg);
    }

    /**
     * Send a message to all connected clients
     * 
     * @param array $data
     */
    public function sendToAll($data)
    {
        $clients = $this->game->getConnectedClients();

        foreach ($clients as $client) {
            $this->sendMessage($client, $data);
        }
    }

    /**
     * Send message to the game host
     */
    public function sendToHost($data)
    {
        $clients = $this->game->getConnectedClients();

        // Send to host - assuming host is always client 0
        $clients->rewind();
        $this->sendMessage($clients->current(), $data);
    }

}