<?php

    namespace Socket;

    require "./core/services/db.service.php";
    require "./core/services/JWT.service.php";

    use Ratchet\MessageComponentInterface;
    use Ratchet\ConnectionInterface;
    use Database\DatabaseService;
    use JWT\JWTService;

    class WebSocket implements MessageComponentInterface
    {
        protected \SplObjectStorage $clients;
        private DatabaseService $dbService;
        private JWTService $JWTService;

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
            $msg = json_decode($msg, true);
            $typeOperation = $msg['typeOperation'];

            if (isset($msg['jwt']) && $this->JWTService->decodeJWT($msg['jwt'])) {
                if ($typeOperation == 'getUserData') {
                    $userData = $this->dbService->getUserData($msg['request']);
                    $response = ['typeOperation' => $typeOperation, 'response' => $userData];

                    $this->sendMessage($response, $from);
                } else if ($typeOperation == 'getGroupList') {
                    $groupList = $this->dbService->getGroupList();
                    $response = ['typeOperation' => $typeOperation, 'response' => $groupList];

                    $this->sendMessage($response, $from);
                } else if ($typeOperation == 'setGroupId') {
                    $from->nowChatId = $msg['request'];
                    echo $from->nowChatId;
                }
            } else {
                if ($typeOperation == 'login') {
                    $userInfo = $this->dbService->loginUser($msg['request']);

                    if (!isset($userInfo['error'])) {
                        $userInfo = $this->JWTService->encodeJWT($userInfo);
                    }

                    $response = ['typeOperation' => $typeOperation, 'response' => $userInfo];
                    $this->sendMessage($response, $from);
                } else if ($typeOperation == 'signUp') {
                    $userInfo = $this->dbService->signUpUser($msg['request']);

                    if (!isset($userInfo['error'])) {
                        $userInfo = $this->JWTService->encodeJWT($userInfo);
                    }

                    $response = ['typeOperation' => $typeOperation, 'response' => $userInfo];
                    $this->sendMessage($response, $from);
                } else {
                    $response = ['typeOperation' => 'errorJWT'];
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