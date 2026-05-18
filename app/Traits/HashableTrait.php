<?php declare(strict_types = 1);

namespace App\Traits;

trait HashableTrait
{
    /* Generatore di hash */
    public function generateHash(int $num): string
    {
        return bin2hex(random_bytes($num));
    }
}