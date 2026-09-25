<?php

namespace App\Engines\Identity\Services;

use RuntimeException;

class OtpRateLimited extends RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct('Terlalu banyak permintaan kod.');
    }
}
