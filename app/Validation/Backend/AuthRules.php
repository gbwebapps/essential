<?php declare(strict_types = 1);

namespace App\Validation\Backend;

/* Assicurati di usare il namespace esatto del tuo Model */
use App\Models\Backend\AuthModel;

class AuthRules
{
    public function checkTokenRule(string $str): bool
    {
        $model = new AuthModel();
        return $model->checkAuthToken($str);
    }
}