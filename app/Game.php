<?php
namespace rbwebdesigns\fill_in_the_blanks;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

/**
 * class Game
 * 
 * This class is passed as a parameter into the WsServer when the server
 * is started. It's job is to take the incoming messages and maintain game
 * state.
 * 
 * Currently nothing is persisted in files or a database, once the server
 * has been stopped all data is lost.
 */
class Game implements MessageComponentInterface
{
    /**
     * @var \SplObjectStorage collection of currently connected
     *   clients (\Ratchet\ConnectionInterface)
     */
    protected $clients;

    /** @var \rbwebdesigns\fill_in_the_blanks\PlayerManager */
    protected $playerManager;

    /**
     * @var mixed[]
     * card => \rbwebdesigns\fill_in_the_blanks\Card
     * player => \rbwebdesigns\fill_in_the_blanks\Player
     */
    protected $cardsInPlay;

    /** @var string */
    protected $status;

    /** @var \rbwebdesigns\fill_in_the_blanks\Messenger */
    protected $messenger;


    /** @var \rbwebdesigns\fill_in_the_blanks\QuestionCardManager */
    public $questionCardManager = null;

    /** @var \rbwebdesigns\fill_in_the_blanks\AnswerCardManager */
    public $answerCardManager = null;
    
    /** @var \rbwebdesigns\fill_in_the_blanks\Player */
    public $host = null;

    /** @var int  Minimum number of players required to play this game */
    public static $minPlayers = 3;

    /** @var int  Maximum time for playeres to choose their cards in seconds (0 = infinite) */
    public $roundTime = 0;

    /** @var int  How many points does a player require to win the game */
    public $winningScore = 5;

    /** @var string[]  Directory name of available card packs */
    public $cardPacks = [];

    /** @var string[]  Directory name of selected card packs */
    public $activeCardPacks = [];

    /** @var boolean  Can the player enter their own text */
    public $allowCustomText = true;

    /** @var boolean  Can the player use images */
    public $allowImages = false;

    /** @var string  Judging mode */
    public $judgingMode = self::GAME_JM_STANDARD;

    /** @var int[]  Judging results in committee mode */
    protected $committeeVotes = [];

    /** @var int  How many rounds have been successfully finished */
    // public $roundNumber = 0;

    /** @var int  How many cards should players have at the start of the round */
    // public $cardCount = 8;

    // Player statuses
    public const STATUS_JUDGE = 'Card czar';
    public const STATUS_IN_PLAY = 'Choosing card(s)';
    public const STATUS_CARDS_CHOSEN = 'Card(s) submitted';
    public const STATUS_CHOOSING_WINNER = 'Picking a winner';
    public const STATUS_CHOSEN_WINNER = 'Waiting';
    public const STATUS_CONNECTED = 'Connected';
    public const STATUS_DISCONNECTED = 'Disconnected';

    // Game states
    public const GAME_STATUS_AWAITING_START = 0;
    public const GAME_STATUS_PLAYERS_CHOOSING = 1;
    public const GAME_STATUS_JUDGE_CHOOSING = 2;
    public const GAME_STATUS_ROUND_WON = 3;
    public const GAME_STATUS_GAME_WON = 4;

    // Exception codes
    public const E_DUPLICATE_USERNAME = 1;

    // Judging modes
    public const GAME_JM_STANDARD = 0;
    public const GAME_JM_COMMITTEE = 1;
    
    /**
     * Game constructor
     */
    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->playerManager = new PlayerManager($this);
        $this->questionCardManager = new QuestionCardManager($this);
        $this->answerCardManager = new AnswerCardManager($this);
        $this->messenger = new Messenger($this);
        $this->status = self::GAME_STATUS_AWAITING_START;

        // Get a list of card packs in the card packs folder
        foreach (scandir(CARDS_PATH) as $file) {
            if ($file == '.' || $file == '..') continue;
            elseif (file_exists(CARDS_PATH .'/'. $file .'/black.txt') && file_exists(CARDS_PATH .'/'. $file .'/white.txt')) {
                // This is a valid card pack
                $this->cardPacks[] = $file;
            }
        }
    }

    /**
     * Connection opened callback
     * 
     * @param \Ratchet\ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    /**
     * Message recieved callback
     * 
     * @param \Ratchet\ConnectionInterface $from
     * @param string $msg
     *   json string containing data from client
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
                // Check game state
                if ($this->status !== self::GAME_STATUS_AWAITING_START) break;

                $this->start($data);
                break;

            case 'next_round':
                // Check game state
                if ($this->status == self::GAME_STATUS_JUDGE_CHOOSING && $data['forced']) {
                    // Continue
                }
                elseif ($this->status !== self::GAME_STATUS_ROUND_WON) {
                    break;
                }

                $this->nextRound();
                break;

            case 'cards_submit':
                // Check game state
                if ($this->status !== self::GAME_STATUS_PLAYERS_CHOOSING) break;

                $this->answerSubmitted($from, $data);
                break;

            case 'round_expired':
                // Timer has run out - trigger judging
                if ($this->status !== self::GAME_STATUS_PLAYERS_CHOOSING) break;

                if (count($this->cardsInPlay) > 0) {
                    $this->startJudging();
                }
                else {
                    $this->nextRound();
                }
                break;

            case 'winner_picked':
                // Ensure we're still in judge choosing mode
                if ($this->status !== self::GAME_STATUS_JUDGE_CHOOSING) break;

                $winningCard = $data['card'];

                switch ($this->judgingMode) {
                    case self::GAME_JM_STANDARD:
                        // Single judge
                        $winner = $this->cardsInPlay[$winningCard]['player'];
                        $winner->score += 1;
                        $this->status = self::GAME_STATUS_ROUND_WON;

                        $this->messenger->sendToAll([
                            'type' => 'round_winner',
                            'winner' => $winner,
                            'players' => $this->playerManager->getActivePlayers(),
                            'card' => $winningCard
                        ]);
                        break;

                    case self::GAME_JM_COMMITTEE:
                        // Everyone gets a say

                        // Add score to somewhere
                        if (!array_key_exists($winningCard, $this->committeeVotes)) {
                            $this->committeeVotes[$winningCard] = 1;
                        }
                        else {
                            $this->committeeVotes[$winningCard]++;
                        }

                        // Record that this user has cast a vote
                        $player = $this->playerManager->getPlayerByResourceId($from->resourceId);
                        $player->hasVoted = true;
                        $player->status = self::STATUS_CHOSEN_WINNER;

                        // Check if everyone has now recorded a vote
                        if ($this->allPlayersJudged()) {
                            $this->committeeFinishedVoting();
                        }
                        else {
                            // Broadcast that this player has submitted their vote
                            $this->messenger->sendToAll([
                                'type' => 'player_judged',
                                'players' => $this->playerManager->getActivePlayers(),
                                'player' => $this->playerManager->getPlayerByResourceId($from->resourceId),
                            ]);
                        }
                        break;
                }
                break;

            case 'force_decision':
                // In committee mode if a player is away, force the game to continue to results
                if ($this->status !== self::GAME_STATUS_JUDGE_CHOOSING) {
                    print "Incorrect game status to do this action \n";
                    break;
                }
                $player = $this->playerManager->getPlayerByResourceId($from->resourceId);
                if ($player->username !== $this->host->username) {
                    print "Player not permitted to do this action ({$player->username} {$this->host->username}) \n";
                    break;
                }

                // Jump straight into the vote finalising stage
                $this->committeeFinishedVoting();
                break;

            case 'reset_game':
                $this->reset();
                break;
        }
    }

    /**
     * Connection closed / player has disconnected
     * 
     * @param Ratchet\ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";

        $disconnectedPlayer = $this->playerManager->markPlayerAsInactive($conn->resourceId);
        
        if ($disconnectedPlayer) {
            $this->messenger->sendToAll([
                'type' => 'player_disconnected',
                'playerName' => $disconnectedPlayer->username,
                'players' => $this->playerManager->getActivePlayers(),
            ]);

            if ($this->status == self::GAME_STATUS_JUDGE_CHOOSING || $this->status == self::GAME_STATUS_PLAYERS_CHOOSING) {
                if ($disconnectedPlayer->username == $this->playerManager->getJudge()->username) {
                    // Return all played white cards to players
                    foreach ($this->playerManager->getAllPlayers() as $player) {
                        $player->returnCardsToPlayer();
                    }
                    // Start next round
                    $this->nextRound();
                }
                else {
                    // Remove the cards from available selection
                    $remainingCards = [];
                    foreach ($this->cardsInPlay as $cardData) {
                        if ($cardData['player']->username !== $disconnectedPlayer->username) {
                            $remainingCards[$cardData['card']->id] = $cardData;
                        }
                    }
                    $this->cardsInPlay = $remainingCards;

                    // restart judging process
                    if ($this->allPlayersDone()) $this->startJudging();
                }
            }
        }
    }

    /**
     * An error has occured with a connected client
     * 
     * @param Ratchet\ConnectionInterface $conn
     * @param Exception $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    /**
     * Get all the data on the connected clients
     * 
     * @return SplObjectStorage
     */
    public function getConnectedClients()
    {
        return $this->clients;
    }

    /**
     * Add a player into game, this is triggered by a message from
     * the client after they have connected into the server. I.e.
     * not in the same process as when they are first connected,
     * this is because we are unable to pass the username in the
     * initial connect call.
     * 
     * Note the player connecting may already be known by username
     * as they may have had an internet outage so this function
     * looks to see if a username match exists and is not in use
     * by a current player.
     * 
     * The game allows a player to join midway through a round and
     * still be able to submit cards.
     * 
     * @todo More thought has needs to be given to what should
     * happen when the host disconnects.
     * 
     * @param ConnectionInterface $from
     * @param array $data
     */
    protected function addPlayer($from, $data)
    {
        // Connect player to game
        try {
            $player = $this->playerManager->connectPlayer($data, $from);
        }
        catch (\Exception $e) {
            switch ($e->getCode()) {
                case self::E_DUPLICATE_USERNAME:
                    $from->send('{ "type": "duplicate_username" }');
                    $from->close();
                    return;
            }
        }

        // Send a message to the player with the game state
        // game_status lets the front-end which screen to show
        // variables past here will be available to game config screen
        $this->messenger->sendMessage($from, [
            'type' => 'connected_game_status',
            'game_status' => $this->status,
            'judge' => $this->playerManager->getJudge(),
            'player_is_host' => $player->isGameHost,
            'card_packs' => $this->cardPacks,
        ]);

        // If they're reconnecting, and have cards, then send them the data
        if (count($player->cards) > 0) {
            $this->messenger->sendMessage($player->getConnection(), [
                'type' => 'answer_card_update',
                'cards' => $player->cards
            ]);
        }

        if ($this->status == self::GAME_STATUS_JUDGE_CHOOSING) {
            // Don't want to let them join in this round - wait until next
            // Ensure the username is not sent to players
            $safeCardData = [];
            foreach ($this->cardsInPlay as $card) {
                $playerId = $card['player']->getConnection()->resourceId;
                if (!array_key_exists($playerId, $safeCardData)) $safeCardData[$playerId] = [];
                $safeCardData[$playerId][] = $card['card'];
            }

            // Now remove player Ids keys!
            $safeCardData = array_values($safeCardData);

            $this->messenger->sendMessage($player->getConnection(), [
                'type' => 'round_judge',
                'currentQuestion' => $this->questionCardManager->currentQuestion,
                'judgeMode' => $this->judgingMode,
                'currentJudge' => $this->judgingMode === self::GAME_JM_STANDARD ? $this->playerManager->getJudge() : null,
                'allCards' => $safeCardData,
                'players' => $this->playerManager->getActivePlayers()
            ]);
            
            $player->status = self::STATUS_CONNECTED;
        }
        elseif ($this->status == self::GAME_STATUS_PLAYERS_CHOOSING) {
            // They've joined mid-way through a round - let them in!

            // The cards should have been returned when they disconnected
            // But just in case!
            if (count($player->cardsInPlay) > 0) {
                $player->status = self::STATUS_CARDS_CHOSEN;
            }
            else {
                $player->status = self::STATUS_IN_PLAY;
            }
            
            if (count($player->cards) == 0) {
                $this->answerCardManager->dealAnswerCards([$player]);
                $this->messenger->sendMessage($player->getConnection(), [
                    'type' => 'answer_card_update',
                    'cards' => $player->cards
                ]);
            }
            
            $this->messenger->sendMessage($player->getConnection(), [
                'type' => 'round_start',
                'questionCard' => $this->questionCardManager->currentQuestion,
                'currentJudge' => $this->judgingMode === self::GAME_JM_STANDARD ? $this->playerManager->getJudge() : null,
                'currentReader' => $this->judgingMode === self::GAME_JM_COMMITTEE ? $this->playerManager->getJudge() : null,
                'roundTime' => $this->roundTime,
                'players' => $this->playerManager->getActivePlayers(),
                'playerInPlay' => $player->status == self::STATUS_IN_PLAY,
                'allowCustomText' => $this->allowCustomText,
                'allowImages' => $this->allowImages
            ]);
        }

        // Notify all players
        $this->messenger->sendToAll([
            'type' => 'player_connected',
            'playerName' => $player->username,
            'host' => $player->isGameHost,
            'players' => $this->playerManager->getActivePlayers(),
        ]);
    }

    /**
     * Try and start the game - will fail if not enough players are connected
     * 
     * @todo We haven't actually verified that the player that sent this
     * message was the game host!
     */
    protected function start($options)
    {
        // Check we've got enough players
        if ($this->clients->count() < self::$minPlayers) {
            $this->messenger->sendToHost([
                'type' => 'start_game_fail',
                'errorType' => 'more_players_needed',
                'minPlayers' => self::$minPlayers
            ]);
            return;
        }

        // Update player status
        $this->playerManager->changeAllPlayersStatus(self::STATUS_IN_PLAY);

        // Update game status
        $this->status = self::GAME_STATUS_PLAYERS_CHOOSING;

        $this->roundTime = $options['maxRoundTime'];
        $this->winningScore = $options['winningScore'];
        $this->allowCustomText = $options['allowCustomText'];
        $this->allowImages = $options['allowImages'];
        $this->activeCardPacks = $options['cardPacks'];
        $this->judgingMode = intval($options['judgingMode']);

        // Select judge/reader
        $judge = $this->playerManager->getJudge();

        if ($this->judgingMode === self::GAME_JM_STANDARD) {
            $judge->status = self::STATUS_JUDGE;
        }

        // Now we know which decks to use, populate black and white card decks
        $this->answerCardManager->buildDeck();
        $this->questionCardManager->buildDeck();

        // Ensure all players have the correct number of cards
        $this->distributeAnswerCards();

        $this->messenger->sendToAll([
            'type' => 'round_start',
            'questionCard' => $this->questionCardManager->getRandomQuestion(),
            'currentJudge' => $this->judgingMode === self::GAME_JM_STANDARD ? $judge : null,
            'currentReader' => $this->judgingMode === self::GAME_JM_COMMITTEE ? $judge : null,
            'roundTime' => $this->roundTime,
            'players' => $this->playerManager->getActivePlayers(),
            'allowCustomText' => $this->allowCustomText,
            'allowImages' => $this->allowImages,
            'judgingMode' => $this->judgingMode,
        ]);
        $this->cardsInPlay = [];
    }

    /**
     * Reset the game state
     */
    protected function reset()
    {
        $this->status = self::GAME_STATUS_AWAITING_START;
        $this->playerManager->resetPlayers();
        $this->questionCardManager->resetDeck();
        $this->answerCardManager->resetDeck();
        $this->cardsInPlay = [];
        $this->messenger->sendToAll([
            'type' => 'game_reset',
            'players' => $this->playerManager->getActivePlayers(),
        ]);
    }

    /**
     * Progress to the next round
     */
    protected function nextRound()
    {
        // Check if a player has won the whole game
        if ($this->playerManager->playerHasWon()) {
            $this->status = self::GAME_STATUS_GAME_WON;
            $this->messenger->sendToAll([
                'type' => 'game_won',
                'players' => $this->playerManager->getActivePlayers(),
            ]);
            return;
        }

        if ($this->judgingMode === self::GAME_JM_COMMITTEE) {
            foreach ($this->playerManager->getActivePlayers() as $player) {
                $player->hasVoted = false;
                $player->status = self::STATUS_IN_PLAY;
            }
        }

        $this->committeeVotes = [];
        $this->status = self::GAME_STATUS_PLAYERS_CHOOSING;
        $this->distributeAnswerCards();
        $this->playerManager->clearCardsInPlay();
        $this->messenger->sendToAll([
            'type' => 'round_start',
            'questionCard' => $this->questionCardManager->getRandomQuestion(),
            'roundTime' => $this->roundTime,
            'currentJudge' => $this->judgingMode === self::GAME_JM_STANDARD ? $this->playerManager->nextJudge() : null,
            'currentReader' => $this->judgingMode === self::GAME_JM_COMMITTEE ? $this->playerManager->nextJudge() : null,
            'players' => $this->playerManager->getActivePlayers(),
            'allowCustomText' => $this->allowCustomText,
            'allowImages' => $this->allowImages
        ]);
        $this->cardsInPlay = [];
    }

    /**
     * Send message out to each client to replenish their answer cards
     */
    protected function distributeAnswerCards()
    {
        $players = $this->playerManager->getActivePlayers();
        $this->answerCardManager->dealAnswerCards($players);
        
        // Send seperate message to each player with cards
        foreach ($players as $player) {
            $connection = $player->getConnection();
            $this->messenger->sendMessage($connection, [
                'type' => 'answer_card_update',
                'cards' => $player->cards
            ]);
        }
    }

    /**
     * Player has submitted answer
     * 
     * @param ConnectionInterface $from
     * @param array $data
     */
    protected function answerSubmitted($from, $data)
    {
        $cards = $data['cards']; // Multi-dimentional array [id => X, text => Y]
        $player = $this->playerManager->getPlayerByResourceId($from->resourceId);
        
        // Ensure player has not already played cards this round
        if (count($player->cardsInPlay) > 0) return;

        $realCards = [];

        // Save who played the card
        foreach ($cards as $card) {
            $id = $card['id'];
            $text = $card['text'];
            
            $realCard = $this->answerCardManager->getAnswerCard($id);
            $realCard->text = $text;

            $realCards[] = $realCard;

            $this->cardsInPlay[$id] = [
                'card' => $realCard,
                'player' => $player
            ];
        }

        // Update player
        $player->playCards($realCards);

        // send message to all clients that user has submitted
        $this->messenger->sendToAll([
            'type' => 'player_submitted',
            'playerName' => $player->username,
            'players' => $this->playerManager->getActivePlayers(),
        ]);

        // Check if all players have submitted cards
        if ($this->allPlayersDone()) $this->startJudging();
    }

    /**
     * Once all players have submitted their white cards for the round
     * this function is called
     */
    protected function startJudging()
    {
        $this->status = self::GAME_STATUS_JUDGE_CHOOSING;

        // Ensure the username is not sent to players
        $safeCardData = [];
        foreach ($this->cardsInPlay as $card) {
            $playerId = $card['player']->getConnection()->resourceId;
            if (!array_key_exists($playerId, $safeCardData)) $safeCardData[$playerId] = [];
            $safeCardData[$playerId][] = $card['card'];
        }

        // Now remove player Ids keys!
        $safeCardData = array_values($safeCardData);

        if ($this->judgingMode == self::GAME_JM_COMMITTEE) {
            foreach ($this->playerManager->getActivePlayers() as $player) {
                $player->status = self::STATUS_CHOOSING_WINNER;
            }
        }

        // Send message to all players revealing the cards
        $this->messenger->sendToAll([
            'type' => 'round_judge',
            'currentQuestion' => $this->questionCardManager->currentQuestion,
            'judgeMode' => $this->judgingMode,
            'currentJudge' => $this->judgingMode === self::GAME_JM_STANDARD ? $this->playerManager->getJudge() : null,
            'allCards' => $safeCardData,
            'players' => $this->playerManager->getActivePlayers()
        ]);
    }

    /**
     * Checks if all players have submitted their cards for this round
     * 
     * @return bool true if all players have submitted cards
     */
    protected function allPlayersDone()
    {
        $judge = $this->playerManager->getJudge();

        foreach ($this->playerManager->getActivePlayers() as $player) {
            // Do not look for a submission from the judge
            if ($this->judgingMode === self::GAME_JM_STANDARD && $player->username === $judge->username) {
                continue;
            }
            if (count($player->cardsInPlay) == 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks if all players have chosen their winner for this round
     * 
     * @return bool true if all players have chosen
     */
    protected function allPlayersJudged()
    {
        foreach ($this->playerManager->getActivePlayers() as $player) {
            if ($player->hasVoted == false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Action to take when everyone has finished voting
     */
    protected function committeeFinishedVoting() {
        // Decide on a winner
        $winners = array_keys($this->committeeVotes, max($this->committeeVotes));

        if (count($winners) > 1) {
            // todo: We've got a tie!
            // Let the person who was due to be Czar pick between those who have tied but who are not?
            // Whoever picked first wins?
            // Random pick?
        }

        $roundWinner = $this->cardsInPlay[$winners[0]]['player'];
        $roundWinner->score += 1;
        $this->status = self::GAME_STATUS_ROUND_WON;

        $this->messenger->sendToAll([
            'type' => 'round_winner',
            'winner' => $roundWinner,
            'players' => $this->playerManager->getActivePlayers(),
            'card' => $winners[0]
        ]);
    }

}
