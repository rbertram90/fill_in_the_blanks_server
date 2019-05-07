/**
 * Black card component
 */
function RoundQuestion(game) {
    Component.call(this, game);

    this.wrapper = document.getElementById('question_outer');
};

RoundQuestion.prototype = Object.create(Component.prototype);
RoundQuestion.prototype.constructor = RoundQuestion;

RoundQuestion.prototype.roundStart = function(message) {
    this.wrapper.innerHTML = '<p class="question">' + message.questionCard.text + '</p>';
};

RoundQuestion.prototype.gameReset = function(message) {
    this.wrapper.innerHTML = "<p class='not-active-message'>Awaiting game start</p>";
};

RoundQuestion.prototype.connectedGameStatus = function (message) {
    if (message.game_status == 0) {
        // Awaiting game start
        this.wrapper.innerHTML = '<p class="not-active-message">Awaiting game start</p>';
    }
    else {
        // Awaiting next round to start
        this.wrapper.innerHTML = '<p class="not-active-message">Awaiting next round to start</p>';
    }
};

RoundQuestion.prototype.serverDisconnected = function(message) {
    this.wrapper.innerHTML = '<p class="not-active-message">Awaiting connection to server</p>';
};