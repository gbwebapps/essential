<?php declare(strict_types = 1);

namespace App\Validation\Backend;

/* Assicurati di usare il namespace esatto del tuo Model */
use App\Models\Backend\AdminsModel;

class AdminsRules
{
    public function safeText(string $str): bool
    {
        return ! preg_match('/[<>\x60]/', $str);
    }
}