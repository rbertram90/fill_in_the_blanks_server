<?php
namespace rbwebdesigns\cah_php;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Game implements MessageComponentInterface
{
    protected $clients;
    protected $playerManager;

    protected $questions = [
        'I went to the supermarket and bought ____.',
        'My favourite ice-cream flavour is ____.',
        '____ tastes nice.'
    ];
    protected $currentQuestion;
    protected $cardsInPlay;

    /**
     * Game constructor
     */
    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->inactiveClients = new \SplObjectStorage;
        $this->playerManager = new PlayerManager;
    }

    /**
     * Connection opened callback
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    /**
     * Message recieved callback
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        if (!isset($data['action'])) return;

        echo sprintf('Message incoming (%d): %s'. PHP_EOL, $from->resourceId, $data['action']);

        switch ($data['action']) {
            case 'player_connected':
                $player = $this->playerManager->connectPlayer($data, $from);
                if (!$player) {
                    $from->send('{ "type": "duplicate_username" }');
                    $from->close(); // @todo test this
                    return;
                }
                if (count($player->cards) > 0) {
                    $this->sendMessage($player->getConnection(), [
                        'type' => 'answer_card_update',
                        'cards' => $player->cards
                    ]); 
                }
                $this->sendToAll([
                    'type' => 'player_connected',
                    'playerName' => $player->username,
                    'host' => $player->isGameHost,
                    'players' => $this->playerManager->getAllPlayers(),
                ]);
                break;

            case 'start_game':
                $this->distributeAnswerCards();
                $this->sendToAll([
                    'type' => 'round_start',
                    'questionCard' => $this->getQuestionCard(),
                    'currentJudge' => $this->playerManager->getJudge()
                ]);
                $this->cardsInPlay = [];
                break;

            case 'cards_submit':
                $cards = $data['cards']; // Array of card IDs
                $player = $this->playerManager->getPlayerByResourceId($from->resourceId);
                
                // Store who played the card
                foreach ($cards as $card) {
                    $this->cardsInPlay[$card] = $this->playerManager->getAnswerData($card);
                    $this->cardsInPlay[$card]['player'] = $player->username;
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
                        $safeCardData[] = [
                            'id' => $card['id'],
                            'text' => $card['text'],
                        ];
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
                $winner = $this->playerManager->getPlayerByUsername($winner);
                
                $this->sendToAll([
                    'type' => 'round_winner',
                    'player' => $winner,
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
     * Get a random question card from the pile
     * 
     * @todo make random
     */
    protected function getQuestionCard()
    {
        $card = $this->questions[0];
        $this->currentQuestion = $card;
        return $card;
    }

    /**
     * Send message out to each client to replenish their answer cards
     */
    protected function distributeAnswerCards()
    {
        $this->playerManager->dealAnswerCards();
        $players = $this->playerManager->getAllPlayers();

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