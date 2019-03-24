// Instance variables
var socket = null;
var statusWrapper = document.getElementById('client_status');
var serverMessages = document.getElementById('server_messages');
var usernameField = document.getElementById('username');
var hostField = document.getElementById('connect_host');
var portField = document.getElementById('connect_port')
var resetGameButton = document.getElementById('reset_game');

// Buttons
var connectButton = document.getElementById("connect_button");
var startGameButton = document.getElementById("start_game");
var playCardsButton = document.getElementById("play_cards");
var nextRoundButton = document.getElementById("next_round");
var pickWinnerButton = null;

var userList = document.getElementById('user_list');
var questionWrapper = document.getElementById('question');
var answersWrapper = document.getElementById('answers');
var clientIsGameHost = false;
var userIsPicking = false;
var playerList = [];
var currentJudge = "";
var cardsSelectable = false;
var cards = null;

var handleMessage = function(e) {
    var data = JSON.parse(e.data);

    switch (data.type) {
        case 'player_connected':
            showServerMessage('<strong>' + data.playerName + '</strong> connected');
            playerList = data.players;
            updatePlayerList();
            break;

        case 'player_disconnected':
            showServerMessage('<strong>' + data.playerName + "</strong> disconnected");
            playerList = data.players;
            updatePlayerList();
            break;

        case 'round_start':
            questionWrapper.innerHTML = data.questionCard.text;
            currentJudge = data.currentJudge.username;
            cardsSelectable = currentJudge !== usernameField.value;

            showServerMessage("Round started - <strong>" + currentJudge + "</strong> is the card czar.");
            document.querySelector(".judging_inner").innerHTML = "";
            document.querySelector("#judging_outer > h2").innerHTML = "";
            if (cardsSelectable) {
                showServerMessage("Choose your card(s)");
                playCardsButton.disabled = false;
            }
            else {
                showServerMessage("Waiting for other players to choose card(s)");
            }
            playerList = data.players;
            updatePlayerList();
            break;

        case 'start_game_fail':
            startGameFailed(data.message);
            break;

        case 'answer_card_update':
            showServerMessage("Cards recieved");
            showAnswerCards(data.cards);
            break;

        case 'player_submitted':
            showServerMessage('<strong>' + data.playerName + '</strong> played their card(s)');
            playerList = data.players;
            updatePlayerList();
            break;

        case 'round_judge':
            showServerMessage("All players have played their card(s)");
            showPlayerSubmissions(data.allCards);
            break;

        case 'round_winner':
            showServerMessage('Round winner is <strong>' + data.winner.username + '</strong>', 'success');
            playerList = data.players;
            updatePlayerList();
            document.getElementById('played_card' + data.card).className = 'card winner';
            nextRoundButton.disabled = false;
            break;

        case 'game_reset':
            playerList = data.players;
            updatePlayerList();
            questionWrapper.innerHTML = "<i>Awaiting game start</i>";
            answersWrapper.innerHTML = "<i>Awaiting game start</i>";
            showServerMessage('Game has been reset', 'success');
            playCardsButton.disabled = true;
            if (clientIsGameHost) startGameButton.disabled = false;
            break;
    }
};

/**
 * Click event for connect to server 
 * 
 * @param {object} event 
 */
var openConnection = function(event) {
    if (usernameField.value.length == 0) {
        return;
    }
    usernameField.disabled = true;
    connectButton.disabled = true;
    hostField.disabled = true;
    portField.disabled = true;

    createServerConnection();

    event.preventDefault();
};

/**
 * Try and open a connection to the server
 * 
 * @param {object} event 
 */
var createServerConnection = function () {
    showServerMessage("Connecting to server...");

    var host = hostField.value;
    var port = portField.value;

    socket = new WebSocket('ws://' + host + ':' + port);

    socket.onopen = function(e) {
        showServerMessage("Connected to " + host + " on port " + port + "!", 'success');
        statusWrapper.innerHTML = "Connected";
        statusWrapper.className = "connected";
        socket.send('{ "action": "player_connected", "username": "' + usernameField.value + '" }');
    };

    socket.onmessage = handleMessage;

    socket.onclose = function(e) {
        showServerMessage('Connection to server failed', 'error');
        usernameField.disabled = false;
        connectButton.disabled = false;
        hostField.disabled = false;
        portField.disabled = false;
        statusWrapper.innerHTML = "Not connected";
        statusWrapper.className = "disconnected";
    };
};

/**
 * Add a message to the messages panel
 * 
 * @param {string} text Message to show
 * @param {string} type Message context - success, error or info
 */
var showServerMessage = function(text, type='info') {
    var d = new Date();
    serverMessages.innerHTML = "<p class='message " + type + "' title='Added at " + d.getHours() + ':' + d.getMinutes() + "'>" + text + "</p>" + serverMessages.innerHTML;
};

/**
 * Click handler for selecting white card(s) to play
 * 
 * @param {event} event 
 */
var selectCard = function (event) {
    if (cardsSelectable) {
        // Toggle active class
        if (this.className.indexOf('active') > -1) {
            this.className = "card";
        }
        else {
            this.className = "card active";
        }
    }
};

/**
 * Click handler for submit card(s) button
 * 
 * @param {event} event 
 */
var submitCards = function (event) {
    var activeCards = document.querySelectorAll('#answers .card.active');
    var cardsRequired = (questionWrapper.innerHTML.match(/____/g) || []).length;

    if (activeCards.length == cardsRequired) {
        var cards = [];
        for (var c = 0; c < activeCards.length; c++) {
            var card = activeCards[c];
            cards.push({ id: card.dataset.id, text: card.innerHTML });
            card.parentNode.removeChild(card);
        }
        socket.send('{ "action": "cards_submit", "cards": ' + JSON.stringify(cards) + ' }');
        playCardsButton.disabled = true;
        cardsSelectable = false;
    }
    else {
        showServerMessage('Please select the correct number of cards', 'error');
    }
};

/**
 * Click handler for selecting winning card
 * 
 * @param {event} event
 */
var highlightWinner = function (e) {
    if (userIsPicking) {
        var allCards = document.querySelectorAll(".judging_inner .card");
        for (i = 0; i < allCards.length; ++i) {
            allCards[i].className = "card";
        }

        // Toggle active class
        if (this.className.indexOf('active') > -1) {
            this.className = "card";
        }
        else {
            this.className = "card active";
        }
    }
};

/**
 * Click handler for confirm selection button
 * Judging player has picked a winning card
 * 
 * @param {event} event
 */
var pickWinner = function (e) {
    var winningCard = document.querySelector(".judging_inner .card.active");

    if (!winningCard) {
        showServerMessage('Please select a card', 'error');
        return;
    }

    pickWinnerButton.disabled = true;
    userIsPicking = false;

    socket.send('{ "action": "winner_picked", "card": ' + winningCard.dataset.id + ' }');

};

/**
 * Show player white card deck
 * 
 * @param {object[]} cards 
 */
var showAnswerCards = function (cards) {
    var output = "";
    for (var c = 0; c < cards.length; c++) {
        output += "<p class='card' data-id='" + cards[c].id + "' contenteditable='true'>" + cards[c].text + "</p>";
    }
    answersWrapper.innerHTML = output;

    cards = document.querySelectorAll("#answers .card");
    for (var c = 0; c < cards.length; c++) {
        cards[c].addEventListener('click', selectCard);
    }
};

/**
 * Show all cards that players have submitted this round
 * 
 * @param {object[]} cards 
 */
var showPlayerSubmissions = function (cards) {
    var output = "";
    var heading = "Player submissions";
    var originalQuestionText = document.getElementById('question').innerHTML;

    for (var c = 0; c < cards.length; c++) {
        // Build up a new string, replacing blanks in question with card text
        var playerCards = cards[c];
        var cardIndex = 0;
        var questionText = originalQuestionText;

        while (questionText.indexOf('____') > -1) {
            questionText = questionText.replace('____', '<strong>' + playerCards[cardIndex].text + '</strong>');
            cardIndex++;
        }

        // just use the ID of first card as winner
        output += "<p class='card' id='played_card" + cards[c][0].id + "' data-id='" + cards[c][0].id + "'>" + questionText + "</p>";
    }

    if (currentJudge == usernameField.value) {
        output += '<button id="pick_winner">Confirm selection</button>';
    }
    
    document.querySelector(".judging_inner").innerHTML = output;

    if (currentJudge == usernameField.value) {
        cards = document.querySelectorAll(".judging_inner .card");
        for (var c = 0; c < cards.length; c++) {
            cards[c].addEventListener('click', highlightWinner);
        }
        userIsPicking = true;
        pickWinnerButton = document.querySelector('#pick_winner');
        pickWinnerButton.addEventListener('click', pickWinner);
        heading = "Pick a winner"
        showServerMessage("It's your turn to choose the winning card");
    }

    document.querySelector("#judging_outer > h2").innerHTML = heading;
    document.querySelector("#judging_outer").style.display = "block";
};

/**
 * Update the players table markup
 */
var updatePlayerList = function() {
    var output = "<table cellpadding='5' cellspacing='1' width='100%'><tr><th></th><th>Username</th><th>Score</th><th>Status</th><th>Czar</th></tr>";
    for (var p = 0; p < playerList.length; p++) {
        var player = playerList[p];
        if (!player.isActive) continue;

        // todo - make this more secure!
        if (player.isGameHost && player.username == usernameField.value) {
            clientIsGameHost = true;
            document.getElementById("host_controls").style.display = 'block';
        }

        output += '<tr data-player-name="' + player.username + '">';
        output += '<td>' + (player.isGameHost ? 'H' : '') + '</td>';
        output += '<td>' + player.username + '</td>';
        output += '<td>' + player.score + '</td>';
        output += '<td>' + player.status + '</td>';
        output += '<td>' + (player.username == currentJudge ? 'X' : '')  + '</td></tr>';
    }
    userList.innerHTML = output + "</table>";
};

/**
 * Click handler for the start game button
 */
var startGame = function(event) {
    if (!clientIsGameHost) return;

    socket.send('{ "action": "start_game" }');
    startGameButton.disabled = true;
    event.preventDefault();
};

/**
 * Error handler for when server failed to start game, this would
 * likely be because there aren't enough players!
 * 
 * @param {string} details Message from server
 */
var startGameFailed = function (details) {
    if (clientIsGameHost) {
        showServerMessage('Failed to start game - ' + details, 'error');
        startGameButton.disabled = false;
    }
};

/**
 * Click handler for start next round button
 * 
 * @param {event} event 
 */
var startNextRound = function(event) {
    if (!clientIsGameHost) return;

    socket.send('{ "action": "next_round" }');
};

/**
 * Click handler for reset game button
 */
var resetGame = function () {
    if (!clientIsGameHost) return;
    if (!confirm('Are you sure you want to reset the game?')) return false;

    socket.send('{ "action": "reset_game" }');
};

connectButton.addEventListener('click', openConnection);
startGameButton.addEventListener('click', startGame);
nextRoundButton.addEventListener('click', startNextRound);
playCardsButton.addEventListener('click', submitCards);
resetGameButton.addEventListener('click', resetGame);

showServerMessage('Welcome to Fill in the Blanks!');