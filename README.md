## PHP Cards Against Humanity server
This application contains both client and server for a Cards Against Humanity Game.

### Hosting a server
 - Clone repo
 - Download or create card pack(s)
   - Currently looks for white.txt and black.txt in card_packs/standard directory
   - Put each question is on a new line with four underscores (____) representing white card input
   - At least 1 white card must be played for each card.
 - Copy to /card_packs folder
 - Point web server root directory to /public to host the client

### Starting the game server
`php app/start-server.php [port number]`
Port number defaults to 8080