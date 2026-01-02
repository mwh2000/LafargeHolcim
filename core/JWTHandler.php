<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class JWTHandler
{
    private $secret;

    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    public function generateToken($payload)
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + (60 * 60 * 24 * 365); // يوم كامل (24 ساعة)

        $token = JWT::encode($payload, $this->secret, 'HS256');

        return [
            'token' => $token,
            'exp' => $payload['exp']
        ];
    }

    public function validateToken($token)
    {
        return JWT::decode($token, new Key($this->secret, 'HS256'));
    }

    public function decodeToken($token)
    {
        try {
            return $this->validateToken($token);
        } catch (Exception $e) {
            return null;
        }
    }

}
