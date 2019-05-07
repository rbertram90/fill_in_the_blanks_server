/**
 * Round submissions component
 */
function PlayerDeck(game) {
    Component.call(this, game);

    this.cardsSelectable = false;
    this.wrapper = document.getElementById('answers_outer');
    this.playCardsButton = document.getElementById("play_cards");
    this.playCardsButton.addEventListener('click', this.submitCards);
};

PlayerDeck.prototype = Object.create(Component.prototype);
PlayerDeck.prototype.constructor = PlayerDeck;

PlayerDeck.prototype.roundStart = function(message) {
    // Enable / disable play cards button
    if (message.playerInPlay != undefined && message.playerInPlay == 0) {
        return;
    }

    this.playCardsButton.disabled = (message.currentJudge.username === this.game.usernameField.value);
    this.game.cardsSelectable = (message.currentJudge.username !== this.game.usernameField.value);
};

PlayerDeck.prototype.gameReset = function(message) {
    this.wrapper.innerHTML = "<p class='not-active-message'>Awaiting game start</p>";
    this.playCardsButton.disabled = true;
};

PlayerDeck.prototype.serverDisconnected = function(message) {
    this.wrapper.innerHTML = '<p class="not-active-message">Awaiting connection to server</p>';
};

PlayerDeck.prototype.connectedGameStatus = function(message) {
    if (message.game_status == 0) {
        // Awaiting game start
        this.wrapper.innerHTML = '<p class="not-active-message">Awaiting game start</p>';
    }
    else {
        // Awaiting next round to start
        this.wrapper.innerHTML = '<p class="not-active-message">Awaiting next round to start</p>';
    }
};

PlayerDeck.prototype.answerCardUpdate = function(message) {
    var output = "";
    for (var c = 0; c < message.cards.length; c++) {
        output += "<p class='card' data-id='" + message.cards[c].id + "' contenteditable='true'>" + message.cards[c].text + "</p>";
    }
    this.wrapper.innerHTML = output;

    cards = document.querySelectorAll("#answers_outer .card");
    for (var c = 0; c < cards.length; c++) {
        cards[c].addEventListener('click', this.selectCard);
    }
};

/**
 * Click handler for selecting white card(s) to play
 * 
 * @param {event} event 
 */
PlayerDeck.prototype.selectCard = function (event) {
    // console.log(window.BlanksGameInstance.cardsSelectable);
    if (window.BlanksGameInstance.cardsSelectable) {
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
PlayerDeck.prototype.submitCards = function (event) {
    var game = window.BlanksGameInstance;
    var deck = game.components.playerDeck;
    var activeCards = document.querySelectorAll('#answers_outer .card.active');
    var cardsRequired = (document.querySelector('#question_outer .question').innerHTML.match(/____/g) || []).length;

    if (activeCards.length == cardsRequired) {
        var cards = [];
        for (var c = 0; c < activeCards.length; c++) {
            var card = activeCards[c];
            cards.push({ id: card.dataset.id, text: card.innerHTML });
            card.parentNode.removeChild(card);
        }
        game.socket.send('{ "action": "cards_submit", "cards": ' + JSON.stringify(cards) + ' }');
        deck.playCardsButton.disabled = true;
        game.cardsSelectable = false;
    }
    else {
        game.components.messagePanel.showMessage('Please select the correct number of cards', 'error');
    }
};