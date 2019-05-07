/**
 * Round submissions component
 */
function RoundSubmissions(game) {
    Component.call(this, game);

    this.wrapper = document.getElementById('judging_outer');
    this.pickWinnerButton = null;
};

RoundSubmissions.prototype = Object.create(Component.prototype);
RoundSubmissions.prototype.constructor = RoundSubmissions;

RoundSubmissions.prototype.roundStart = function(message) {
    this.wrapper.innerHTML = "<p class='not-active-message'>Awaiting player submissions</p>";

    // document.querySelector(".judging_inner").innerHTML = "";
    // document.querySelector("#judging_outer > h2").innerHTML = "";
};

RoundSubmissions.prototype.connectedGameStatus = function (message) {
    if (message.game_status == 0) {
        // Awaiting game start
        this.wrapper.innerHTML = '<p class="not-active-message">Awaiting game start</p>';
    }
    else {
        // Awaiting next round to start
        this.wrapper.innerHTML = '<p class="not-active-message">Awaiting next round to start</p>';
    }
};

RoundSubmissions.prototype.roundJudge = function (message) {
    var output = "";
    var heading = "Player submissions";
    var originalQuestionText = document.querySelector('#question_outer .question').innerHTML; // should really store this somewhere!

    for (var c = 0; c < message.allCards.length; c++) {
        // Build up a new string, replacing blanks in question with card text
        var playerCards = message.allCards[c];
        var cardIndex = 0;
        var questionText = originalQuestionText;

        while (questionText.indexOf('____') > -1) {
            questionText = questionText.replace('____', '<strong>' + playerCards[cardIndex].text + '</strong>');
            cardIndex++;
        }

        // just use the ID of first card as winner
        output += "<p class='card' id='played_card" + message.allCards[c][0].id + "' data-id='" + message.allCards[c][0].id + "'>" + questionText + "</p>";
    }

    if (message.currentJudge.username == this.game.usernameField.value) {
        output += '<button id="pick_winner">Confirm selection</button>';
    }
    
    this.wrapper.innerHTML = output;

    if (message.currentJudge.username == this.game.usernameField.value) {
        var cards = document.querySelectorAll("#judging_outer .card");
        for (var c = 0; c < cards.length; c++) {
            cards[c].addEventListener('click', this.highlightWinner);
        }
        this.game.userIsPicking = true;
        this.pickWinnerButton = document.querySelector('#pick_winner');
        this.pickWinnerButton.addEventListener('click', this.pickWinner);
        heading = "Pick a winner"
        this.game.components.messagePanel.showMessage("It's your turn to choose the winning card");
    }

    // document.querySelector("#judging_outer > h2").innerHTML = heading;
    // document.querySelector("#judging_outer").style.display = "block";
};

/**
 * Click handler for selecting winning card
 * 
 * @param {event} event
 */
RoundSubmissions.prototype.highlightWinner = function (event) {
    var game = window.BlanksGameInstance;

    if (game.userIsPicking) {
        var allCards = document.querySelectorAll("#judging_outer .card");
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
RoundSubmissions.prototype.pickWinner = function (event) {
    var game = window.BlanksGameInstance;
    var roundSubmissions = game.components.roundSubmissions;
    var winningCard = document.querySelector("#judging_outer .card.active");

    if (!winningCard) {
        showServerMessage('Please select a card', 'error');
        return;
    }

    roundSubmissions.pickWinnerButton.disabled = true;
    userIsPicking = false;

    game.socket.send('{ "action": "winner_picked", "card": ' + winningCard.dataset.id + ' }');

};

RoundSubmissions.prototype.serverDisconnected = function(message) {
    this.wrapper.innerHTML = '<p class="not-active-message">Awaiting connection to server</p>';
};