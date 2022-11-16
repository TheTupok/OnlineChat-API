<?php

    namespace Socket;

    require "db.service.php";
    require "JWT.service.php";

    use Ratchet\MessageComponentInterface;
    use Ratchet\ConnectionInterface;
    use Database\DatabaseService;
    use JWT\JWTService;

    class WebSocket implements MessageComponentInterface
    {
        protected $clients;
        private $dbService;
        private $JWTService;

        public function __construct()
        {
            $this->clients = new \SplObjectStorage;
            $this->dbService = new DatabaseService();
            $this->JWTService = new JWTService();
        }

        public function sendMessage($msg, $conn)
        {
            foreach ($this->clients as $client) {
                if ($conn == $client) {
                    $client->send(json_encode($msg));
                }
            }
        }

        public function onOpen(ConnectionInterface $conn)
        {
            $this->clients->attach($conn);
            echo "Новое подключение ($conn->resourceId)\n";
        }

        public function onMessage(ConnectionInterface $from, $msg)
        {
            $query = $from->httpRequest->getUri()->getQuery();
            $outputQuery = [];
            parse_str($query, $outputQuery);

            $msg = json_decode($msg, true);
            $typeOperation = $msg['typeOperation'];

            if ($this->JWTService->decodeJWT($outputQuery['jwt'])) {
                $response = ['typeOperation' => 'checkJWT', 'response' => '200'];
                $this->sendMessage($response, $from);

                if ($typeOperation == 'getUserData') {
                    $userData = $this->dbService->getUserData($msg['userId']);
                    $response = ['typeOperation' => $typeOperation, 'response' => $userData];

                    $this->sendMessage($response, $from);
                }
            } else {
                if ($typeOperation == 'login') {
                    $userInfo = $this->dbService->loginUser($msg);

                    if (!isset($userInfo['error'])) {
                        $userInfo = $this->JWTService->encodeJWT($userInfo);
                    }

                    $response = ['typeOperation' => $typeOperation, 'response' => $userInfo];
                    $this->sendMessage($response, $from);
                } else if ($typeOperation == 'signUp') {
                    $userInfo = $this->dbService->signUpUser($msg);

                    if (!isset($userInfo['error'])) {
                        $userInfo = $this->JWTService->encodeJWT($userInfo);
                    }

                    $response = ['typeOperation' => $typeOperation, 'response' => $userInfo];
                    $this->sendMessage($response, $from);
                } else {
                    $response = ['typeOperation' => 'checkJWT', 'response' => '400'];
                    $this->sendMessage($response, $from);
                }
            }
        }

        public function onClose(ConnectionInterface $conn)
        {
            $this->clients->detach($conn);
            echo "Отключение пользователя $conn->resourceId \n";
        }

        public function onError(ConnectionInterface $conn, \Exception $e)
        {
//            echo "Есть ошибка: {$e->getMessage()}\n";
//            $conn->close();
        }
    }