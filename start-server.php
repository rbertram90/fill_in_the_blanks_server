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

$version = '2019-06-17';

require __DIR__ . '/vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    print "Server must be run from command line";
    exit;
}

if (!file_exists(__DIR__ . '/config.json')) {
    print "Please copy config_default.json to config.json and check server variables";
    exit;
}

print "***********************************\n\n";
print "   Welcome to Fill in the Blanks   \n\n";
print "   Version {$version}              \n\n";
print "***********************************\n\n";

$config = json_decode(file_get_contents(__DIR__ . '/config.json'), true);

print "Loaded configuration:\n\n";

foreach ($config as $key => $value) {
    define(strtoupper($key), $value);
    print strtoupper($key) .' = '. $value .PHP_EOL;
}

print PHP_EOL;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Game()
        )
    ),
    $config['port']
);

print "Game server ready, awaiting new connections...\n\n";

$server->run();