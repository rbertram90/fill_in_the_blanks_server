function Component(game) {
    this.game = game;
}

/**
 * Messaging system is split out by on-page component.
 * Each component extends this base class and can implement
 * any number of the following functions that are called when
 * the message is recieved by the server.
 */
Component.prototype.sendMessage = function (message) {
    switch (message.type) {
        /**
         * type: "player_connected"
         * playerName: string ("Player1")
         * host: boolean (true)
         * players: Player[]
         */
        case 'player_connected':
            if (typeof this.playerConnected == 'function')
                return this.playerConnected(message);
            break;
        case 'player_disconnected':
            if (typeof this.playerDisconnected == 'function')
                return this.playerDisconnected(message);
            break;
        case 'connected_game_status':
            if (typeof this.connectedGameStatus == 'function')
                return this.connectedGameStatus(message);
            break;
        case 'round_start':
            if (typeof this.roundStart == 'function')
                return this.roundStart(message);
            break;
        case 'start_game_fail':
            if (typeof this.startGameFail == 'function')
                return this.startGameFail(message);
            break;
        case 'answer_card_update':
            if (typeof this.answerCardUpdate == 'function')
                return this.answerCardUpdate(message);
            break;
        case 'player_submitted':
            if (typeof this.playerSubmitted == 'function')
                return this.playerSubmitted(message);
            break;
        case 'round_judge':
            if (typeof this.roundJudge == 'function')
                return this.roundJudge(message);
            break;
        case 'round_winner':
            if (typeof this.roundWinner == 'function')
                return this.roundWinner(message);
            break;
        case 'game_reset':
            if (typeof this.gameReset == 'function')
                return this.gameReset(message);
            break;
        case 'server_disconnected':
            if (typeof this.serverDisconnected == 'function')
                return this.serverDisconnected(message);
            break;
    }
};