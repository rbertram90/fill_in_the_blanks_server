<?php
/**
 * Cards against humanity online server
 *
 * @author R Bertram <ricky@rbwebdesigns.co.uk>
 */
 
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use rbwebdesigns\cah_php\Game;

require __DIR__ . '/vendor/autoload.php';

$port = isset($argv[1]) ? $argv[1] : 8080;

define('CARDS_ROOT', 'C:/xampp_7.2.6/htdocs/php_cards_against_humanity/card_packs');

$server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new Game()
            )
        ),
        $port
    );

$server->run();