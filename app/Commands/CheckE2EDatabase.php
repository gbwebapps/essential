<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckE2EDatabase extends BaseCommand
{
    protected $group = 'Testing';
    protected $name = 'check:e2e-database';
    protected $description = 'Verifica la configurazione del database E2E.';

    public function run(array $params)
    {
        $db = db_connect();

        CLI::write('Environment: ' . ENVIRONMENT);
        CLI::write('Database: ' . $db->getDatabase());
        CLI::write('Connection group: ' . config('Database')->defaultGroup);
    }
}
