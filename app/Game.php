<?php
namespace rbwebdesigns\cah_php;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Game implements MessageComponentInterface
{
    protected $clients;
    protected $playerManager;
    protected $questionCardManager;
    protected $answerCardManager;
    
    protected $cardsInPlay;

    /**
     * Game constructor
     */
    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->playerManager = new PlayerManager;
        $this->questionCardManager = new QuestionCardManager;
        $this->answerCardManager = new AnswerCardManager;
    }

    /**
     * Connection opened callback
     * 
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    /**
     * Message recieved callback
     * 
     * @param ConnectionInterface $from
     * @param string $msg
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        if (!isset($data['action'])) return;

        echo sprintf('Message incoming (%d): %s'. PHP_EOL, $from->resourceId, $data['action']);

        switch ($data['action']) {
            case 'player_connected':
                $this->addPlayer($from, $data);
                break;

            case 'start_game':
                $this->distributeAnswerCards();
                $this->sendToAll([
                    'type' => 'round_start',
                    'questionCard' => $this->questionCardManager->getRandomQuestion(),
                    'currentJudge' => $this->playerManager->getJudge()
                ]);
                $this->cardsInPlay = [];
                break;

            case 'cards_submit':
                $cards = $data['cards']; // Array of card IDs
                $player = $this->playerManager->getPlayerByResourceId($from->resourceId);
                
                // Store who played the card
                foreach ($cards as $card) {
                    $this->cardsInPlay[$card] = [
                        'card' => $this->answerCardManager->getAnswerCard($card),
                        'player' => $player
                    ];
                }

                $player->status = 'Waiting';
                $player->cardsInPlay = $cards;
                $player->removeCards($cards);

                // send message to all clients that user has submitted
                $this->sendToAll([
                    'type' => 'player_submitted',
                    'playerName' => $player->username,
                    'players' => $this->playerManager->getAllPlayers(),
                ]);

                if ($this->allPlayersDone()) {
                    // Ensure the username is not sent to all players
                    $safeCardData = [];
                    foreach ($this->cardsInPlay as $card) {
                        $safeCardData[] = $card['card'];
                    }

                    // Send message to all players revealing the cards
                    $this->sendToAll([
                        'type' => 'round_judge',
                        'currentJudge' => $this->playerManager->getJudge(),
                        'allCards' => $safeCardData
                    ]);
                }
                break;

            case 'winner_picked':
                $winningCard = $data['card'];
                $winner = $this->cardsInPlay[$winningCard]['player'];
                $winner->score += 1;
                
                $this->sendToAll([
                    'type' => 'round_winner',
                    'winner' => $winner,
                    'players' => $this->playerManager->getAllPlayers(),
                    'card' => $winningCard
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
        
        if ($player) {
            $this->sendToAll([
                'type' => 'player_disconnected',
                'playerName' => $player->username,
                'players' => $this->playerManager->getAllPlayers(),
            ]);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    /**
     * Add a player into game from connection details and username
     * 
     * @param ConnectionInterface $from
     * @param array $data
     */
    protected function addPlayer($from, $data)
    {
        // Connect player to game
        $player = $this->playerManager->connectPlayer($data, $from);

        // If no player returned then this username was in-use
        if (!$player) {
            $from->send('{ "type": "duplicate_username" }');
            $from->close(); // @todo test this
            return;
        }

        // If they're reconnecting and have cards then send them the data
        if (count($player->cards) > 0) {
            $this->sendMessage($player->getConnection(), [
                'type' => 'answer_card_update',
                'cards' => $player->cards
            ]); 
        }

        // @todo Send them current question?

        // Notify all players
        $this->sendToAll([
            'type' => 'player_connected',
            'playerName' => $player->username,
            'host' => $player->isGameHost,
            'players' => $this->playerManager->getAllPlayers(),
        ]);
    }

    /**
     * Send a message to a single client
     */
    protected function sendMessage($client, $data)
    {
        $msg = json_encode($data);
        $client->send($msg);
    }

    /**
     * Send a message to all connected clients
     */
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

    /**
     * Send message out to each client to replenish their answer cards
     */
    protected function distributeAnswerCards()
    {
        $players = $this->playerManager->getAllPlayers();
        $this->answerCardManager->dealAnswerCards($players);
        
        // Send seperate message to each player with cards
        foreach ($players as $player) {
            $connection = $player->getConnection();
            $this->sendMessage($connection, [
                'type' => 'answer_card_update',
                'cards' => $player->cards
            ]);
        }
    }

    /**
     * Checks if all players have submitted their cards for this round
     * 
     * @return bool true if all players have submitted cards
     */
    protected function allPlayersDone()
    {
        $judge = $this->playerManager->getJudge();

        foreach ($this->playerManager->getAllPlayers() as $player) {
            if ($player->username == $judge->username) continue;
            if (count($player->cardsInPlay) == 0) {
                return false;
            }
        }
        return true;
    }

}