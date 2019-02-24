<?php
namespace rbwebdesigns\cah_php;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $playerManager;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->inactiveClients = new \SplObjectStorage;
        $this->playerManager = new PlayerManager;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // if ($this->userExists($conn->remoteAddress)) {
        //    $conn->send('{ "message": "duplicate connection" }');
        //    $conn->close();
        //    return;
        // }

        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";

        // foreach ($this->clients as $client) {
        //    $client->send($conn->remoteAddress ." connected");
        // }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // $numRecv = count($this->clients) - 1;
        // echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
        //     , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        $data = json_decode($msg, true);
        if (!isset($data['action'])) return;

        switch ($data['action']) {
            case 'player_connected':
                $player = $this->playerManager->connectPlayer($data, $from);
                $this->sendToAll([
                    'type' => 'player_connected',
                    'playerName' => $player->username,
                    'host' => $player->isGameHost,
                    'players' => $this->playerManager->getAllPlayers(),
                ]);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";

        $player = $this->playerManager->markPlayerAsInactive($conn->resourceId);

        $this->sendToAll([
            'type' => 'player_disconnected',
            'playerName' => $player->username,
            'players' => $this->playerManager->getAllPlayers(),
        ]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function sendToAll($data)
    {
        foreach ($this->clients as $client) {
            // if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                // $client->send($msg);
            // }
            $msg = json_encode($data);
            $client->send($msg);
        }
    }
}