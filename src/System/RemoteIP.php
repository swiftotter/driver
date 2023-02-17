<?php

declare(strict_types=1);

namespace Driver\System;

use GuzzleHttp\Client;

class RemoteIP
{
    private ?string $ip = null;

    public function getPublicIP(): string
    {
        if (!$this->ip) {
            $client = new Client();
            $response = $client->request('GET', 'https://api.ipify.org?format=json');
            $body = json_decode($response->getBody()->getContents(), true);

            $this->ip = $body['ip'];
        }

        return $this->ip;
    }
}
