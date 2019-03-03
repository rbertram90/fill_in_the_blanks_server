<!DOCTYPE html>
<html>
<head>
    <title>Cards against humanity clone</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <header>
        <h1>CAH Client</h1>
    </header>

    <main>
        <label for="host">Server (IP/Domain)</label>
        <input type="text" value="localhost" id="connect_host">
        <label for="host">Port</label>
        <input type="text" value="8080" id="connect_port">
        <label for="username">1. Enter your username</label>
        <input type="text" id="username" value="player1">
        <button id="connect_button" type="button">Connect</button>

        <div id="client_status" class="disconnected">Not connected</div>

        <div id="host_controls" style="display: none;">
            <button id="start_game" type="button">Start game</button>
        </div>

        <h2>Question</h2>
        <div id="question"></div>

        <div class="judging_outer">
            <h2>Choose a winner...</h2>
            <div class="judging_inner"></div>
        </div>

        <h2>Answers</h2>
        <button id="play_cards" type="button" disabled>Play card(s)</button>
        <div id="answers"></div>
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