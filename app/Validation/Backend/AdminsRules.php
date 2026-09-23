<?php declare(strict_types = 1);

namespace App\Validation\Backend;

class AdminsRules
{
    public function safeText(string $str): bool
    {
        return ! preg_match('/[<>\x60]/', $str);
    }
}