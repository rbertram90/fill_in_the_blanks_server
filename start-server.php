<?php
/**
 * Cards against humanity online server
 *
 * @author R Bertram <ricky@rbwebdesigns.co.uk>
 */
 
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use rbwebdesigns\cah_php\Chat;

require __DIR__ . '/vendor/autoload.php';

$server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new Chat()
            )
        ),
        8080
    );

$server->run();