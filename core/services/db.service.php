<?php

    namespace Database;

    use mysqli;
    use ImageService;

    class DatabaseService
    {
        protected ImageService $imageService;

        private function openDatabaseConn()
        {
            $mysqli = new mysqli("localhost", "root", "root", "onlinechat");
            if ($mysqli->connect_error) {
                die("Error database connection: " . $mysqli->connect_error);
            }

            return $mysqli;
        }

        public function getLastId($table)
        {
            $mysqli = $this->openDatabaseConn();
            $sql = "SELECT MAX(id) FROM $table";

            $result = $mysqli->query($sql);
            $row = $result->fetch_assoc();

            $mysqli->close();

            return $row['MAX(id)'];
        }

        public function getUserData($id): array|null
        {
            $mysqli = $this->openDatabaseConn();
            $sql = "SELECT id, username, image, role FROM users WHERE id = $id";

            $result = $mysqli->query($sql);
            $row = $result->fetch_assoc();

            $mysqli->close();
            return $row;
        }

        public function getGroupList(): array|null
        {
            $mysqli = $this->openDatabaseConn();
            $sql = "SELECT * FROM listgroups";

            $groups = array();

            $result = $mysqli->query($sql);
            if ($result->num_rows > 0) {
                foreach($result as $row) {
                    $groups[] = $row;
                }
            }

            $mysqli->close();
            return $groups;
        }

        public function signUpUser($userParam): array
        {
            $mysqli = $this->openDatabaseConn();
            $id = $this->getLastId('users') + 1;

            $sql = "SELECT login FROM users WHERE login = '{$userParam['username']}'";
            $result = $mysqli->query($sql)->fetch_assoc();

            if (empty($result)) {
                $path = 'temp/standard_user_avatar.jpg';
                $image = $this->imageService->encodeImage($path);

                $sql = "INSERT INTO users(id, username, login, password, image)
                VALUES (
                $id,
                '{$userParam['username']}',
                '{$userParam['username']}',
                '{$userParam['password']}',
                '$image')";

                $mysqli->query($sql);
                $mysqli->close();

                return ['id' => $id, 'username' => $userParam['username']];

            } else {
                return ['error' => ['code' => '102', 'error-message' => 'This login exists']];
            }
        }

        public function loginUser($userParam): array
        {
            $mysqli = $this->openDatabaseConn();

            $sql = "SELECT id, username FROM users 
                           WHERE login = '{$userParam['username']}' 
                             and password = '{$userParam['password']}';";
            $result = $mysqli->query($sql);
            $row = $result->fetch_assoc();

            $mysqli->close();

            if (!empty($row)) {
                return $row;
            } else {
                return ['error' => ['code' => '102', 'error-message' => 'Incorrect password or login']];
            }
        }
    }