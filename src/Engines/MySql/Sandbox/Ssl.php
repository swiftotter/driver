<?php

declare(strict_types=1);

namespace Driver\Engines\MySql\Sandbox;

class Ssl
{
    private const RDS_CA_URL = 'https://s3.amazonaws.com/rds-downloads/rds-combined-ca-bundle.pem';
    private const SYSTEM_PATH = '/tmp/rds-combined-ca-bundle.pem';

    public function getPath(): ?string
    {
        if (!file_exists(self::SYSTEM_PATH)) {
            file_put_contents(self::SYSTEM_PATH, fopen(self::RDS_CA_URL, 'r'));
        }

        if (file_exists(self::SYSTEM_PATH)) {
            return self::SYSTEM_PATH;
        } else {
            return null;
        }
    }
}
