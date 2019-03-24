<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Fill in the Blanks</title>
    <link rel="stylesheet" type="text/css" href="/css/front.css">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet"> 
</head>
<body>
<header>
    <h1>PHP <i style="color: white;">Fill in the Blanks</i> game</h1>
    <p>Client and server application for a 'Cards Against Humanity' style game.</p>
</header>
<main>
    <section>
        <h2>Project status</h2>
        <p>(17/09/2019) Early stages of development: there are missing features and features that aren't meant to be there (AKA bugs!)</p>

        <h3>On the critical 'fix list'</h3>
        <ul>
            <li>Have a game end once the winning score is reached</li>
            <li><span class="done">Handle client re-connect properly</span> <i>(Improved 24/03/19)</i></li>
        </ul>

        <h3>Future features</h3>
        <ul>
            <li>Change white card text on-the-fly</li>
            <li>Allow 3 options at the start of each round for which black cards is used.</li>
        </ul>

        <h3>Contributing</h3>
        <p>This project is open source, check it out on <a href="https://github.com/rbertram90/fill_in_the_blanks" target="_blank">GitHub</a>. Feel free to open a ticket for a feature request/ bug report, if you want to get your hands dirty, i'm open to pull requests.</p>
    </section>
    <section>
        <h2>Client</h2>
        <p>The latest version of the client is available for anyone to use at <a href="https://fillintheblanks.rbwebdesigns.co.uk/game.php">https://fillintheblanks.rbwebdesigns.co.uk/game.php</a> however it is down to individuals to host game servers as described below.</p>
        <a href="/game.php" class="big-button">Play now</a>
    </section>
    <section>
        <h2>Server</h2>

        <h3>Requirements</h3>
        <ul>
            <li>PHP >= 5.4.2</li>
            <li>Composer (<a href="https://getcomposer.org/" target="_blank">https://getcomposer.org/</a>)</li>
        </ul>

        <h3>Setup</h3>
        <ul>
            <li>Clone repo</li>
            <li>Run <code><b>composer</b> install</code></li>
            <li>Download or create card pack(s)</li>
            <ul>
                <li>Starter packs are available from <a href="https://fillintheblanks.rbwebdesigns.co.uk/standard.zip">https://fillintheblanks.rbwebdesigns.co.uk/standard.zip</a>
                <li>Currently looks for white.txt and black.txt in card_packs/standard directory
                <li>Put each question is on a new line with four underscores (____) representing white card input
            </ul>
            <li>Copy to /card_packs folder</li>
        </ul>

        <h3>Starting the game</h3>
        <ul>
            <li>Open a terminal</li>
            <li>Change into project root directory</li>
            <li>Run command <code><b>php</b> start-server.php <i>[port number]</i></code></li>
            <li>Port number defaults to <b>8080</b></li>
        </ul>
    </section>
    <section>
        <a href="/game.php" class="big-button">Play now</a>
    </section>
</main>
</body>
</html>