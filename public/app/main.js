/**
 * Class BlanksGame
 */
function BlanksGame() {
    // Create and initialise all page components
    this.components = {
        messagePanel: new MessagesPanel(this),
        playerList: new PlayerList(this),
        roundQuestion: new RoundQuestion(this),
        roundSubmissions: new RoundSubmissions(this),
        playerDeck: new PlayerDeck(this)
    };

    this.socket = null;

    this.clientIsGameHost = false;
    this.userIsPicking = false;
    this.playerList = [];
    this.currentJudge = "";
    this.cardsSelectable = false;
    this.cards = null;

    this.statusWrapper = document.getElementById('client_status');

    this.connectButton = document.getElementById("connect_button");
    this.connectButton.addEventListener('click', this.openConnection);

    this.usernameField = document.getElementById('username');
    this.hostField = document.getElementById('connect_host');
    this.portField = document.getElementById('connect_port');
}

BlanksGame.prototype.handleMessage = function(e) {
    var data = JSON.parse(e.data);
    var game = window.BlanksGameInstance;

    // console.log('got message: ' + data.type);

    switch (data.type) {
        case 'player_connected':
            // Check if the player that connected is local player
            // If they are game host then enable buttons
            if (data.host) {
                game.clientIsGameHost = true;

                game.startGameButton = document.getElementById("start_game");
                game.startGameButton.addEventListener('click', game.startGame);

                game.nextRoundButton = document.getElementById("next_round");
                game.nextRoundButton.addEventListener('click', game.startNextRound);
                
                game.resetGameButton = document.getElementById('reset_game');
                game.resetGameButton.addEventListener('click', game.resetGame);
            }
            break;

        // case 'player_disconnected':
        // case 'connected_game_status':
        case 'round_start':
            var currentJudge = data.currentJudge.username;
            this.currentJudge = currentJudge;
            break;

        // case 'start_game_fail':

        // case 'answer_card_update':
        //     showAnswerCards(data.cards);
        //     break;

        // case 'player_submitted':
        // case 'round_judge':

        case 'round_winner':
            document.getElementById('played_card' + data.card).className = 'card winner';
            if (game.clientIsGameHost) {
                game.nextRoundButton.disabled = false;
            }
            break;

        case 'game_reset':
            if (game.clientIsGameHost) {
                game.startGameButton.disabled = false;
            }
            break;
    }

    // Main call to let the UI update itself!
    game.updateComponents(data);
};

BlanksGame.prototype.updateComponents = function(message) {
    for (var i in this.components) {
        this.components[i].sendMessage(message);
    }
};

/**
 * Click event for connect to server 
 * 
 * @param {object} event 
 */
BlanksGame.prototype.openConnection = function(event) {
    var game = window.BlanksGameInstance;

    if (game.usernameField.value.length == 0) {
        return;
    }
    game.usernameField.disabled = true;
    game.connectButton.disabled = true;
    game.hostField.disabled = true;
    game.portField.disabled = true;

    game.createServerConnection();

    event.preventDefault();
};

/**
 * Try and open a connection to the server
 * 
 * @param {object} event 
 */
BlanksGame.prototype.createServerConnection = function () {
    var game = window.BlanksGameInstance;
    game.components.messagePanel.showMessage("Connecting to server...");

    var host = game.hostField.value;
    var port = game.portField.value;

    game.socket = new WebSocket('ws://' + host + ':' + port);

    game.socket.onopen = function(e) {
        game.components.messagePanel.showMessage("Connected to <strong>" + host + "</strong> on port <strong>" + port + "</strong>!", 'success');
        game.statusWrapper.innerHTML = "Connected";
        game.statusWrapper.className = "connected";
        game.socket.send('{ "action": "player_connected", "username": "' + game.usernameField.value + '" }');
    };

    game.socket.onmessage = game.handleMessage;

    game.socket.onclose = function(e) {
        game.components.messagePanel.showMessage('Connection to server failed', 'error');
        game.usernameField.disabled = false;
        game.connectButton.disabled = false;
        game.hostField.disabled = false;
        game.portField.disabled = false;
        game.statusWrapper.innerHTML = "Not connected";
        game.statusWrapper.className = "disconnected";

        game.updateComponents({
            type: 'server_disconnected'
        });
    };
};

/**
 * Click handler for the start game button
 */
BlanksGame.prototype.startGame = function(event) {
    var game = window.BlanksGameInstance;
    if (!game.clientIsGameHost) return;

    game.socket.send('{ "action": "start_game" }');
    game.startGameButton.disabled = true;
    event.preventDefault();
};

/**
 * Click handler for start next round button
 * 
 * @param {event} event 
 */
BlanksGame.prototype.startNextRound = function(event) {
    var game = window.BlanksGameInstance;
    if (!game.clientIsGameHost) return;
    game.socket.send('{ "action": "next_round" }');
};

/**
 * Click handler for reset game button
 */
BlanksGame.prototype.resetGame = function () {
    if (!clientIsGameHost) return;
    if (!confirm('Are you sure you want to reset the game?')) return false;

    socket.send('{ "action": "reset_game" }');
};


window.BlanksGameInstance = new BlanksGame();