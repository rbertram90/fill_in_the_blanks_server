<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Fill in the Blanks game client</title>
    <link rel="stylesheet" type="text/css" href="/css/game.css">
</head>
<body>
    <header>
        <h1>Fill in the Blanks</h1>

        <form id="connect_form">
            <div class="field">
                <label for="host">Host</label>
                <input type="text" value="localhost" id="connect_host" required>
            </div>
            <div class="field">
                <label for="host">Port</label>
                <input type="text" value="8080" id="connect_port" required size="4">
            </div>
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" value="player1" required>
            </div>
            <div class="actions">
                <button id="connect_button" type="button">Connect</button>
            </div>
        </form>

        <div id="client_status" class="disconnected">Not connected</div>
    </header>

    <main>
        <div id="host_controls" style="display: none;">
            <label for="winning_score">Winning score</label>
            <select id="winning_score">
                <option>1</option>
                <option>2</option>
                <option>3</option>
                <option>4</option>
                <option>4</option>
                <option selected>5</option>
                <option>6</option>
                <option>7</option>
                <option>8</option>
                <option>9</option>
                <option>10</option>
            </select>

            <button id="start_game" type="button">Start game</button>

            <button id="next_round" type="button" disabled>Trigger next round</button>
            <button id="reset_game" type="button">Reset game</button>
        </div>

        <h2>Current question</h2>
        <div id="question"><i>Connection to server required</i></div>

        <div id="judging_outer">
            <h2></h2>
            <div class="judging_inner"></div>
        </div>

        <h2>Your cards</h2>
        <button id="play_cards" type="button" disabled>Play card(s)</button>
        <div id="answers">
            <p><i>Awaiting game start</i></p>
        </div>
    </main>

    <aside>
        <h2>Messages</h2>
        <div id="server_messages"></div>

        <h2>Players</h2>
        <div id="user_list"></div>
    </aside>

    <script src="app/main.js"></script>

</body>
</html>