<!DOCTYPE html>
<html>
<head>
    <title>Cards against humanity clone</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <h1>CAH Client</h1>
    <label for="username">1. Enter your username</label>
    <input type="text" id="username">
    <button id="connect_button" type="button">Connect</button>

    <div id="client_status" class="disconnected">Not connected</div>

    <h2>Players</h2>
    <div id="user_list"></div>

    <h2>Log</h2>
    <div id="server_messages"></div>

    <script>
        socket = null;
        var statusWrapper = document.getElementById('client_status');
        var serverMessages = document.getElementById('server_messages');
        var usernameField = document.getElementById('username');
        var connectButton = document.getElementById("connect_button");
        var userList = document.getElementById('user_list');

        var openConnection = function(event) {
            if (username.value.length == 0) {
                return;
            }
            usernameField.disabled = true;
            connectButton.disabled = true;

            createServerConnection();

            
            event.preventDefault();
        }

        var createServerConnection = function () {
            showServerMessage("Connecting to server...");
            socket = new WebSocket('ws://localhost:8080');

            socket.onopen = function(e) {
                showServerMessage("Connected!");
                statusWrapper.innerHTML = "Connected";
                statusWrapper.className = "connected";
                socket.send('{ "action": "player_connected", "username": "' + username.value + '" }');
            };

            socket.onmessage = function(e) {
                console.log(e.data);
                var data = JSON.parse(e.data);
                switch (data.type) {
                    case 'player_connected':
                        userList.innerHTML += '<p>' + data.playerName + '</p>';
                        showServerMessage(data.playerName + " connected");
                        break;
                }
            };

            socket.onclose = function(e) {
                showServerMessage("Connection to server lost");
                usernameField.disabled = false;
                connectButton.disabled = false;
                statusWrapper.innerHTML = "Not connected";
                statusWrapper.className = "disconnected";
            };
        };

        var showServerMessage = function(text) {
            serverMessages.innerHTML = "<p>" + text + "</p>" + serverMessages.innerHTML;
        };

        connectButton.addEventListener('click', openConnection);
    </script>

</body>
</html>


