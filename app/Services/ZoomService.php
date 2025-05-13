<?php
// app/Services/ZoomService.php

namespace App\Services;

use GuzzleHttp\Client;
use Firebase\JWT\JWT;
use Carbon\Carbon;

class ZoomService
{
    protected Client $http;
    protected string $key;
    protected string $secret;

    public function __construct()
    {
        $this->key    = config('services.zoom.key');
        $this->secret = config('services.zoom.secret');
        $this->http   = new Client([
            'base_uri' => 'https://api.zoom.us/v2/',
            'timeout'  => 10,
        ]);
    }

    /** Generate a short-lived JWT */
    protected function getJwt(): string
    {
        $payload = [
            'iss' => $this->key,
            'exp' => time() + 60,
        ];
        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * Create one Zoom meeting
     *
     * @param  array  $payload  // see below for shape
     * @return array
     */
    public function createMeeting(array $payload): array
    {
        $token = $this->getJwt();
        $response = $this->http->post('users/teamozrit@gmail.com/meetings', [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Accept'        => 'application/json',
            ],
            'json' => $payload,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
