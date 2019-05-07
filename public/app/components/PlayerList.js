/**
 * Messages panel
 */
function PlayerList(game) {
    Component.call(this, game);

    this.wrapper = document.getElementById('user_list');
    this.players = [];
};

PlayerList.prototype = Object.create(Component.prototype);
PlayerList.prototype.constructor = PlayerList;

PlayerList.prototype.triggerRedraw = function(message) {
    this.players = message.players;
    this.redraw();
};

PlayerList.prototype.playerConnected = PlayerList.prototype.triggerRedraw;
PlayerList.prototype.playerDisconnected = PlayerList.prototype.triggerRedraw;
PlayerList.prototype.roundStart = PlayerList.prototype.triggerRedraw;
PlayerList.prototype.playerSubmitted = PlayerList.prototype.triggerRedraw;
PlayerList.prototype.roundWinner = PlayerList.prototype.triggerRedraw;
PlayerList.prototype.gameReset = PlayerList.prototype.triggerRedraw;

PlayerList.prototype.serverDisconnected = function(message) {
    this.wrapper.innerHTML = '<p class="not-active-message">Awaiting connection to server</p>';
};

PlayerList.prototype.redraw = function() {
    var output = "<table cellpadding='5' cellspacing='1' width='100%'><tr><th></th><th>Username</th><th>Score</th><th>Status</th><th>Czar</th></tr>";
    for (var p = 0; p < this.players.length; p++) {
        var player = this.players[p];
        if (!player.isActive) continue;

        // todo - make this more secure!
        if (player.isGameHost && player.username == document.getElementById('username').value) {
            clientIsGameHost = true;
            // why is this being done here??!
            document.getElementById("host_controls").style.display = 'block';
        }

        output += '<tr data-player-name="' + player.username + '">';
        output += '<td>' + (player.isGameHost ? 'H' : '') + '</td>';
        output += '<td>' + player.username + '</td>';
        output += '<td>' + player.score + '</td>';
        output += '<td>' + player.status + '</td>';
        output += '<td>' + (player.username == this.game.currentJudge ? 'X' : '')  + '</td></tr>';
    }
    this.wrapper.innerHTML = output + "</table>";
};