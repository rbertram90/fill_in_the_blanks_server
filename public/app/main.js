// Instance variables
var socket = null;
var statusWrapper = document.getElementById('client_status');
var serverMessages = document.getElementById('server_messages');
var usernameField = document.getElementById('username');

// Buttons
var connectButton = document.getElementById("connect_button");
var startGameButton = document.getElementById("start_game");
var playCardsButton = document.getElementById("play_cards");

var userList = document.getElementById('user_list');
var questionWrapper = document.getElementById('question');
var answersWrapper = document.getElementById('answers');
var clientIsGameHost = false;
var playerList = [];
var currentJudge = "";
var cardsSelectable = false;
var cards = null;

var openConnection = function(event) {
    if (usernameField.value.length == 0) {
        return;
    }
    usernameField.disabled = true;
    connectButton.disabled = true;

    createServerConnection();

    event.preventDefault();
}

var createServerConnection = function () {
    showServerMessage("Connecting to server...");

    var host = document.getElementById('connect_host').value;
    var port = document.getElementById('connect_port').value;

    socket = new WebSocket('ws://' + host + ':' + port);

    socket.onopen = function(e) {
        showServerMessage("Connected to " + host + " on port " + port + "!");
        statusWrapper.innerHTML = "Connected";
        statusWrapper.className = "connected";
        socket.send('{ "action": "player_connected", "username": "' + usernameField.value + '" }');
    };

    socket.onmessage = function(e) {
        var data = JSON.parse(e.data);
        switch (data.type) {
            case 'player_connected':
                showServerMessage(data.playerName + " connected");
                playerList = data.players;
                updatePlayerList();
                break;
            case 'player_disconnected':
                showServerMessage(data.playerName + " disconnected");
                playerList = data.players;
                updatePlayerList();
                break;
            case 'round_start':
                showServerMessage("Round started");
                questionWrapper.innerHTML = data.questionCard;
                currentJudge = data.currentJudge.username;
                cardsSelectable = currentJudge !== usernameField.value;
                if (cardsSelectable) {
                    showServerMessage("Choose your card(s)");
                    playCardsButton.disabled = false;
                }
                else {
                    showServerMessage("Waiting for other players to choose card(s)");
                }
                break;
            case 'answer_card_update':
                showServerMessage("Cards recieved");
                showAnswerCards(data.cards);
                break;
            case 'player_submitted':
                showServerMessage(data.playerName + " played their card(s)");
                playerList = data.players;
                updatePlayerList();
                break;
            case 'round_judge':
                showServerMessage("All players have played their card(s)");
                showPlayerSubmissions(data.allCards);
                break;
            case 'round_winner':
                showServerMessage("Round winner is " + data.player.username);
                document.getElementById('played_card' + data.card).className = 'card winner';
                break;
        }
    };

    socket.onclose = function(e) {
        showServerMessage("Connection to server failed");
        usernameField.disabled = false;
        connectButton.disabled = false;
        statusWrapper.innerHTML = "Not connected";
        statusWrapper.className = "disconnected";
    };
};

var showServerMessage = function(text) {
    serverMessages.innerHTML = "<p>" + text + "</p>" + serverMessages.innerHTML;
};

var selectCard = function (e) {
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

var submitCards = function (e) {
    var activeCards = document.querySelectorAll('#answers .card.active');
    var cardsRequired = (questionWrapper.innerHTML.match(/____/g) || []).length;

    if (activeCards.length == cardsRequired) {
        var cardIndexes = [];
        for (var c = 0; c < activeCards.length; c++) {
            cardIndexes.push(activeCards[c].dataset.id);
        }
        socket.send('{ "action": "cards_submit", "cards": [' + cardIndexes.toString() + '] }');
        playCardsButton.disabled = true;
    }
    else {
        showServerMessage("Please select the correct number of cards");
    }
};

/**
 * Judging player has picked a winning card
 */
var highlightWinner = function (e) {
    if (true) {
        for (card in document.querySelectorAll(".judging_inner .card")) {
            this.className = "card";
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
 * Judging player has picked a winning card
 */
var pickWinner = function (e) {
    var winningCard = document.querySelector(".judging_inner .card.active");

    if (!winningCard) {
        showServerMessage("Please select a card");
        return;
    }

    socket.send('{ "action": "winner_picked", "card": ' + winningCard.dataset.id + ' }');

};

var showAnswerCards = function (cards) {
    var output = "";
    for (var c = 0; c < cards.length; c++) {
        output += "<p class='card' data-id='" + cards[c].id + "'>" + cards[c].text + "</p>";
    }
    answersWrapper.innerHTML = output;

    cards = document.querySelectorAll("#answers .card");
    for (var c = 0; c < cards.length; c++) {
        cards[c].addEventListener('click', selectCard);
    }
};

var showPlayerSubmissions = function (cards) {
    var output = "";
    for (var c = 0; c < cards.length; c++) {
        output += "<p class='card' id='played_card" + cards[c].id + "' data-id='" + cards[c].id + "'>" + cards[c].text + "</p>";
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
        document.querySelector('#pick_winner').addEventListener('click', pickWinner);
        showServerMessage("Pick a winning card");
    }
};

var updatePlayerList = function() {
    var output = "";
    for (var p = 0; p < playerList.length; p++) {
        var player = playerList[p];
        if (!player.isActive) continue;

        // todo - make this more secure!
        if (player.isGameHost && player.username == usernameField.value) {
            document.getElementById("host_controls").style.display = 'block';
        }

        var label = player.isGameHost ? player.username + ' (host)' : player.username;
        label += " - " + player.status;
        output += '<p data-player-name="' + player.username + '">' + label + '</p>';
    }
    userList.innerHTML = output;
};

var startGame = function(event) {
    socket.send('{ "action": "start_game" }');
    startGameButton.disabled = true;
    event.preventDefault();
};

connectButton.addEventListener('click', openConnection);
startGameButton.addEventListener('click', startGame);
playCardsButton.addEventListener('click', submitCards);

showServerMessage("Welcome to Cards Against Humanity!");