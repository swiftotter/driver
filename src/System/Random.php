<?php

declare(strict_types=1);

namespace Driver\System;

class Random
{
    public function getRandomString(int $length): string
    {
        return bin2hex(openssl_random_pseudo_bytes((int)round($length / 2)));
    }
}
