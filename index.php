<?php
    require "./vendor/autoload.php";
    require "./services/websocket.service.php";

    use Ratchet\Server\IoServer;
    use Ratchet\Http\HttpServer;
    use Ratchet\WebSocket\WsServer;

    use Socket\WebSocket;

    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new WebSocket()
            )
        ),
        8080
    );

    $server->run();