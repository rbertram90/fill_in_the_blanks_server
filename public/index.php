<!DOCTYPE html>
<html>
<head>
    <title>Cards against humanity clone</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <header>
        <h1>CAH Client</h1>

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
            <button id="start_game" type="button">Start game</button>
        </div>

        <h2>Current question</h2>
        <div id="question"></div>

        <div id="judging_outer">
            <h2></h2>
            <div class="judging_inner"></div>
        </div>

        <h2>Your cards</h2>
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