<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;

class FCMService
{
    private $serviceAccount;

    public function __construct($serviceAccountPath)
    {
        $this->serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
    }

    private function generateAccessToken()
    {
        $now = time();
        $payload = [
            "iss" => $this->serviceAccount['client_email'],
            "sub" => $this->serviceAccount['client_email'],
            "aud" => "https://oauth2.googleapis.com/token",
            "iat" => $now,
            "exp" => $now + 3600,
            "scope" => "https://www.googleapis.com/auth/firebase.messaging"
        ];

        $jwt = JWT::encode($payload, $this->serviceAccount['private_key'], 'RS256');

        $response = $this->makeHttpRequest("https://oauth2.googleapis.com/token", [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]);

        return $response['access_token'] ?? null;
    }

    private function makeHttpRequest($url, $postData)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function sendNotification($token, $title, $body)
    {
        $accessToken = $this->generateAccessToken();
        $url = "https://fcm.googleapis.com/v1/projects/{$this->serviceAccount['project_id']}/messages:send";

        $data = [
            "message" => [
                "token" => $token,
                "notification" => [
                    "title" => $title,
                    "body" => $body
                ]
            ]
        ];

        $headers = [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}
