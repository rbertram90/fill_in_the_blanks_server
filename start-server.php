<?php
/**
 * Fill in the Blanks game server
 *
 * @author R Bertram <ricky@rbwebdesigns.co.uk>
 */
 
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use rbwebdesigns\fill_in_the_blanks\Game;

require __DIR__ . '/vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    print "Server must be run from command line";
    exit;
}

if (!file_exists(__DIR__ . '/config.json')) {
    print "Please copy config_default.json to config.json and check server variables";
    exit;
}

$config = json_decode(file_get_contents(__DIR__ . '/config.json'));
$port = $config->port;
define('CARDS_ROOT', $config->cards_path);

$server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new Game()
            )
        ),
        $port
    );

$server->run();