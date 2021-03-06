<img src="https://raw.githubusercontent.com/rbertram90/fill_in_the_blanks_client/master/public/images/logo.png" width="300" alt="Fill in the Blanks logo">

## Fill in the Blanks game server
This application contains a PHP websocket server for a 'Cards Against Humanity' style game.

### Client
The lastest version of the client is available for anyone to use at http://fillintheblanks.rbwebdesigns.co.uk/game.php however it is down to individuals to host the game server as described below.

### Server requirements
 - PHP >= 5.4.2
 - Composer (https://getcomposer.org/)

### Hosting a server
 - Clone repo
 - Run `composer install`
 - Download or create card pack(s)
   - Starter cards are available from http://fillintheblanks.rbwebdesigns.co.uk/standard.zip
   - Currently looks for white.txt and black.txt in card_packs/standard directory
   - Put each question is on a new line with four underscores (____) representing white card input
 - Copy to /card_packs folder
 - Copy config_default.json to config.json and set correct server variables
   - port - port number to use
   - cards_path - absolute path to directory where the decks of cards are saved on the server
   - host_user - user name for the game host - anyone who enters the game with this name will be the host, if left blank defaults to the first person that enters the lobby.

### Starting the game server
 - Open a terminal
 - Change into project root directory
 - Run command `php start-server.php`