<?php

    namespace JWT;

    use Exception;
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    class JWTService
    {
        private string $JWT_KEY = 'JWT_SECRET_KEY';

        public function encodeJWT($data): string
        {
            return JWT::encode($data, $this->JWT_KEY, 'HS256');
        }

        public function decodeJWT($jwt): bool|\stdClass
        {
            try {
                return JWT::decode($jwt, new Key($this->JWT_KEY, 'HS256'));
            } catch(Exception) {
                return false;
            }
        }
    }